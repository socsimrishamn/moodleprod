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
 * @package    mod_courseguide;
 * @copyright  2017 onwards Coventry University {@link http://www.coventry.ac.uk/}
 * @author     Jerome Mouneyrac - Bepaw Pty Ltd <jerome@bepaw.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_courseguide\form;
defined('MOODLE_INTERNAL') || die();

use \block_moderator_guide\form\template_generic;

/**
 * Edit template form.
 *
 * @package    mod_courseguide;
 * @copyright  2017 onwards Coventry University {@link http://www.coventry.ac.uk/}
 * @author     Jerome Mouneyrac - Bepaw Pty Ltd <jerome@bepaw.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class template extends template_generic {

    /**
     * Form definition.
     */
    public function definition() {
        parent::definition();
        $mform = $this->_form;

        $suggestedguidename = $mform->createElement('text', 'defaultguidename',
            get_string('defaultguidename', 'courseguide'));
        $mform->insertElementBefore($suggestedguidename, 'organization');
        $mform->setType('defaultguidename', PARAM_TEXT);

        $displaymodeoptions = array('any' => get_string('any', 'courseguide'),
            'moodle' => get_string('moodle', 'courseguide'),
            'inline' => get_string('inline', 'courseguide'),
            'collapsable' => get_string('collapsable', 'courseguide'));
        $displaymode = $mform->createElement('select', 'displaymode', get_string('displaymode',
            'courseguide'), $displaymodeoptions);
        $mform->insertElementBefore($displaymode, 'organization');
        $mform->setType('displaymode', PARAM_TEXT);

        $defaultdisplaymodeoptions = array('moodle' => get_string('moodle', 'courseguide'),
            'inline' => get_string('inline', 'courseguide'),
            'collapsable' => get_string('collapsable', 'courseguide'));
        $defaultdisplaymode = $mform->createElement('select', 'defaultdisplaymode', get_string('defaultdisplaymode',
            'courseguide'), $defaultdisplaymodeoptions);
        $mform->insertElementBefore($defaultdisplaymode, 'organization');
        $mform->setType('defaultdisplaymode', PARAM_TEXT);
        $mform->disabledIf('defaultdisplaymode', 'displaymode', 'noteq', 'any');
    }

}
