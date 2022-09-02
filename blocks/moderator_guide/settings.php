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
 * Plugin administration pages are defined here.
 *
 * @package    block_moderator_guide
 * @copyright  2016 onwards Coventry University {@link http://www.coventry.ac.uk/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Jerome Mouneyrac <jerome@mouneyrac.com>
 */

defined('MOODLE_INTERNAL') || die();

// Mock an admin settings page to be able to use admin_externalpage_setup().
$ADMIN->add('blocksettings', new admin_externalpage('block_moderator_guide_generic_admin_page', get_string('pluginname',
    'block_moderator_guide'), new moodle_url("/admin/settings.php", array('section' => 'blocksettingmoderator_guide')),
    'block/moderator_guide:edittemplate', true));

if ($ADMIN->fulltree) {
    $tplurl = new moodle_url('/blocks/moderator_guide/manage_templates.php');
    $guideurl = new moodle_url('/blocks/moderator_guide/manage_guides.php');

    $settings->add(new admin_setting_configtext('block_moderator_guide/restriction', get_string('profilerestriction',
        'block_moderator_guide'), get_string('profilerestrictiondesc', 'block_moderator_guide'), '', PARAM_ALPHANUM));
    $settings->add(new admin_setting_configcheckbox('block_moderator_guide/moderation', get_string('configmoderation', 'block_moderator_guide'),
        get_string('configmoderationdesc', 'block_moderator_guide'), 0));
    $settings->add(new admin_setting_heading('block_moderator_guide/guidemenu', '',
        html_writer::tag('ul',
            html_writer::tag('li', html_writer::link($tplurl, get_string('managetemplates', 'block_moderator_guide'))) .
            html_writer::tag('li', html_writer::link($guideurl, get_string('manageguides', 'block_moderator_guide')))
        )
    ));

    unset($tplurl, $guideurl);
}
