@mod @mod_moodleforum
Feature: A teacher can set one of 3 possible options for tracking read moodleforum posts
  In order to ease the moodleforum posts follow up
  As a user
  I need to distinct the unread posts from the read ones

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
    And I log in as "admin"
    And I am on site homepage
    And I follow "Course 1"
    And I turn editing mode on

  Scenario: Tracking moodleforum posts off
    Given I add a "moodleforum" to section "1" and I fill the form with:
      | moodleforum name | Test moodleforum name |
      | Description | Test moodleforum description |
      | Read tracking | Off |
    And I add a new discussion to "Test moodleforum name" moodleforum with:
      | Subject | Test post subject |
      | Message | Test post message |
    And I log out
    When I log in as "student1"
    And I am on "Course 1" course homepage
    Then I should not see "1 unread post"
    And I follow "Test moodleforum name"
    And I should not see "Track unread posts"

  Scenario: Tracking moodleforum posts optional
    Given I add a "moodleforum" to section "1" and I fill the form with:
      | moodleforum name | Test moodleforum name |
      | Description | Test moodleforum description |
      | Read tracking | Optional |
    And I add a new discussion to "Test moodleforum name" moodleforum with:
      | Subject | Test post subject |
      | Message | Test post message |
    And I log out
    When I log in as "student1"
    And I am on "Course 1" course homepage
    Then I should see "1 unread post"
    And I follow "Test moodleforum name"
    And I follow "Don't track unread posts"
    And I wait to be redirected
    And I follow "Course 1"
    And I should not see "1 unread post"
    And I follow "Test moodleforum name"
    And I follow "Track unread posts"
    And I wait to be redirected
    And I click on "1" "link" in the "Admin User" "table_row"
    And I follow "Course 1"
    And I should not see "1 unread post"

  Scenario: Tracking moodleforum posts forced
    Given the following config values are set as admin:
      | allowforcedreadtracking | 1 | moodleforum |
    And I am on site homepage
    And I follow "Course 1"
    Given I add a "moodleforum" to section "1" and I fill the form with:
      | moodleforum name | Test moodleforum name |
      | Description | Test moodleforum description |
      | Read tracking | Force |
    And I add a new discussion to "Test moodleforum name" moodleforum with:
      | Subject | Test post subject |
      | Message | Test post message |
    And I log out
    When I log in as "student1"
    And I am on "Course 1" course homepage
    Then I should see "1 unread post"
    And I follow "1 unread post"
    And I should not see "Don't track unread posts"
    And I follow "Test post subject"
    And I follow "Course 1"
    And I should not see "1 unread post"

  Scenario: Tracking moodleforum posts forced (with force disabled)
    Given the following config values are set as admin:
      | allowforcedreadtracking | 1 | moodleforum |
    And I am on site homepage
    And I follow "Course 1"
    Given I add a "moodleforum" to section "1" and I fill the form with:
      | moodleforum name | Test moodleforum name |
      | Description | Test moodleforum description |
      | Read tracking | Force |
    And I add a new discussion to "Test moodleforum name" moodleforum with:
      | Subject | Test post subject |
      | Message | Test post message |
    And the following config values are set as admin:
      | allowforcedreadtracking | 0 | moodleforum |
    And I log out
    When I log in as "student1"
    And I am on "Course 1" course homepage
    Then I should see "1 unread post"
    And I follow "Test moodleforum name"
    And I follow "Don't track unread posts"
    And I wait to be redirected
    And I follow "Course 1"
    And I should not see "1 unread post"
    And I follow "Test moodleforum name"
    And I follow "Track unread posts"
    And I wait to be redirected
    And I click on "1" "link" in the "Admin User" "table_row"
    And I follow "Course 1"
    And I should not see "1 unread post"
