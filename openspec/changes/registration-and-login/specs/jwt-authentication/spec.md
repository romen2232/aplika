# JWT Authentication Specification (Delta)

## Purpose

This spec documents modifications to the existing JWT authentication infrastructure to integrate with the new domain User aggregate and repository, replacing the in-memory user provider.

## ADDED Requirements

### Requirement: JwtAuthenticator Loads User from Repository

The JwtAuthenticator SHALL load the domain User from UserRepository using the ID extracted from the JWT token, then map it to the Symfony Security User.

#### Scenario: Authenticator successfully loads user from repository

- GIVEN a valid JWT token containing user ID "abc-123"
- AND a user exists in the repository with ID "abc-123"
- WHEN the JwtAuthenticator processes the token
- THEN the authenticator SHALL call UserRepository.findById("abc-123")
- AND the authenticator SHALL map the domain User to Infrastructure\Security\User
- AND the Security\User SHALL contain the user's ID, email, and roles

#### Scenario: Authenticator fails when user not found in repository

- GIVEN a valid JWT token containing user ID "abc-123"
- AND no user exists in the repository with ID "abc-123"
- WHEN the JwtAuthenticator processes the token
- THEN the authenticator SHALL throw an AuthenticationException
- AND the request SHALL be rejected with 401 Unauthorized

### Requirement: Security Configuration Uses Custom User Provider

The security.yaml configuration SHALL be updated to use a custom user provider that loads users from the repository, replacing the in-memory provider.

#### Scenario: Security firewall uses custom user provider

- GIVEN the security.yaml configuration
- WHEN the application boots
- THEN the firewall SHALL use the custom user provider (not users_in_memory)
- AND the custom provider SHALL delegate to UserRepository

#### Scenario: Password hasher is configured for User entity

- GIVEN the security.yaml configuration
- WHEN password hashing is performed
- THEN the hasher SHALL use the configured algorithm (auto/bcrypt/argon2i)
- AND the hasher SHALL work with the domain User entity via the Security\User adapter

## MODIFIED Requirements

### Requirement: JwtAuthenticator User Extraction

The JwtAuthenticator SHALL extract user identity from the JWT token AND load the full User entity from the repository, instead of only extracting claims without persistence lookup.

(Previously: JwtAuthenticator extracted id, email, and roles from token claims and built a Security\User directly, without repository lookup.)

#### Scenario: Token validation and user loading

- GIVEN a valid JWT token
- WHEN the JwtAuthenticator processes the token
- THEN the TokenValidator SHALL validate the token signature and expiration
- AND the TokenUserExtractor SHALL extract id, email, and roles from claims
- AND the UserRepository SHALL be queried to load the full User entity
- AND the Security\User SHALL be built from the domain User

## REMOVED Requirements

### Requirement: In-Memory User Provider

The `users_in_memory` provider configuration SHALL be removed from security.yaml, as it is replaced by the custom repository-based provider.

(Reason: In-memory provider does not support persistent users; repository-based provider is required for registration and login.)
(Migration: None — the in-memory provider was a placeholder with no actual users configured.)
