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
 * Services
 * @package    block_moderator_guide
 * @copyright  2017 onwards Coventry University {@link http://www.coventry.ac.uk/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Jerome Mouneyrac - Bepaw Pty Ltd <jerome@bepaw.com>
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
    'block_moderator_guide_get_guides_courses' => [
        'classname'     => 'block_moderator_guide\\webservice\\external',
        'methodname'    => 'get_guides_courses',
        'description'   => 'Return the block moderator guides sorted by courses. It can be filtered by organisation.
            Each courses are returned with the total number of incomplete and complete guides.',
        'type'          => 'read',
        'ajax'          => true,
        'loginrequired' => true
    ],
];
