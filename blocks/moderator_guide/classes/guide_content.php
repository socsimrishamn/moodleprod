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
 * Guide contents.
 *
 * @package    block_moderator_guide;
 * @copyright  2016 onwards Coventry University {@link http://www.coventry.ac.uk/}
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_moderator_guide;
defined('MOODLE_INTERNAL') || die();

use stdClass;

/**
 * Guide contents class.
 *
 * @package    block_moderator_guide;
 * @copyright  2016 onwards Coventry University {@link http://www.coventry.ac.uk/}
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class guide_content extends stdClass {

    /** @var int Database ID. */
    public $id;
    /** @var int Guide ID. */
    public $guideid;
    /** @var string Value. */
    public $value;
    /** @var int Value format. */
    public $valueformat;
    /** @var string Placeholder ID. */
    public $placeholderid;
    /** @var string Placeholder type. */
    public $placeholdertype;
    /** @var int Time created. */
    public $timecreated;
    /** @var int Time modified. */
    public $timemodified;

    /** @var array List of fields which we can update an existing instance with. */
    protected $importablefields = ['value', 'valueformat'];

    /**
     * Constructor.
     *
     * @param stdClass|null $record The record.
     */
    public function __construct(stdClass $record = null) {
        foreach ((array) $record as $key => $value) {
            $this->{$key} = $value;
        }
    }

    /**
     * Get the record for database submission.
     *
     * @return stdClass
     */
    public function get_record() {
        return (object) [
            'id' => $this->id,
            'guideid' => $this->guideid,
            'value' => $this->value,
            'valueformat' => $this->valueformat,
            'placeholderid' => $this->placeholderid,
            'placeholdertype' => $this->placeholdertype,
            'timecreated' => $this->timecreated,
            'timemodified' => $this->timemodified,
        ];
    }

    /**
     * Import values.
     *
     * @param stdClass $record Another record.
     * @return void
     */
    public function import_value(stdClass $record) {
        $this->value = $record->value;
        $this->valueformat = isset($record->valueformat) ? $record->valueformat : null;
    }

}
