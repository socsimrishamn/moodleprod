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
 * Guide.
 *
 * @package    mod_courseguide;
 * @copyright  2017 onwards Coventry University {@link http://www.coventry.ac.uk/}
 * @author     Jerome Mouneyrac - Bepaw Pty Ltd <jerome@bepaw.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_courseguide;
defined('MOODLE_INTERNAL') || die();

use block_moderator_guide\guide_generic;

/**
 * Guide class.
 *
 * The guide class for the block.
 *
 * @package    mod_courseguide;
 * @copyright  2017 onwards Coventry University {@link http://www.coventry.ac.uk/}
 * @author     Jerome Mouneyrac - Bepaw Pty Ltd <jerome@bepaw.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class guide extends guide_generic {

    /**
     * Constructor.
     *
     * @param template $template The template.
     * @param \stdClass|null $record The record.
     * @param guide_content[] $contents The contents.
     */
    public function __construct(template $template, \stdClass $record = null, array $contents = []) {
        global $DB;

        // Set the name of the record if it exists.
        if (!empty($record->courseguideid)) {
            $courseguide = $DB->get_record('courseguide', array('id' => $record->courseguideid));
            $record->name = $courseguide->name;
        }

        parent::__construct($template, $record, $contents);
    }

    /**
     * Get the default record.
     *
     * @return stdClass
     */
    protected function get_default_record() {
        $record = parent::get_default_record();
        $record->courseid = SITEID;
        $record->displaymode = $this->template->get_display_mode();
        if ($record->displaymode == 'any') {
            $record->displaymode = $this->template->get_default_display_mode();
        }
        return $record;
    }

    /**
     * Get the course ID.
     *
     * @return int
     */
    public function get_courseid() {

        // if preview mode.
        if (empty($this->record->courseguideid)) {
            // return course system.
            return SITEID;
        }

        $cm = $this->get_coursemodule();

        return $cm->course;
    }

    /**
     * Get the course module.
     *
     * @return int
     */
    public function get_coursemodule() {

        $cm = get_coursemodule_from_instance('courseguide', $this->record->courseguideid);

        return $cm;
    }

    /**
     * Get guide display mode (moodle, inline, collapsable).
     *
     * @return mixed
     */
    public function get_displaymode() {
        return $this->record->displaymode;
    }

    /**
     * Get courseguide id.
     *
     * @return mixed
     */
    public function get_courseguideid() {
        return $this->record->courseguideid;
    }

}
