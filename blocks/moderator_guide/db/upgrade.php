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
 * Plugin upgrade steps are defined here.
 *
 * @package    block_moderator_guide
 * @category   upgrade
 * @copyright  2016 onwards Coventry University {@link http://www.coventry.ac.uk/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Jerome Mouneyrac <jerome@mouneyrac.com>
 */

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/upgradelib.php');

/**
 * Execute block_moderator_guide upgrade from the given old version.
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_block_moderator_guide_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    // For further information please read the Upgrade API documentation:
    // https://docs.moodle.org/dev/Upgrade_API
    //
    // You will also have to create the db/install.xml file by using the XMLDB Editor.
    // Documentation for the XMLDB Editor can be found at:
    // https://docs.moodle.org/dev/XMLDB_editor.

    if ($oldversion < 2016110305) {

        // Define table block_mdrtr_guide_templates to be created.
        $table = new xmldb_table('block_mdrtr_guide_templates');

        // Adding fields to table block_mdrtr_guide_templates.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('name', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('organization', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('description', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('template', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('templateformat', XMLDB_TYPE_INTEGER, '3', null, null, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, null, null, null);

        // Adding keys to table block_mdrtr_guide_templates.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Conditionally launch create table for block_mdrtr_guide_templates.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define table block_mdrtr_guide_guides to be created.
        $table = new xmldb_table('block_mdrtr_guide_guides');

        // Adding fields to table block_mdrtr_guide_guides.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('name', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('templateid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('creatorid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, null, null, null);

        // Adding keys to table block_mdrtr_guide_guides.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Conditionally launch create table for block_mdrtr_guide_guides.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define table block_mdrtr_guide_contents to be created.
        $table = new xmldb_table('block_mdrtr_guide_contents');

        // Adding fields to table block_mdrtr_guide_contents.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('guideid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('value', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('valueformat', XMLDB_TYPE_INTEGER, '3', null, null, null, null);
        $table->add_field('placeholdernumber', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('placeholdertype', XMLDB_TYPE_CHAR, '50', null, null, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, null, null, null);

        // Adding keys to table block_mdrtr_guide_contents.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Conditionally launch create table for block_mdrtr_guide_contents.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Moderator_guide savepoint reached.
        upgrade_block_savepoint(true, 2016110305, 'moderator_guide');
    }

    if ($oldversion < 2016110306) {

        // Define field hidden to be added to block_mdrtr_guide_templates.
        $table = new xmldb_table('block_mdrtr_guide_templates');
        $field = new xmldb_field('hidden', XMLDB_TYPE_INTEGER, '1', null, null, null, '1', 'timemodified');

        // Conditionally launch add field hidden.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Moderator_guide savepoint reached.
        upgrade_block_savepoint(true, 2016110306, 'moderator_guide');
    }

    if ($oldversion < 2016110307) {

        // Define field hidden to be added to block_mdrtr_guide_guides.
        $table = new xmldb_table('block_mdrtr_guide_guides');
        $field = new xmldb_field('hidden', XMLDB_TYPE_INTEGER, '1', null, null, null, '0', 'timemodified');

        // Conditionally launch add field hidden.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Moderator_guide savepoint reached.
        upgrade_block_savepoint(true, 2016110307, 'moderator_guide');
    }

    if ($oldversion < 2016110308) {

        // Changing type of field placeholdernumber on table block_mdrtr_guide_contents to char.
        $table = new xmldb_table('block_mdrtr_guide_contents');
        $field = new xmldb_field('placeholdernumber', XMLDB_TYPE_CHAR, '255', null, null, null, null, 'valueformat');

        // Launch change of type for field placeholdernumber.
        $dbman->change_field_type($table, $field);

        // Rename field placeholderid on table block_mdrtr_guide_contents to NEWNAMEGOESHERE.
        $table = new xmldb_table('block_mdrtr_guide_contents');
        $field = new xmldb_field('placeholdernumber', XMLDB_TYPE_CHAR, '255', null, null, null, null, 'valueformat');

        // Launch rename field placeholderid.
        $dbman->rename_field($table, $field, 'placeholderid');

        // Define index guideplaceholder (unique) to be added to block_mdrtr_guide_contents.
        $table = new xmldb_table('block_mdrtr_guide_contents');
        $index = new xmldb_index('guideplaceholder', XMLDB_INDEX_UNIQUE, array('guideid', 'placeholderid'));

        // Conditionally launch add index guideplaceholder.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Moderator_guide savepoint reached.
        upgrade_block_savepoint(true, 2016110308, 'moderator_guide');
    }

    if ($oldversion < 2016110309) {

        // Define field defaultguidename to be added to block_mdrtr_guide_templates.
        $table = new xmldb_table('block_mdrtr_guide_templates');
        $field = new xmldb_field('defaultguidename', XMLDB_TYPE_CHAR, '255', null, null, null, null, 'hidden');

        // Conditionally launch add field defaultguidename.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Moderator_guide savepoint reached.
        upgrade_block_savepoint(true, 2016110309, 'moderator_guide');
    }

    if ($oldversion < 2017010900) {

        // Migrate setting which was set in core instead of the plugin.
        $restriction = get_config(null, 'blockmoderatorguiderestriction');
        if ($restriction !== false) {
            set_config('restriction', $restriction, 'block_moderator_guide');
            unset_config('blockmoderatorguiderestriction', null);
        }

        upgrade_block_savepoint(true, 2017010900, 'moderator_guide');
    }

    if ($oldversion < 2017072600) {

        // Define field cancomplete to be added to block_mdrtr_guide_templates.
        $table = new xmldb_table('block_mdrtr_guide_templates');
        $field = new xmldb_field('cancomplete', XMLDB_TYPE_INTEGER, '1', null, null, null, '0', 'defaultguidename');

        // Conditionally launch add field cancomplete.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field canreview to be added to block_mdrtr_guide_templates.
        $table = new xmldb_table('block_mdrtr_guide_templates');
        $field = new xmldb_field('canreview', XMLDB_TYPE_INTEGER, '1', null, null, null, '0', 'cancomplete');

        // Conditionally launch add field canreview.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field completed to be added to block_mdrtr_guide_guides.
        $table = new xmldb_table('block_mdrtr_guide_guides');
        $field = new xmldb_field('completed', XMLDB_TYPE_INTEGER, '1', null, null, null, '0', 'hidden');

        // Conditionally launch add field completed.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field reviewed to be added to block_mdrtr_guide_guides.
        $table = new xmldb_table('block_mdrtr_guide_guides');
        $field = new xmldb_field('reviewed', XMLDB_TYPE_INTEGER, '1', null, null, null, '0', 'completed');

        // Conditionally launch add field reviewed.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field reviewcomment to be added to block_mdrtr_guide_guides.
        $table = new xmldb_table('block_mdrtr_guide_guides');
        $field = new xmldb_field('reviewcomment', XMLDB_TYPE_TEXT, null, null, null, null, null, 'reviewed');

        // Conditionally launch add field reviewcomment.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }


        // Moderator_guide savepoint reached.
        upgrade_block_savepoint(true, 2017072600, 'moderator_guide');
    }

    if ($oldversion < 2017073100) {

        // Define field completeduserid to be added to block_mdrtr_guide_guides.
        $table = new xmldb_table('block_mdrtr_guide_guides');
        $field = new xmldb_field('completeduserid', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'reviewcomment');

        // Conditionally launch add field completeduserid.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field completedtime to be added to block_mdrtr_guide_guides.
        $table = new xmldb_table('block_mdrtr_guide_guides');
        $field = new xmldb_field('completedtime', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'completeduserid');

        // Conditionally launch add field completedtime.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field reviewedtime to be added to block_mdrtr_guide_guides.
        $table = new xmldb_table('block_mdrtr_guide_guides');
        $field = new xmldb_field('reviewedtime', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'completedtime');

        // Conditionally launch add field reviewedtime.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field revieweduserid to be added to block_mdrtr_guide_guides.
        $table = new xmldb_table('block_mdrtr_guide_guides');
        $field = new xmldb_field('revieweduserid', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'reviewedtime');

        // Conditionally launch add field revieweduserid.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Moderator_guide savepoint reached.
        upgrade_block_savepoint(true, 2017073100, 'moderator_guide');
    }

    return true;
}
