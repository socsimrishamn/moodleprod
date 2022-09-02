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
 * Manage guides (admin and course pages)
 *
 * @package    block_moderator_guide
 * @copyright  2016 onwards Coventry University {@link http://www.coventry.ac.uk/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Jerome Mouneyrac <jerome@mouneyrac.com>
 */

require(__DIR__ . '/../../config.php');

$courseid = optional_param('courseid', 0, PARAM_INT);
$manager = new \block_moderator_guide\manager();

$dbrequestconditions = array();
if (empty($courseid)) {

    require_once($CFG->libdir . '/adminlib.php');
    admin_externalpage_setup('block_moderator_guide_generic_admin_page');

    // Set-up the page.
    $PAGE->set_heading(get_string('manageguides', 'block_moderator_guide'));
    $PAGE->set_title(get_string('manageguides', 'block_moderator_guide'));
    $PAGE->set_url(new moodle_url('/blocks/moderator_guide/manage_guides.php'));
    $PAGE->navbar->add(get_string('manageguides', 'block_moderator_guide'));

    $templateid = optional_param('templateid', 0, PARAM_INT);
    if (!empty($templateid)) {
        $dbrequestconditions['templateid'] = $templateid;
    }

} else {
    require_course_login($courseid);

    $PAGE->set_pagelayout('incourse');
    $PAGE->set_url(new moodle_url('/blocks/moderator_guide/manage_guides.php', array('courseid' => $courseid)));
    $PAGE->navbar->add(get_string('pluginname', 'block_moderator_guide'));
    $PAGE->navbar->add(get_string('manageguides', 'block_moderator_guide'));
    $PAGE->set_heading($COURSE->fullname);
    $PAGE->set_title(get_string('manageguides', 'block_moderator_guide'));
    $dbrequestconditions['courseid'] = $courseid;
}

require_capability('block/moderator_guide:editguide', $PAGE->context);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('manageguides', 'block_moderator_guide'));

// Check a template exists otherwise no need to go further.
if ($manager->has_visible_templates()) {

    $strorgas = get_string('organizations', 'block_moderator_guide');
    $strtemplate = get_string('template', 'block_moderator_guide');
    $strcourse = get_string('course', 'block_moderator_guide');
    $strname = get_string('name', 'block_moderator_guide');
    $strauthor = get_string('author', 'block_moderator_guide');
    $strcompleted = get_string('completed', 'block_moderator_guide');
    $strreviewed = get_string('reviewed', 'block_moderator_guide');
    $stractions = get_string('actions', 'block_moderator_guide');

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

            $coursename = $DB->get_field('course', 'shortname', array('id' => $guide->courseid));
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

            $authorname = fullname($DB->get_record('user', array('id' => $guide->creatorid)));

            $begintag = '';
            $endtag = '';
            $hideswitch = 1;
            $hideswitchstr = get_string('hide', 'block_moderator_guide');
            if ($guide->hidden) {
                $begintag = '<span style="color:LightGrey">';
                $endtag = '</span>';
                $hideswitch = 0;
                $hideswitchstr = get_string('show', 'block_moderator_guide');
            }

            $actions = [
                html_writer::link(new moodle_url('/blocks/moderator_guide/edit_guide.php',
                    array('sesskey' => sesskey(), 'action' => 'edit', 'hide' => $hideswitch, 'id' => $guide->id,
                    'courseid' => $courseid)), $hideswitchstr),

                html_writer::link(new moodle_url('/blocks/moderator_guide/edit_guide.php',
                    array('sesskey' => sesskey(), 'action' => 'delete', 'id' => $guide->id, 'courseid' => $courseid)),
                    get_string('delete')),

                html_writer::link(new moodle_url('/blocks/moderator_guide/edit_guide.php',
                    array('sesskey' => sesskey(), 'action' => 'edit', 'id' => $guide->id, 'courseid' => $courseid)),
                    get_string('edit')),

                html_writer::link(new moodle_url('/blocks/moderator_guide/view.php',
                    array('sesskey' => sesskey(), 'guideid' => $guide->id, 'courseid' => $courseid)), get_string('view'))
            ];

            $table->data[] = array(
                $begintag . $template->organization . $endtag,
                $begintag . $template->name . $endtag,
                $begintag . $coursename . $endtag,
                $begintag . $guide->name . $endtag,
                $begintag . $authorname . $endtag,
                $begintag . $completeddetail . $endtag,
                $begintag . $revieweddetail . $endtag,
                implode('&nbsp;&nbsp;', $actions)
            );
        }

    }

    echo html_writer::table($table);

    if (!empty($courseid)) {
        $addbutton = $OUTPUT->single_button(new moodle_url('/blocks/moderator_guide/edit_guide.php',
            array('action' => 'add', 'courseid' => $courseid)), get_string('addguide', 'block_moderator_guide'));
        echo html_writer::div($addbutton, 'block_moderator_guide_addbutton');

        if (is_siteadmin($USER)) {
            echo html_writer::link(new moodle_url('/blocks/moderator_guide/manage_templates.php'),
                get_string('managetemplates', 'block_moderator_guide'));
        }
    } else {
        echo $OUTPUT->box(get_string('addguidehelp', 'block_moderator_guide'), 'generalbox mdl-align');
    }

} else {
    echo $OUTPUT->box(get_string('notemplate', 'block_moderator_guide'), 'generalbox mdl-align');

    if (!empty($courseid) && is_siteadmin($USER)) {
        echo html_writer::link(new moodle_url('/blocks/moderator_guide/manage_templates.php'),
            get_string('managetemplates', 'block_moderator_guide'));
    }
}

echo $OUTPUT->footer();
