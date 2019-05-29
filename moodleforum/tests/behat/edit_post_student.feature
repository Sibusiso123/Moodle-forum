@mod @mod_moodleforum
Feature: Students can edit or delete their moodleforum posts within a set time limit
  In order to refine moodleforum posts
  As a user
  I need to edit or delete my moodleforum posts within a certain period of time after posting

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email |
      | student1 | Student | 1 | student1@example.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1 | 0 |
    And the following "course enrolments" exist:
      | user | course | role |
      | student1 | C1 | student |
    And the following "activities" exist:
      | activity       | name                     | intro                            | course  | idnumber       |
      | moodleforum | Test moodleforum name | Test moodleforum description  | C1      | moodleforum |
    And I log in as "student1"
    And I follow "C1"
    And I add a new discussion to "Test moodleforum name" moodleforum with:
      | Subject | moodleforum post subject |
      | Message | This is the body |

  Scenario: Edit moodleforum post
    Given I follow "moodleforum post subject"
    And I follow "Edit"
    When I set the following fields to these values:
      | Subject | Edited post subject |
      | Message | Edited post body |
    And I press "Save changes"
    And I wait to be redirected
    Then I should see "Edited post subject"
    And I should see "Edited post body"

  Scenario: Delete moodleforum post
    Given I follow "moodleforum post subject"
    When I follow "Delete"
    And I press "Continue"
    Then I should not see "moodleforum post subject"