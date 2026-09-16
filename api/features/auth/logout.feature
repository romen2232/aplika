Feature: User logout

  Scenario: Successful logout revokes the session and clears both cookies
    When I login with email "test@aplika.com" and password "password123"
    Then the response status code should be 200
    When I logout
    Then the response status code should be 200
    And the response should not have a cookie named "access_token" with a value
    And the response should not have a cookie named "refresh_token" with a value

  Scenario: Logout invalidates the refresh token
    When I login with email "test@aplika.com" and password "password123"
    Then the response status code should be 200
    And I save the current refresh token cookie
    When I logout
    Then the response status code should be 200
    When I refresh with the saved refresh token
    Then the response status code should be 401

  Scenario: Logout without a session is idempotent
    When I request "POST" "/api/auth/logout"
    Then the response status code should be 200
    And the response should not have a cookie named "access_token" with a value
    And the response should not have a cookie named "refresh_token" with a value
