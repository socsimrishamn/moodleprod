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
 * The course guide module configuration variables
 *
 * @package    mod_courseguide
 * @copyright  2017 Jerome Mouneyrac <jerome@bepaw.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author  Jerome Mouneyrac <jerome@bepaw.com>
 */

defined('MOODLE_INTERNAL') || die();

$ADMIN->add('modsettings', new admin_externalpage('mod_courseguide_generic_admin_page', get_string('pluginname',
    'courseguide'), new moodle_url("/admin/settings.php", array('section' => 'modsettingcourseguide')),
    'mod/courseguide:edittemplate', true));

if ($ADMIN->fulltree) {

    $settings->add(new admin_setting_configtext('block_moderator_guide/restriction',
        get_string('profilerestriction', 'block_moderator_guide'),
        get_string('profilerestrictiondesc', 'block_moderator_guide') . ' '
        . get_string('warningprofilerestrictiondesc', 'courseguide'),
        '', PARAM_ALPHANUM));

    $settings->add(new admin_setting_configcheckbox('block_moderator_guide/moderation',
        get_string('configmoderation', 'block_moderator_guide'),
        get_string('configmoderationdesc', 'block_moderator_guide') . ' '
        . get_string('warningconfigmoderationdesc', 'courseguide'), 0));

    $tplurl = new moodle_url('/mod/courseguide/manage_templates.php');
    $guideurl = new moodle_url('/mod/courseguide/manage_guides.php');
    $settings->add(new admin_setting_heading('mod_courseguide/guidemenu', '',
        html_writer::tag('ul',
            html_writer::tag('li', html_writer::link($tplurl, get_string('managetemplates', 'courseguide')))
        )
    ));

}
