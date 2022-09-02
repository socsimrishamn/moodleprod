<?php
// This file is part of Moderator Guide plugin for Moodle
//
// Moderator Guide plugin for Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moderator Guide plugin for Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moderator Guide plugin for Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Manage guides (admin page)
 *
 * @package    mod_courseguide
 * @copyright  2017 onwards Coventry University {@link http://www.coventry.ac.uk/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Jerome Mouneyrac - Bepaw Pty Ltd <jerome@bepaw.com>
 */

require(__DIR__ . '/../../config.php');

$manager = new \mod_courseguide\manager();

$dbrequestconditions = array();

require_once($CFG->libdir . '/adminlib.php');
admin_externalpage_setup('mod_courseguide_generic_admin_page');

// Set-up the page.
$PAGE->set_heading(get_string('viewguides', 'courseguide'));
$PAGE->set_title(get_string('viewguides', 'courseguide'));
$PAGE->set_url(new moodle_url('/mod/courseguide/manage_guides.php'));
$PAGE->navbar->add(get_string('viewguides', 'courseguide'));

$templateid = optional_param('templateid', 0, PARAM_INT);
if (!empty($templateid)) {
    $dbrequestconditions['templateid'] = $templateid;
}

require_capability('mod/courseguide:editguide', $PAGE->context);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('viewguides', 'courseguide'));

// Check a template exists otherwise no need to go further.
if ($manager->has_visible_templates()) {

    $strorgas = get_string('organizations', 'courseguide');
    $strtemplate = get_string('template', 'courseguide');
    $strcourse = get_string('course', 'courseguide');
    $strname = get_string('name', 'courseguide');
    $strauthor = get_string('author', 'courseguide');
    $strcompleted = get_string('completed', 'block_moderator_guide');
    $strreviewed = get_string('reviewed', 'block_moderator_guide');
    $stractions = get_string('actions', 'courseguide');

    $table = new html_table();
    $table->head = array($strorgas, $strtemplate, $strcourse, $strname, $strauthor, $strcompleted,
        $strreviewed, $stractions);
    $table->colclasses = array('mdl-left organizations', 'mdl-left template', 'mdl-left course', 'mdl-left name',
        'mdl-left completed', 'mdl-left reviewed', 'mdl-left author', 'mdl-left config');
    $table->attributes = array('class' => 'admintable manageguides generaltable');
    $table->id = 'manageguidestable';
    $table->data = array();

    $allguides = $manager->list_visible_guides($dbrequestconditions);

    if (!empty($allguides)) {

        foreach ($allguides as $guideobj) {
            $guide = $guideobj->get_record();
            $templateobj = $guideobj->get_template();
            $template = $templateobj->get_record();

            $coursename = $DB->get_field('course', 'shortname', array('id' => $guideobj->get_courseid()));
            $authorname = fullname($DB->get_record('user', array('id' => $guide->creatorid)));

            $completeddetail = "";
            if ($guide->completed) {
                $completeddetail = fullname($DB->get_record('user', array('id' => $guide->completeduserid)));
                $completeddetail .= " - " . userdate($guide->completedtime);
            }
            $revieweddetail = "";
            if ($guide->reviewed) {
                $revieweddetail = fullname($DB->get_record('user', array('id' => $guide->revieweduserid)));
                $revieweddetail .= " - " . userdate($guide->reviewedtime);
            }

            $actions = [
                html_writer::link(new moodle_url('/mod/courseguide/view.php',
                    array('sesskey' => sesskey(), 'id' => $guideobj->get_coursemodule()->id)), get_string('view'))
            ];

            $table->data[] = array(
                $template->organization,
                $template->name,
                $coursename,
                $guide->name,
                $authorname,
                $completeddetail,
                $revieweddetail,
                implode('&nbsp;&nbsp;', $actions)
            );
        }

    }

    echo html_writer::table($table);

    if (!empty($courseguideid)) {
        $addbutton = $OUTPUT->single_button(new moodle_url('/mod/courseguide/edit_guide.php',
            array('action' => 'add', 'courseguideid' => $courseguideid)), get_string('addguide', 'courseguide'));
        echo html_writer::div($addbutton, 'mod_courseguide_addbutton');

        if (is_siteadmin($USER)) {
            echo html_writer::link(new moodle_url('/mod/courseguide/manage_templates.php'),
                get_string('managetemplates', 'courseguide'));
        }
    } else {
        echo $OUTPUT->box(get_string('addguidehelp', 'courseguide'), 'generalbox mdl-align');
    }

} else {
    echo $OUTPUT->box(get_string('notemplate', 'courseguide'), 'generalbox mdl-align');

    if (!empty($courseid) && is_siteadmin($USER)) {
        echo html_writer::link(new moodle_url('/mod/courseguide/manage_templates.php'),
            get_string('managetemplates', 'courseguide'));
    }
}

echo $OUTPUT->footer();
