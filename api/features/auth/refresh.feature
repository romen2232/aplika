Feature: Token refresh

  Scenario: Valid refresh token rotation
    When I login with email "test@aplika.com" and password "password123"
    Then the response status code should be 200
    When I refresh my token
    Then the response status code should be 200
    And the response should have a cookie named "access_token"
    And the response should have a cookie named "refresh_token"

  Scenario: Reused refresh token revokes entire family
    When I login with email "test@aplika.com" and password "password123"
    Then the response status code should be 200
    And I save the current refresh token cookie
    When I refresh my token
    Then the response status code should be 200
    When I refresh with the saved refresh token
    Then the response status code should be 401

  Scenario: Missing refresh token returns 401
    When I request "POST" "/api/auth/refresh"
    Then the response status code should be 401
