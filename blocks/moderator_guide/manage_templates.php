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
 * @package    block_moderator_guide
 * @copyright  2016 onwards Coventry University {@link http://www.coventry.ac.uk/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Jerome Mouneyrac <jerome@mouneyrac.com>
 */

require(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

// Print the header.
admin_externalpage_setup('block_moderator_guide_generic_admin_page');

$manager = new \block_moderator_guide\manager();

// Set-up the page.
$PAGE->set_heading(get_string('managetemplates', 'block_moderator_guide'));
$PAGE->set_title(get_string('managetemplates', 'block_moderator_guide'));
$PAGE->set_url(new moodle_url('/blocks/moderator_guide/manage_templates.php'));
$PAGE->navbar->add(get_string('managetemplates', 'block_moderator_guide'));

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('managetemplates', 'block_moderator_guide'));

$strorgas = get_string('organizations', 'block_moderator_guide');
$strname = get_string('name', 'block_moderator_guide');
$stractions = get_string('actions', 'block_moderator_guide');

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
    $hideswitchstr = get_string('hide', 'block_moderator_guide');
    if ($template->hidden) {
        $begintag = '<span style="color:LightGrey">';
        $endtag = '</span>';
        $hideswitch = 0;
        $hideswitchstr = get_string('show', 'block_moderator_guide');
    }

    // Check for guides.
    $guideslink = '';
    $hasguides = $manager->has_guides($templateobj);
    if ($hasguides) {
        $guideslink = html_writer::link(new moodle_url('/blocks/moderator_guide/manage_guides.php',
            array('templateid' => $template->id)), get_string('guides', 'block_moderator_guide'));
    }

    $actions = [
        html_writer::link(new moodle_url('/blocks/moderator_guide/edit_template.php',
            array('sesskey' => sesskey(), 'action' => 'edit', 'hide' => $hideswitch, 'id' => $template->id)), $hideswitchstr),

        html_writer::link(new moodle_url('/blocks/moderator_guide/edit_template.php',
            array('sesskey' => sesskey(), 'action' => 'delete', 'id' => $template->id)), get_string('delete')),

        html_writer::link(new moodle_url('/blocks/moderator_guide/edit_template.php',
            array('sesskey' => sesskey(), 'action' => 'edit', 'id' => $template->id)), get_string('edit')),

        html_writer::link(new moodle_url('/blocks/moderator_guide/edit_guide.php',
            array('action' => 'preview', 'sesskey' => sesskey(), 'forcetemplate' => $template->id)), get_string('preview')),

        $guideslink
    ];

    $table->data[] = array(
        $begintag . $template->organization . $endtag,
        $begintag . $template->name . $endtag,
        implode('&nbsp;&nbsp;', $actions)
    );
}

echo html_writer::table($table);

echo $OUTPUT->box(get_string('managetemplatesdesc', 'block_moderator_guide'), 'generalbox mdl-align');

$addbutton = $OUTPUT->single_button(new moodle_url('/blocks/moderator_guide/edit_template.php',
    array('action' => 'add')), get_string('addtemplate', 'block_moderator_guide'));
echo html_writer::div($addbutton, 'block_moderator_guide_addbutton');

echo $OUTPUT->footer();
