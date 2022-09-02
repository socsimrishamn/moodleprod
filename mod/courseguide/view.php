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
 * Prints a particular instance of courseguide
 *
 * You can have a rather longer description of the file as well,
 * if you like, and it can span multiple lines.
 *
 * @package    mod_courseguide
 * @copyright  2017 onwards Coventry University {@link http://www.coventry.ac.uk/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author  Jerome Mouneyrac <jerome@bepaw.com>
 */

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/lib.php');

$id = optional_param('id', 0, PARAM_INT); // Course_module ID, or
$n  = optional_param('n', 0, PARAM_INT);  // ... courseguide instance ID - it should be named as the first character of the module.

if ($id) {

    // If it is the moderation form then retrieve the correct $cmid
    // as $id now contains the courseguideid (we are in a redirection page).
    $action = optional_param('action', 'none', PARAM_ALPHA);
    $courseguideid = optional_param('guideid', 0, PARAM_INT);
    if ($action === 'save') {
        $cmid = $action = optional_param('cmid', 0, PARAM_INT);
    } else {
        $cmid = $id;
    }

    $cm         = get_coursemodule_from_id('courseguide', $cmid, 0, false, MUST_EXIST);
    $course     = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $courseguide  = $DB->get_record('courseguide', array('id' => $cm->instance), '*', MUST_EXIST);
} else if ($n) {
    $courseguide  = $DB->get_record('courseguide', array('id' => $n), '*', MUST_EXIST);
    $course     = $DB->get_record('course', array('id' => $courseguide->course), '*', MUST_EXIST);
    $cm         = get_coursemodule_from_instance('courseguide', $courseguide->id, $course->id, false, MUST_EXIST);
} else {
    error('You must specify a course_module ID or an instance ID');
}

require_login($course, true, $cm);
$manager = new \mod_courseguide\manager();

$event = \mod_courseguide\event\course_module_viewed::create(array(
    'objectid' => $PAGE->cm->instance,
    'context' => $PAGE->context,
));
$event->add_record_snapshot('course', $PAGE->course);
$event->add_record_snapshot($PAGE->cm->modname, $courseguide);
$event->trigger();

// Print the page header.

$PAGE->set_url('/mod/courseguide/view.php', array('id' => $cm->id));
$PAGE->set_title(format_string($courseguide->name));
$PAGE->set_heading(format_string($course->fullname));

/*
 * Other things you may want to set - remove if not needed.
 * $PAGE->set_cacheable(false);
 * $PAGE->set_focuscontrol('some-html-id');
 * $PAGE->add_body_class('courseguide-'.$somevar);
 */

// Output starts here.
echo $OUTPUT->header();



// Conditions to show the intro can change to look for own settings or whatever.
if ($courseguide->intro) {
    echo $OUTPUT->box(format_module_intro('courseguide', $courseguide, $cm->id), 'generalbox mod_introbox', 'courseguideintro');
}


// Check if a guide exist.
$guideid = $DB->get_field($manager->get_guides_table(), 'id', ['courseguideid' => $courseguide->id]);
if (empty($guideid)) {

    // Add edit guide button if the user can edit it.
    if (has_capability('mod/courseguide:editguide', $PAGE->context)) {
        $editurl = new moodle_url('/mod/courseguide/edit_guide.php',
            array('courseguideid' => $courseguide->id, 'sesskey' => sesskey(), 'action' => 'add'));
        echo $OUTPUT->single_button($editurl, get_string('editguide', 'courseguide'), 'post',
            array('class' => 'singlebutton mod_courseguide_right'));

        echo get_string('mustaddguide', 'courseguide');
    } else {
        echo get_string('noguide', 'courseguide');
    }


} else {

    // Check we are allowed to access the guide.
    $guideobj = $manager->get_guide_by_instanceid($courseguide->id); // guideid and cmid are the same.
    $guide = $guideobj->get_record();
    require_capability('mod/courseguide:viewguide', $PAGE->context);

    echo $manager->render_guide_and_edit_button($guideobj);

    $moderationformhtml = $manager->hanlde_and_render_moderation_form($guideobj, $guide, $guideid, $cm->id);

    echo $moderationformhtml;

}


// Finish the page.
echo $OUTPUT->footer();
