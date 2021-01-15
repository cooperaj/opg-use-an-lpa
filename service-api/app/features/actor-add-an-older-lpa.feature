@actor @addAnOlderLpa
Feature: Add an older LPA
  As a user
  I can add a paper LPA registered before after 31st August 2020 to my account

  Background:
    Given I have been given access to use an LPA via a paper document
    And I am a user of the lpa application
    And I am currently signed in

  @integration @pact
  Scenario: The user can add an older LPA to their account
    Given I am on the add an older LPA page
    When I provide the details from a valid paper document
    And I confirm that those details are correct
    Then a letter is requested containing a one time use code

  @integration @pact
  Scenario: The user cannot add an older LPA to their account if they have an activation key
    Given I am on the add an older LPA page
    When I provide the details from a valid paper document
    And I confirm that those details are correct
    And I already have a valid activation key for my LPA
    Then I am told that I have an activation key for this LPA and where to find it

  @integration
  Scenario: The user cannot add an old LPA to their account as it does not exist
    Given I am on the add an older LPA page
    When I provide details that do not match the paper document
    Then I am informed that an LPA could not be found with these details
