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
 * Manager.
 *
 * @package    mod_courseguide;
 * @copyright  2017 onwards Coventry University {@link http://www.coventry.ac.uk/}
 * @author     Jerome Mouneyrac - Bepaw Pty Ltd <jerome@bepaw.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_courseguide;
defined('MOODLE_INTERNAL') || die();

use context_course;
use stdClass;
use block_moderator_guide\manager_base;
use block_moderator_guide\guide_base;

/**
 * Manager class.
 *
 * The manager for the block.
 *
 * @package    mod_courseguide;
 * @copyright  2017 onwards Coventry University {@link http://www.coventry.ac.uk/}
 * @author     Jerome Mouneyrac - Bepaw Pty Ltd <jerome@bepaw.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class manager extends manager_base {

    /**
     * Return the component.
     *
     * @return string
     */
    public function get_component() {
        return 'mod_courseguide';
    }

    /**
     * Return the template table.
     *
     * @return string
     */
    public function get_templates_table() {
        return 'mod_courseguide_templates';
    }

    /**
     * Return the guides table.
     *
     * @return string
     */
    public function get_guides_table() {
        return 'mod_courseguide_guides';
    }

    /**
     * Return the guide contents table.
     *
     * @return string
     */
    public function get_guide_contents_table() {
        return 'mod_courseguide_contents';
    }

    /**
     * Return the template class to use.
     *
     * @return string
     */
    public function get_template_class() {
        return 'mod_courseguide\\template';
    }

    /**
     * Return the guide class to use.
     *
     * @return string
     */
    public function get_guide_class() {
        return 'mod_courseguide\\guide';
    }

    /**
     * Get guide SQL order.
     *
     * @return string
     */
    public function get_guide_sql_order() {
        return 'id';
    }

    /**
     * Get the context of a guide.
     *
     * @param guide $guide The guide.
     * @return context
     */
    public function get_guide_context(\block_moderator_guide\guide_base $guide) {
        return context_course::instance($guide->get_courseid());
    }

    /**
     * Get the profile field to restrict templates on.
     *
     * @return null|string
     */
    protected function get_restriction_profile_field() {
        $restrictionfield = get_config('block_moderator_guide', 'restriction');
        return !empty($restrictionfield) ? $restrictionfield : null;
    }

    /**
     * Process a template's form data.
     *
     * @param template_base $template The template.
     * @param stdClass $formdata Form data.
     */
    protected function process_template_form_data(\block_moderator_guide\template_base $template, stdClass $data) {
        parent::process_template_form_data($template, $data);

        // set the default display mode if displaymode is not 'any'
        if ($data->displaymode !== 'any') {
            $data->defaultdisplaymode = $data->displaymode;
        }

        $template->import_record((object) ['defaultguidename' => $data->defaultguidename,
            'displaymode' => $data->displaymode, 'defaultdisplaymode' => $data->defaultdisplaymode]);
    }

    /**
     * Get a guide by cmid.
     *
     * @param int $cmid The Course Guide instance ID.
     * @return guide_base
     */
    public function get_guide_by_instanceid($courseguideid) {
        global $DB;
        $guideid = $DB->get_field($this->get_guides_table(), 'id', ['courseguideid' => $courseguideid]);

        if (empty($guideid)) {
            return false;
        }

        return $this->get_guide($guideid);
    }

    /**
     * Save a guide.
     *
     * @param guide_base $guide The guide
     * @param stdClass[] $files Information about the files (draftitemid and filearea).
     * @return guide_base A new guide instance.
     */
    public function save_guide(guide_base $guide, array $files = []) {
        global $DB;

        // update the activity name.
        $record = $guide->get_record();
        if (!empty($record->name) && !empty($record->courseguideid)) {
            $courseguide = new stdClass();
            $courseguide->id = $record->courseguideid;
            $courseguide->name = $record->name;
            $DB->update_record('courseguide', $courseguide);
            rebuild_course_cache($guide->get_courseid(), true);
        }

        return parent::save_guide($guide, $files);
    }

    /**
     * Function to serve pluginfiles.
     *
     * This should be called from within the plugin's pluginfile function.
     *
     * @return void
     */
    public function serve_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload,
                                     array $options = array(), array $permissions = array()) {

        parent::serve_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload,
            $options, array('system' => 'mod/courseguide:viewguide', 'context' => 'mod/courseguide:viewguide'));

    }

    /**
     * Render a guide with edit button, also check if the user can access it.
     *
     * @param guide_base $guide
     * @return string
     */
    public function render_guide_and_edit_button(guide_base $guide) {
        global $COURSE, $PAGE, $OUTPUT;

        $o = $this->render_guide($guide);

        // Add edit guide button if the user can edit it.
        if (has_capability('mod/courseguide:editguide', $PAGE->context)) {
            $editurl = new \moodle_url('/mod/courseguide/edit_guide.php',
                array('courseid' => $COURSE->id, 'courseguideid' => $guide->get_courseguideid(),
                    'sesskey' => sesskey(), 'action' => 'edit', 'id' => $guide->get_id()));
            $o = $OUTPUT->single_button($editurl, get_string('editguide', 'courseguide'), 'post',
                    array('class' => 'singlebutton mod_courseguide_right')) . $o        ;
        }

        return $o;
    }

    /**
     * Render a guide.
     *
     * @param guide_base $guide The guide
     * @return string HTML.
     */
    public function render_guide(guide_base $guide) {
        $o = '';

        if ($this->can_access_guide($guide)) {

            $o = parent::render_guide($guide);

        } else {
            $o =  get_string('cannotaccessguide', 'courseguide');
        }

        return $o;
    }

    /**
     * Return the guides sorted by courses. It can be filtered by organisation.
     * Each courses are returned with the total number of incomplete and complete guides.
     *
     * This function may be inefficient of a very large number of guides/courses.
     * If it is the cache then only call once a day per cron and cache the rest of the result.
     *
     * @param string $courseid
     * @param string $courseshortname
     * @param string $organisation
     * @return array of courses (id,
     *                           name,
     *                           shortname,
     *                           array of guides (id, name, hidden, organisation, totalincompletefields)
     *                           total incomplete guides
     *                           total complete guides
     *                           total guides)
     */
    public function get_guides_courses($courseid = '', $courseshortname = '', $organisation = '',
                                       $limitfrom = 0, $limitnum = 0) {
        global $DB;

        $sqlparams = array();

        //We are searching the entire Moodle site and filtering per organisation.
        $sql = 'SELECT  guide.id, 
                        courseguide.name, 
                        guide.templateid, 
                        guide.courseguideid, 
                        course.fullname AS coursename, 
                        course.shortname AS courseshortname, 
                        course.id AS courseid,
                        coursemodules.visible AS visible,
                        guide.completed, 
                        guide.reviewed
                FROM {mod_courseguide_guides} AS guide
                JOIN {course_modules} AS coursemodules ON guide.courseguideid = coursemodules.instance
                JOIN {course} AS course ON course.id = coursemodules.course
                JOIN {courseguide} AS courseguide ON courseguide.id = guide.courseguideid';

        // retrieve the course if courseid not empty.
        $sqlwhere = '';
        if (!empty($courseid)) {
            $sqlwhere = ' WHERE course.id = :cid';
            $sqlparams['cid'] = $courseid;
        }

        // find all courses with this shortname.
        $courses = $DB->get_records_select('course', $DB->sql_like('shortname', '\''.$courseshortname.'\''));

        if (!empty($courses)) {
            $sqlwhere .= empty($sqlwhere) ? 'WHERE ' : ' AND ';
            $courseids = array();
            foreach ($courses as $course) {
                $courseids[] = $course->id;
            }
            list ($incond, $inparams) = $DB->get_in_or_equal($courseids, SQL_PARAMS_NAMED, 'courseids');
            $sqlparams = array_merge($sqlparams, $inparams);
            $sqlwhere .= ' course.id ' .$incond;
        }

        if (!empty($organisation)) {
            $sql .= ' JOIN {mod_courseguide_templates} as template ON template.id = guide.templateid';
            $sqlwhere .= empty($sqlwhere) ? 'WHERE ' : ' AND ';
            $sqlwhere .= ' template.organization = :organisation';
            $sqlparams['organisation'] = $organisation;
        }

        $guides = $DB->get_records_sql($sql . $sqlwhere, $sqlparams, $limitfrom, $limitnum);

        $courses = array();
        foreach($guides as $guide) {

            if (!isset($courses[$guide->courseid])) {

                // Initialize the course data.
                $courses[$guide->courseid] = array();
                $courses[$guide->courseid]['guides'] = array();
                $courses[$guide->courseid]['totalincomplete'] = 0;
                $courses[$guide->courseid]['totalcomplete'] = 0;
                $courses[$guide->courseid]['totalmarkascompleted'] = 0;
                $courses[$guide->courseid]['totalmarkasreviewed'] = 0;
                $courses[$guide->courseid]['totalguides'] = 0;

                // Set course info.
                $courses[$guide->courseid]['id'] = $guide->courseid;
                $courses[$guide->courseid]['name'] = $guide->coursename;
                $courses[$guide->courseid]['shortname'] = $guide->courseshortname;
            }

            // Find out the empty and not empty fields.
            $guideobj = $this->get_guide($guide->id);
            $emptyfields = $this->get_total_empty_fields($guideobj);
            $guide->totalincompletefields = $emptyfields['empty'];
            $guide->totalcompletefields = $emptyfields['notempty'];
            $guide->hidden = empty($guide->visible)?1:0;

            // Add the guide to the course guide list.
            $courses[$guide->courseid]['guides'][] = $guide;

            // Increment the statistics.
            if (!empty($guide->totalincompletefields)) {
                $courses[$guide->courseid]['totalincomplete']++;
            } else {
                $courses[$guide->courseid]['totalcomplete']++;
            }
            if (!empty($guide->completed)) {
                $courses[$guide->courseid]['totalmarkascompleted']++;
            }
            if (!empty($guide->reviewed)) {
                $courses[$guide->courseid]['totalmarkasreviewed']++;
            }
            $courses[$guide->courseid]['totalguides']++;
        }

        return $courses;
    }

}

