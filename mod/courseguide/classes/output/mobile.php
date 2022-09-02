<?php
// This file is part of the Course guide module for Moodle - http://moodle.org/
//
// It is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// It is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Mobile output class for course guide
 *
 * @package    mod_courseguide
 * @copyright 2018 Coventry University
 * @author Jerome Mouneyrac <jerome@bepaw.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_courseguide\output;

defined('MOODLE_INTERNAL') || die();

use context_module;
// use mod_courseguide_external;

/**
 * Mobile output class for course guide
 *
 * @package    mod_courseguide
 * @copyright 2018 Coventry University
 * @author Jerome Mouneyrac <jerome@bepaw.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mobile {

    /**
     * Returns the course view for the mobile app.
     * @param  array $args Arguments from tool_mobile_get_content WS
     *
     * @return array       HTML, javascript and otherdata
     */
    public static function mobile_course_view($args) {
        global $OUTPUT, $USER, $DB;

        $args = (object) $args;
        $cm = get_coursemodule_from_id('courseguide', $args->cmid);

        // Capabilities check.
        require_login($args->courseid , false , $cm, true, true);

        $context = context_module::instance($cm->id);

        require_capability ('mod/courseguide:viewguide', $context);

        $courseguide = $DB->get_record('courseguide', array('id' => $cm->instance));

        $manager = new \mod_courseguide\manager();
        $courseguide  = $DB->get_record('courseguide', array('id' => $cm->instance), '*', MUST_EXIST);
        $guide = $manager->get_guide_by_instanceid($courseguide->id); // guideid and cmid are the same.

        $htmlelements = array();
        $template = $guide->get_template();
        $templatefields = $template->parse();
        $links = array();
        $allfiles = array();
        foreach ($templatefields as $fieldid => $field) {
            switch ($field['input']) {
                case 'html':
                    $html = $guide->get_content($field['value']);
                    $guidecontext = \context_course::instance($guide->get_courseid());
                    $itemid = $guide->get_id();

                    list($html->value, $html->format) =     external_format_text($html->value, FORMAT_MOODLE,
                        $guidecontext->id, 'mod_courseguide',
                        'htmlplaceholder_' . $fieldid, $itemid);
                    $htmlelements[] = array('value' => $html->value, 'text' => true);
                    break;

                case 'files':
                    $files = $manager->get_guide_files_in_area($guide, 'filesplaceholder_' . $fieldid);

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
                            $filehtml = array('value' => '',
                                'files' => true, 'size' => $file->get_filesize(), 'url' => $fileinfo->fileurl,
                                'name' => $file->get_filename(), 'timemodified' => $file->get_timemodified() );
                            $htmlelements[] = $filehtml;
                            $allfiles[] = (object) array('mimetype' => $file->get_mimetype(), 'size' => $file->get_filesize(), 'fileurl' => $fileinfo->fileurl->out(),
                                'filename' => $file->get_filename(), 'timemodified' => $file->get_timemodified(), 'timecreated' => $file->get_timecreated() );
                        } else {
                            $foldernames = explode('/', $file->get_filepath());
                            $foldername = $foldernames[$indentation];
                            if ($file->get_filename() == '.' and !empty($foldername)) {

                                $iconimage = $OUTPUT->pix_icon('f/folder', $file->get_filename(), 'moodle');

                                $htmlelements[] = array('value' => $iconimage . '&nbsp;' . $foldername . ':', 'text' => true);
                            }
                        }
                    }

                    break;

                case 'link':
                    // Store the link, the link html will be created once we match linkname.
                    $link = $guide->get_content($field['value']);
                    $links[$link->placeholderid . '_linkname'] = $link->value; // Store the url.
                    break;

                case 'linkname':
                    $linkname = $guide->get_content($field['value']);
                    $link = \html_writer::link(new \moodle_url($links[$linkname->placeholderid]), $linkname->value);
                    $htmlelements[] = array('value' => $link, 'text' => true);
                    break;

                case 'static':
                    if ($template instanceof template_base) {
                         $context = \context_system::instance();
                    } else {
                        $context = \context_course::instance($guide->get_courseid());
                    }

                    $itemid = $template->get_id();
                    $html = new \stdClass();
                    list($html->value, $html->format) = external_format_text($field['value'], FORMAT_MOODLE,
                        $context->id, 'mod_courseguide', 'template', $itemid);
                    $htmlelements[] = array('value' => $html->value, 'text' => true);
                    break;

                default:
                    throw new coding_exception('template placeholder type unkown');
                    break;
            }
        }

        $data = array(
            'htmlelements' => $htmlelements,
            'cmid' => $cm->id,
        );

        return array(
            'templates' => array(
                array(
                    'id' => 'main',
                    'html' => $OUTPUT->render_from_template('mod_courseguide/mobile_view_page', $data),
                ),
            ),
            'javascript' => '',
            'otherdata' => '',
            'files' => $allfiles
        );
    }
}