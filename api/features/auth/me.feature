Feature: Get current user profile

  Scenario: Authenticated user retrieves their profile
    Given I am authenticated as "admin@aplika.com"
    When I request "GET" "/api/me"
    Then the response status code should be 200
    And the response should contain JSON:
      """
      {
      "id": "@string@",
      "email": "admin@aplika.com",
      "roles": ["ROLE_USER", "ROLE_ADMIN"]
      }
      """

  Scenario: Unauthenticated user cannot access profile
    When I request "GET" "/api/me"
    Then the response status code should be 401
