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
 * Template.
 *
 * @package    mod_courseguide;
 * @copyright  2017 onwards Coventry University {@link http://www.coventry.ac.uk/}
 * @author     Jerome Mouneyrac - Bepaw Pty Ltd <jerome@bepaw.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_courseguide;
defined('MOODLE_INTERNAL') || die();

use block_moderator_guide\template_base;

/**
 * Template class.
 *
 * The template class for the block.
 *
 * @package    mod_courseguide;
 * @copyright  2017 onwards Coventry University {@link http://www.coventry.ac.uk/}
 * @author     Jerome Mouneyrac - Bepaw Pty Ltd <jerome@bepaw.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class template extends template_base {

    /**
     * Get a blank record.
     *
     * @return stdClass
     */
    protected function get_default_record() {
        $record = parent::get_default_record();
        $record->defaultguidename = '';
        $record->displaymode = 'any';
        $record->defaultdisplaymode = 'moodle';
        $record->template = get_string('templateexample', 'courseguide');
        return $record;
    }

    /**
     * Return the default "guide name".
     *
     * @return string
     */
    public function get_default_guide_name() {
        return $this->record->defaultguidename;
    }

    /**
     * Return the default "display mode".
     *
     * @return string
     */
    public function get_default_display_mode() {
        return $this->record->defaultdisplaymode;
    }

    /**
     * Return the "display mode".
     *
     * @return string
     */
    public function get_display_mode() {
        return $this->record->displaymode;
    }

}
