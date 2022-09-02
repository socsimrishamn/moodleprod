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
 * Moderator guide restore task.
 *
 * @package    block_moderator_guide
 * @copyright  2017 onwards Coventry University {@link http://www.coventry.ac.uk/}
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/restore_moderator_guide_stepslib.php');

/**
 * Moderator guide restore task class.
 *
 * @package    block_moderator_guide
 * @copyright  2017 onwards Coventry University {@link http://www.coventry.ac.uk/}
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_moderator_guide_block_task extends restore_block_task {

    /**
     * Return the old course context ID.
     * @return int
     */
    public function get_old_course_contextid() {
        return $this->plan->get_info()->original_course_contextid;
    }

    /**
     * Return the old context system ID.
     * @return int
     */
    public function get_old_system_contextid() {
        return $this->plan->get_info()->original_system_contextid;
    }

    /**
     * Define my settings.
     */
    protected function define_my_settings() {
    }

    /**
     * Define my steps.
     */
    protected function define_my_steps() {
        $this->add_step(new restore_moderator_guide_block_structure_step('mdrtguide', 'mdrtguide.xml'));
    }

    /**
     * File areas.
     * @return array
     */
    public function get_fileareas() {
        return array();
    }

    /**
     * Config data.
     */
    public function get_configdata_encoded_attributes() {
    }

    /**
     * Define decode contents.
     * @return array
     */
    public static function define_decode_contents() {
        return array();
    }

    /**
     * Define decode rules.
     * @return array
     */
    public static function define_decode_rules() {
        return array();
    }

    /**
     * Encore content links.
     * @param  string $content The content.
     * @return string
     */
    public static function encode_content_links($content) {
        return $content;
    }
}
