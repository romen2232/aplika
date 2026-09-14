@fixtures
Feature: Base fixtures

  Scenario: Load base reference data
    Given the database has directly the following users:
      | id                                   | email              | password                                                           | roles          | created_at         | updated_at         |
      | 00000000-0000-0000-0000-000000000001 | admin@joblog.com   | $2y$13$ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuv         | ["ROLE_ADMIN"] | 2026-01-01 00:00:00| 2026-01-01 00:00:00|
      | 00000000-0000-0000-0000-000000000002 | test@joblog.com    | $2y$13$ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuv         | ["ROLE_USER"]  | 2026-01-01 00:00:00| 2026-01-01 00:00:00|

  Scenario: Load users via API
    Given the following users are registered via API:
      | email              | password     |
      | alice@joblog.com   | SecurePass1  |
      | bob@joblog.com     | SecurePass2  |
