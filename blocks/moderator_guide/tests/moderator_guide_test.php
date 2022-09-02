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
 * Moderator Guide tests.
 * To run it: vendor/bin/phpunit blocks/moderator_guide/test/moderator_guide_test.php
 *
 * @package    block_moderator_guide
 * @category   test
 * @copyright  2017 onwards Coventry University {@link http://www.coventry.ac.uk/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Jerome Mouneyrac - Bepaw Pty Ltd <jerome@mouneyrac.com>
 */

defined('MOODLE_INTERNAL') || die();

use block_moderator_guide\template_base;

/**
 * Moderator Guide tests class.
 *
 * @package    block_moderator_guide
 * @category   test
 * @copyright  2017 onwards Coventry University {@link http://www.coventry.ac.uk/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Jerome Mouneyrac - Bepaw Pty Ltd <jerome@mouneyrac.com>
 */
class block_moderator_guide_testcase extends advanced_testcase {
    /** @var stdClass Keeps course object */
    private $course;

    /**
     * Setup test data.
     */
    public function setUp() {
        $this->resetAfterTest();
        $this->setAdminUser();

        // Create course.
        $this->course = $this->getDataGenerator()->create_course();
    }

    /**
     * Test block_moderator_guide_parse_template().
     */
    public function test_guide_parse_template() {
        global $CFG;

        // Create a template.
        $templatepart0 = "<h3>Grading advices for external grader</h3>
            <p>if you are an external grader this document will help you to know how to grade this course.</p>
            <p>You can know more about <a href=\"http://www.google.com\">UK grading requirement</a> on the government site.</p>
            <p>";
        $templatepart1 = "[1:html]";
        $templatepart2 = "</p>
            <p>We recommend you read these following files:</p>
            <p>";
        $templatepart3 = "[2:files]";
        $templatepart4 = "</p>
            <p><br /></p>";
        $templatepart5 = "[3:html:BEGIN]";
        // All the placeholders in the following line should be ignored and considered as HTML.
        $templatepart5default = "<div>Some HTML</div> [5:files] [6:html] [3:html] [1:html]";
        $templatepart5closing = "[3:html:END]";
        $templatepart6 = "[4:link]"; // id = 15 for this test.
        $templatepart7 = "[5:link:BEGIN]";
        // All the placeholders in the following line should be ignored and considered as HTML.
        $templatepart7default = "A default title for the link [5:files] [6:html] [3:html:END] [4:link]";
        $templatepart7closing = "[5:link:END]";

        $template = $templatepart0
            . $templatepart1
            . $templatepart2
            . $templatepart3
            . $templatepart4
            . $templatepart5
            . $templatepart5default
            . $templatepart5closing
            . $templatepart6
            . $templatepart7
            . $templatepart7default
            . $templatepart7closing;

        // Parse the template.
        $tpl = new \block_moderator_guide\template_generic((object) ['template' => $template]);
        $templatearray = $tpl->parse();

        // Checking that the template parsing result contains the expected values.
        $this->assertEquals(0, $templatearray[0]['id']);
        $this->assertEquals('static', $templatearray[0]['input']);
        $this->assertEquals($templatepart0, $templatearray[0]['value']);

        $this->assertEquals(1, $templatearray[1]['id']);
        $this->assertEquals('html', $templatearray[1]['input']);
        $this->assertEquals($templatepart1, $templatearray[1]['value']);

        $this->assertEquals(2, $templatearray[2]['id']);
        $this->assertEquals('static', $templatearray[2]['input']);
        $this->assertEquals($templatepart2, $templatearray[2]['value']);

        $this->assertEquals(3, $templatearray[3]['id']);
        $this->assertEquals('files', $templatearray[3]['input']);
        $this->assertEquals($templatepart3, $templatearray[3]['value']);

        $this->assertEquals(4, $templatearray[4]['id']);
        $this->assertEquals('static', $templatearray[4]['input']);
        $this->assertEquals($templatepart4, $templatearray[4]['value']);

        $this->assertEquals(5, $templatearray[5]['id']);
        $this->assertEquals('html', $templatearray[5]['input']);
        $this->assertEquals($templatepart5, $templatearray[5]['value']);
        $this->assertEquals($templatepart5default, $templatearray[5]['default']);

        // The id is going to be 15 here, because all placeholders, and spaces between placeholder, in
        // the default HTML cause the regex to add a new element (the algorythm just ignores these elements).
        $this->assertEquals(15, $templatearray[15]['id']);
        $this->assertEquals('link', $templatearray[15]['input']);
        $this->assertEquals($templatepart6, $templatearray[15]['value']);

        $this->assertEquals('15_linkname', $templatearray['15_linkname']['id']);
        $this->assertEquals('linkname', $templatearray['15_linkname']['input']);
        $this->assertEquals($templatepart6.'_linkname', $templatearray['15_linkname']['value']);

        $this->assertEquals(16, $templatearray[16]['id']);
        $this->assertEquals('link', $templatearray[16]['input']);
        $this->assertEquals($templatepart7, $templatearray[16]['value']);

        $this->assertEquals('16_linkname', $templatearray['16_linkname']['id']);
        $this->assertEquals('linkname', $templatearray['16_linkname']['input']);
        $this->assertEquals($templatepart7.'_linkname', $templatearray['16_linkname']['value']);
        $this->assertEquals($templatepart7default, $templatearray['16_linkname']['default']);
    }

    public function test_compare_template_contents() {
        // Create a template.
        $templatepart0 = "<h3>Grading advices for external grader</h3>
            <p>if you are an external grader this document will help you to know how to grade this course.</p>
            <p>You can know more about <a href=\"http://www.google.com\">UK grading requirement</a> on the government site.</p>
            <p>";
        $templatepart1 = "[1:html]";
        $templatepart2 = "</p>
            <p>We recommend you read these following files:</p>
            <p>";
        $templatepart3 = "[2:files]";
        $templatepart4 = "</p>
            <p><br /></p>";
        $templatepart5 = "[3:html:BEGIN]";
        // All the placeholders in the following line should be ignored and considered as HTML.
        $templatepart5default = "<div>Some HTML</div> [5:files] [6:html] [3:html] [1:html]";
        $templatepart5closing = "[3:html:END]";
        $templatepart6 = "[4:link]"; // id = 15 for this test.
        $templatepart7 = "[5:link:BEGIN]";
        // All the placeholders in the following line should be ignored and considered as HTML.
        $templatepart7default = "A default title for the link [5:files] [6:html] [3:html:END] [4:link]";
        $templatepart7closing = "[5:link:END]";

        $template = $templatepart0
            . $templatepart1
            . $templatepart2
            . $templatepart3
            . $templatepart4
            . $templatepart5
            . $templatepart5default
            . $templatepart5closing
            . $templatepart6
            . $templatepart7
            . $templatepart7default
            . $templatepart7closing;

        // Our comparison base.
        $tpl1 = new \block_moderator_guide\template_generic((object) ['template' => $template]);

        // They are the same.
        $tpl2 = new \block_moderator_guide\template_generic((object) ['template' => $template]);
        $this->assertTrue(template_base::are_template_contents_similar($tpl1, $tpl2));

        // They should still match on static, or default change.
        $template = $templatepart0
            . $templatepart1
            . $templatepart2
            . $templatepart3
            . $templatepart4
            . $templatepart5
            . "I'm just in the default" . $templatepart5default
            . $templatepart5closing
            . $templatepart6
            . $templatepart7
            . $templatepart7default
            . $templatepart7closing
            . "Something static";

        $tpl2 = new \block_moderator_guide\template_generic((object) ['template' => $template]);
        $this->assertTrue(template_base::are_template_contents_similar($tpl1, $tpl2));

        // Now I remove a field, so they are different.
        $template = $templatepart0
            // . $templatepart1
            . $templatepart2
            . $templatepart3
            . $templatepart4
            . $templatepart5
            . $templatepart5default
            . $templatepart5closing
            . $templatepart6
            . $templatepart7
            . $templatepart7default
            . $templatepart7closing;

        $tpl2 = new \block_moderator_guide\template_generic((object) ['template' => $template]);
        $this->assertFalse(template_base::are_template_contents_similar($tpl1, $tpl2));

        // Now I add a field, so they are different.
        $template = $templatepart0
            . $templatepart1
            . $templatepart2
            . $templatepart3
            . $templatepart4
            . $templatepart5
            . $templatepart5default
            . $templatepart5closing
            . $templatepart6
            . $templatepart7
            . $templatepart7default
            . $templatepart7closing
            . 'Here [6:html] Yay!';

        $tpl2 = new \block_moderator_guide\template_generic((object) ['template' => $template]);
        $this->assertFalse(template_base::are_template_contents_similar($tpl1, $tpl2));

        // Now I change a field, so they are different.
        $template = $templatepart0
            . '[1:files]'
            . $templatepart2
            . $templatepart3
            . $templatepart4
            . $templatepart5
            . $templatepart5default
            . $templatepart5closing
            . $templatepart6
            . $templatepart7
            . $templatepart7default
            . $templatepart7closing;

        $tpl2 = new \block_moderator_guide\template_generic((object) ['template' => $template]);
        $this->assertFalse(template_base::are_template_contents_similar($tpl1, $tpl2));
    }
}
