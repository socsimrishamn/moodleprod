<?php
// This file is part of Moderator Guide plugin for Moodle
//
// Moderator Guide plugin for Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moderator Guide plugin for Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moderator Guide plugin for Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Moderator Guide lib
 *
 * @package    block_moderator_guide
 * @copyright  2016 onwards Coventry University {@link http://www.coventry.ac.uk/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Jerome Mouneyrac <jerome@mouneyrac.com>
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Serve the block files.
 *
 * @param object $course The course.
 * @param object $cm The course module.
 * @param context $context The context.
 * @param string $filearea The file area.
 * @param array $args Arguments.
 * @param bool $forcedownload Whether to force the download.
 * @param array $options Options.
 * @return bool
 */
function block_moderator_guide_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = array()) {
    $manager = new \block_moderator_guide\manager();
    return $manager->serve_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, $options);
}
