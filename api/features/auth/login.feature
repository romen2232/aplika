Feature: User login

  Scenario: Successful login with valid credentials
    Given there is a user with email "user@example.com" and password "SecurePass123"
    When I login with email "user@example.com" and password "SecurePass123"
    Then the response status code should be 200
    And the response should contain JSON:
      """
      {
        "token": "@string@"
      }
      """

  Scenario: Login fails with incorrect password
    Given there is a user with email "user@example.com" and password "SecurePass123"
    When I login with email "user@example.com" and password "WrongPass"
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
    Given there is a user with email "user@example.com" and password "SecurePass123"
    When I request "POST" "/api/auth/login"
    Then the response status code should be 400
    And the response should contain JSON:
      """
      {
        "error": "Password is required"
      }
      """

  Scenario: JWT token from login authenticates subsequent requests
    Given there is a user with email "auth@example.com" and password "SecurePass123"
    When I login with email "auth@example.com" and password "SecurePass123"
    Then the response status code should be 200
    When I request "GET" "/api/me"
    Then the response status code should be 200
    And the response should contain JSON:
      """
      {
        "id": "@string@",
        "email": "auth@example.com",
        "roles": ["ROLE_USER"]
      }
      """
