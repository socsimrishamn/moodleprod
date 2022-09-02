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
 * Block moderator guide backup steplib.
 *
 * @package    block_moderator_guide
 * @copyright  2017 onwards Coventry University {@link http://www.coventry.ac.uk/}
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Block moderator guide backup structure step class.
 *
 * @package    block_moderator_guide
 * @copyright  2017 onwards Coventry University {@link http://www.coventry.ac.uk/}
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_moderator_guide_block_structure_step extends backup_block_structure_step {

    /**
     * Define structure.
     */
    protected function define_structure() {
        global $DB;

        $manager = new \block_moderator_guide\manager();
        $userinfo = $this->get_setting_value('users');

        // Define each element separated.
        $guidesconfig = new backup_nested_element('guides');
        $guideconfig = new backup_nested_element('guide', ['id'], [
            'name',
            'templateid',
            'creatorid',
            'hidden',
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
        $templatesconfig = new backup_nested_element('templates');
        $templatefields = [
            'name',
            'organization',
            'description',
            'template',
            'templateformat',
            'hidden',
            'defaultguidename',
            'cancomplete',
            'canreview'
        ];
        $templateconfig = new backup_nested_element('template', ['id'], $templatefields);

        // Prepare the structure. Note, that the templates MUST come first, in order to be restored first.
        $blockconfig = $this->prepare_block_structure($templatesconfig);
        $blockconfig->add_child($guidesconfig);
        $templatesconfig->add_child($templateconfig);
        $guidesconfig->add_child($guideconfig);
        $guideconfig->add_child($guidecontentsconfig);
        if ($userinfo) {
            $guidecontentsconfig->add_child($guidecontentconfig);
        }

        // Define sources.
        $guidestbl = $manager->get_guides_table();
        $contentstbl = $manager->get_guide_contents_table();
        $templatestbl = $manager->get_templates_table();

        $guideconfig->set_source_table($guidestbl, ['courseid' => backup::VAR_COURSEID]);
        if ($userinfo) {
            $guidecontentconfig->set_source_table($contentstbl, ['guideid' => backup::VAR_PARENTID]);
        }

        $sqlfields = implode(', t.', $templatefields);
        $sql = "SELECT DISTINCT(t.id), t.$sqlfields
                  FROM {{$guidestbl}} g
                  JOIN {{$templatestbl}} t
                    ON t.id = g.templateid
                 WHERE g.courseid = :courseid";
        $templateconfig->set_source_sql($sql, ['courseid' => backup::VAR_COURSEID]);

        // Annotations.
        $guideconfig->annotate_ids('user', 'creatorid');

        // File annotations. Note that we can't use $manager->get_*_context(), so we dupe the manager's logic.
        $templatescontext = context_system::instance();
        $templateconfig->annotate_files($manager->get_component(), 'template', 'id', $templatescontext->id);

        // Return the root element.
        return $blockconfig;
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
class backup_moderator_guide_contents_files_step extends backup_execution_step {

    /**
     * Define execution of guide contents files step.
     */
    public function define_execution() {
        global $DB;

        $userinfo = $this->get_setting_value('users');
        if (!$userinfo) {
            return;
        }

        // Note that we're copying the logic from manager_base::get_guide_context() here.
        $context = context_course::instance($this->get_courseid());
        $manager = new \block_moderator_guide\manager();
        $component = $manager->get_component();
        $guidetbl = $manager->get_guides_table();

        // Find all relevant guides.
        // TODO Possible improvement by limiting the query to guides containing placeholders supporting files (editor, files).
        $rs = $DB->get_recordset($guidetbl, ['courseid' => $this->get_courseid()]);
        foreach ($rs as $record) {
            // Annotate the content files for each guide.
            backup_structure_dbops::annotate_files($this->get_backupid(), $context->id, $component, null, $record->id);
        }
        $rs->close();
    }

}
