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
 * Manager base.
 *
 * @package    block_moderator_guide;
 * @copyright  2016 onwards Coventry University {@link http://www.coventry.ac.uk/}
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_moderator_guide;
defined('MOODLE_INTERNAL') || die();

use coding_exception;
use context_course;
use context_system;
use core_text;
use core_user;
use html_writer;
use moodle_exception;
use moodle_url;
use stdClass;

/**
 * Manager base class.
 *
 * This class has to be extended by any plugin which wants to use the
 * template/guide functionalities. Instances of this class will be
 * the main point of contact with the template/guide API.
 *
 * Plugins must redefine the tables they wish to use for the
 * persistence of their template, guides, and guide contents.
 *
 * @package    block_moderator_guide;
 * @copyright  2016 onwards Coventry University {@link http://www.coventry.ac.uk/}
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class manager_base {

    /**
     * Return the component.
     *
     * @return string
     */
    abstract public function get_component();

    /**
     * Return the template table.
     *
     * @return string
     */
    abstract public function get_templates_table();

    /**
     * Return the guides table.
     *
     * @return string
     */
    abstract public function get_guides_table();

    /**
     * Return the guide contents table.
     *
     * @return string
     */
    abstract public function get_guide_contents_table();

    /**
     * Return the template class to use.
     *
     * @return string
     */
    public function get_template_class() {
        return 'block_moderator_guide\\template_generic';
    }

    /**
     * Return the guide class to use.
     *
     * @return string
     */
    public function get_guide_class() {
        return 'block_moderator_guide\\guide_generic';
    }

    /**
     * Get a template.
     *
     * @param int $id The template ID.
     * @return template_base
     */
    public function get_template($id) {
        global $DB;
        $record = $DB->get_record($this->get_templates_table(), ['id' => $id], '*', MUST_EXIST);
        return $this->make_template_from_record($record);
    }

    /**
     * Get a blank template.
     *
     * @return template_base
     */
    public function get_new_template() {
        return $this->make_template_from_record(null);
    }

    /**
     * Get the templates.
     *
     * @param array $conditions
     * @return template_base[]
     */
    public function list_templates(array $conditions = []) {
        $params = $conditions;
        $sqlfragments = array_map(function($key) {
            return "$key = :$key";
        }, array_keys($conditions));
        $sql = implode(' AND ', $sqlfragments);
        return $this->list_templates_select($sql, $params);
    }

    /**
     * Get the templates from a sql statement.
     *
     * @param $sql
     * @param $params
     * @return template_base[]
     */
    protected function list_templates_select($sql, $params) {
        global $DB;
        $records = $DB->get_records_select($this->get_templates_table(), $sql, $params, 'organization ASC, name ASC');
        return array_map([$this, 'make_template_from_record'], $records);
    }

    /**
     * Get the templates visible to the current user.
     *
     * @return template_base[]
     */
    public function list_visible_templates() {
        global $USER;
        list($vissql, $visparams) = $this->get_visible_templates_sql($USER);
        return $this->list_templates_select($vissql, $visparams);
    }

    /**
     * Return whether the current user has visible templates.
     *
     * The definition of visible is not restricted to a tempalte beind hidden
     * or not, it focuses more on whether there are restrictions for the user
     * to see these templates.
     *
     * @param bool $includehidden When true, hidden templates are counted as visible.
     * @return bool
     */
    public function has_visible_templates($includehidden = false) {
        global $DB, $USER;
        if ($includehidden) {
            list($vissql, $visparams) = $this->get_restriction_profile_sql($USER);
        } else {
            list($vissql, $visparams) = $this->get_visible_templates_sql($USER);
        }
        return $DB->record_exists_select($this->get_templates_table(), $vissql, $visparams);
    }

    /**
     * Save a template
     *
     * @param template_base $template The template
     * @param stdClass $formdata The data from the form to import in the record.
     * @return template_base A new template instance.
     */
    public function save_template(template_base $template, stdClass $formdata = null) {
        global $DB, $USER;

        $tbl = $this->get_templates_table();

        // Ensure that the template exists.
        if (!$template->get_id()) {
            $record = $template->get_record();
            $record->timecreated = time();
            $record->timemodified = $record->timecreated;
            $record->id = $DB->insert_record($tbl, $record);

            // Recreate a template object with newer record.
            $template = $this->make_template_from_record($record);
        }

        // Process the form's data.
        if (!empty($formdata)) {
            $this->process_template_form_data($template, $formdata);
        }

        // Finally, do the real saving.
        $record = $template->get_record();
        $DB->update_record($tbl, $record);

        // Refresh the object and return it.
        return $this->make_template_from_record($record);
    }

    /**
     * Process a template's form data.
     *
     * This updates the template object, while saving files if necessary.
     * Note that you will need to manually save the object after calling this.
     *
     * @param template_base $template The template.
     * @param stdClass $formdata Form data.
     */
    protected function process_template_form_data(template_base $template, stdClass $data) {
        $record = new stdClass();
        $record->name = $data->name;
        $record->description = $data->description;
        $record->organization = $data->organization;
        $record->cancomplete = empty($data->cancomplete) ? 0 : $data->cancomplete;
        $record->canreview = empty($data->canreview) ? 0 : $data->canreview;

        // The editor was submitted.
        if (!empty($data->template_editor)) {
            $context = $this->get_template_context($template);
            $component = $this->get_component();
            $itemid = $template->get_id();
            $editoroptions = $this->get_template_editor_options($template);

            $record->template_editor = $data->template_editor;
            file_postupdate_standard_editor($record, 'template', $editoroptions, $context, $component, 'template', $itemid);
            unset($record->template_editor);
        }

        $template->import_record($record);
    }

    /**
     * Delete a template.
     *
     * @param int $id The template ID.
     * @return void
     */
    public function delete_template($id) {
        global $DB;

        // TODO Make more efficient on the database.
        $guides = $this->list_guides($id);
        foreach ($guides as $guide) {
            $this->delete_guide($guide);
        }

        $DB->delete_records($this->get_templates_table(), ['id' => $id]);
    }

    /**
     * Get the context of a template.
     *
     * This will be used for permissions and file storage.
     *
     * @param template_base $template The template.
     * @return context
     */
    public function get_template_context(template_base $template) {
        return context_system::instance();
    }

    /**
     * Prepare the template editor files.
     *
     * @param template_base $template The template.
     * @param stdClass $saveinobj The object to write to.
     * @return void
     */
    public function prepare_template_editor_files(template_base $template, $saveinobj) {
        $context = $this->get_template_context($template);
        $component = $this->get_component();
        $templateid = $template->get_id() ? $template->get_id() : null;
        $editoroptions = $this->get_template_editor_options($template);

        // Prepare the files, and editor content.
        file_prepare_standard_editor($saveinobj, 'template', $editoroptions, $context,
            $component, 'template', $templateid);
    }

    /**
     * Return the editor options.
     *
     * @param template_base $template The template.
     * @return array
     */
    public function get_template_editor_options(template_base $template) {
        global $CFG, $COURSE;
        $context = $this->get_template_context($template);
        $maxbytes = get_user_max_upload_file_size($context, $CFG->maxbytes, $COURSE->maxbytes);
        return [
            'maxfiles' => EDITOR_UNLIMITED_FILES,
            'maxbytes' => $maxbytes,
            'noclean' => true,
            'return_types' => FILE_INTERNAL | FILE_EXTERNAL,
            'subdirs' => file_area_contains_subdirs($context, $this->get_component(), 'template', $template->get_id()),
            'context' => $context,
        ];
    }

    /**
     * Return the guide editor options.
     *
     * @return array
     */
    public function get_guide_editor_options() {
        global $CFG, $COURSE;
        $context = context_course::instance($COURSE->id);
        $maxbytes = get_user_max_upload_file_size($context, $CFG->maxbytes, $COURSE->maxbytes);
        return [
            'maxfiles' => -1,
            'maxbytes' => $maxbytes,
            'noclean' => true,
            'subdirs' => 1,
            'context' => $context,
        ];
    }

    /**
     * Rewrite a template's content pluginfile URLs.
     *
     * We can pass either a template, or a guide. This indicates the context from
     * which we are trying to view the files. E.g. when viewing a guide we need to
     * get access to a template's files, but not apply the same level of permissions.
     *
     * @param template_base $template The template.
     * @param string $text The text.
     * @return string
     */
    public function rewrite_template_pluginfile_urls($templateorguide, $text) {
        if ($templateorguide instanceof template_base) {
            $context = $this->get_template_context($templateorguide);
        } else {
            $context = $this->get_guide_context($templateorguide);
        }

        $itemid = $templateorguide->get_id();
        return file_rewrite_pluginfile_urls($text, 'pluginfile.php', $context->id, $this->get_component(), 'template', $itemid);
    }

    /**
     * Get a guide.
     *
     * @param int $guideid The guide ID.
     * @return guide_base
     */
    public function get_guide($guideid) {
        global $DB;

        // TODO Make queries more efficient.
        $record = $DB->get_record($this->get_guides_table(), ['id' => $guideid], '*', MUST_EXIST);
        $template = $this->get_template($record->templateid);
        $contents = array_map([$this, 'make_guide_content_from_record'],
            $DB->get_records($this->get_guide_contents_table(), ['guideid' => $record->id]));

        return $this->make_guide_from_record($template, $record, $contents);
    }

    /**
     * Get the guide files in an area.
     *
     * @param guide_base $guide The guide.
     * @param string $filearea The file area.
     * @return stdClass[] containing file => stored_file[], fileurl => string.
     */
    public function get_guide_files_in_area(guide_base $guide, $filearea) {
        $fs = get_file_storage();
        $context = $this->get_guide_context($guide);
        $component = $this->get_component();
        $itemid = $guide->get_id();

        $files = $fs->get_area_files($context->id, $component, $filearea, $itemid);
        return array_map(function($file) {
            return (object) [
                'file' => $file,
                'fileurl' => $file->is_directory() ? null :
                    moodle_url::make_pluginfile_url(
                        $file->get_contextid(),
                        $file->get_component(),
                        $file->get_filearea(),
                        $file->get_itemid(),
                        $file->get_filepath(),
                        $file->get_filename()
                    )
            ];
        }, $files);
    }

    /**
     * Get a blank guide.
     *
     * @param template_base $template The template
     * @return guide_base
     */
    public function get_new_guide(template_base $template) {
        $class = $this->get_guide_class();
        return new $class($template);
    }

    /**
     * Return whether the template has guides.
     *
     * @param int|template_base $templateorid The template, or its ID.
     * @return bool
     */
    public function has_guides($templateorid) {
        global $DB;
        $id = is_object($templateorid) ? $templateorid->get_id() : $templateorid;
        return $DB->record_exists($this->get_guides_table(), ['templateid' => $id]);
    }

    /**
     * Get the guides.
     *
     * @param int|template_base $templateorid The template, or its ID
     * @return guide_base[]
     */
    public function list_guides($templateorid) {
        $id = is_object($templateorid) ? $templateorid->get_id() : $templateorid;
        return $this->list_guides_select('templateid = ?', [$id]);
    }

    /**
     * List the guides from SQL fragments.
     *
     * @param string $sqlfragment The SQL fragment.
     * @param array $params The parameters.
     * @return guide_base[]
     */
    public function list_guides_select($sqlfragment, $params) {
        global $DB;

        $guidetbl = $this->get_guides_table();
        $tpltbl = $this->get_templates_table();
        $order = $this->get_guide_sql_order();

        $sql = "SELECT g.*
                  FROM {{$guidetbl}} g
                  JOIN {{$tpltbl}} t
                    ON t.id = g.templateid
                 WHERE $sqlfragment
              ORDER BY $order";

        // TODO Make queries more efficient. E.g. fetch template in same query.
        $records = $DB->get_records_sql($sql, $params);
        return array_map(function($record) use ($DB) {
            $template = $this->get_template($record->templateid);
            $contents = array_map([$this, 'make_guide_content_from_record'],
                $DB->get_records($this->get_guide_contents_table(), ['guideid' => $record->id]));
            return $this->make_guide_from_record($template, $record, $contents);
        }, $records);
    }

    /**
     * List the guides visible to the current user.
     *
     * @param array $conditions Array of additional conditions.
     * @return guide_base[]
     */
    public function list_visible_guides(array $conditions = []) {
        global $USER;

        $params = $conditions;
        $sqlfragments = array_map(function($key) {
            return "g.$key = :$key";
        }, array_keys($conditions));

        list($orgsql, $orgparams) = $this->get_restriction_profile_sql($USER, 't');
        $sqlfragments[] = $orgsql;
        $params += $orgparams;

        $sql = implode(' AND ', $sqlfragments);
        return $this->list_guides_select($sql, $params);
    }

    /**
     * Get guide SQL order.
     *
     * @return string
     */
    public function get_guide_sql_order() {
        return '';
    }

    /**
     * Prepare the guide's files for form submission.
     *
     * @param guide_base $guide Guide.
     * @param stdClass $saveinobj Object to write the IDs in.
     * @return void
     */
    public function prepare_guide_files(guide_base $guide, stdClass $saveinobj) {
        $component = $this->get_component();
        $context = $this->get_guide_context($guide);
        $itemid = $guide->get_id() ? $guide->get_id() : null;

        $templatefields = $guide->get_template()->parse();
        foreach ($templatefields as $fieldid => $field) {
            $fieldname = 'field_' . $fieldid;
            if ($field['input'] == 'files') {
                $draftitemid = file_get_submitted_draft_itemid($fieldname);
                file_prepare_draft_area($draftitemid, $context->id, $component,'filesplaceholder_' . $fieldid,
                    $itemid, ['subdirs' => 1]);

                $saveinobj->{$fieldname} = $draftitemid;
            } else if ($field['input'] == 'html') {
                // HTML
                $draftitemid = file_get_submitted_draft_itemid($fieldname);
                if (!empty($saveinobj->{$fieldname})) {
                    $saveinobj->{$fieldname}['text'] = file_prepare_draft_area($draftitemid, $context->id, $component, 'htmlplaceholder_'. $fieldid,
                        $itemid, $this->get_guide_editor_options(), $saveinobj->{$fieldname}['text']);
                } else {
                    // case when we add a guide (default text do not contain files)
                    file_prepare_draft_area($draftitemid, $context->id, $component, 'htmlplaceholder_'. $fieldid,
                        $itemid, $this->get_guide_editor_options());
                }

                $saveinobj->{$fieldname}['itemid'] = $draftitemid;
            }
        }
    }

    /**
     * Save a guide.
     *
     * @param guide_base $guide The guide
     * @param stdClass[] $files Information about the files (draftitemid and filearea).
     * @return guide_base A new guide instance.
     */
    public function save_guide(guide_base $guide, array $files = []) {
        global $DB, $USER;

        $tbl = $this->get_guides_table();
        $record = $guide->get_record();
        $record->timemodified = time();

        if (!empty($record->id)) {
            $DB->update_record($tbl, $record);
        } else {
            $record->creatorid = $USER->id;
            $record->timecreated = $record->timemodified;
            $record->id = $DB->insert_record($tbl, $record);
        }

        // Recreate the guide instance with newer record.
        $guide = $this->make_guide_from_record($guide->get_template(), $record, $guide->get_contents());

        // Save the guide's contents.
        $this->save_guide_contents($guide, $files);

        // Save the guide's files.
        $this->save_guide_files($guide, $files);

        return $guide;
    }

    /**
     * Save a guide's contents.
     *
     * @param guide_base $guide The guide.
     * @param object $files the files (to retrieve the htmlplaceholder fileareas)
     * @return void
     */
    protected function save_guide_contents(guide_base $guide, $files) {
        global $DB;
        if (!$guide->get_id()) {
            throw new coding_exception('The guide must be saved first!');
        }

        $table = $this->get_guide_contents_table();
        $contents = $guide->get_contents();
        $finalcontents = [];

        foreach ($contents as $content) {
            $record = $content->get_record();
            $record->guideid = $guide->get_id();
            $record->timemodified = time();

            if( $content->placeholdertype == 'html') {
                foreach($files as $file) {
                    if ($file->placeholderid == $content->placeholderid) {
                        $record->value = file_save_draft_area_files($file->draftitemid,
                            $this->get_guide_context($guide)->id,
                            $this->get_component(), $file->filearea, $guide->get_id(),
                            $this->get_guide_editor_options(), $record->value);
                    }
                }
            }

            if (!$record->id) {
                $record->timecreated = $record->timemodified;
                $record->id = $DB->insert_record($table, $record);
            } else {
                $DB->update_record($table, $record);
            }

            $finalcontents[] = $this->make_guide_content_from_record($record);
        }

        // Replace the contents in the guide.
        $guide->set_contents($finalcontents);

        return $finalcontents;
    }

    /**
     * Save a guide's files.
     *
     * @param guide_base $guide The guide.
     * @param stdClass[] $files The files.
     * @return void
     */
    protected function save_guide_files(guide_base $guide, array $files) {
        if (!$guide->get_id()) {
            throw new coding_exception('The guide must be saved first!');
        }

        $component = $this->get_component();
        $context = $this->get_guide_context($guide);
        $itemid = $guide->get_id();

        foreach ($files as $file) {
            file_save_draft_area_files($file->draftitemid, $context->id, $component, $file->filearea, $itemid, ['subdirs' => 1]);
        }
    }

    /**
     * Delete a guide.
     *
     * @param guide_base|int $guideorid The guide or its ID.
     * @return void
     */
    public function delete_guide($guideorid) {
        global $DB;

        $guide = $guideorid;
        if (!is_object($guide)) {
            $guide = $this->get_guide($guideorid);
        }

        // Delete all file area for this guide.
        $this->delete_guide_files($guide);

        // Delete all guide contents.
        $DB->delete_records($this->get_guide_contents_table(), ['guideid' => $guide->get_id()]);

        // Delete the guide.
        $DB->delete_records($this->get_guides_table(), ['id' => $guide->get_id()]);
    }

    /**
     * Delete guide files.
     *
     * /!\ The default impement deletes all the files throughout
     * the component which have an itemid matching the guide ID.
     *
     * Override this method if you need to be more selective when deleting files.
     *
     * @param guide_base $guide The guide.
     * @return void
     */
    protected function delete_guide_files(guide_base $guide) {
        // Check that the guide is legit.
        $guideid = $guide->get_id();
        if (empty($guideid)) {
            return;
        }

        $context = $this->get_guide_context($guide);
        $component = $this->get_component();

        // TODO Limit deletion to areas which are used by the guide.
        $fs = get_file_storage();
        $fs->delete_area_files($context->id, $component, false, $guideid);
    }

    /**
     * Get the context of a guide.
     *
     * This will be used for permissions and file storage.
     *
     * @param guide_base $guide The guide.
     * @return context
     */
    abstract public function get_guide_context(guide_base $guide);

    /**
     * Return the HTML of the files from the file manager placeholder.
     *
     * @param $files from get_guide_files_in_area()
     * @return string
     */
    public function render_files($files) {
        global $OUTPUT;

        $filehtml = '';
        foreach ($files as $fileinfo) {

            $file = $fileinfo->file;

            // calculate indentation.
            $indentation = substr_count($file->get_filepath(), '/') - 1; // root is '/'
            $indentationhtml = '';
            if (!empty($indentation)) {
                for ($i = 1; $i <= $indentation; $i++) {
                    $indentationhtml .= '&nbsp;&nbsp;&nbsp;&nbsp;';
                }
            }

            if ($fileinfo->fileurl) {
                $iconimage = $OUTPUT->pix_icon(file_file_icon($file, 16), $file->get_filename(), 'moodle');
                $filehtml .= html_writer::link($fileinfo->fileurl,
                        $indentationhtml . $iconimage . '&nbsp;' . $file->get_filename()) . '<br/>';
            } else {
                $foldernames = explode('/', $file->get_filepath());
                $foldername = $foldernames[$indentation];
                if ($file->get_filename() == '.' and !empty($foldername)) {

                    $iconimage = $OUTPUT->pix_icon('f/folder', $file->get_filename(), 'moodle');

                    $filehtml .= html_writer::tag('span',
                        $indentationhtml . $iconimage . '&nbsp;' . $foldername . ':',
                        array('class' => 'block_moderator_guide_foldername'));
                }
            }
        }
        return $filehtml;
    }

    /**
     * Render a guide.
     *
     * @param guide_base $guide The guide
     * @return string HTML.
     */
    public function render_guide(guide_base $guide) {
        $o = '';

        $template = $guide->get_template();
        $templatefields = $template->parse();
        $links = array();

        foreach ($templatefields as $fieldid => $field) {
            switch ($field['input']) {
                case 'html':
                    $html = $guide->get_content($field['value']);
                    $guidecontext = $this->get_guide_context($guide);
                    $itemid = $guide->get_id();

                    $html->value = file_rewrite_pluginfile_urls($html->value, 'pluginfile.php',
                        $guidecontext->id, $this->get_component(),
                        'htmlplaceholder_' . $fieldid, $itemid);

                    $o .= html_writer::span($html->value);
                    break;

                case 'files':
                    $files = $this->get_guide_files_in_area($guide, 'filesplaceholder_' . $fieldid);
                    $o .= $this->render_files($files);
                    break;

                case 'link':
                    // Store the link, the link html will be created once we match linkname.
                    $link = $guide->get_content($field['value']);
                    $links[$link->placeholderid . '_linkname'] = $link->value; // Store the url.
                    break;

                case 'linkname':
                    $linkname = $guide->get_content($field['value']);
                    $o .= html_writer::link(new moodle_url($links[$linkname->placeholderid]), $linkname->value);
                    break;

                case 'static':
                    $field['value'] = $this->rewrite_template_pluginfile_urls($template, $field['value']);
                    $o .= html_writer::span($field['value']);
                    break;

                default:
                    throw new coding_exception('template placeholder type unkown');
                    break;
            }
        }

        return $o;
    }

    /**
     * Return the total number of empty fields and not empty fields.
     *
     * @param guide_base $guide
     * @return array
     * @throws coding_exception
     */
    public function get_total_empty_fields(guide_base $guide) {
        $fields = array('empty' => 0, 'notempty' => 0);

        $template = $guide->get_template();
        $templatefields = $template->parse();

        foreach ($templatefields as $fieldid => $field) {
            switch ($field['input']) {
                case 'html':
                    if (empty($guide->get_content($field['value']))) {
                        $fields['empty']++;
                    } else {
                        $fields['notempty']++;
                    }

                    break;

                case 'files':
                    $files = $this->get_guide_files_in_area($guide, 'filesplaceholder_' . $fieldid);
                    $empty = true;
                    foreach ($files as $fileinfo) {
                        $file = $fileinfo->file;
                        if ($fileinfo->fileurl) {
                            $empty = false;
                        }
                    }
                    if ($empty) {
                        $fields['empty']++;
                    } else {
                        $fields['notempty']++;
                    }
                    break;

                case 'link':
                    if (empty($guide->get_content($field['value']))) {
                        $fields['empty']++;
                    } else {
                        $fields['notempty']++;
                    }

                    break;

                case 'linkname':
                    if (empty($guide->get_content($field['value']))) {
                        $fields['empty']++;
                    } else {
                        $fields['notempty']++;
                    }

                    break;

                case 'static':

                    break;

                default:
                    throw new coding_exception('template placeholder type unkown');
                    break;
            }
        }

        return $fields;
    }

    /**
     * Get the profile field to restrict templates on.
     *
     * @return null|string
     */
    protected function get_restriction_profile_field() {
        return null;
    }

    /**
     * Get the profile restriction restriction SQL statement.
     *
     * @param $user
     * @param string $tablealias
     * @return array With SQL statement and params.
     */
    protected function get_restriction_profile_sql($user, $tablealias = '') {
        global $DB;

        $sql = '1=1';
        $params = [];

        $field = $this->get_restriction_profile_field();
        if ($field === null || is_siteadmin($user)) {
            // There are no restriction fields, or the user is an admin.
            return [$sql, $params];
        }

        $this->load_user_profile_fields($user);
        if (empty($user->profile[$field])) {
            // The user has not filled in their profile field, no restriction then.
            return [$sql, $params];
        }

        $fieldname = $tablealias ? $tablealias . '.' . 'organization' : 'organization';
        $sqllike = $DB->sql_like($fieldname, ':userorg', false, false);

        // When the organization is empty, or null, or matches the user can see it.
        $sql = "($fieldname = :nothing OR $fieldname IS NULL OR $sqllike)";
        $params = ['nothing' => '', 'userorg' => $user->profile[$field]];
        return [$sql, $params];
    }

    /**
     * Whether a user can access a template.
     *
     * Typically this re-implements the same logic as {self::get_restriction_profile_sql}.
     *
     * Do not add logic about visibility here.
     *
     * @param template_base $template The template.
     * @param null $user
     * @return bool
     */
    public function can_access_template(template_base $template, $user = null) {
        global $USER;
        $user = $user === null ? $USER : $user;

        $field = $this->get_restriction_profile_field();
        if ($field === null || is_siteadmin($user) || !$template->has_organization()) {
            // There are no restriction fields, the user is an admin, or the template has no org.
            return true;
        }

        $this->load_user_profile_fields($user);
        if (empty($user->profile[$field])) {
            // The user has not filled in their profile field, no restriction then.
            return true;
        }

        return core_text::strtolower($user->profile[$field]) === core_text::strtolower($template->get_organization());
    }

    /**
     * Require the template to be accessible.
     *
     * @param template_base $template The template.
     * @return void
     */
    public function require_can_access_template(template_base $template) {
        if (!$this->can_access_template($template)) {
            throw new moodle_exception('cannotmanagetemplate');
        }
    }

    /**
     * Whether the user can access the guide.
     *
     * Do not add logic about visibility here.
     *
     * @param guide_base $guide The guide.
     * @return bool
     */
    public function can_access_guide(guide_base $guide) {
        return $this->can_access_template($guide->get_template());
    }

    /**
     * Require the guide to be accessible.
     *
     * @param guide_base $guide The guide.
     * @return void
     */
    public function require_can_access_guide(guide_base $guide) {
        if (!$this->can_access_guide($guide)) {
            throw new moodle_exception('cannotmanageguide');
        }
    }

    /**
     * Get the SQL fragment for the user's visible templates.
     *
     * @param stdClass $user The user.
     * @param string $tablealias
     * @return array With SQL and params.
     */
    protected function get_visible_templates_sql($user, $tablealias = '') {
        list($orgsql, $orgparams) = $this->get_restriction_profile_sql($user, $tablealias);
        return ["hidden = :hidden AND $orgsql", array_merge($orgparams, ['hidden' => 0])];
    }

    /**
     * Ensures that the profile fields are loaded.
     *
     * @param stdClass $user The user object.
     * @return void
     */
    protected function load_user_profile_fields($user) {
        global $CFG, $DB;
        if (isset($user->profile)) {
            return;
        }
        require_once($CFG->dirroot . '/user/profile/lib.php');
        profile_load_data($user);
    }

    /**
     * Make a template object from a DB record.
     *
     * @param stdClass|null $record The db record.
     * @return template_base
     */
    protected function make_template_from_record($record) {
        $class = $this->get_template_class();
        return new $class($record);
    }

    /**
     * Make a guide object from a DB record.
     *
     * @param tempalte_base $template The template.
     * @param stdClass $record The db record.
     * @param guide_content $contents The contents.
     * @return guide_base
     */
    protected function make_guide_from_record(template_base $template, $record, array $contents) {
        $class = $this->get_guide_class();
        return new $class($template, $record, $contents);
    }

    /**
     * Make a guide contents object from a DB record.
     *
     * @param stdClass $record The db record.
     * @return guide_content
     */
    protected function make_guide_content_from_record($record) {
        return new guide_content($record);
    }

    /**
     * Function to serve pluginfiles.
     *
     * This should be called from within the plugin's pluginfile function.
     *
     * @param $course
     * @param $cm
     * @param $context
     * @param $filearea
     * @param $args
     * @param $forcedownload
     * @param array $options
     * @param array $permissions
     * @return bool
     */
    public function serve_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload,
                                     array $options = array(), array $permissions = array()) {

        // Make sure the filearea starts with filesplaceholder_.
        if ((strpos($filearea, 'filesplaceholder_') !== 0)
            && (strpos($filearea, 'htmlplaceholder_') !== 0)
            && ($filearea !== 'template')) {
            return false;
        }

        // The item ID of the resource we're interested in.
        $itemid = array_shift($args);

        // Set default permissions.
        if (empty($permissions['system'])) {
            $permissions['system'] = 'block/moderator_guide:viewguide';
        }
        if (empty($permissions['context'])) {
            $permissions['context'] = 'block/moderator_guide:viewguide';
        }

        // Checking permissions to access this.
        if ($context instanceof context_system) {

            // We are viewing the template, or creating a new guide.
            $template = $this->get_template($itemid);
            $context = $this->get_template_context($template);

            // Legacy code. We just need to be able to view the guide in the front page
            // to access the files stored in the template.
            require_capability($permissions['system'], context_course::instance(SITEID));

        } else {

            // We are viewing the guide.
            $guide = $this->get_guide($itemid);
            $context = $this->get_guide_context($guide);
            $this->require_can_access_guide($guide);
            require_capability($permissions['context'], $context);

            // The template files, viewed from within the guide, must have the template's context and ID.
            if ($filearea === 'template') {
                $template = $guide->get_template();
                $context = $this->get_template_context($template);
                $itemid = $template->get_id();
            }
        }

        // Extract the filename / filepath from the $args array.
        $filename = array_pop($args); // The last item in the $args array.
        if (!$args) {
            $filepath = '/'; // When $args is empty => the path is '/'.
        } else {
            $filepath = '/' . implode('/', $args) . '/'; // Here $args contains elements of the filepath.
        }

        // Retrieve the file from the Files API.
        $fs = get_file_storage();
        $file = $fs->get_file($context->id, $this->get_component(), $filearea, $itemid, $filepath, $filename);
        if (!$file) {
            return false; // The file does not exist.
        }

        // Send the file.
        send_stored_file($file, null, 0, $forcedownload, $options);
    }

    /**
     * Update the moderation fields of a guide.
     * @param $data
     * @param bool $editcompleteinfo
     * @param bool $editreviewinfo
     */
    public function update_guide_moderation_fields($data, $editcompleteinfo = true, $editreviewinfo = true) {
        global $DB, $USER;

        $guide = new stdClass();
        $guide->id = $data->id;

        if ($editcompleteinfo) {
            if (empty($data->completed)) {
                $guide->completed = 0;
                $guide->completedtime = 0;
                $guide->completeduserid = 0;
            } else {
                // Do not override completion information if the guide is already marked as completed.
                $guidealreadycompleted = $DB->get_field($this->get_guides_table(),
                    'completed', array('id' => $guide->id));
                if (empty($guidealreadycompleted)) {
                    $guide->completed = $data->completed;
                    $guide->completedtime = time();
                    $guide->completeduserid = $USER->id;
                } else if (empty($editreviewinfo)) {
                    // As we are not overriding the completion information,
                    // if ever we also do not update the review information, then exit the function.
                    return "";
                }
            }
        }

        if ($editreviewinfo) {
            if (empty($data->reviewed)) {
                $guide->reviewed = 0;
            } else {
                $guide->reviewed = $data->reviewed;
            }

            if (empty($data->reviewed) && empty($data->reviewcomment)) {
                $guide->reviewedtime = 0;
                $guide->revieweduserid = 0;
            } else {
                $guide->reviewedtime = time();
                $guide->revieweduserid = $USER->id;
            }

            $guide->reviewcomment = $data->reviewcomment;
        }

        $DB->update_record($this->get_guides_table(), $guide);
    }

    public function hanlde_and_render_moderation_form($guideobj, $guide, $guideid, $cmid = false) {
        global $PAGE, $OUTPUT, $CFG;

        $action = optional_param('action', 'save', PARAM_ALPHA);

        // Display moderation block.
        $template = $guideobj->get_template();
        $formurl = new moodle_url($PAGE->url, ['guideid' => $guideid, 'action' => $action, 'cmid' => $cmid]);
        $moderationform = new \block_moderator_guide\form\moderation($formurl->out(false),
            ['data' => ['completed' => $guide->completed,
                'reviewed' => $guide->reviewed,
                'reviewcomment' => $guide->reviewcomment,
                'id' => $guideid],
                'template' => $template,
                'manager' => $this]);
        $cancomplete = has_capability('block/moderator_guide:cancomplete', $PAGE->context);
        $canreview = has_capability('block/moderator_guide:canreview', $PAGE->context);
        $canviewreview = has_capability('block/moderator_guide:canviewreview', $PAGE->context);
        $canviewcomplete = has_capability('block/moderator_guide:canviewcomplete', $PAGE->context);

        // Save the moderation fields after user press Saves changes in the moderation section.
        $data = $moderationform->get_data();
        if ($data && $action === 'save') {
            require_sesskey();

            // Some checks.
            if(!$cancomplete && !empty($data->completed)) {
                throw new moodle_exception('notallowedtocomplete');
            }
            if(!$canreview && (!empty($data->reviewed) || !empty($data->reviewcomment))) {
                throw new moodle_exception('notallowedtoreview');
            }

            $this->update_guide_moderation_fields($data, $cancomplete, $canreview);
        }

        if(!empty(get_config('block_moderator_guide', 'moderation'))
            && ($cancomplete ||
                $canreview ||
                ($canviewcomplete &&
                    !empty($guideobj->get_completed())) ||
                ($canviewreview &&
                    (!empty($guideobj->get_reviewed() || !empty($guideobj->get_reviewcomment())))))
            && ($template->get_cancomplete() || $template->get_canreview())) {

            echo $OUTPUT->box_start('generalbox toolbox alert alert-info', 'moderationbox');

            echo $OUTPUT->heading(get_string('moderation', 'block_moderator_guide'));

            if ($cancomplete || $canreview) {
                $moderationform->display();
            }

            if(!$cancomplete && !empty($guideobj->get_completed()) && $canviewcomplete) {
                $completedauthor = core_user::get_user($guideobj->get_completeduserid());

                echo '<strong>'
                    . get_string('completed', 'block_moderator_guide')
                    . ' ( ' . fullname($completedauthor) . ' - ' . userdate($guideobj->get_completedtime()) . ' )'
                    . '</strong><br/><br/>';
            }

            if(!$canreview && $canviewreview) {
                $reviewauthor = core_user::get_user($guideobj->get_revieweduserid());

                if (!empty($guideobj->get_reviewed())) {
                    echo '<strong>'
                        . get_string('reviewed', 'block_moderator_guide')
                        . ' ( ' . fullname($reviewauthor) . ' - ' . userdate($guideobj->get_reviewedtime()) . ' )'
                        . '</strong><br/><br/>';
                }

                if (!empty($guideobj->get_reviewcomment())) {
                    echo '<strong>'
                        . get_string('reviewcomment', 'block_moderator_guide')
                        . ' ( ' . fullname($reviewauthor) . ' - ' . userdate($guideobj->get_reviewedtime()) . ' )'
                        . '</strong>'
                        . '<br/>'
                        . $guideobj->get_reviewcomment();
                }
            }

            echo $OUTPUT->box_end();
        }
    }
}
