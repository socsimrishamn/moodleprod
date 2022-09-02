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
 * Block moderator_guide is defined here.
 *
 * @package    block_moderator_guide
 * @copyright  2016 onwards Coventry University {@link http://www.coventry.ac.uk/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Jerome Mouneyrac <jerome@mouneyrac.com>
 */

defined('MOODLE_INTERNAL') || die();

/**
 * moderator_guide block.
 *
 * @package    block_moderator_guide
 * @copyright  2016 onwards Coventry University {@link http://www.coventry.ac.uk/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Jerome Mouneyrac <jerome@mouneyrac.com>
 */
class block_moderator_guide extends block_base {

    /**
     * Initializes class member variables.
     */
    public function init() {
        // Needed by Moodle to differentiate between blocks.
        $this->title = get_string('pluginname', 'block_moderator_guide');
    }

    /**
     * Returns the block contents.
     *
     * @return stdClass The block contents.
     */
    public function get_content() {
        global $COURSE, $DB, $PAGE;

        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass();
        $this->content->items = array();
        $this->content->icons = array();
        $this->content->footer = '';
        $this->content->text = '';

        if (!has_capability('block/moderator_guide:viewguide', $PAGE->context)) {
            return $this->content;

        } else if (empty($this->instance)) {
            return $this->content;
        }

        $manager = new \block_moderator_guide\manager();
        $guides = $manager->list_visible_guides(['courseid' => $COURSE->id, 'hidden' => 0]);

        if (empty($guides)) {
            $text = get_string('noguidesforthiscourse', 'block_moderator_guide');
        } else {
            $text = '<ul>';
            foreach ($guides as $guideobj) {
                $guide = $guideobj->get_record();
                $text .= '<li>' . html_writer::link(new moodle_url('/blocks/moderator_guide/view.php',
                    array('guideid' => $guide->id)), $guide->name) . '</li>';
            }
            $text .= '</ul>';
        }

        if (has_capability('block/moderator_guide:editguide', context_course::instance($COURSE->id))) {
            $manageguideslink = html_writer::link(new moodle_url('/blocks/moderator_guide/manage_guides.php',
                array('courseid' => $COURSE->id)), get_string('manageguides', 'block_moderator_guide'));
            $text .= html_writer::div($manageguideslink, 'block_moderator_guide_manageguide');
        }

        $this->content->text = $text;

        return $this->content;
    }

    /**
     * Defines configuration data.
     *
     * The function is called immediatly after init().
     */
    public function specialization() {

        // Load user defined title and make sure it's never empty.
        // config_title is defined in the edit_form.php file.
        if (empty($this->config->title)) {
            $this->title = get_string('pluginname', 'block_moderator_guide');
        } else {
            $this->title = $this->config->title;
        }
    }

    /**
     * Enables global configuration of the block in settings.php.
     *
     * @return bool True if the global configuration is enabled.
     */
    public function has_config() {
        return true;
    }

    /**
     * Sets the applicable formats for the block.
     *
     * @return string[] Array of pages and permissions.
     */
    public function applicable_formats() {
        return array('all' => true);
    }
}
