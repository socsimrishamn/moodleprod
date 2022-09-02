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
 * @package    block_moderator_guide;
 * @copyright  2016 onwards Coventry University {@link http://www.coventry.ac.uk/}
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_moderator_guide;
defined('MOODLE_INTERNAL') || die();

/**
 * Guide class.
 *
 * The guide class for the block.
 *
 * @package    block_moderator_guide;
 * @copyright  2016 onwards Coventry University {@link http://www.coventry.ac.uk/}
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class guide extends guide_generic {

    /**
     * Get the default record.
     *
     * @return stdClass
     */
    protected function get_default_record() {
        $record = parent::get_default_record();
        $record->courseid = SITEID;
        $record->name = $this->template->get_default_guide_name();
        return $record;
    }

    /**
     * Get the course ID.
     *
     * @return int
     */
    public function get_courseid() {
        return $this->record->courseid;
    }

}
