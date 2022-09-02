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
 * Template form.
 *
 * @package    block_moderator_guide;
 * @copyright  2016 onwards Coventry University {@link http://www.coventry.ac.uk/}
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_moderator_guide\form;
defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

use context_system;
use stdClass;
use block_moderator_guide\manager;

/**
 * Edit template form.
 *
 * @package    block_moderator_guide
 * @copyright  2016 onwards Coventry University {@link http://www.coventry.ac.uk/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Jerome Mouneyrac <jerome@mouneyrac.com>
 */
class template_generic extends \moodleform {

    /**
     * Form definition.
     */
    public function definition() {
        global $DB;

        $mform = $this->_form;
        $template = $this->_customdata['template'];
        $templateobj = $this->_customdata['templateobj'];
        $templateid = $template->id;
        $manager = $this->_customdata['manager'];

        $mform->addElement('header', 'templateheader',
            get_string('template', 'block_moderator_guide'));

        $mform->addElement('text', 'name', get_string('name'));
        $mform->addRule('name', get_string('required'), 'required', null, 'client');
        $mform->setType('name', PARAM_TEXT);

        $mform->addElement('text', 'organization', get_string('organization', 'block_moderator_guide'), 'maxlength="255"');
        $mform->setType('organization', PARAM_TEXT);

        $mform->addElement('textarea', 'description', get_string('description', 'block_moderator_guide'),
            array('rows' => 8, 'cols' => 50));
        $mform->setType('description', PARAM_TEXT);

        $hasguides = $manager->has_guides($templateid);
        if (!empty($templateid) && $hasguides) {
            $templatetext = $manager->rewrite_template_pluginfile_urls($templateobj, $templateobj->get_template_content());
            $mform->addElement('static', 'templatetext', get_string('template', 'block_moderator_guide'), $templatetext);

            // TODO - use the moodleform function to try to put the correct value in template['text'],
            // TODO - instead of avoiding updating template.
            $mform->addElement('hidden', 'dontupdatetemplate', 1);
            $mform->setType('dontupdatetemplate', PARAM_INT);
            $mform->setConstant('dontupdatetemplate', 1);
        }

        if (empty($hasguides)) {
            $mform->addElement('editor', 'template_editor', get_string('template', 'block_moderator_guide'),
                null, $manager->get_template_editor_options($templateobj));
            $mform->setType('template', PARAM_CLEANHTML);
        }

        // Moderation fields
        if(!empty(get_config('block_moderator_guide', 'moderation'))) {
            $mform->addElement('checkbox', 'cancomplete', '',
                ' ' . get_string('cancomplete', 'block_moderator_guide'));
            $mform->setType('cancomplete', PARAM_INT);
            $mform->addElement('checkbox', 'canreview', '',
                ' ' . get_string('canreview', 'block_moderator_guide'));
            $mform->setType('canreview', PARAM_INT);
        }

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        if (!empty($templateid)) {
            $buttonlabel = get_string('savechanges');
        } else {
            $buttonlabel = get_string('addtemplate', 'block_moderator_guide');
        }

        $this->add_action_buttons(true, $buttonlabel);

        $this->set_data($template);
    }

    /**
     * Convenience method to get the data.
     *
     * @return stdClass|null
     */
    public function get_data() {
        $data = parent::get_data();
        if (!$data) {
            return $data;
        }

        if (!empty($data->dontupdatetemplate)) {
            unset($data->template_editor);
        }

        return $data;
    }

}
