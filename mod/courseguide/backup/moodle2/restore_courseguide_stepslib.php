<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Define all the restore steps that will be used by the restore_courseguide_activity_task
 *
 * @package   mod_courseguide
 * @category  backup
 * @copyright  2017 onwards Coventry University {@link http://www.coventry.ac.uk/}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author  Jerome Mouneyrac <jerome@bepaw.com>
 */

defined('MOODLE_INTERNAL') || die();

use \block_moderator_guide\template_base;

/**
 * Structure step to restore one courseguide activity
 *
 * @package   mod_courseguide
 * @category  backup
 * @copyright  2017 onwards Coventry University {@link http://www.coventry.ac.uk/}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author  Jerome Mouneyrac <jerome@bepaw.com>
 */
class restore_courseguide_activity_structure_step extends restore_activity_structure_step {

    /** @var manager_base The manager. */
    protected $manager;
    /** @var string[] The content file areas indexed by template ID. */
    protected $contentfileareas;

    /**
     * Defines structure of path elements to be processed during the restore
     *
     * @return array of {@link restore_path_element}
     */
    protected function define_structure() {

        $userinfo = $this->get_setting_value('userinfo');

        $paths = array();
        $paths[] = new restore_path_element('courseguide', '/activity/courseguide');
        $paths[] = new restore_path_element('template', '/activity/template');
        $paths[] = new restore_path_element('guide', '/activity/guide', true);

        if ($userinfo) {
            $paths[] = new restore_path_element('contents', '/activity/guide/contents/content');
        }

        // Return the paths wrapped into standard activity structure.
        return $this->prepare_activity_structure($paths);
    }

    /**
     * Return the manager.
     *
     * @return manager_base
     */
    protected function get_manager() {
        if (!$this->manager) {
            $this->manager = new \mod_courseguide\manager();
        }
        return $this->manager;
    }

    /**
     * Process the given restore path element data
     *
     * @param array $data parsed element data
     */
    protected function process_courseguide($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->course = $this->get_courseid();

        if (empty($data->timecreated)) {
            $data->timecreated = time();
        }

        if (empty($data->timemodified)) {
            $data->timemodified = time();
        }

        if ($data->grade < 0) {
            // Scale found, get mapping.
            $data->grade = -($this->get_mappingid('scale', abs($data->grade)));
        }

        // Create the courseguide instance.
        $newitemid = $DB->insert_record('courseguide', $data);
        $this->apply_activity_instance($newitemid);
    }

    /**
     * Process template.
     */
    protected function process_template($data) {
        $data = (object) $data;

        $id = $data->id;
        unset($data->id);
        $data->hidden = 1;  // Force hidden value if we need to recreate the template.

        $manager = $this->get_manager();
        $templatetorestore = $manager->get_new_template();
        $templatetorestore->import_record($data);

        $template = null;
        $candidates = [];

        // If we're on the same site, we checkout the template with the same ID.
        if ($this->task->is_samesite()) {
            try {
                $candidates = [$manager->get_template($id)];
            } catch (dml_missing_record_exception $e) {
                $candidates = [];
            }
        }

        // Add more candidate templates, based on their name.
        $candidates = array_merge($candidates, $manager->list_templates(['name' => $data->name]));

        // Find the first candidate which content matches what we need.
        foreach ($candidates as $candidate) {
            if (template_base::are_template_contents_similar($templatetorestore, $candidate)) {
                $template = $candidate;
                break;
            }
        }

        // No templates were found, so we gotta create a new one.
        $restorefiles = false;
        if (!$template) {
            $template = $manager->save_template($templatetorestore);
            $restorefiles = true;
        }

        // Save the content file areas of the template.
        $this->contentfileareas[$template->get_id()] = $template->get_content_fileareas();

        // Store the mapping, this also ensures that files are restored.
        $this->set_mapping('courseguide_tpl', $id, $template->get_id(), $restorefiles, $this->task->get_old_system_contextid());
    }

    /**
     * Process guide.
     */
    protected function process_guide($data) {
        global $DB;

        $data = (object) $data;

        $mappingid = $this->get_mappingid('courseguide_tpl', $data->templateid);
        if (!$mappingid) {
            $this->log('mod_courseguide: Could not find mapping id for template ' . $data->templateid, backup::LOG_WARNING);
            return;
        }

        // Find the template.
        $manager = $this->get_manager();
        $template = $manager->get_template($mappingid);

        // Reorganise the data.
        $contents = [];
        if (!empty($data->contents) && is_array($data->contents)
                && !empty($data->contents['content']) && is_array($data->contents['content'])) {
            $contents = $data->contents['content'];
        }
        $id = $data->id;
        unset($data->id);
        unset($data->templateid);
        unset($data->contents);
        $data->name = null;     // Empty the data, otherwise it overrides the activity name.
        $data->courseguideid = $this->task->get_activityid();
        $data->createorid = $this->get_mappingid('user', $data->creatorid, $this->task->get_userid());

        // Convert the contents to content objects.;
        $contents = array_map(function($content) {
            $content = (object) $content;
            unset($content->id);
            unset($content->guideid);
            return $content;
        }, $contents);

        // Make the guide, and save.
        $guide = $manager->get_new_guide($template);
        $guide->import_record($data);
        $guide->import_contents($contents);
        $guide->fill_missing_contents();
        $guide = $manager->save_guide($guide);

        // Delete the moderation fields if we should not restore user data.
        $userinfo = $this->get_setting_value('users');
        if (!$userinfo) {
            $data = new stdClass();
            $data->id = $guide->get_id();
            $data->completed = 0;
            $data->reviewed = 0;
            $data->reviewcomment = '';
            $manager->update_guide_moderation_fields($data);
        }

        // Save the mapping, and indicate that we want to restore files from there.
        $restorefiles = $this->get_setting_value('users');
        $oldcontextid = $this->task->get_old_course_contextid();  // Warning! Copied logic from manager_base::get_guide_context.
        $this->set_mapping('courseguide_guide', $id, $guide->get_id(), $restorefiles, $oldcontextid);
    }

    /**
     * Post-execution actions
     */
    protected function after_execute() {
        // Add courseguide related files, no need to match by itemname (just internally handled context).
        $this->add_related_files('mod_courseguide', 'intro', null);

        $manager = $this->get_manager();
        $this->add_related_files($manager->get_component(), 'template', 'courseguide_tpl',
            $this->task->get_old_system_contextid());

        $userinfo = $this->get_setting_value('userinfo');
        if ($userinfo) {
            // We restore the content files.
            foreach ($this->contentfileareas as $templateid => $fileareas) {
                foreach ($fileareas as $filearea) {
                    // Warning! Copied logic from manager_base::get_guide_context().
                    $oldcontextid = $this->task->get_old_course_contextid();
                    $this->add_related_files($manager->get_component(), $filearea, 'courseguide_guide', $oldcontextid);
                }
            }
        }
    }
}
