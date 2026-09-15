Feature: User registration

  Scenario: Successful registration with valid data
    When I register with email "newuser@example.com" and password "password123"
    Then the response status code should be 201
    And the response should contain JSON:
      """
      {
      "id": "@string@",
      "email": "newuser@example.com"
      }
      """

  Scenario: Registration fails with duplicate email
    When I register with email "test@aplika.com" and password "AnotherPass456"
    Then the response status code should be 409
    And the response should contain JSON:
      """
      {
      "error": "Email already registered"
      }
      """

  Scenario: Registration fails with invalid email format
    When I register with email "not-an-email" and password "password123"
    Then the response status code should be 400
    And the response should contain JSON:
      """
      {
      "error": "Invalid email format"
      }
      """

  Scenario: Registration fails with weak password
    When I register with email "user@example.com" and password "short"
    Then the response status code should be 400
    And the response should contain JSON:
      """
      {
      "error": "Password must be at least 8 characters"
      }
      """

  Scenario: Registration fails with missing email
    When I register with email "" and password "password123"
    Then the response status code should be 400
    And the response should contain JSON:
      """
      {
      "error": "Email is required"
      }
      """
