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
 * Moderation form.
 *
 * @package    block_moderator_guide;
 * @copyright  2017 onwards Coventry University {@link http://www.coventry.ac.uk/}
 * @author     Jerome Mouneyrac <jerome@bepaw.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_moderator_guide\form;
defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Moderation form.
 *
 * @package    block_moderator_guide;
 * @copyright  2017 onwards Coventry University {@link http://www.coventry.ac.uk/}
 * @author     Jerome Mouneyrac <jerome@bepaw.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class moderation extends \moodleform {

    /**
     * Form definition.
     */
    public function definition() {
        global $PAGE;

        $mform = $this->_form;
        $data = $this->_customdata['data'];
        $template = $this->_customdata['template'];

        // Moderation fields
        if(!empty(get_config('block_moderator_guide', 'moderation'))) {

            $cancomplete = has_capability('block/moderator_guide:cancomplete', $PAGE->context);
            $canreview = has_capability('block/moderator_guide:canreview', $PAGE->context);

            if ($cancomplete && $template->get_cancomplete()) {
                $mform->addElement('checkbox', 'completed',
                    get_string('completed', 'block_moderator_guide'));
                $mform->setType('completed', PARAM_INT);
            }

            if ($canreview && $template->get_canreview()) {
                $mform->addElement('checkbox', 'reviewed',
                    get_string('reviewed', 'block_moderator_guide'));
                $mform->setType('reviewed', PARAM_INT);

                $mform->addElement('textarea', 'reviewcomment',
                    get_string('reviewcomment', 'block_moderator_guide'),
                    array('rows' => 8, 'cols' => 50));
                $mform->setType('reviewcomment', PARAM_TEXT);
            }

        }

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $this->add_action_buttons(false, get_string('savechanges'));

        $this->set_data($data);
    }
}
