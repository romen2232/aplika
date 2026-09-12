Feature: Get current user profile

  Scenario: Authenticated user retrieves their profile
    Given there is a user with email "john@example.com" and password "secret123"
    And I am authenticated as "john@example.com"
    When I request "GET" "/api/me"
    Then the response status code should be 200
    And the response should contain JSON:
      """
      {
        "id": "@string@",
        "email": "john@example.com",
        "roles": ["ROLE_USER"]
      }
      """

  Scenario: Unauthenticated user cannot access profile
    When I request "GET" "/api/me"
    Then the response status code should be 401
