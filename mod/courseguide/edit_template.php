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
 * Add/edit a template
 *
 * @package    mod_courseguide;
 * @copyright  2017 onwards Coventry University {@link http://www.coventry.ac.uk/}
 * @author     Jerome Mouneyrac - Bepaw Pty Ltd <jerome@bepaw.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

use \mod_courseguide\form\template as templateform;

$action = optional_param('action', 'add', PARAM_ALPHA);
$confirm = optional_param('confirm', false, PARAM_INT);

admin_externalpage_setup('mod_courseguide_generic_admin_page');

// Not really useful as anyway it is an admin page but just in case you want to move away from admin,
// then don't forget it ;).
require_capability('mod/courseguide:edittemplate', $PAGE->context);

$manager = new \mod_courseguide\manager();

// Retrieve template if edit mode.
$templateobj = $manager->get_new_template();
$template = $templateobj->get_record();
$templateid = 0;

if ($action === 'delete') {
    require_sesskey();
    $templateid = required_param('id', PARAM_INT);

    if ($confirm) {
        // TODO Delete associated area files.
        $manager->delete_template($templateid);
        redirect(new moodle_url('/mod/courseguide/manage_templates.php'));

    } else {
        // Display Delete Template confirmation page.
        $templateobj = $manager->get_template($templateid);
        $template = $templateobj->get_record();
        $title = get_string('confirmdeletetemplate', 'courseguide');

        mod_courseguide_set_page($title);

        echo $OUTPUT->header();
        $message = get_string('confirmdeletetemplatetext', 'courseguide', $template);

        // Check for existing guides using this template.
        $guides = $manager->list_guides($templateobj);
        if (!empty($guides)) {
            $message .= '<br/><br/><strong>' . get_string('warningdeletetemplate', 'courseguide') . '</strong><br/><ul>';
            foreach ($guides as $guide) {
                $message .= '<li>' .
                    html_writer::link(new moodle_url('/mod/courseguide/view.php', array('guideid' => $guide->get_id())),
                    $guide->get_name()) . '</li>';
            }
            $message .= '</ul>';
        }

        $continue = new single_button(new moodle_url('/mod/courseguide/edit_template.php',
            array('action' => 'delete', 'sesskey' => sesskey(), 'id' => $templateid, 'confirm' => 1)),
            get_string('delete', 'courseguide'));
        $cancel = new single_button(new moodle_url('/mod/courseguide/manage_templates.php'),
            get_string('cancel', 'courseguide'));
        echo $OUTPUT->confirm($message, $continue, $cancel);
        echo $OUTPUT->footer();
        die();
    }
} else if ($action === 'edit') {
    require_sesskey();

    // Find the template and set the page title.
    $templateid = required_param('id', PARAM_INT);
    $templateobj = $manager->get_template($templateid);
    $template = $templateobj->get_record();
    $title = get_string('edittemplate', 'courseguide');

    // Show / Hide update + redirect to manage templates page.
    $hide = optional_param('hide', -1, PARAM_INT);
    if ($hide !== -1) {
        $templateobj->import_record(['hidden' => $hide]);
        $templateobj = $manager->save_template($templateobj);
        redirect(new moodle_url('/mod/courseguide/manage_templates.php'));
    }

} else {
    // This is a Add template page.
    $title = get_string('addtemplate', 'courseguide');
}

// Set the page.
mod_courseguide_set_page($title);

// Prepare the draft file area for the template.
$manager->prepare_template_editor_files($templateobj, $template);

$formurl = new moodle_url($PAGE->url, ['id' => $templateobj->get_id(), 'action' => $action]);
$form = new templateform($formurl->out(false), ['template' => $template, 'templateobj' => $templateobj, 'manager' => $manager]);
if ($form->is_cancelled()) {
    redirect(new moodle_url('/mod/courseguide/manage_templates.php'));

} else if ($data = $form->get_data()) {
    $templateobj = $manager->save_template($templateobj, $data);
    redirect(new moodle_url('/mod/courseguide/manage_templates.php'));
}

$PAGE->requires->jquery();
$PAGE->requires->js('/mod/courseguide/edit_template.js');

echo $OUTPUT->header();

$form->display();

echo $OUTPUT->footer();

/**
 * Set the $PAGE navbar/crumtrail, the heading, the title and the url.
 *
 * @param string $title the page title.
 */
function mod_courseguide_set_page($title) {
    global $PAGE;
    $PAGE->set_heading($title);
    $PAGE->set_title($title);
    $PAGE->set_url(new moodle_url('/mod/courseguide/edit_template.php'));
    $PAGE->navbar->add(get_string('managetemplates', 'courseguide'),
        new moodle_url('/mod/courseguide/manage_templates.php'));
    $PAGE->navbar->add($title);
}
