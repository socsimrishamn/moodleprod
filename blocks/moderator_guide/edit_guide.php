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
 * @package    block_moderator_guide
 * @copyright  2016 onwards Coventry University {@link http://www.coventry.ac.uk/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Jerome Mouneyrac <jerome@mouneyrac.com>
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

$action = optional_param('action', 'add', PARAM_ALPHA);

// If courseid is not passsed in the then this is and admin page.
$courseid = optional_param('courseid', 0, PARAM_INT);

// The preview mode is exclusively available from the admin.
$ispreview = ( $action == 'preview' );
if ($ispreview) {
    // Check the user is an administrator.
    // Do not remove this check without modifying block_moderator_guide_template_exists call.
    if (!is_siteadmin()) {
        throw new moodle_exception('You are not allow to preview template (only administrator can).');
    }

    $courseid = 0;
}

// First things first.
if (empty($courseid)) {
    admin_externalpage_setup('block_moderator_guide_generic_admin_page');
} else {
    require_course_login($courseid);
}

$manager = new \block_moderator_guide\manager();

// Check a template exists otherwise no need to go further send them back to manage guide where a proper message is displayed.
if (!$manager->has_visible_templates($ispreview)) {
    redirect(new moodle_url('/blocks/moderator_guide/manage_guides.php', array('courseid' => $courseid, 'sesskkey' => sesskey())));
}

// Not really useful as anyway it is an admin page but just in case you want to move away from admin,
// then don't forget it ;).
require_capability('block/moderator_guide:editguide', $PAGE->context);

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
        redirect(new moodle_url('/blocks/moderator_guide/manage_guides.php', array('courseid' => $courseid)));
    } else {
        // Display Delete Template confirmation page.
        $title = get_string('confirmdeleteguide', 'block_moderator_guide');

        block_moderator_guide_set_page($title, $courseid);

        echo $OUTPUT->header();
        $message = get_string('confirmdeleteguidetext', 'block_moderator_guide', $guide);
        $continue = new single_button(new moodle_url('/blocks/moderator_guide/edit_guide.php',
            array('action' => 'delete', 'sesskey' => sesskey(), 'id' => $guideid, 'confirm' => 1, 'courseid' => $courseid)),
            get_string('delete', 'block_moderator_guide'));
        $cancel = new single_button(new moodle_url('/blocks/moderator_guide/manage_guides.php', array('courseid' => $courseid)),
            get_string('cancel', 'block_moderator_guide'));
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
    $title = get_string('editguide', 'block_moderator_guide');

    // Show / Hide update + redirect to manage guides page.
    $hide = optional_param('hide', -1, PARAM_INT);
    if ($hide !== -1) {
        $guideobj->import_record(['hidden' => $hide]);
        $guideobj = $manager->save_guide($guideobj);
        redirect(new moodle_url('/blocks/moderator_guide/manage_guides.php', array('courseid' => $courseid)));
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

    \block_moderator_guide\form\guide_generic::prepare_record_with_guide_contents($guideobj, $guide);

} else if ($ispreview) {
    $title = get_string('previewguide', 'block_moderator_guide');
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
    $title = get_string('addguide', 'block_moderator_guide');

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
}

block_moderator_guide_set_page($title, $courseid, $ispreview);

$manager->prepare_guide_files($guideobj, $guide);

$form = new \block_moderator_guide\form\guide_generic($PAGE->url,
    ['guide' => $guide, 'guideobj' => $guideobj, 'manager' => $manager]);

if ($form->is_cancelled()) {
    redirect(new moodle_url('/blocks/moderator_guide/manage_guides.php', array('courseid' => $courseid)));
} else if ($data = $form->get_data()) {
    // Creating new guide.
    $newguide = new stdClass();
    $newguide->name = $data->name;

    // Set the courseid if we are in a course.
    if (!empty($courseid)) {
        $newguide->courseid = $courseid;
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

    redirect(new moodle_url('/blocks/moderator_guide/manage_guides.php', array('courseid' => $courseid)));
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
 * @param int $courseid The course ID.
 * @param bool $preview Whether the page is in preview mode.
 */
function block_moderator_guide_set_page($title, $courseid, $preview = false) {
    global $PAGE, $COURSE;

    if (empty($courseid)) {
        // Set-up the page.
        $PAGE->set_heading($title);
        $PAGE->set_url(new moodle_url('/blocks/moderator_guide/edit_guide.php'));

        if ($preview) {
            $PAGE->navbar->add(get_string('managetemplates', 'block_moderator_guide'),
                new moodle_url('/blocks/moderator_guide/manage_templates.php'));
            $PAGE->navbar->add($title);
        } else {
            $PAGE->navbar->add(get_string('manageguides', 'block_moderator_guide'),
                new moodle_url('/blocks/moderator_guide/manage_guides.php'));
            $PAGE->navbar->add($title);
        }
    } else {
        $PAGE->set_pagelayout('incourse');
        $PAGE->navbar->add(get_string('pluginname', 'block_moderator_guide'));
        $PAGE->navbar->add(get_string('manageguides', 'block_moderator_guide'),
            new moodle_url('/blocks/moderator_guide/manage_guides.php', array('courseid' => $courseid)));
        $PAGE->navbar->add($title);
        $PAGE->set_heading($COURSE->fullname);
    }

    $PAGE->set_title($title);
    $guideid = optional_param('id', 0, PARAM_INT);
    $action = optional_param('action', 'add', PARAM_ALPHA);
    $PAGE->set_url(new moodle_url('/blocks/moderator_guide/edit_guide.php',
        array('courseid' => $courseid, 'id' => $guideid, 'action' => $action, 'sesskey' => sesskey())));
}
