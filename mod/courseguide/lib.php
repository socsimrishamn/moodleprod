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
 * Library of interface functions and constants for module courseguide
 *
 * All the core Moodle functions, neeeded to allow the module to work
 * integrated in Moodle should be placed here.
 *
 * All the courseguide specific functions, needed to implement all the module
 * logic, should go to locallib.php. This will help to save some memory when
 * Moodle is performing actions across all modules.
 *
 * @package    mod_courseguide
 * @copyright  2017 onwards Coventry University {@link http://www.coventry.ac.uk/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author  Jerome Mouneyrac <jerome@bepaw.com>
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Example constant, you probably want to remove this :-)
 */
define('COURSEGUIDE_ULTIMATE_ANSWER', 42);

/* Moodle core API */

/**
 * Returns the information on whether the module supports a feature
 *
 * See {@link plugin_supports()} for more info.
 *
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed true if the feature is supported, null if unknown
 */
function courseguide_supports($feature) {

    switch($feature) {
        case FEATURE_MOD_INTRO:
            return true;
        case FEATURE_SHOW_DESCRIPTION:
            return true;
        case FEATURE_GRADE_HAS_GRADE:
            return true;
        case FEATURE_BACKUP_MOODLE2:
            return true;
        default:
            return null;
    }
}

/**
 * Saves a new instance of the courseguide into the database
 *
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will create a new instance and return the id number
 * of the new instance.
 *
 * @param stdClass $courseguide Submitted data from the form in mod_form.php
 * @param mod_courseguide_mod_form $mform The form instance itself (if needed)
 * @return int The id of the newly inserted courseguide record
 */
function courseguide_add_instance(stdClass $courseguide, mod_courseguide_mod_form $mform = null) {
    global $DB, $USER;

    $courseguide->timecreated = time();

    $courseguide->id = $DB->insert_record('courseguide', $courseguide);

    courseguide_grade_item_update($courseguide);

    return $courseguide->id;
}

/**
 * Updates an instance of the courseguide in the database
 *
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will update an existing instance with new data.
 *
 * @param stdClass $courseguide An object from the form in mod_form.php
 * @param mod_courseguide_mod_form $mform The form instance itself (if needed)
 * @return boolean Success/Fail
 */
function courseguide_update_instance(stdClass $courseguide, mod_courseguide_mod_form $mform = null) {
    global $DB;

    $courseguide->timemodified = time();
    $courseguide->id = $courseguide->instance;

    // You may have to add extra stuff in here.

    $result = $DB->update_record('courseguide', $courseguide);

    courseguide_grade_item_update($courseguide);

    return $result;
}

/**
 * This standard function will check all instances of this module
 * and make sure there are up-to-date events created for each of them.
 * If courseid = 0, then every courseguide event in the site is checked, else
 * only courseguide events belonging to the course specified are checked.
 * This is only required if the module is generating calendar events.
 *
 * @param int $courseid Course ID
 * @return bool
 */
function courseguide_refresh_events($courseid = 0) {
    global $DB;

    if ($courseid == 0) {
        if (!$courseguides = $DB->get_records('courseguide')) {
            return true;
        }
    } else {
        if (!$courseguides = $DB->get_records('courseguide', array('course' => $courseid))) {
            return true;
        }
    }

    foreach ($courseguides as $courseguide) {
        // Create a function such as the one below to deal with updating calendar events.
        // courseguide_update_events($courseguide);
    }

    return true;
}

/**
 * Removes an instance of the courseguide from the database
 *
 * Given an ID of an instance of this module,
 * this function will permanently delete the instance
 * and any data that depends on it.
 *
 * @param int $id Id of the module instance
 * @return boolean Success/Failure
 */
function courseguide_delete_instance($id) {
    global $DB;

    if (! $courseguide = $DB->get_record('courseguide', array('id' => $id))) {
        return false;
    }

    // Delete any dependent records here.
    $manager = new \mod_courseguide\manager();
    $guide = $manager->get_guide_by_instanceid($courseguide->id);
    if (!empty($guide)) {
        $manager->delete_guide($guide);
    }

    $DB->delete_records('courseguide', array('id' => $courseguide->id));

    courseguide_grade_item_delete($courseguide);

    return true;
}

/**
 * Returns a small object with summary information about what a
 * user has done with a given particular instance of this module
 * Used for user activity reports.
 *
 * $return->time = the time they did it
 * $return->info = a short text description
 *
 * @param stdClass $course The course record
 * @param stdClass $user The user record
 * @param cm_info|stdClass $mod The course module info object or record
 * @param stdClass $courseguide The courseguide instance record
 * @return stdClass|null
 */
function courseguide_user_outline($course, $user, $mod, $courseguide) {

    $return = new stdClass();
    $return->time = 0;
    $return->info = '';
    return $return;
}

/**
 * Prints a detailed representation of what a user has done with
 * a given particular instance of this module, for user activity reports.
 *
 * It is supposed to echo directly without returning a value.
 *
 * @param stdClass $course the current course record
 * @param stdClass $user the record of the user we are generating report for
 * @param cm_info $mod course module info
 * @param stdClass $courseguide the module instance record
 */
function courseguide_user_complete($course, $user, $mod, $courseguide) {
}

/**
 * Given a course and a time, this module should find recent activity
 * that has occurred in courseguide activities and print it out.
 *
 * @param stdClass $course The course record
 * @param bool $viewfullnames Should we display full names
 * @param int $timestart Print activity since this timestamp
 * @return boolean True if anything was printed, otherwise false
 */
function courseguide_print_recent_activity($course, $viewfullnames, $timestart) {
    return false;
}

/**
 * Prepares the recent activity data
 *
 * This callback function is supposed to populate the passed array with
 * custom activity records. These records are then rendered into HTML via
 * {@link courseguide_print_recent_mod_activity()}.
 *
 * Returns void, it adds items into $activities and increases $index.
 *
 * @param array $activities sequentially indexed array of objects with added 'cmid' property
 * @param int $index the index in the $activities to use for the next record
 * @param int $timestart append activity since this time
 * @param int $courseid the id of the course we produce the report for
 * @param int $cmid course module id
 * @param int $userid check for a particular user's activity only, defaults to 0 (all users)
 * @param int $groupid check for a particular group's activity only, defaults to 0 (all groups)
 */
function courseguide_get_recent_mod_activity(&$activities, &$index, $timestart, $courseid, $cmid, $userid=0, $groupid=0) {
}

/**
 * Prints single activity item prepared by {@link courseguide_get_recent_mod_activity()}
 *
 * @param stdClass $activity activity record with added 'cmid' property
 * @param int $courseid the id of the course we produce the report for
 * @param bool $detail print detailed report
 * @param array $modnames as returned by {@link get_module_types_names()}
 * @param bool $viewfullnames display users' full names
 */
function courseguide_print_recent_mod_activity($activity, $courseid, $detail, $modnames, $viewfullnames) {
}

/**
 * Function to be run periodically according to the moodle cron
 *
 * This function searches for things that need to be done, such
 * as sending out mail, toggling flags etc ...
 *
 * Note that this has been deprecated in favour of scheduled task API.
 *
 * @return boolean
 */
function courseguide_cron () {
    return true;
}

/**
 * Returns all other caps used in the module
 *
 * For example, this could be array('moodle/site:accessallgroups') if the
 * module uses that capability.
 *
 * @return array
 */
function courseguide_get_extra_capabilities() {
    return array();
}

/* Gradebook API */

/**
 * Is a given scale used by the instance of courseguide?
 *
 * This function returns if a scale is being used by one courseguide
 * if it has support for grading and scales.
 *
 * @param int $courseguideid ID of an instance of this module
 * @param int $scaleid ID of the scale
 * @return bool true if the scale is used by the given courseguide instance
 */
function courseguide_scale_used($courseguideid, $scaleid) {
    global $DB;

    if ($scaleid and $DB->record_exists('courseguide', array('id' => $courseguideid, 'grade' => -$scaleid))) {
        return true;
    } else {
        return false;
    }
}

/**
 * Checks if scale is being used by any instance of courseguide.
 *
 * This is used to find out if scale used anywhere.
 *
 * @param int $scaleid ID of the scale
 * @return boolean true if the scale is used by any courseguide instance
 */
function courseguide_scale_used_anywhere($scaleid) {
    global $DB;

    if ($scaleid and $DB->record_exists('courseguide', array('grade' => -$scaleid))) {
        return true;
    } else {
        return false;
    }
}

/**
 * Creates or updates grade item for the given courseguide instance
 *
 * Needed by {@link grade_update_mod_grades()}.
 *
 * @param stdClass $courseguide instance object with extra cmidnumber and modname property
 * @param bool $reset reset grades in the gradebook
 * @return void
 */
function courseguide_grade_item_update(stdClass $courseguide, $reset=false) {
    global $CFG;
    require_once($CFG->libdir.'/gradelib.php');

    $item = array();
    $item['itemname'] = clean_param($courseguide->name, PARAM_NOTAGS);
    $item['gradetype'] = GRADE_TYPE_VALUE;

    if ($courseguide->grade > 0) {
        $item['gradetype'] = GRADE_TYPE_VALUE;
        $item['grademax']  = $courseguide->grade;
        $item['grademin']  = 0;
    } else if ($courseguide->grade < 0) {
        $item['gradetype'] = GRADE_TYPE_SCALE;
        $item['scaleid']   = -$courseguide->grade;
    } else {
        $item['gradetype'] = GRADE_TYPE_NONE;
    }

    if ($reset) {
        $item['reset'] = true;
    }

    grade_update('mod/courseguide', $courseguide->course, 'mod', 'courseguide',
            $courseguide->id, 0, null, $item);
}

/**
 * Delete grade item for given courseguide instance
 *
 * @param stdClass $courseguide instance object
 * @return grade_item
 */
function courseguide_grade_item_delete($courseguide) {
    global $CFG;
    require_once($CFG->libdir.'/gradelib.php');

    return grade_update('mod/courseguide', $courseguide->course, 'mod', 'courseguide',
            $courseguide->id, 0, null, array('deleted' => 1));
}

/**
 * Update courseguide grades in the gradebook
 *
 * Needed by {@link grade_update_mod_grades()}.
 *
 * @param stdClass $courseguide instance object with extra cmidnumber and modname property
 * @param int $userid update grade of specific user only, 0 means all participants
 */
function courseguide_update_grades(stdClass $courseguide, $userid = 0) {
    global $CFG, $DB;
    require_once($CFG->libdir.'/gradelib.php');

    // Populate array of grade objects indexed by userid.
    $grades = array();

    grade_update('mod/courseguide', $courseguide->course, 'mod', 'courseguide', $courseguide->id, 0, $grades);
}

/* File API */

/**
 * Returns the lists of all browsable file areas within the given module context
 *
 * The file area 'intro' for the activity introduction field is added automatically
 * by {@link file_browser::get_file_info_context_module()}
 *
 * @param stdClass $course
 * @param stdClass $cm
 * @param stdClass $context
 * @return array of [(string)filearea] => (string)description
 */
function courseguide_get_file_areas($course, $cm, $context) {
    return array();
}

/**
 * File browsing support for courseguide file areas
 *
 * @package mod_courseguide
 * @category files
 *
 * @param file_browser $browser
 * @param array $areas
 * @param stdClass $course
 * @param stdClass $cm
 * @param stdClass $context
 * @param string $filearea
 * @param int $itemid
 * @param string $filepath
 * @param string $filename
 * @return file_info instance or null if not found
 */
function courseguide_get_file_info($browser, $areas, $course, $cm, $context, $filearea, $itemid, $filepath, $filename) {
    return null;
}

/**
 * Serve the block files.
 *
 * @param object $course The course.
 * @param object $cm The course module.
 * @param context $context The context.
 * @param string $filearea The file area.
 * @param array $args Arguments.
 * @param bool $forcedownload Whether to force the download.
 * @param array $options Options.
 * @return bool
 */
function courseguide_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = array()) {
    $manager = new \mod_courseguide\manager();
    return $manager->serve_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, $options);
}

/* Navigation API */

/**
 * Extends the global navigation tree by adding courseguide nodes if there is a relevant content
 *
 * This can be called by an AJAX request so do not rely on $PAGE as it might not be set up properly.
 *
 * @param navigation_node $navref An object representing the navigation tree node of the courseguide module instance
 * @param stdClass $course current course record
 * @param stdClass $module current courseguide instance record
 * @param cm_info $cm course module information
 */
function courseguide_extend_navigation(navigation_node $navref, stdClass $course, stdClass $module, cm_info $cm) {
    // TODO Delete this function and its docblock, or implement it.
}

/**
 * Extends the settings navigation with the courseguide settings
 *
 * This function is called when the context for the page is a courseguide module. This is not called by AJAX
 * so it is safe to rely on the $PAGE.
 *
 * @param settings_navigation $settingsnav complete settings navigation tree
 * @param navigation_node $courseguidenode courseguide administration node
 */
function courseguide_extend_settings_navigation(settings_navigation $settingsnav, navigation_node $courseguidenode=null) {
    // TODO Delete this function and its docblock, or implement it.
}


/**
 * Called when viewing course page. Shows extra details after the link if
 * enabled.
 *
 * @param cm_info $cm Course module information
 */
function courseguide_cm_info_view(cm_info $cm) {
        global $DB, $PAGE;

        // retrieve guide.
        $courseguideid = $DB->get_field('course_modules', 'instance', array('id' => $cm->id));
        $manager = new \mod_courseguide\manager();
        $guide = $manager->get_guide_by_instanceid($courseguideid);
        if (!empty($guide)) {

            if ($PAGE->user_is_editing()) {
                $guidehtml = $manager->render_guide_and_edit_button($guide);
            } else {
                $guidehtml = $manager->render_guide($guide);
            }

            if ($guide->get_displaymode() == 'inline') {
                $cm->set_after_link(' ' . html_writer::tag('span', '<br/><br/>'
                        . $guidehtml,
                        array('class' => '')));
            } else if ($guide->get_displaymode() == 'collapsable') {
                // Jquery is required by your theme on the course main page. Jquery can not be included on the course main page from an
                // activity module (from my current knowledge). So it is up to you to add it if your theme does not support it
                // (for example in Clean theme on 2.8, add "echo $PAGE->requires->jquery();" before other html in your layout/columns3.php)
                // Note because Moodle stopped with YUI from Moodle 3.x we didn't use YUI even thougt it is loaded be default on the main course page.

                // requires->js will be required only once and the assumption is that Jquery should always be loaded by most theme on main course page.
                $PAGE->requires->js('/mod/courseguide/main_course_collapsable.js');
                $cm->set_extra_classes('mod_courseguide_toggle');
                $cm->set_after_link(' ' . html_writer::tag('span',
                        '<span class="mod_courseguide_expand" course-module-id="' . $cm->id . '"><span class="mod_courseguide_expandicon">◀</span>'
                        . '<span class="mod_courseguide_collapsable_content" course-module-id="' . $cm->id . '"><br/><br/>'
                        . $guidehtml . '</span></span>',
                        array('class' => '')));
            }
        }

}