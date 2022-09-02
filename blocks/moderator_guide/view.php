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
 * View a guide.
 *
 * @package    block_moderator_guide
 * @copyright  2016 onwards Coventry University {@link http://www.coventry.ac.uk/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Jerome Mouneyrac <jerome@mouneyrac.com>
 */

require_once(__DIR__ . '/../../config.php');

require_once(__DIR__ . '/classes/form/moderation.php');

$manager = new \block_moderator_guide\manager();
$guideid = required_param('guideid', PARAM_INT);
$guideobj = $manager->get_guide($guideid);
$guide = $guideobj->get_record();

require_course_login($guideobj->get_courseid());
require_capability('block/moderator_guide:viewguide', $PAGE->context);

// This is a Add guide page.
$title = $guide->name;

$PAGE->set_pagelayout('incourse');
$PAGE->set_url(new moodle_url('/blocks/moderator_guide/view.php', array('guideid' => $guideid)));
$PAGE->navbar->add(get_string('pluginname', 'block_moderator_guide'));
$PAGE->navbar->add($title);
$PAGE->set_heading($title);
$PAGE->set_title($title);

// Check if the user can see the guide.
$manager->require_can_access_guide($guideobj);

echo $OUTPUT->header();

// Add edit guide button if the user can edit it.
if (has_capability('block/moderator_guide:editguide', $PAGE->context)) {
    $editurl = new moodle_url('/blocks/moderator_guide/edit_guide.php',
        array('courseid' => $COURSE->id, 'sesskey' => sesskey(), 'action' => 'edit', 'id' => $guideid));
    echo $OUTPUT->single_button($editurl, get_string('editguide', 'block_moderator_guide'), 'post',
        array('class' => 'singlebutton block_moderator_guide_right'));
}

echo $manager->render_guide($guideobj);

$moderationformhtml = $manager->hanlde_and_render_moderation_form($guideobj, $guide, $guideid, false);

echo $moderationformhtml;

echo $OUTPUT->footer();
