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
 * @package    block_moderator_guide
 * @copyright  2017 onwards Coventry University {@link http://www.coventry.ac.uk/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Jerome Mouneyrac - Bepaw Pty Ltd <jerome@bepaw.com>
 */

namespace block_moderator_guide\webservice;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . "/externallib.php");

use context_system;
use external_api;
use external_function_parameters;
use external_value;
use external_single_structure;
use external_multiple_structure;

/**
 * External API class.
 *
 * @package    block_moderator_guide
 * @copyright  2017 onwards Coventry University {@link http://www.coventry.ac.uk/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Jerome Mouneyrac - Bepaw Pty Ltd <jerome@bepaw.com>
 */
class external extends external_api {

    /**
     * Returns description of get_guides_courses() parameters.
     *
     * @return \external_function_parameters
     */
    public static function get_guides_courses_parameters() {
        return new external_function_parameters(
            array(
                'courseid' => new external_value(PARAM_INT, 'course ID', VALUE_DEFAULT, ''),
                'courseshortname' => new external_value(PARAM_TEXT, 'course shortname - can contain %',
                    VALUE_DEFAULT, ''),
                'organisation' => new external_value(PARAM_TEXT,
                    'organisation, only guides with this organisation will be returned/considered in the results',
                    VALUE_DEFAULT, ''),
                'limitfrom' => new external_value(PARAM_INT, 'sql limit from', VALUE_DEFAULT, 0),
                'limitnum' => new external_value(PARAM_INT, 'sql limit num', VALUE_DEFAULT, 0)
            )
        );
    }

    /**
     * Get guides status per course.
     *
     * @param string $courseid
     * @param string $courseshortname
     * @param string $organisation
     * @param int $limitfrom
     * @param int $limitnum
     * @param string $component
     * @return mixed
     */
    protected static function get_the_guides_courses($courseid = '', $courseshortname = '', $organisation = '',
                                                     $limitfrom = 0, $limitnum = 0, $component = 'block_moderator_guide') {

        // Clean parameters.
        $params = self::validate_parameters(self::get_guides_courses_parameters(), array(
            'courseid' => $courseid, 'courseshortname' => $courseshortname, 'organisation' => $organisation,
            'limitfrom' => $limitfrom, 'limitnum' => $limitnum
        ));

        // Check context and capabilities.
        $context = context_system::instance();
        require_capability('block/moderator_guide:viewguidestatus', $context);
        self::validate_context($context);

        // Retrieve the info.
        $managerclassname = '\\'.$component. '\\manager';
        $manager = new $managerclassname();
        $info = $manager->get_guides_courses($params['courseid'], $params['courseshortname'], $params['organisation'],
            $params['limitfrom'], $params['limitnum'] );

        return $info;
    }

    /**
     * Get guides status per course (wrapper for the generic function)
     *
     * @param string $courseid
     * @param string $courseshortname
     * @param string $organisation
     * @param int $limitfrom
     * @param int $limitnum
     * @return mixed
     */
    public static function get_guides_courses($courseid = '', $courseshortname = '',
                                              $organisation = '', $limitfrom = 0, $limitnum = 0) {
        return self::get_the_guides_courses($courseid , $courseshortname, $organisation, $limitfrom,
            $limitnum, 'block_moderator_guide');
    }

    /**
     * Returns description of get_guides_courses() result value.
     *
     * @return \external_description
     */
    public static function get_guides_courses_returns() {
        return new external_multiple_structure(
            new external_single_structure(
                array(
                    'id'       => new external_value(PARAM_INT, 'course id'),
                    'guides' => new external_multiple_structure(
                        new external_single_structure(
                            array(
                                'id'       => new external_value(PARAM_INT, 'guide id'),
                                'name' => new external_value(PARAM_TEXT, 'guide name'),
                                'totalincompletefields' => new external_value(PARAM_INT, 'number of empty fields'),
                                'totalcompletefields' => new external_value(PARAM_INT, 'number of completed fields'),
                                'completed' =>  new external_value(PARAM_INT, '1 if marked as completed', VALUE_OPTIONAL),
                                'reviewed' =>  new external_value(PARAM_INT, '1 if marked as reviewed', VALUE_OPTIONAL),
                                'hidden' => new external_value(PARAM_INT, '1 is the guide is empty'),
                                'templateid' => new external_value(PARAM_INT, 'template id'),
                            )
                        ), 'the guides in the course'
                    ),
                    'totalincomplete' => new external_value(PARAM_INT, 'total number of guide with unfilled fields'),
                    'totalcomplete' => new external_value(PARAM_INT, 'total number of guide with all fields completed'),
                    'totalmarkascompleted' => new external_value(PARAM_INT, 'total number of guide marked as completed', VALUE_OPTIONAL),
                    'totalmarkasreviewed' => new external_value(PARAM_INT, 'total number of guide marked as reviewed', VALUE_OPTIONAL),
                    'totalguides' => new external_value(PARAM_INT, 'total number of guides'),
                    'shortname' => new external_value(PARAM_TEXT, 'course shortname')
                )
            )
        );
    }

}
