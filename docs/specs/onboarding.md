# Onboarding Spec

> Collect job search preferences after registration to personalize the experience.

## Status

Draft

## Overview

After a user registers, they are prompted to complete an onboarding form that collects their job search preferences. This information personalizes the experience and provides context for future features (job matching, recommendations, etc.).

The onboarding is **skippable** — users can defer completion and return to it later.

---

## Product Spec

### User Flow

1. User registers → auto-logged in (existing behavior)
2. If `onboardingCompleted === false` → redirect to `/onboarding`
3. User completes form → saved to Candidate profile → redirect to `/dashboard`
4. If user skips → goes to dashboard, but on next visit redirected back to onboarding
5. User can complete onboarding from a settings/profile page later

### Form Fields

| Field | Input Type | Required | Validation |
|-------|-----------|----------|------------|
| Target roles | Tag input (free-text, comma-separated) | Yes | Min 1 role |
| Target locations | Tag input (free-text, comma-separated) | Yes | Min 1 location |
| Salary range | Min number + Max number + Currency dropdown | No | Min ≤ Max if both set |
| Work mode | Checkboxes: Remote / Hybrid / On-site | No | — |
| Employment type | Checkboxes: Full-time / Contract / Part-time | No | — |

### Completion Behavior

- **Submit**: Save preferences → set `onboardingCompleted = true` → redirect to `/dashboard`
- **Skip**: Set `onboardingCompleted = false` → redirect to `/dashboard` → show "Complete your profile" banner
- **Next visit**: If `onboardingCompleted === false`, redirect to `/onboarding`

---

## Technical Spec

### Backend — Candidate Module

```
api/src/Candidate/
├── Domain/
│   ├── Candidate.php                    (aggregate root)
│   ├── CandidateRepository.php          (interface)
│   └── ValueObject/
│       └── SalaryRange.php              (min, max, currency)
├── Application/
│   ├── Command/
│   │   └── CompleteOnboarding/
│   │       ├── CompleteOnboarding.php
│   │       └── CompleteOnboardingHandler.php
│   └── Query/
│       └── GetCandidateProfile/
│           ├── GetCandidateProfile.php
│           ├── GetCandidateProfileHandler.php
│           └── CandidateProfileReadModel.php
└── Infrastructure/
    ├── Controller/
    │   └── OnboardingController.php     (PUT /api/candidate/onboarding)
    └── Persistence/
        ├── DoctrineCandidateRepository.php
        └── CandidateModel.php           (Doctrine mapping)
```

### Domain Model

**Candidate Aggregate**:
```php
Candidate
├── id: Uuid                    // same as User.id
├── targetRoles: string[]
├── targetLocations: string[]
├── salaryRange: ?SalaryRange
├── workModes: string[]         // remote, hybrid, onsite
├── employmentTypes: string[]   // full-time, contract, part-time
├── onboardingCompleted: bool
├── createdAt: DateTimeImmutable
└── updatedAt: DateTimeImmutable
```

**SalaryRange Value Object**:
```php
SalaryRange
├── min: ?int
├── max: ?int
└── currency: string            // ISO 4217 (EUR, USD, etc.)
```

### API Contract

**Update GetMe response** (existing endpoint):
```
GET /api/me
Response: {
  id: string,
  email: string,
  roles: string[],
  onboardingCompleted: boolean    // NEW
}
```

**Complete onboarding**:
```
PUT /api/candidate/onboarding
Authorization: Required
Body: {
  targetRoles: string[],
  targetLocations: string[],
  salaryRange?: { min: int, max: int, currency: string },
  workModes: string[],
  employmentTypes: string[]
}
Response: 200 { onboardingCompleted: true }
```

**Validation rules**:
- `targetRoles`: required, min 1 item
- `targetLocations`: required, min 1 item
- `salaryRange`: optional, but if present must have `min`, `max`, `currency` and `min ≤ max`
- `workModes`: optional, array of strings
- `employmentTypes`: optional, array of strings

### Frontend

**New page**: `frontend/app/[lang]/onboarding/page.tsx`

**Redirect logic** in `frontend/proxy.ts`:
```typescript
// After locale redirect, check onboarding status
if (user is authenticated && !onboardingCompleted && pathname !== '/onboarding') {
  redirect to `/${locale}/onboarding`
}
```

**AuthContext update**:
- Fetch `onboardingCompleted` from `/api/me`
- Store in auth state
- Expose to components

**Components**:
- `OnboardingForm.tsx` — form with all fields
- `TagInput.tsx` — reusable tag input component (comma-separated)
- `SalaryRangeInput.tsx` — min/max/currency inputs

**i18n**: Add dictionary entries for `en` and `es` under `onboarding` key.

### Database Schema

```sql
CREATE TABLE candidate (
    id UUID PRIMARY KEY,
    target_roles JSONB NOT NULL DEFAULT '[]',
    target_locations JSONB NOT NULL DEFAULT '[]',
    salary_min INT,
    salary_max INT,
    salary_currency VARCHAR(3),
    work_modes JSONB NOT NULL DEFAULT '[]',
    employment_types JSONB NOT NULL DEFAULT '[]',
    onboarding_completed BOOLEAN NOT NULL DEFAULT FALSE,
    created_at TIMESTAMP WITH TIME ZONE NOT NULL,
    updated_at TIMESTAMP WITH TIME ZONE NOT NULL
);

-- Foreign key to user table
ALTER TABLE candidate ADD CONSTRAINT fk_candidate_user
    FOREIGN KEY (id) REFERENCES "user"(id) ON DELETE CASCADE;
```

### Testing

**Backend (PHPSpec)**:
- Candidate can complete onboarding with required fields
- Candidate cannot complete onboarding without target roles
- Candidate cannot complete onboarding without target locations
- Salary range validation (min ≤ max)
- Candidate can skip onboarding (onboardingCompleted remains false)

**Frontend (Vitest)**:
- Form validation (required fields)
- Tag input behavior
- Salary range validation
- Redirect logic when onboarding not completed

**E2E (Playwright)**:
- Register → redirect to onboarding → complete → redirect to dashboard
- Register → skip onboarding → redirect to dashboard → revisit → redirect to onboarding

---

## Implementation Phases

| Phase | Scope | Key Deliverables |
|-------|-------|-----------------|
| 1 | Domain | Candidate aggregate, PHPSpec tests |
| 2 | API | Commands, queries, controller, update GetMe |
| 3 | Frontend | `/onboarding` page, redirect logic, form UI |
| 4 | Integration | E2E tests, i18n, polish |

---

## Future Enhancements

- **Profile settings page**: Allow users to edit onboarding preferences later
- **Progressive onboarding**: Show onboarding as a multi-step wizard instead of single form
- **Smart defaults**: Pre-fill fields based on user behavior or imported data
- **Localization**: Support more languages beyond `en` and `es`

---

## Related

- [ADR 001: Domain-Driven Design](../adr/001-domain-driven-design.md)
- [ADR 003: CQRS](../adr/003-cqrs.md)
- [Job Pipeline Spec](./job-pipeline.md)
