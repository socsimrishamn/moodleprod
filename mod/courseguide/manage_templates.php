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
 * Config changes report.
 *
 * @package    mod_courseguide;
 * @copyright  2017 onwards Coventry University {@link http://www.coventry.ac.uk/}
 * @author     Jerome Mouneyrac - Bepaw Pty Ltd <jerome@bepaw.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

// Print the header.
admin_externalpage_setup('mod_courseguide_generic_admin_page');

$manager = new \mod_courseguide\manager();

// Set-up the page.
$PAGE->set_heading(get_string('managetemplates', 'courseguide'));
$PAGE->set_title(get_string('managetemplates', 'courseguide'));
$PAGE->set_url(new moodle_url('/mod/courseguide/manage_templates.php'));
$PAGE->navbar->add(get_string('managetemplates', 'courseguide'));

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('managetemplates', 'courseguide'));

$strorgas = get_string('organizations', 'courseguide');
$strname = get_string('name', 'courseguide');
$stractions = get_string('actions', 'courseguide');

$table = new html_table();
$table->head = array($strorgas, $strname, $stractions);
$table->colclasses = array('mdl-left organizations', 'mdl-left name', 'mdl-left config');
$table->attributes = array('class' => 'admintable managetemplates generaltable');
$table->id = 'managetemplatestable';
$table->data = array();

$alltemplates = $manager->list_templates();
foreach ($alltemplates as $templateobj) {
    $template = $templateobj->get_record();

    $begintag = '';
    $endtag = '';
    $hideswitch = 1;
    $hideswitchstr = get_string('hide', 'courseguide');
    if ($template->hidden) {
        $begintag = '<span style="color:LightGrey">';
        $endtag = '</span>';
        $hideswitch = 0;
        $hideswitchstr = get_string('show', 'courseguide');
    }

    // Check for guides.
    $guideslink = '';
    $deletelink = '';
    $hasguides = $manager->has_guides($templateobj);
    if ($hasguides) {
        $guideslink = '<span class="mod_courseguide_admin_action">' . html_writer::link(new moodle_url('/mod/courseguide/manage_guides.php',
            array('templateid' => $template->id)), get_string('guides', 'courseguide')) . '</span>';
    } else {
        $deletelink = '<span class="mod_courseguide_admin_action">' . html_writer::link(new moodle_url('/mod/courseguide/edit_template.php',
            array('sesskey' => sesskey(), 'action' => 'delete', 'id' => $template->id)), get_string('delete')) . '</span>';
    }

    $actions = [
        '<span class="mod_courseguide_admin_action">' . html_writer::link(new moodle_url('/mod/courseguide/edit_template.php',
            array('sesskey' => sesskey(), 'action' => 'edit', 'hide' => $hideswitch, 'id' => $template->id)), $hideswitchstr) . '</span>',

        '<span class="mod_courseguide_admin_action">' . html_writer::link(new moodle_url('/mod/courseguide/edit_template.php',
            array('sesskey' => sesskey(), 'action' => 'edit', 'id' => $template->id)), get_string('edit')) . '</span>',

        '<span class="mod_courseguide_admin_action">' . html_writer::link(new moodle_url('/mod/courseguide/edit_guide.php',
            array('action' => 'preview', 'sesskey' => sesskey(), 'forcetemplate' => $template->id)), get_string('preview')) . '</span>',

        $deletelink,

        $guideslink
    ];

    $table->data[] = array(
        $begintag . $template->organization . $endtag,
        $begintag . $template->name . $endtag,
        implode('', $actions)
    );
}

echo html_writer::table($table);

echo $OUTPUT->box(get_string('managetemplatesdesc', 'courseguide'), 'generalbox mdl-align');
//
$addbutton = $OUTPUT->single_button(new moodle_url('/mod/courseguide/edit_template.php',
    array('action' => 'add')), get_string('addtemplate', 'courseguide'));
echo html_writer::div($addbutton, 'mod_courseguide_addbutton');

echo $OUTPUT->footer();
