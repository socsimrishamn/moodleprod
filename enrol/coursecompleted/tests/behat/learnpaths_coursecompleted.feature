@enrol @ewallah @enrol_coursecompleted
Feature: Learnpaths with course completion enrolment

  Background:
    Given the following "courses" exist:
      | fullname | shortname | startdate     | enddate                    | enablecompletion |
      | Course 1 | C1        | ##yesterday## | ##tomorrow##               | 1                |
      | Course 2 | C2        | ##tomorrow##  | ##last day of next month## | 1                |
      | Course 3 | C3        | ##tomorrow##  | ##last day of next month## | 1                |
      | Course 4 | C4        | ##tomorrow##  | ##last day of next month## | 1                |
    And the following "users" exist:
      | username |
      | user1    |
    And the following "course enrolments" exist:
      | user     | course | role    |
      | user1    | C1     | student |
    And I log in as "admin"
    And I navigate to "Plugins > Enrolments > Manage enrol plugins" in site administration
    And I click on "Disable" "link" in the "Guest access" "table_row"
    And I click on "Disable" "link" in the "Self enrolment" "table_row"
    And I click on "Disable" "link" in the "Cohort sync" "table_row"
    And I click on "Enable" "link" in the "Course completed enrolment" "table_row"

  @javascript
  Scenario: Learning paths with course completion enrolments
    When I am on "Course 2" course homepage
    And I navigate to "Users > Enrolment methods" in current page administration
    And I select "Course completed enrolment" from the "Add method" singleselect
    And I set the following fields to these values:
       | Course | Course 1 |
    And I press "Add method"
    And I am on "Course 3" course homepage
    And I navigate to "Users > Enrolment methods" in current page administration
    And I select "Course completed enrolment" from the "Add method" singleselect
    And I set the following fields to these values:
       | Course | Course 2 |
    And I press "Add method"
    And I am on "Course 4" course homepage
    And I navigate to "Users > Enrolment methods" in current page administration
    And I select "Course completed enrolment" from the "Add method" singleselect
    And I set the following fields to these values:
       | Course | Course 3 |
    And I press "Add method"
    And I am on "Course 4" course homepage
    And I log out
    When I am on the "C2" "Course" page logged in as "user1"
    Then I should see "You will be enrolled in this course when"
    And I am on the "C3" "Course" page
    Then I should see "You will be enrolled in this course when"
    And I am on the "C3" "Course" page
    Then I should see "You will be enrolled in this course when"
