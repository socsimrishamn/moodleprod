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
 * Template base.
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
 * Template base class.
 *
 * @package    block_moderator_guide;
 * @copyright  2016 onwards Coventry University {@link http://www.coventry.ac.uk/}
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class template_base {

    /** The regex to parse the fields. */
    const FIELDSREGEX = '/(\[[0-9]*:html\]|\[[0-9]*:files\]|\[[0-9]*:link\]|[[0-9]*:html:BEGIN\]|[[0-9]*:html:END\]' .
        '|[[0-9]*:link:BEGIN\]|[[0-9]*:link:END\])/';

    /** @var stdClass Record */
    protected $record = null;

    /**
     * Constructor.
     *
     * @param stdClass|null $record The template data.
     */
    public function __construct(\stdClass $record = null) {
        if ($record === null) {
            $record = $this->get_default_record();
        }
        $this->record = $record;
    }

    /**
     * Get a blank record.
     *
     * @return stdClass
     */
    protected function get_default_record() {
        return (object) [
            'id' => 0,
            'name' => '',
            'organization' => '',
            'description' => '',
            'descriptionformat' => FORMAT_HTML,
            'template' => get_string('templateexample', 'block_moderator_guide'),
            'templateformat' => FORMAT_HTML,
            'timecreated' => 0,
            'timemodified' => 0,
            'cancomplete' => 0,
            'canreview' => 0,
            'hidden' => 1           // By default templates are hidden.
        ];
    }

    /**
     * Get the template ID.
     *
     * @return int 0 when unknown.
     */
    public function get_id() {
        return !empty($this->record->id) ? $this->record->id : 0;
    }

    /**
     * Get the template's name.
     *
     * @return string
     */
    public function get_name() {
        return $this->record->name;
    }

    /**
     * Get the template's description.
     *
     * @return string
     */
    public function get_description() {
        return $this->record->description;
    }

    /**
     * Get the template's record.
     *
     * @return stdClass
     */
    public function get_record() {
        // Make a shallow copy.
        return (object) (array) $this->record;
    }

    /**
     * Get the regex to split the fields on.
     *
     * @return string
     */
    protected function get_fields_regex() {
        return static::FIELDSREGEX;
    }

    /**
     * Get the template content.
     *
     * @return string
     */
    public function get_template_content() {
        if (!isset($this->record->template)) {
            return '';
        }
        return $this->record->template;
    }

    /**
     * Get the template format.
     *
     * @return string
     */
    public function get_template_format() {
        return $this->record->templateformat;
    }

    /**
     * Get the organization.
     *
     * @return string
     */
    public function get_organization() {
        return !empty($this->record->organization) ? $this->record->organization : '';
    }

    /**
     * Get the can complete status.
     *
     * @return int
     */
    public function get_cancomplete() {
        return !empty($this->record->cancomplete) ? $this->record->cancomplete : 0;
    }

    /**
     * Get the can review status.
     *
     * @return int
     */
    public function get_canreview() {
        return !empty($this->record->canreview) ? $this->record->canreview : 0;
    }

    /**
     * Whether the template has an organization.
     *
     * @return bool
     */
    public function has_organization() {
        return !empty($this->record->organization);
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
     * Parse the template.
     *
     * @return array of fields.
     */
    public function parse() {
        $content = $this->get_template_content();
        $parsedfields = preg_split($this->get_fields_regex(), $content, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);

        $defaultplaceholder = false;
        $fields = array();
        $default = '';
        foreach ($parsedfields as $id => $field) {
            switch ($field) {
                case (preg_match('/\[[0-9]*:html\]/', $field) ? true : false):
                    if ($defaultplaceholder) {
                        // It is just a default html value [X:html] inside a [Y:html:BEGIN] (should not happen,
                        // it is likely a user error).
                        $default .= $field;
                    } else {
                        $fields[$id] = array('input' => 'html', 'value' => $field, 'id' => $id);
                    }
                    break;
                case (preg_match('/\[[0-9]*:files\]/', $field) ? true : false):
                    if ($defaultplaceholder) {
                        // It is just a default html value [X:files] inside a [Y:html:BEGIN] (should not happen,
                        // it is likely a user error).
                        $default .= $field;
                    } else {
                        $fields[$id] = array('input' => 'files', 'value' => $field, 'id' => $id);
                    }

                    break;
                case (preg_match('/\[[0-9]*:link\]/', $field) ? true : false):
                    if ($defaultplaceholder) {
                        // It is just a default html value [X:link] inside a [Y:html:BEGIN] (should not happen,
                        // it is likely a user error).
                        $default .= $field;
                    } else {
                        $fields[$id] = array('input' => 'link', 'value' => $field, 'id' => $id);
                        $fields[$id . '_linkname'] = array('input' => 'linkname',
                            'value' => $field . '_linkname', 'id' => $id . '_linkname');
                    }

                    break;
                case (preg_match('/[[0-9]*:html:BEGIN\]/', $field) ? true : false):
                    if ($defaultplaceholder) {
                        // It is just a value [X:html:BEGIN] inside a [Y:type:BEGIN] (should not happen,
                        // it is likely a user error).
                        $default .= $field;
                    } else {
                        // Starting to record the default html till we reach a END placeholder.
                        $default = '';
                        $defaulthtmlid = $id;
                        $defaulthtmlvalue = $field;
                        $defaultplaceholder = 'html';
                    }
                    break;
                case (preg_match('/[[0-9]*:html:END\]/', $field) ? true : false):
                    if ($defaultplaceholder and $defaultplaceholder === 'html') {
                        $defaultplaceholder = false;
                        $fields[$defaulthtmlid] = array('input' => 'html', 'value' => $defaulthtmlvalue, 'id' => $defaulthtmlid,
                            'default' => $default);
                    } else {
                        if ($defaultplaceholder) {
                            // It is just a default html value [X:html:END] inside a [Y:type:BEGIN] (should not happen,
                            // it is likely a user error).
                            $default .= $field;
                        } else {
                            // It is just static html value [X:html:END] (should not happen, it is likely a user error).
                            $fields[$id] = array('input' => 'static', 'value' => $field, 'id' => $id);
                        }
                    }

                    break;
                case (preg_match('/[[0-9]*:link:BEGIN\]/', $field) ? true : false):
                    if ($defaultplaceholder) {
                        // It is just a [X:link:BEGIN] inside a [Y:type:BEGIN] (should not happen,
                        // it is likely a user error).
                        $default .= $field;
                    } else {
                        // Starting to record the default html till we reach a END placeholder.
                        $default = '';
                        $defaultlinkid = $id;
                        $defaultlinkvalue = $field;
                        $defaultplaceholder = 'link';
                    }
                    break;
                case (preg_match('/[[0-9]*:link:END\]/', $field) ? true : false):
                    if ($defaultplaceholder and $defaultplaceholder === 'link') {
                        $defaultplaceholder = false;
                        $fields[$defaultlinkid] = array('input' => 'link', 'value' => $defaultlinkvalue, 'id' => $defaultlinkid);
                        $fields[$defaultlinkid . '_linkname'] = array('input' => 'linkname',
                            'value' => $defaultlinkvalue . '_linkname',
                            'id' => $defaultlinkid . '_linkname', 'default' => $default);
                    } else {
                        if ($defaultplaceholder) {
                            // It is just a default html value [X:link:END] inside a [Y:type:BEGIN] (should not happen,
                            // it is likely a user error).
                            $default .= $field;
                        } else {
                            // It is just static html value [X:link:END] (should not happen, it is likely a user error).
                            $fields[$id] = array('input' => 'static', 'value' => $field, 'id' => $id);
                        }

                    }

                    break;
                default:
                    if ($defaultplaceholder) {
                        // Should be typical case of inside html between [X:html:BEGIN] and [Y:html:BEGIN].
                        $default .= $field;
                    } else {
                        // Just pure HTML outside placeholder.
                        $fields[$id] = array('input' => 'static', 'value' => $field, 'id' => $id);
                    }
                    break;
            }
        }

        return $fields;
    }

    /**
     * Get the file areas of the content.
     *
     * Those file areas are typically stored against the guides which
     * were created from this template, but the template content indicates
     * what the names of the fileareas are.
     *
     * @return string[]
     */
    public function get_content_fileareas() {
        $fields = $this->parse();
        return array_reduce($fields, function($carry, $item) {
            if ($item['input'] === 'files') {
                $carry[] = 'filesplaceholder_' . $item['id'];
            } else if ($item['input'] === 'html') {
                $carry[] = 'htmlplaceholder_' . $item['id'];
            }
            return $carry;
        }, []);
    }

    /**
     * Get the default guide contents.
     *
     * @return guide_content[]
     */
    public function get_default_guide_contents() {
        $contents = [];

        $fields = $this->parse();
        foreach ($fields as $field) {
            $record = new stdClass();
            switch ($field['input']) {
                case 'html':
                    $record->placeholderid = $field['value'];
                    $record->placeholdertype = $field['input'];
                    $record->value = !empty($field['default']) ? $field['default'] : '';
                    $record->valueformat = FORMAT_HTML;
                    break;
                case 'files':
                    break;
                case 'link':
                case 'linkname':
                    $record->placeholderid = $field['value'];
                    $record->placeholdertype = $field['input'];
                    $record->value = !empty($field['default']) ? $field['default'] : '';
                    break;
                default:
                    break;
            }

            if (empty($record->placeholderid)) {
                continue;
            }

            $contents[] = new guide_content($record);
        }

        return $contents;
    }

    /**
     * Compute if two templates have the same content.
     *
     * This includes similar placeholders, indexes, values, etc...
     *
     * This does not compare the static values, nor the default values,
     * as they may change without affecting the guides.
     *
     * @param template_base $template1 The first template.
     * @param template_base $template2 The second template.
     * @return bool
     */
    public static function are_template_contents_similar(template_base $template1, template_base $template2) {
        $content1 = $template1->parse();
        $content2 = $template2->parse();

        $fields1 = array_filter($content1, 'block_moderator_guide\\template_base::filter_fields_for_comparison');
        $fields2 = array_filter($content2, 'block_moderator_guide\\template_base::filter_fields_for_comparison');

        $diff1 = array_udiff_assoc($fields1, $fields2, 'block_moderator_guide\\template_base::compare_fields');
        $diff2 = array_udiff_assoc($fields2, $fields1, 'block_moderator_guide\\template_base::compare_fields');

        return empty($diff1) && empty($diff2);
    }

    /**
     * Compare two fields.
     *
     * We ignore the default values as a change their will not have any impact on us.
     *
     * @param array $field1 Portion of data from template_base::parse.
     * @param array $field2 Portion of data from template_base::parse.
     * @return int 0 when identical, else 1.
     */
    public static function compare_fields(array $field1, array $field2) {
        return ($field1['input'] === $field2['input']
            && $field1['value'] === $field2['value']
            && $field1['id'] === $field2['id']) ? 0 : 1;
    }

    /**
     * Callback function to filter out non static fields.
     *
     * @param array $data Portion of data from template_base::parse.
     * @return bool
     */
    public static function filter_fields_for_comparison(array $data) {
        return $data['input'] !== 'static';
    }

}
