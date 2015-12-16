@javascript @performance
Feature: Edit a product
  In order to be productive
  As a regular user
  I need to be able to display the product edit form quickly

  Scenario: Successfully edit a product
    Given I am on the login screen
    And I fill in "_username" with "admin"
    And I fill in "_password" with "admin"
    And I press "Log in"
    And I wait for the "dashboard" to appear
    And I follow "Manage Products"
    And I wait for the "product grid" to appear
    And I click on the first line of the product grid
    And I wait for the "product edit form" to appear

