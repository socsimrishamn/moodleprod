@mod @mod_courseguide
Feature: Add a template to the course guide plugin
  In order to let teachers create a guide
  As a user
  I need to add a template in the template manager page

  Background:
    # the change window size is just for me to test in my specific environment and avoiding the MDL-52970 bug.
    Given I change window size to "large"
    Given the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1 | 0 |
    And the following "users" exist:
      | username | firstname | lastname | email |
      | teacher1 | Main | Teacher | teacher1@example.com |
      | externalteacher | External | Teacher | externalteacher@example.com |
      | student1 | Amy | Student | student1@example.com |
    And the following "course enrolments" exist:
      | user | course | role |
      | teacher1 | C1 | editingteacher |
      | externalteacher | C1 | teacher |
      | student1 | C1 | student |

  @javascript
  Scenario: Add a template

    # ADDING A TEMPLATE
    When I log in as "admin"
    And I navigate to "Course guide" node in "Site administration > Plugins > Activity modules"
    And I follow "Manage templates"
    And I press "Add template"
    And I set the field "Name" to "Behat Template"
    And I set the field "Suggested guide name" to "Behat guide"
    And I set the field "Template" to "<h1>Some hardcoded HTML</h1> and some text and some placeholders: [1:html] [2:html:BEGIN]some default[2:html:END] [3:files] [4:link] [5:link:BEGIN]default link name[5:link:END]"
    # This test was failing during my run if you don't manually scroll to the button during the test.
    # It is obviously a selenium (v2.53.1) + firefox (47.0.1) bug and likely not going to happen on other version than
    # the one I am using. See https://tracker.moodle.org/browse/MDL-52970.
    And I press "Add template"
    Then I should see "Behat Template"

    # SHOWING A TEMPLATE
    When I follow "Show"
    Then I should see "Hide"

    # PREVIEW A TEMPLATE
    When I follow "Preview"
    Then I should see "Some hardcoded HTML"
    When I follow "Manage templates"
    Then I should see "Behat Template"

    # CREATING A GUIDE
    When I log out
    When I log in as "teacher1"
    And I follow "Course 1"
    Then I should not see "Course guide test"
    Given I turn editing mode on
    And I add a "Course guide" to section "1" and I fill the form with:
      | Course guide name | Course guide test |
    Then I follow "Course guide test"
    Then I should see "Click on"
    Then I press "Edit guide"
    Then the "Template" select box should contain "Behat Template"
    And the "value" attribute of "#id_name" "css_element" should contain "Course guide test"
    And I should see "Some hardcoded HTML"
    And I should see "some default" in the "#id_field_3editable" "css_element"
    And I set the field with xpath "//*[@id='id_field_9']" to "http://www.coventry.ac.uk/"
    And I set the field with xpath "//*[@id='id_field_9_linkname']" to "Coventry University"
    And I set the field with xpath "//*[@id='id_field_11']" to "https://moodle.org"
    When I press "Add guide"
    Then I should see "Some hardcoded HTML"

    # ACCESS GUIDE AS EXTERNAL TEACHER
    When I log out
    And I log in as "externalteacher"
    And I follow "Course 1"
    And I follow "Course guide test"
    Then I should see "Some hardcoded HTML"
    And I should see "some default"
    And I should see "Coventry University"
    And I should see "default link name"

    # CAN STILL ACCESS GUIDE WHEN TEMPLATE IS HIDDEN
    When I log out
    And I log in as "admin"
    And I navigate to "Course guide" node in "Site administration > Plugins > Activity modules"
    And I follow "Manage templates"
    And I follow "Hide"
    Then I should see "Show"
    When I log out
    And I log in as "externalteacher"
    And I follow "Course 1"
    And I follow "Course guide test"
    Then I should see "Some hardcoded HTML"

    # CAN NOT CREATE A GUIDE WHEN TEMPLATE IS HIDDEN
    When I log out
    And I log in as "teacher1"
    And I follow "Course 1"
    And I follow "Course guide test"
    And I press "Edit guide"
    Then I should see "No templates"

    # ADDING AN ORGANISATION IN THE PLUGIN (AND LATER TO THE TEMPLATE) BUT NOT SETTING USER PROFILE -> EXTERNAL TEACHER CAN SEE GUIDE
    When I log out
    And I log in as "admin"
    And I navigate to "Course guide" node in "Site administration > Plugins > Activity modules"
    And I follow "Manage templates"
    # re-show the template
    And I follow "Show"
    And I follow "Course guide"
    And I set the field "Restrict by Custom Profile Field" to "faculty"
    And I press "Save changes"
    And I navigate to "User profile fields" node in "Site administration > Users > Accounts"
    And I set the field with xpath "//*[@class='select autosubmit singleselect']" to "Text input"
    And I set the field "Short name (must be unique)" to "faculty"
    And I set the field "Name" to "Faculty"
    And I press "Save changes"
    And I log out
    And I log in as "externalteacher"
    And I follow "Course 1"
    And I follow "Course guide test"
    Then I should see "Some hardcoded HTML"
    When I log out
    And I log in as "admin"
    And I navigate to "Course guide" node in "Site administration > Plugins > Activity modules"
    And I follow "Manage templates"
    And I follow "Edit"
    And I set the field "Organization" to "FACULTY1"
    And I press "Save changes"
    And I log out
    And I log in as "externalteacher"
    And I follow "Course 1"
    And I follow "Course guide test"
    Then I should see "Some hardcoded HTML"

    # ADDING A DIFFERENT USER PROFILE ORGANISATION -> EXT TEACHER CAN NOT SEE THE GUIDE & TEACHER CAN NOT PICK THE TEMPLATE (TEMPLATE SET TO SHOW)
    When I log out
    And I log in as "admin"
    And I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I set the field with xpath "//*[@id='id_realname']" to "main"
    And I press "Add filter"
    And I click on "#users a[title='Edit'] img" "css_element"
    And I click on "Expand all" "link_or_button"
    And I set the field "Faculty" to "FACULTY987"
    And I press "Update profile"
    And I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I press "Remove all filters"
    And I set the field with xpath "//*[@id='id_realname']" to "external"
    And I press "Add filter"
    And I click on "#users a[title='Edit'] img" "css_element"
    And I click on "Expand all" "link_or_button"
    And I set the field "Faculty" to "FACULTY987"
    And I press "Update profile"
    And I log out
    And I log in as "teacher1"
    And I follow "Course 1"
    And I follow "Course guide test"
    Then I should see "You can not access the guide"
    And I press "Edit guide"
    And I should see "No templates"

    # ADDING THE SAME USER PROFILE ORGANISATION -> EXT TEACHER CAN SEE THE GUIDE & TEACHER CAN PICK THE TEMPLATE (TEMPLATE SET TO SHOW)
    When I log out
    And I log in as "admin"
    And I navigate to "Course guide" node in "Site administration > Plugins > Activity modules"
    And I follow "Manage templates"
    And I follow "Edit"
    And I set the field "Organization" to "FACULTY987"
    And I press "Save changes"
    And I log out
    And I log in as "teacher1"
    And I follow "Course 1"
    And I follow "Course guide test"
    And I should see "Some hardcoded HTML"
    And I press "Edit guide"
    Then I should not see "No templates"

    # CHECK WE CAN NOT DELETE THE TEMPLATE WHEN A GUIDE EXISTS
    When I log out
    And I log in as "admin"
    And I navigate to "Course guide" node in "Site administration > Plugins > Activity modules"
    And I follow "Manage templates"
    Then I follow "Guides"

    # DELETING THE GUIDE AND THE TEMPLATE
    When I log out
    And I log in as "admin"
    And I follow "Course 1"
    Given I turn editing mode on
    And I delete "Course guide test" activity
    And I navigate to "Course guide" node in "Site administration > Plugins > Activity modules"
    And I follow "Manage templates"
    And I follow "Delete"
    And I press "Delete"
    Then I should not see "Behat template"
