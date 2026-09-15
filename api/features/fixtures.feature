@fixtures
Feature: Base fixtures

  Scenario: Load base reference data
    Given the database has directly the following users:
      | id                                   | email            | roles                       | created_at          | updated_at          |
      | 00000000-0000-0000-0000-000000000001 | admin@aplika.com | ["ROLE_USER", "ROLE_ADMIN"] | 2026-01-01 00:00:00 | 2026-01-01 00:00:00 |
      | 00000000-0000-0000-0000-000000000002 | test@aplika.com  | ["ROLE_USER"]               | 2026-01-01 00:00:00 | 2026-01-01 00:00:00 |

  Scenario: Load users via API
    Given the following users are registered via API:
      | email            |
      | alice@aplika.com |
      | bob@aplika.com   |
