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
 * External API.
 *
 * @package    mod_courseguide
 * @copyright  2017 onwards Coventry University {@link http://www.coventry.ac.uk/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Jerome Mouneyrac - Bepaw Pty Ltd <jerome@bepaw.com>
 */

namespace mod_courseguide\webservice;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . "/externallib.php");

/**
 * External API class.
 *
 * @package    mod_courseguide
 * @copyright  2017 onwards Coventry University {@link http://www.coventry.ac.uk/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Jerome Mouneyrac - Bepaw Pty Ltd <jerome@bepaw.com>
 */
class external extends \block_moderator_guide\webservice\external {



    /**
     * Get guides status per course (wrapper for the generic function)
     *
     * @param string $courseid
     * @param string $courseshortname
     * @param string $organisation
     */
    public static function get_guides_courses($courseid = '', $courseshortname = '',
                                              $organisation = '', $limitfrom = 0, $limitnum = 0) {

        return parent::get_the_guides_courses($courseid , $courseshortname, $organisation, $limitfrom, $limitnum, 'mod_courseguide');
    }

}
