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
 * @package    mod_courseguide;
 * @copyright  2017 onwards Coventry University {@link http://www.coventry.ac.uk/}
 * @author     Jerome Mouneyrac - Bepaw Pty Ltd <jerome@bepaw.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_courseguide\form;
defined('MOODLE_INTERNAL') || die();

use \block_moderator_guide\form\guide_generic;

/**
 * Edit template form.
 *
 * @package    mod_courseguide;
 * @copyright  2017 onwards Coventry University {@link http://www.coventry.ac.uk/}
 * @author     Jerome Mouneyrac - Bepaw Pty Ltd <jerome@bepaw.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class guide extends guide_generic {

    /**
     * Form definition.
     */
    public function definition() {
        parent::definition();
        $mform = $this->_form;
        $template = $this->_customdata['selectedtemplate'];
        $guide = $this->_customdata['guide'];

        $el = $mform->createElement('static', '',
            get_string('defaultguidename', 'courseguide'), $template->defaultguidename);
        $mform->insertElementBefore($el, 'template');
        $mform->setType('defaultguidename', PARAM_TEXT);

        if ($template->displaymode != 'any') {
            $displaymodeoptions = array($template->displaymode => get_string($template->displaymode, 'courseguide'));
        } else {
            $displaymodeoptions = array(
                'moodle' => get_string('moodle', 'courseguide'),
                'inline' => get_string('inline', 'courseguide'),
                'collapsable' => get_string('collapsable', 'courseguide'));
        }

        $displaymode = $mform->createElement('select', 'displaymode', get_string('displaymode',
            'courseguide'), $displaymodeoptions);
        $mform->insertElementBefore($displaymode, 'template');
        $mform->setType('displaymode', PARAM_TEXT);
    }

}
