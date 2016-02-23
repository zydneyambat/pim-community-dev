@javascript
Feature: Update user preferences
  In order for users to be able to choose their preferences
  As an administrator
  I need to synchronize user preferences with the catalog configuration

  Background:
    Given an "apparel" catalog configuration
    And I am logged in as "Peter"

  Scenario: Successfully delete a tree used by a user
    Given I edit the "Julia" user
    And I visit the "Additional" tab
    Then I should see the text "Default tree"
    And I should see the text "2013 collection"
<<<<<<< HEAD
    When I am on the "tablet" channel page
    Then I should see the Code, English (United States), Currencies, Locales and Category tree fields
    And I fill in the following information:
      | Category tree | 2014 collection |
    And I press the "Save" button
    Then I should not see the text "There are unsaved changes."
=======
    When I edit the "tablet" channel
    And I change the "Category tree" to "2014 collection"
    And I save the channel
    And I should see the flash message "Channel successfully saved"
>>>>>>> 5ebd467... New hash nav
    And I edit the "2013_collection" category
    And I press the "Delete" button
    And I confirm the deletion
    And I edit the "Julia" user
    And I visit the "Additional" tab
<<<<<<< HEAD
    Then I should see the text "Default tree (required) 2014 collection"
=======
    Then I should see the text "Default tree"
    And I should see the text "2014 collection"
    And I should not see "2013 collection"
>>>>>>> 5ebd467... New hash nav

  Scenario: Successfully delete a channel used by a user
    Given I edit the "Peter" user
    And I visit the "Additional" tab
    Then I should see the text "Catalog scope"
    And I should see the text "Print"
<<<<<<< HEAD
    When I am on the "Print" channel page
=======
    When I edit the "Print" channel
>>>>>>> 5ebd467... New hash nav
    And I press the "Delete" button
    And I confirm the deletion
    And I edit the "Peter" user
    And I visit the "Additional" tab
<<<<<<< HEAD
    Then I should see the text "Catalog scope (required) Ecommerce"
=======
    Then I should see the text "Catalog scope"
    And I should see the text "Ecommerce"
    And I should not see "Print"
>>>>>>> 5ebd467... New hash nav

  Scenario: Successfully disable a locale used by a user
    Given I edit the "Julia" user
    And I visit the "Additional" tab
    And I change the "Catalog locale" to "fr_FR"
    And I save the user
    Then I should see the flash message "User saved"
    And I should not see the text "There are unsaved changes."
    When I visit the "Additional" tab
    Then I should see the text "Catalog locale"
    And I should see the text "fr_FR"
    When I am on the "ecommerce" channel page
    And I press the "Delete" button
    And I confirm the deletion
    And I edit the "Julia" user
    And I visit the "Additional" tab
<<<<<<< HEAD
    Then I should see the text "Catalog locale (required) de_DE"
=======
    Then I should see the text "Catalog locale"
    And I should see the text "de_DE"
    And I should not see "fr_FR"
>>>>>>> 5ebd467... New hash nav
