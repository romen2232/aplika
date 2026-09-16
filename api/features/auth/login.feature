Feature: User login

  Scenario: Successful login with valid credentials
    When I login with email "test@aplika.com" and password "password123"
    Then the response status code should be 200
    And the response should have a cookie named "access_token"
    And the response should have a cookie named "refresh_token"

  Scenario: Login fails with incorrect password
    When I login with email "test@aplika.com" and password "WrongPass"
    Then the response status code should be 401
    And the response should contain JSON:
      """
      {
      "error": "Invalid credentials"
      }
      """

  Scenario: Login fails with non-existent user
    When I login with email "missing@example.com" and password "AnyPass123"
    Then the response status code should be 401
    And the response should contain JSON:
      """
      {
      "error": "Invalid credentials"
      }
      """

  Scenario: Login fails with missing password
    When I login with email "test@aplika.com" and no password
    Then the response status code should be 400
    And the response should contain JSON:
      """
      {
      "error": "Password is required"
      }
      """

  Scenario: Login fails with invalid email format
    When I login with email "not-an-email" and password "password123"
    Then the response status code should be 400
    And the response should contain JSON:
      """
      {
      "error": "Invalid email format"
      }
      """

  Scenario: Cookie-authenticated user can access protected endpoint
    When I login with email "test@aplika.com" and password "password123"
    Then the response status code should be 200
    When I request "GET" "/api/me"
    Then the response status code should be 200
    And the response should contain JSON:
      """
      {
      "id": "@string@",
      "email": "test@aplika.com",
      "roles": ["ROLE_USER"]
      }
      """
