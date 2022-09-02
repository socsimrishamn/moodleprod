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
 * Abstract guide.
 *
 * @package    block_moderator_guide;
 * @copyright  2016 onwards Coventry University {@link http://www.coventry.ac.uk/}
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_moderator_guide;
defined('MOODLE_INTERNAL') || die();

/**
 * Abstract guide class.
 *
 * Class holding the rudimentary functionality of a guide.
 *
 * @package    block_moderator_guide;
 * @copyright  2016 onwards Coventry University {@link http://www.coventry.ac.uk/}
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class guide_base {

    /** @var template_base The template. */
    protected $template;

    /** @var stdClass The record. */
    protected $record;

    /** @var guide_content[] The contents */
    protected $contents;

    /**
     * Constructor.
     *
     * @param template_base $template The template.
     * @param \stdClass|null $record The record.
     * @param guide_content[] $contents The contents.
     */
    public function __construct(template_base $template, \stdClass $record = null, array $contents = []) {
        $this->template = $template;
        $this->record = $record !== null ? $record : $this->get_default_record();
        $this->contents = $contents;
    }

    /**
     * Get a blank record.
     *
     * @return stdClass
     */
    protected function get_default_record() {
        return (object) [
            'id' => 0,
            'templateid' => $this->template->get_id(),
            'creatorid' => 0,
            'timecreated' => 0,
            'timemodified' => 0,
            'hidden' => 0,
            'completed' => 0,
            'reviewed' => 0,
            'reviewcomment' => '',
            'completeduserid' => 0,
            'completedtime' => 0,
            'revieweduserid' => 0,
            'reviewedtime' => 0
        ];
    }

    /**
     * Get the ID.
     *
     * @return int 0 when unknown.
     */
    public function get_id() {
        return !empty($this->record->id) ? $this->record->id : 0;
    }

    /**
     * Get the template.
     *
     * @return template_base
     */
    public function get_template() {
        return $this->template;
    }

    /**
     * Get the name of the guide.
     *
     * @return string
     */
    abstract public function get_name();

    /**
     * Get the completed status.
     *
     * @return int
     */
    public function get_completed() {
        return $this->record->completed;
    }

    /**
     * Get the completed status time.
     *
     * @return int
     */
    public function get_completedtime() {
        return $this->record->completedtime;
    }

    /**
     * Get the completed status author.
     *
     * @return int
     */
    public function get_completeduserid() {
        return $this->record->completeduserid;
    }

    /**
     * Get the reviewed status.
     *
     * @return int
     */
    public function get_reviewed() {
        return $this->record->reviewed;
    }

    /**
     * Get the reviewed status time.
     *
     * @return int
     */
    public function get_reviewedtime() {
        return $this->record->reviewedtime;
    }

    /**
     * Get the reviewed status author.
     *
     * @return int
     */
    public function get_revieweduserid() {
        return $this->record->revieweduserid;
    }

    /**
     * Get the review comment.
     *
     * @return string
     */
    public function get_reviewcomment() {
        return $this->record->reviewcomment;
    }

    /**
     * Get the record.
     *
     * @return stdClass
     */
    public function get_record() {
        $record = (object) (array) $this->record;
        $record->templateid = $this->template->get_id();
        return $record;
    }

    /**
     * Return a content by placeholderid.
     *
     * @param $placeholderid
     * @return guide_content|mixed|null
     */
    public function get_content($placeholderid) {
        foreach ($this->contents as $content) {
            if ($content->placeholderid == $placeholderid) {
                return $content;
            }
        }
        return null;
    }

    /**
     * Get the contents.
     *
     * @return guide_content[]
     */
    public function get_contents() {
        return $this->contents;
    }

    /**
     * Import the values from a record.
     *
     * @param \stdClass|array $record Record.
     * @return void
     */
    public function import_record($record) {
        foreach ((array) $record as $key => $value) {
            $this->record->{$key} = $value;
        }
    }

    /**
     * Import contents.
     *
     * This does not override all the fields of existing content, only the value ones.
     *
     * @param stdClass[] $newcontents The new contents as stdClass.
     * @return void
     */
    public function import_contents(array $newcontents) {
        // Organise existing content indexed by key.
        $contents = array_reduce($this->contents, function($carry, $content) {
            $carry[$content->placeholderid] = $content;
            return $carry;
        }, []);

        // Merge new contents with existing content.
        foreach ($newcontents as $newcontent) {
            if (isset($contents[$newcontent->placeholderid])) {
                // We have to update an existing one.
                $existing = $contents[$newcontent->placeholderid];
                $existing->import_value($newcontent);
            } else {
                // Simply add the one.
                $contents[] = new guide_content($newcontent);
            }
        }

        $this->contents = $contents;
    }

    /**
     * Replaces the existing content.
     *
     * @param guide_content[] $contents The contents.
     */
    public function set_contents(array $contents) {
        $this->contents = $contents;
    }

    /**
     * Fill in the missing contents from the template.
     *
     * @return void
     */
    public function fill_missing_contents() {
        $defaultcontents = $this->get_template()->get_default_guide_contents();
        foreach ($defaultcontents as $content) {
            $localcontent = $this->get_content($content->placeholderid);
            if ($localcontent === null) {
                $this->contents[] = $content;
            }
        }
    }

}
