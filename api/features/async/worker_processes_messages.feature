Feature: Async message processing
  In order to handle background tasks
  As the system
  I need to dispatch and consume async messages

  Scenario: Dispatching an async message and processing it
    Given the application is running in the "test" environment
    When the worker processes pending messages
    Then the application is running in the "test" environment
