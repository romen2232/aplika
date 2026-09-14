# User Registration Specification

## Purpose

User registration enables new users to create accounts with email and password, establishing the foundation for authentication in Joblog.

## Requirements

### Requirement: Register User via API

The system SHALL accept POST requests to `/api/auth/register` with email and password, validate inputs, create a User entity, and return a 201 Created response.

#### Scenario: Successful registration with valid data

- GIVEN no user exists with email "user@example.com"
- WHEN POST /api/auth/register is called with `{"email": "user@example.com", "password": "SecurePass123"}`
- THEN the response status SHALL be 201 Created
- AND the response body SHALL contain `{"id": "<uuid>", "email": "user@example.com"}`
- AND a User entity SHALL be persisted with the given email and hashed password
- AND the password SHALL NOT be stored in plain text

#### Scenario: Registration fails with duplicate email

- GIVEN a user already exists with email "user@example.com"
- WHEN POST /api/auth/register is called with `{"email": "user@example.com", "password": "AnotherPass456"}`
- THEN the response status SHALL be 409 Conflict
- AND the response body SHALL contain `{"error": "Email already registered"}`
- AND no new User entity SHALL be created

#### Scenario: Registration fails with invalid email format

- GIVEN no user exists
- WHEN POST /api/auth/register is called with `{"email": "not-an-email", "password": "SecurePass123"}`
- THEN the response status SHALL be 400 Bad Request
- AND the response body SHALL contain `{"error": "Invalid email format"}`

#### Scenario: Registration fails with weak password

- GIVEN no user exists
- WHEN POST /api/auth/register is called with `{"email": "user@example.com", "password": "123"}`
- THEN the response status SHALL be 400 Bad Request
- AND the response body SHALL contain `{"error": "Password must be at least 8 characters"}`

#### Scenario: Registration fails with missing required fields

- GIVEN no user exists
- WHEN POST /api/auth/register is called with `{"email": "user@example.com"}`
- THEN the response status SHALL be 400 Bad Request
- AND the response body SHALL contain `{"error": "Password is required"}`

### Requirement: Domain User Aggregate Creation

The domain User aggregate MUST encapsulate email validation, password hashing, and identity generation without framework dependencies.

#### Scenario: User entity creation with valid email and password

- GIVEN a valid email "user@example.com" and plain password "SecurePass123"
- WHEN a User is registered via the domain
- THEN the User SHALL have a unique UUID identifier
- AND the email SHALL be validated as a proper email format
- AND the password SHALL be stored as a hash (not plain text)
- AND the User SHALL be marked as not yet verified (email verification deferred)

#### Scenario: User entity rejects invalid email

- GIVEN an invalid email "not-an-email"
- WHEN attempting to create a User
- THEN the domain SHALL throw an exception or return an error
- AND no User entity SHALL be created

#### Scenario: User entity rejects weak password

- GIVEN a password shorter than 8 characters
- WHEN attempting to create a User
- THEN the domain SHALL throw an exception or return an error
- AND no User entity SHALL be created

### Requirement: UserRepository Persistence

The system SHALL provide a UserRepository interface in the domain layer with a Doctrine implementation in infrastructure, enabling User persistence and retrieval.

#### Scenario: Save and retrieve User by email

- GIVEN a User entity with email "user@example.com"
- WHEN the User is saved via UserRepository
- AND findByEmail("user@example.com") is called
- THEN the same User entity SHALL be returned

#### Scenario: Save and retrieve User by ID

- GIVEN a User entity with ID "abc-123"
- WHEN the User is saved via UserRepository
- AND findById("abc-123") is called
- THEN the same User entity SHALL be returned

#### Scenario: findByEmail returns null for non-existent email

- GIVEN no user exists with email "missing@example.com"
- WHEN findByEmail("missing@example.com") is called
- THEN null SHALL be returned
