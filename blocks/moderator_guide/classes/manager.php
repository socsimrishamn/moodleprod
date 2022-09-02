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
 * Manager.
 *
 * @package    block_moderator_guide;
 * @copyright  2016 onwards Coventry University {@link http://www.coventry.ac.uk/}
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_moderator_guide;
defined('MOODLE_INTERNAL') || die();

use context_course;
use stdClass;

/**
 * Manager class.
 *
 * The manager for the block.
 *
 * @package    block_moderator_guide;
 * @copyright  2016 onwards Coventry University {@link http://www.coventry.ac.uk/}
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class manager extends manager_base {

    /**
     * Return the component.
     *
     * @return string
     */
    public function get_component() {
        return 'block_moderator_guide';
    }

    /**
     * Return the template table.
     *
     * @return string
     */
    public function get_templates_table() {
        return 'block_mdrtr_guide_templates';
    }

    /**
     * Return the guides table.
     *
     * @return string
     */
    public function get_guides_table() {
        return 'block_mdrtr_guide_guides';
    }

    /**
     * Return the guide contents table.
     *
     * @return string
     */
    public function get_guide_contents_table() {
        return 'block_mdrtr_guide_contents';
    }

    /**
     * Return the template class to use.
     *
     * @return string
     */
    public function get_template_class() {
        return 'block_moderator_guide\\template';
    }

    /**
     * Return the guide class to use.
     *
     * @return string
     */
    public function get_guide_class() {
        return 'block_moderator_guide\\guide';
    }

    /**
     * Get guide SQL order.
     *
     * @return string
     */
    public function get_guide_sql_order() {
        return 'name';
    }

    /**
     * Get the context of a guide.
     *
     * @param guide_base $guide The guide.
     * @return context
     */
    public function get_guide_context(guide_base $guide) {
        return context_course::instance($guide->get_courseid());
    }

    /**
     * Get the profile field to restrict templates on.
     *
     * @return null|string
     */
    protected function get_restriction_profile_field() {
        $restrictionfield = get_config('block_moderator_guide', 'restriction');
        return !empty($restrictionfield) ? $restrictionfield : null;
    }

    /**
     * Process a template's form data.
     *
     * @param template_base $template The template.
     * @param stdClass $formdata Form data.
     */
    protected function process_template_form_data(template_base $template, stdClass $data) {
        parent::process_template_form_data($template, $data);
        $template->import_record((object) ['defaultguidename' => $data->defaultguidename]);
    }

    /**
     * Return the guides sorted by courses. It can be filtered by organisation.
     * Each courses are returned with the total number of incomplete and complete guides.
     *
     * This function may be inefficient of a very large number of guides/courses.
     * If it is the cache then only call once a day per cron and cache the rest of the result.
     *
     * @param string $courseid
     * @param string $courseshortname
     * @param string $organisation
     * @param integer $limitfrom
     * @param integer $limitnum
     * @return array of courses (id,
     *                           name,
     *                           shortname,
     *                           array of guides (id, name, hidden, organisation, totalincompletefields)
     *                           total incomplete guides
     *                           total complete guides
     *                           total guides)
     */
    public function get_guides_courses($courseid = '', $courseshortname = '', $organisation = '',
                                       $limitfrom = 0, $limitnum = 0) {
        global $DB;

        $sqlparams = array();

        // We are searching the entire Moodle site and filtering per organisation.
        $sql = 'SELECT guide.id, guide.name, guide.hidden, guide.templateid, guide.courseid, guide.completed, guide.reviewed 
                FROM {block_mdrtr_guide_guides} guide';

        // Retrieve the course if courseid not empty.
        $sqlwhere = '';
        if (!empty($courseid)) {
            $sqlwhere = ' WHERE courseid = :courseid';
            $sqlparams['courseid'] = $courseid;
        }

        // Limit to some course shortname.
        if (!empty($courseshortname)) {
            // Find all courses with this shortname.
            $courses = $DB->get_records_select('course', $DB->sql_like('shortname', '\''.$courseshortname.'\''));

            if (!empty($courses)) {
                $sqlwhere .= empty($sqlwhere) ? 'WHERE ' : ' AND ';
                $courseids = array();
                foreach ($courses as $course) {
                    $courseids[] = $course->id;
                }
                list ($incond, $inparams) = $DB->get_in_or_equal($courseids, SQL_PARAMS_NAMED, 'courseids');
                $sqlparams = array_merge($sqlparams, $inparams);
                $sqlwhere .= ' courseid ' .$incond;
            }
        }

        if (!empty($organisation)) {
            $sql .= ' JOIN {block_mdrtr_guide_templates} template ON template.id = guide.templateid';
            $sqlwhere .= empty($sqlwhere) ? 'WHERE ' : ' AND ';
            $sqlwhere .= ' template.organization = :organisation';
            $sqlparams['organisation'] = $organisation;
        }

        $guides = $DB->get_records_sql($sql . $sqlwhere, $sqlparams, $limitfrom, $limitnum);

        $courses = array();
        foreach ($guides as $guide) {

            if (!isset($courses[$guide->courseid])) {

                // Initialize the course data.
                $courses[$guide->courseid] = array();
                $courses[$guide->courseid]['guides'] = array();
                $courses[$guide->courseid]['totalincomplete'] = 0;
                $courses[$guide->courseid]['totalcomplete'] = 0;
                $courses[$guide->courseid]['totalmarkascompleted'] = 0;
                $courses[$guide->courseid]['totalmarkasreviewed'] = 0;
                $courses[$guide->courseid]['totalguides'] = 0;

                // Set course info.
                $course = $DB->get_record('course', array('id' => $guide->courseid));
                $courses[$guide->courseid]['id'] = $course->id;
                $courses[$guide->courseid]['name'] = $course->fullname;
                $courses[$guide->courseid]['shortname'] = $course->shortname;
            }

            // Find out the empty and not empty fields.
            $guideobj = $this->get_guide($guide->id);
            $emptyfields = $this->get_total_empty_fields($guideobj);
            $guide->totalincompletefields = $emptyfields['empty'];
            $guide->totalcompletefields = $emptyfields['notempty'];

            // Add the guide to the course guide list.
            $courses[$guide->courseid]['guides'][] = $guide;

            // Increment the statistics.
            if (!empty($guide->totalincompletefields)) {
                $courses[$guide->courseid]['totalincomplete']++;
            } else {
                $courses[$guide->courseid]['totalcomplete']++;
            }
            if (!empty($guide->completed)) {
                $courses[$guide->courseid]['totalmarkascompleted']++;
            }
            if (!empty($guide->reviewed)) {
                $courses[$guide->courseid]['totalmarkasreviewed']++;
            }
            $courses[$guide->courseid]['totalguides']++;
        }

        return $courses;
    }
}

