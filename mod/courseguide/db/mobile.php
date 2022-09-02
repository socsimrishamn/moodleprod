<?php
// This file is part of the Course guide module for Moodle - http://moodle.org/
//
// It is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// It is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.
/**
 * Course guide mobile addons
 *
 * @package    mod_courseguide
 * @copyright 2018 Coventry University
 * @author Jerome Mouneyrac <jerome@bepaw.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
$addons = array(
    "mod_courseguide" => array(
        "handlers" => array( // Different places where the add-on will display content.
            'courseguide' => array( // Handler unique name (can be anything)
                'displaydata' => array(
                    'title' => 'pluginname',
                    'icon' => $CFG->wwwroot . '/mod/courseguide/pix/icon.gif',
                    'class' => '',
                ),
                'delegate' => 'CoreCourseModuleDelegate', // Delegate (where to display the link to the add-on)
                'method' => 'mobile_course_view', // Main function in \mod_courseguide\output\mobile
                'offlinefunctions' => array(
                    'mobile_course_view' => array(),
                ), // Function needs caching for offline.
            )
        ),
        'lang' => array(
            array('pluginname', 'courseguide'),
        )
    )
);