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
 * Add/edit a guide.
 *
 * @package    mod_courseguide;
 * @copyright  2017 onwards Coventry University {@link http://www.coventry.ac.uk/}
 * @author     Jerome Mouneyrac - Bepaw Pty Ltd <jerome@bepaw.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

$action = optional_param('action', 'add', PARAM_ALPHA);

// If courseguideid is not passsed in the then this is an admin page.
$courseguideid = optional_param('courseguideid', 0, PARAM_INT);

// The preview mode is exclusively available from the admin.
$ispreview = ( $action == 'preview' );
if ($ispreview) {
    // check the user is an administrator.
    // Do not remove this check without modifying block_moderator_guide_template_exists call.
    if (!is_siteadmin()) {
        throw new moodle_exception('You are not allow to preview template (only administrator can).');
    }

    $courseguideid = 0;
}

// First things first.
if (empty($courseguideid)) {
    admin_externalpage_setup('mod_courseguide_generic_admin_page');
} else {
    $cm = get_coursemodule_from_instance('courseguide', $courseguideid);
    require_course_login($cm->course);
}

$manager = new \mod_courseguide\manager();

// Check a template exists otherwise no need to go further send them back to manage guide where a proper message is displayed.
if (!$manager->has_visible_templates($ispreview)) {
    $title = get_string('editguide', 'courseguide');
    mod_courseguide_set_page($title, $courseguideid);
    echo $OUTPUT->header();
    echo $OUTPUT->box(get_string('notemplate', 'courseguide'), 'generalbox mdl-align');
    if (is_siteadmin($USER)) {
        echo html_writer::link(new moodle_url('/mod/courseguide/manage_templates.php'),
            get_string('managetemplates', 'courseguide'));
    }
    echo $OUTPUT->footer();
    die();
}

// Not really useful as anyway it is an admin page but just in case you want to move away from admin,
// then don't forget it ;).
require_capability('mod/courseguide:editguide', $PAGE->context);

// Retrieve guide if edit mode.
if ($action === 'delete') {
    require_sesskey();
    $guideid = required_param('id', PARAM_INT);
    $guideobj = $manager->get_guide($guideid);
    $manager->require_can_access_guide($guideobj);
    $guide = $guideobj->get_record();

    $confirm = optional_param('confirm', false, PARAM_INT);
    if ($confirm) {
        $manager->delete_guide($guideobj);
        redirect(new moodle_url('/mod/courseguide/manage_guides.php', array('courseguideid' => $courseguideid)));
    } else {
        // Display Delete Template confirmation page.
        $title = get_string('confirmdeleteguide', 'courseguide');

        mod_courseguide_set_page($title, $courseguideid);

        echo $OUTPUT->header();
        $message = get_string('confirmdeleteguidetext', 'courseguide', $guide);
        $continue = new single_button(new moodle_url('/mod/courseguide/edit_guide.php',
            array('action' => 'delete', 'sesskey' => sesskey(), 'id' => $guideid, 'confirm' => 1, 'courseguideid' => $courseguideid)),
            get_string('delete', 'courseguide'));
        $cancel = new single_button(new moodle_url('/mod/courseguide/manage_guides.php', array('courseguideid' => $courseguideid)),
            get_string('cancel', 'courseguide'));
        echo $OUTPUT->confirm($message, $continue, $cancel);
        echo $OUTPUT->footer();
        die();
    }

} else if ($action === 'edit') {
    require_sesskey();

    // Find the guide and set the page title.
    $guideid = required_param('id', PARAM_INT);
    $guideobj = $manager->get_guide($guideid);
    $manager->require_can_access_guide($guideobj);
    $guide = $guideobj->get_record();
    $title = get_string('editguide', 'courseguide');

    // Show / Hide update + redirect to manage guides page.
    $hide = optional_param('hide', -1, PARAM_INT);
    if ($hide !== -1) {
        $guideobj->import_record(['hidden' => $hide]);
        $guideobj = $manager->save_guide($guideobj);
        redirect(new moodle_url('/mod/courseguide/manage_guides.php', array('courseguideid' => $courseguideid)));
    }

    // Set the guide contents.
    $forcetemplate = optional_param('forcetemplate', 0, PARAM_INT);
    if (empty($forcetemplate)) {
        $templateid = $guide->templateid;
    } else {
        // Switch guide to another template.
        $templateid = $forcetemplate;
        $templateobj = $manager->get_template($templateid);

        $newguideobj = $manager->get_new_guide($templateobj);
        $newguideobj->import_record($guideobj->get_record());
        $newguideobj->set_contents($guideobj->get_contents());

        $guideobj = $newguideobj;
        $guide = $guideobj->get_record();
    }

    \mod_courseguide\form\guide::prepare_record_with_guide_contents($guideobj, $guide);

} else if ($ispreview) {
    $title = get_string('previewguide', 'courseguide');
    $forcetemplate = required_param('forcetemplate', PARAM_INT);

    $templateobj = $manager->get_template($forcetemplate);
    $currenttemplate = $templateobj->get_record();
    $manager->require_can_access_template($templateobj);
    $currenttemplatefields = $templateobj->parse();

    $guideobj = $manager->get_new_guide($templateobj);
    $guide = $guideobj->get_record();
    $guide->action = 'preview';

} else {
    // This is a Add guide page.
    $title = get_string('addguide', 'courseguide');

    // Set the guide contents.
    $forcetemplate = optional_param('forcetemplate', 0, PARAM_INT);

    if (empty($forcetemplate)) {
        $formtemplateid = optional_param('template', 0, PARAM_INT);
        if (empty($formtemplateid)) {
            // Take the last template (same logic as in the forms.php file).
            $templatesobj = $manager->list_visible_templates();
            $templateobj = array_pop($templatesobj);
            $template = $templateobj->get_record();
            $templateid = $template->id;
        } else {
            $templateid = $formtemplateid;
        }
    } else {
        $templateid = $forcetemplate;
    }

    $templateobj = $manager->get_template($templateid);
    $manager->require_can_access_template($templateobj);

    $currenttemplate = $templateobj->get_record();
    $currenttemplatefields = $templateobj->parse();

    $guideobj = $manager->get_new_guide($templateobj);
    $guide = $guideobj->get_record();
    $courseguide = $DB->get_record('courseguide', array('id' => $courseguideid));
    $guide->name = $courseguide->name;
    $guide->courseguideid = $courseguide->id;
}

mod_courseguide_set_page($title, $courseguideid, $ispreview);

$manager->prepare_guide_files($guideobj, $guide);

$form = new \mod_courseguide\form\guide($PAGE->url,
    ['guide' => $guide, 'guideobj' => $guideobj, 'manager' => $manager]);

if ($form->is_cancelled()) {
    $cm = $guideobj->get_coursemodule();
    redirect(new moodle_url('/mod/courseguide/view.php', array('id' => $cm->id)));
} else if ($data = $form->get_data()) {
    // Creating new guide.
    $newguide = new stdClass();
    $newguide->name = $data->name;
    $newguide->displaymode = $data->displaymode;

    // Set the courseguideid if we are in a course module activity.
    if (!empty($courseguideid)) {
        $newguide->courseguideid = $courseguideid;
    }

    $templatefields = $guideobj->get_template()->parse();
    $newcontents = $form->extract_contents_from_data($data, $templatefields);
    $files = $form->extract_files_from_data($data, $templatefields);

    $guideobj->import_record($newguide);
    $guideobj->import_contents($newcontents);
    $guideobj = $manager->save_guide($guideobj, $files);

    // Set the completed status if it is not existing or reset.
    $data->id = $guideobj->get_id();
    $cancomplete = has_capability('block/moderator_guide:cancomplete', $PAGE->context);
    $manager->update_guide_moderation_fields($data, $cancomplete, false);

    $cm = $guideobj->get_coursemodule();
    redirect(new moodle_url('/mod/courseguide/view.php', array('id' => $cm->id)));
}

$PAGE->requires->jquery();
$PAGE->requires->js('/blocks/moderator_guide/edit_guide.js');

echo $OUTPUT->header();
$form->display();
echo $OUTPUT->footer();

/**
 * Set the $PAGE navbar/crumtrail, the heading, the title and the url.
 *
 * @param string $title the page title.
 * @param int $courseguideid The course guide ID.
 * @param bool $preview Whether the page is in preview mode.
 */
function mod_courseguide_set_page($title, $courseguideid, $preview = false) {
    global $PAGE, $COURSE, $DB;

    if (empty($courseguideid)) {
        // Set-up the page.
        $PAGE->set_heading($title);
        $PAGE->set_url(new moodle_url('/mod/courseguide/edit_guide.php'));

        if ($preview) {
            $PAGE->navbar->add(get_string('managetemplates', 'courseguide'),
                new moodle_url('/mod/courseguide/manage_templates.php'));
            $PAGE->navbar->add($title);
        } else {
            $PAGE->navbar->add(get_string('manageguides', 'courseguide'),
                new moodle_url('/mod/courseguide/manage_guides.php'));
            $PAGE->navbar->add($title);
        }
    } else {
        $courseguide = $DB->get_record('courseguide', array('id' => $courseguideid));
        $PAGE->set_pagelayout('incourse');
        $PAGE->navbar->add($courseguide->name);
        $PAGE->navbar->add($title);
        $PAGE->set_heading($COURSE->fullname);
    }

    $PAGE->set_title($title);
    $guideid = optional_param('id', 0, PARAM_INT);
    $action = optional_param('action', 'add', PARAM_ALPHA);
    $PAGE->set_url(new moodle_url('/mod/courseguide/edit_guide.php',
        array('courseguideid' => $courseguideid, 'id' => $guideid, 'action' => $action, 'sesskey' => sesskey())));
}
