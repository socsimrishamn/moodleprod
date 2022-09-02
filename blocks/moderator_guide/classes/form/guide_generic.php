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
 * Guide form.
 *
 * @package    block_moderator_guide;
 * @copyright  2016 onwards Coventry University {@link http://www.coventry.ac.uk/}
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_moderator_guide\form;
defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

use stdClass;
use context_course;
use moodleform;
use block_moderator_guide\guide_base;
use block_moderator_guide\guide_content;
use block_moderator_guide\manager;

/**
 * Guide edit form.
 *
 * @package    block_moderator_guide
 * @copyright  2016 onwards Coventry University {@link http://www.coventry.ac.uk/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Jerome Mouneyrac <jerome@mouneyrac.com>
 */
class guide_generic extends moodleform {

    /**
     * Definition.
     */
    public function definition() {
        global $DB, $CFG, $PAGE, $COURSE, $USER;

        // Require for serving file.
        $coursecontext = context_course::instance($COURSE->id);

        $mform = $this->_form;
        $guide = $this->_customdata['guide'];
        $guideobj = $this->_customdata['guideobj'];
        $manager = $this->_customdata['manager'];
        $ispreview = !empty($guide->action) && $guide->action === 'preview';
        $pluginfileobj = $guideobj;

        $mform->addElement('header', 'guideheader', get_string('guide', 'block_moderator_guide'));

        $mform->addElement('text', 'name', get_string('name'));
        $mform->addRule('name', get_string('required'), 'required', null, 'client');
        $mform->setType('name', PARAM_TEXT);

        $options = array();
        $forcetemplate = optional_param('forcetemplate', 0, PARAM_INT);
        if (!empty($guide->action) && $ispreview) {
            $templatesobj = [$manager->get_template($forcetemplate)];
        } else {
            $templatesobj = $manager->list_visible_templates();
        }
        $templates = array_map(function($tpl) {
            return $tpl->get_record();
        }, $templatesobj);

        foreach ($templates as $template) {
            if (empty($selectedtemplate)) {
                // Force the template if found.
                if (!empty($forcetemplate) && $forcetemplate == $template->id) {
                    $selectedtemplate = $template;
                    $guide->templateid = $template->id;
                    $mform->addElement('hidden', 'forcetemplate', $forcetemplate);
                    $mform->setType('forcetemplate', PARAM_INT);
                }

                // Retrieve the current template.
                if (!empty($guide->templateid) && $template->id === $guide->templateid) {
                    $currenttemplate = $template;
                }
            }
            $options[$template->id] = $template->name;
        }

        // Set a template by default if none has been set.
        if (empty($selectedtemplate)) {
            if (!empty($currenttemplate)) {
                $selectedtemplate = $currenttemplate;
            } else {
                // The last template created (same as logic as in the edit_guide.php for adding a guide).
                $selectedtemplate = $template;
            }
        }

        $onchangeurl = $PAGE->url->out(false) . '&forcetemplate=';
        $mform->addElement('select', 'template', get_string('template', 'block_moderator_guide'), $options,
            array('changeurl' => $onchangeurl));
        $mform->setType('template', PARAM_INT);
        $mform->getElement('template')->setSelected($selectedtemplate->id);

        // Set the template in the form overwriting the definition() so extending form class can access it.
        $this->_customdata['selectedtemplate'] = $selectedtemplate;

        // Quick and dirty way to find the template object from the selected template.
        foreach ($templatesobj as $templateobj) {
            if ($templateobj->get_id() == $selectedtemplate->id) {
                break;
            }
        }

        // What object to use when rewriting URLs.
        if ($ispreview || !$guideobj->get_id()) {
            $pluginfileobj = $templateobj;
        }

        if (!empty($templateobj->get_description())) {
            $mform->addElement('static', 'template_description',
                get_string('description', 'block_moderator_guide'),
                $templateobj->get_description());
        }

        // Create the template input fields.
        $templatefields = $templateobj->parse();
        $guidelabelnotdisplayedyet = true;
        foreach ($templatefields as $field) {
            $fieldlabel = '';
            if ($guidelabelnotdisplayedyet) {
                $fieldlabel = get_string('guide', 'block_moderator_guide');
                $guidelabelnotdisplayedyet = false;
            }
            switch ($field['input']) {
                case 'html':
                    $mform->addElement('editor', 'field_' . $field['id'], $fieldlabel,
                        null, $manager->get_guide_editor_options());
                    $mform->setType('field_' . $field['id'], PARAM_RAW);

                    // Set default.
                    $fieldname = 'field_' . $field['id'];
                    if (empty($guide->id) && !empty($field['default'])) {
                        $guide->{$fieldname}['text'] = $field['default'];
                    }
                    break;
                case 'files':
                    $mform->addElement('filemanager', 'field_' . $field['id'], $fieldlabel, null,
                        array('subdirs' => 1, 'accepted_types' => '*'));
                    break;
                case 'link':
                    $mform->addElement('text', 'field_' . $field['id'], $fieldlabel, array('placeholder' => 'Link URL'));
                    $mform->setType('field_' . $field['id'], PARAM_URL);
                    break;
                case 'linkname':
                    $mform->addElement('text', 'field_' . $field['id'], $fieldlabel, array('placeholder' => 'Link Name'));
                    $mform->setType('field_' . $field['id'], PARAM_TEXT);

                    // Set default.
                    $fieldname = 'field_' . $field['id'];
                    if (empty($guide->id) && !empty($field['default'])) {
                        $guide->{$fieldname} = clean_param($field['default'], PARAM_NOTAGS);
                    }
                    break;
                case 'static':
                    // Convert file urls.
                    $field['value'] = $manager->rewrite_template_pluginfile_urls($pluginfileobj, $field['value']);
                    $mform->addElement('static', 'field_' . $field['id'], $fieldlabel, $field['value']);
                    break;
                default:
                    throw moodle_exception('type_unkown');
                    break;
            }
        }

        // Moderation fields
        if(!empty(get_config('block_moderator_guide', 'moderation'))) {

            $cancomplete = has_capability('block/moderator_guide:cancomplete', $PAGE->context);

            if ($cancomplete && $templateobj->get_cancomplete()) {
                $mform->addElement('checkbox', 'completed',
                    get_string('completed', 'block_moderator_guide'));
                $mform->setType('completed', PARAM_INT);
            }
        }

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        if (!empty($guide->id)) {
            $buttonlabel = get_string('savechanges');
            $mform->setDefault('template', $guide->templateid);
        } else {
            $buttonlabel = get_string('addguide', 'block_moderator_guide');
        }

        if (empty($guide->action) || $guide->action !== 'preview') {
            $this->add_action_buttons(true, $buttonlabel);
        }

        $this->set_data($guide);
    }

    /**
     * Extract the content from the data.
     *
     * @param stdClass $data Data
     * @param array $templatefields The template fields.
     * @return stdClass[]
     */
    public function extract_contents_from_data(stdClass $data, array $templatefields) {
        $contents = [];

        foreach ($templatefields as $fieldid => $field) {
            $content = new stdClass();
            switch ($field['input']) {
                case 'html':
                    $fieldname = 'field_' . $fieldid;
                    $content->placeholderid = $field['value'];
                    $content->placeholdertype = $field['input'];
                    $content->value = $data->{$fieldname}['text'];
                    $content->valueformat = $data->{$fieldname}['format'];
                    break;
                case 'files':
                    break;
                case 'link':
                case 'linkname':
                    $fieldname = 'field_' . $fieldid;
                    $content->placeholderid = $field['value'];
                    $content->placeholdertype = $field['input'];
                    $content->value = $data->{$fieldname};
                    break;
                default:
                    break;
            }

            // Skip empty content.
            if (empty($content->placeholderid)) {
                continue;
            }

            $contents[] = $content;
        }

        return $contents;
    }

    /**
     * Extract files information from data.
     *
     * @param stdClass $data Data.
     * @param array $templatefields The template fields.
     * @return array Containing an object with draftitemid and filearea.
     */
    public function extract_files_from_data(stdClass $data, array $templatefields) {
        $files = [];

        foreach ($templatefields as $fieldid => $field) {
            if ($field['input'] !== 'files' && $field['input'] !== 'html' ) {
                continue;
            }

            $fieldname = 'field_' . $fieldid;

            if ($field['input'] == 'files') {
                $draftitemid = $data->{$fieldname}; // filemanager draftitemid is in a field_X[itemid] hidden input.
                $filearea = 'filesplaceholder_' . $fieldid;
            } else {
                $draftitemid = $data->{$fieldname}['itemid']; // editor draftitemid is in a field_X[itemid] hidden input.
                $filearea = 'htmlplaceholder_' . $fieldid;
            }

            $files[] = (object) [
                'draftitemid' => $draftitemid,
                'filearea' => $filearea,
                'placeholderid' => $field['value']
            ];
        }

        return $files;
    }

    /**
     * Prepare a record for editing in a form.
     *
     * @param guide_base $guide
     * @param stdClass $saveinobj
     */
    public static function prepare_record_with_guide_contents(guide_base $guide, $saveinobj) {
        $templatefields = $guide->get_template()->parse();
        foreach ($templatefields as $fieldid => $field) {
            switch ($field['input']) {
                case 'html':
                    $guidecontent = $guide->get_content($field['value']);
                    // Check if guidecontent is empty (it happens when the template is forced - i.e. switch during edit mode).
                    if (!empty($guidecontent)) {
                        $fieldname = 'field_' . $fieldid;
                        $fieldnameformat = 'field_' . $fieldid . 'format';
                        $saveinobj->{$fieldname}['text'] = $guidecontent->value;
                        $saveinobj->{$fieldnameformat}['format'] = $guidecontent->valueformat;
                    }
                    break;
                case 'files':
                    // No need to the file manager value here - it is done by the file API.
                    break;
                case 'link':
                case 'linkname':
                    $guidecontent = $guide->get_content($field['value']);
                    // Check if guidecontent is empty (it happens when the template is forced - i.e. switch during edit mode).
                    if (!empty($guidecontent)) {
                        $fieldname = 'field_' . $fieldid;
                        $saveinobj->{$fieldname} = $guidecontent->value;
                    }
                    break;
                default:
                    break;
            }
        }
    }
}
