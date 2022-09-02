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
 * Define all the backup steps that will be used by the backup_courseguide_activity_task
 *
 * @package   mod_courseguide
 * @category  backup
 * @copyright  2017 onwards Coventry University {@link http://www.coventry.ac.uk/}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author  Jerome Mouneyrac <jerome@bepaw.com>
 */

defined('MOODLE_INTERNAL') || die;

/**
 * Define the complete courseguide structure for backup, with file and id annotations
 *
 * @package   mod_courseguide
 * @category  backup
 * @copyright  2017 onwards Coventry University {@link http://www.coventry.ac.uk/}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author  Jerome Mouneyrac <jerome@bepaw.com>
 */
class backup_courseguide_activity_structure_step extends backup_activity_structure_step {

    /**
     * Defines the backup structure of the module
     *
     * @return backup_nested_element
     */
    protected function define_structure() {
        $manager = new \mod_courseguide\manager();

        // Get know if we are including userinfo.
        $userinfo = $this->get_setting_value('userinfo');

        // Define the root element describing the courseguide instance.
        $courseguide = new backup_nested_element('courseguide', array('id'), array(
            'name', 'intro', 'introformat', 'grade'));

        // Additional config.
        $guideconfig = new backup_nested_element('guide', ['id'], [
            'templateid',
            'courseguideid',
            'displaymode',
            'creatorid',
            'completed',
            'reviewed',
            'reviewcomment',
            'completeduserid',
            'completedtime',
            'reviewedtime',
            'revieweduserid'
        ]);
        $guidecontentsconfig = new backup_nested_element('contents');
        $guidecontentconfig = new backup_nested_element('content', ['id'], [
            'guideid',
            'value',
            'valueformat',
            'placeholderid',
            'placeholdertype',
        ]);
        $templatefields = [
            'name',
            'organization',
            'description',
            'template',
            'templateformat',
            'hidden',
            'defaultguidename',
            'displaymode',
            'defaultdisplaymode',
            'cancomplete',
            'canreview'
        ];
        $templateconfig = new backup_nested_element('template', ['id'], $templatefields);

        // Prepare the final structure.
        $structure = $this->prepare_activity_structure($courseguide);
        $structure->add_child($templateconfig);
        $structure->add_child($guideconfig);
        $guideconfig->add_child($guidecontentsconfig);
        $guidecontentsconfig->add_child($guidecontentconfig);

        // Define data sources.
        $guidestbl = $manager->get_guides_table();
        $contentstbl = $manager->get_guide_contents_table();
        $templatestbl = $manager->get_templates_table();

        $courseguide->set_source_table('courseguide', array('id' => backup::VAR_ACTIVITYID));
        $guideconfig->set_source_table($guidestbl, ['courseguideid' => backup::VAR_ACTIVITYID]);
        $guidecontentconfig->set_source_table($contentstbl, ['guideid' => backup::VAR_PARENTID]);

        $sqlfields = implode(', t.', $templatefields);
        $sql = "SELECT t.id, t.$sqlfields
                  FROM {{$templatestbl}} t
                  JOIN {{$guidestbl}} g
                    ON t.id = g.templateid
                 WHERE g.courseguideid = :id";
        $templateconfig->set_source_sql($sql, ['id' => backup::VAR_ACTIVITYID]);

        // Annotate IDs.
        $guideconfig->annotate_ids('user', 'creatorid');

        // Define file annotations (we do not use itemid in this example).
        $courseguide->annotate_files('mod_courseguide', 'intro', null);

        // Note that we can't use $manager->get_*_context(), so we dupe the manager's logic.
        $templatescontext = context_system::instance();
        $templateconfig->annotate_files($manager->get_component(), 'template', 'id', $templatescontext->id);

        return $structure;
    }
}

/**
 * Guide contents files step.
 *
 * Why this? Because to annotate the files we need to know the name of the filearea
 * in which the files are stored. But because the file areas are dynamically generated
 * we have to manually use backup_structure_dbops::annotate_files, rather than relying
 * on the more common backup_structure_step::annotate_files().
 *
 * @package    block_moderator_guide
 * @copyright  2017 onwards Coventry University {@link http://www.coventry.ac.uk/}
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_courseguide_contents_files_step extends backup_execution_step {

    public function define_execution() {
        global $DB;

        $userinfo = $this->get_setting_value('users');
        if (!$userinfo) {
            return;
        }

        // Note that we're copying the logic from manager_base::get_guide_context() here.
        $context = context_course::instance($this->get_courseid());
        $manager = new \mod_courseguide\manager();
        $component = $manager->get_component();
        $guidetbl = $manager->get_guides_table();

        // Find the guide.
        $guide = $DB->get_record($guidetbl, ['courseguideid' => $this->task->get_activityid()], '*', IGNORE_MISSING);
        if ($guide) {
            backup_structure_dbops::annotate_files($this->get_backupid(), $context->id, $component, null, $guide->id);
        }
    }

}
