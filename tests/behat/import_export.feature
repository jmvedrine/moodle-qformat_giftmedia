@qformat @qformat_giftmedia @javascript @_file_upload
Feature: Test importing questions from GIFT with medias format.
  In order to reuse questions with images and sound
  As an teacher
  I need to be able to import them in GIFT with medias format.

  Background:
    Given the following "courses" exist:
      | fullname | shortname | format |
      | Course 1 | C1        | topics |
    And the following "users" exist:
      | username | firstname |
      | teacher  | Teacher   |
    And the following "course enrolments" exist:
      | user    | course | role           |
      | teacher | C1     | editingteacher |
    And I log in as "teacher"
    And I am on "Course 1" course homepage

  Scenario: import one GIFT with medias question
    When I navigate to "Import" node in "Course administration > Question bank"
    And I set the field "id_format_giftmedia" to "1"
    And I upload "question/format/giftmedia/tests/fixtures/giftmedia_example.zip" file to "Import" filemanager
    And I press "id_submitbutton"
    Then I should see "Parsing questions from import file."
    And I should see "Importing 1 questions from file"
    And I should see "Listen to this"
    And I should see "What is it?"
    When I press "Continue"
    Then I should see "MC question with images and an MP3 file"
