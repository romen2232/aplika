# Job Pipeline Spec

> Kanban board for managing the job search process with drag-and-drop status changes, job details, notes, tasks, timeline, and CV management.

## Status

Draft

## Overview

The Job Pipeline is a Kanban board that gives users a visual overview of their job search. Each job offer is represented as a card that can be moved between columns (status changes), opened into a detail panel, and acted upon with various features.

The pipeline is the core feature of Aplika — it transforms a list of job applications into an actionable workflow.

---

## Product Spec

### Board Layout

5 columns representing the job search stages:

```
Saved → Applied → Interview → Offer → Closed
```

**Status transitions**:
- Saved → Applied
- Saved → Closed
- Applied → Interview
- Applied → Closed
- Interview → Offer
- Interview → Closed
- Offer → Closed
- Closed → Saved (reopen)

### Job Card (on the board)

**Visible at a glance**:
- Job title
- Company name
- Location (icon + text)
- Days since last activity (e.g., "3d ago")
- Next action label + due date (red if overdue)
- CV indicator (has CV attached or not)

**Interactions**:
- **Drag-and-drop** between columns → changes status
- **Click** → opens detail panel (slide-in from right)
- **Quick actions** (context menu or hover): mark complete, add note, set next action

### Job Detail Panel

Opens as a **slide-in panel from the right** (preserves board context).

**Sections**:

1. **Header**
   - Title, company, status badge
   - External link to original offer (opens in new tab)

2. **Job Info**
   - Location, salary, employment type, work mode
   - Skills/tags
   - Description preview (expandable)

3. **Recruiter**
   - Name, email, phone (editable inline)

4. **Next Action** (prominent)
   - Rule-based suggestion (editable by user)
   - Due date
   - Edit inline

5. **Notes**
   - Add/edit/delete free-text notes
   - Markdown support (future)

6. **Tasks**
   - Checklist with optional due dates
   - Check to complete
   - Example: "Prepare technical interview (due: Sept 20)"

7. **Timeline**
   - Chronological feed of events
   - **Auto-generated**: "Status changed to Interview", "Application submitted", "Task completed"
   - **User-written**: "Talked to recruiter", "Sent follow-up email"
   - Follow-up items with due dates highlighted

8. **CV**
   - Dropdown to select from uploaded CVs
   - Shows which CV was sent for this application

9. **Meta**
   - Created date
   - Last updated
   - Applied date (if applicable)

### Adding Jobs

Three methods (MVP):

1. **URL Import**
   - User pastes URL → system extracts meta tags (title, description, og:image)
   - User confirms/edits extracted data
   - Saved as "Saved" status
   - Basic meta-tag extraction for MVP (AI-powered extraction later)

2. **Manual Form**
   - Traditional form with all fields
   - All fields editable

3. **Quick Add**
   - Minimal form: title + company + URL
   - Saved to "Saved" column

### Filters (on the board)

MVP filters (client-side):
- Search by company/title (text)
- By location (text)
- By date range (created, applied)
- Has next action due / overdue
- Has CV attached

### Next Action Rules (auto-suggested, user-editable)

| Condition | Suggestion |
|-----------|-----------|
| Saved > 7 days, not applied | "Apply to this position" |
| Applied > 14 days, no response | "Follow up with recruiter" |
| Interview scheduled (future) | "Prepare for interview" |
| Interview completed > 3 days | "Send thank you email" |
| Offer received | "Review offer details" |

User can accept, edit, or dismiss the suggestion.

### CV Management

- Upload CVs from a dedicated section or within job detail
- Each CV has a label (e.g., "Backend Symfony", "Full Stack React")
- Storage: Docker volume (dev), S3 (production)
- One CV associated per job offer
- Future: AI matching, auto-generation

---

## Technical Spec

### Domain Model

**JobOffer Aggregate** (Job module):

```php
JobOffer (Aggregate Root)
├── id: Uuid
├── ownerId: Uuid
├── status: JobOfferStatus
├── title: string
├── company: string
├── location: ?string
├── salary: ?SalaryRange
├── description: ?string
├── url: ?string
├── source: ?string
├── employmentType: ?string
├── workMode: ?string
├── skills: string[]
├── recruiterName: ?string
├── recruiterEmail: ?string
├── recruiterPhone: ?string
├── notes: Note[]
├── tasks: Task[]
├── timelineEvents: TimelineEvent[]
├── cvId: ?Uuid
├── nextAction: ?string
├── nextActionDueDate: ?DateTimeImmutable
├── createdAt: DateTimeImmutable
├── updatedAt: DateTimeImmutable
└── appliedAt: ?DateTimeImmutable
```

**Task** (Entity within JobOffer):
```php
Task
├── id: Uuid
├── title: string
├── description: ?string
├── dueDate: ?DateTimeImmutable
├── completed: bool
├── completedAt: ?DateTimeImmutable
└── createdAt: DateTimeImmutable
```

**Note** (Entity within JobOffer):
```php
Note
├── id: Uuid
├── content: string
├── createdAt: DateTimeImmutable
└── updatedAt: DateTimeImmutable
```

**TimelineEvent** (Entity within JobOffer):
```php
TimelineEvent
├── id: Uuid
├── type: 'auto' | 'manual'
├── title: string
├── description: ?string
├── occurredAt: DateTimeImmutable
└── dueDate: ?DateTimeImmutable
```

**JobOfferStatus** (Value Object):
```php
enum JobOfferStatus: string {
    case SAVED = 'saved';
    case APPLIED = 'applied';
    case INTERVIEW = 'interview';
    case OFFER = 'offer';
    case CLOSED = 'closed';
}
```

**Cv Aggregate** (new Cv module):
```php
Cv (Aggregate Root)
├── id: Uuid
├── ownerId: Uuid
├── label: string
├── filePath: string
├── originalFilename: string
├── mimeType: string
├── fileSize: int
├── createdAt: DateTimeImmutable
└── updatedAt: DateTimeImmutable
```

### Status Transitions (Domain Rules)

Enforced in the `JobOffer` aggregate:

```php
public function changeStatus(JobOfferStatus $newStatus): void
{
    $allowedTransitions = [
        JobOfferStatus::SAVED => [JobOfferStatus::APPLIED, JobOfferStatus::CLOSED],
        JobOfferStatus::APPLIED => [JobOfferStatus::INTERVIEW, JobOfferStatus::CLOSED],
        JobOfferStatus::INTERVIEW => [JobOfferStatus::OFFER, JobOfferStatus::CLOSED],
        JobOfferStatus::OFFER => [JobOfferStatus::CLOSED],
        JobOfferStatus::CLOSED => [JobOfferStatus::SAVED], // reopen
    ];

    if (!in_array($newStatus, $allowedTransitions[$this->status], true)) {
        throw InvalidStatusTransitionException::fromTo($this->status, $newStatus);
    }

    $this->status = $newStatus;
    $this->recordTimelineEvent('auto', "Status changed to {$newStatus->value}");

    if ($newStatus === JobOfferStatus::APPLIED && $this->appliedAt === null) {
        $this->appliedAt = new DateTimeImmutable();
    }
}
```

### Backend Structure

```
api/src/Job/
├── Domain/
│   ├── JobOffer.php
│   ├── JobOfferRepository.php
│   ├── JobOfferStatus.php
│   ├── ValueObject/
│   │   └── SalaryRange.php
│   └── Exception/
│       └── InvalidStatusTransitionException.php
├── Application/
│   ├── Command/
│   │   ├── CreateJobOffer/
│   │   ├── ImportJobFromUrl/
│   │   ├── UpdateJobOfferStatus/
│   │   ├── UpdateJobOffer/
│   │   ├── AddNote/
│   │   ├── DeleteNote/
│   │   ├── AddTask/
│   │   ├── CompleteTask/
│   │   ├── AddTimelineEvent/
│   │   ├── SetNextAction/
│   │   └── AssociateCv/
│   └── Query/
│       ├── ListJobOffers/
│       ├── GetJobOfferDetail/
│       └── GetJobOfferBoard/
└── Infrastructure/
    ├── Controller/
    │   ├── JobOfferController.php
    │   └── JobImportController.php
    ├── Persistence/
    │   ├── DoctrineJobOfferRepository.php
    │   └── DoctrineJobOfferReadRepository.php
    └── Service/
        └── UrlMetaExtractor.php

api/src/Cv/
├── Domain/
│   ├── Cv.php
│   ├── CvRepository.php
│   └── CvStorageInterface.php
├── Application/
│   ├── Command/
│   │   ├── UploadCv/
│   │   └── DeleteCv/
│   └── Query/
│       └── ListCvs/
└── Infrastructure/
    ├── Controller/
    │   └── CvController.php
    ├── Persistence/
    │   └── DoctrineCvRepository.php
    └── Storage/
        ├── LocalStorageAdapter.php      (Docker volume)
        └── S3StorageAdapter.php         (production)
```

### CQRS Read Models

**Board read model** (lightweight, for the Kanban view):
```php
JobOfferBoardItem
├── id: Uuid
├── title: string
├── company: string
├── location: ?string
├── status: JobOfferStatus
├── nextAction: ?string
├── nextActionDueDate: ?DateTimeImmutable
├── hasCv: bool
├── lastActivityAt: DateTimeImmutable
└── createdAt: DateTimeImmutable
```

**Detail read model** (full, for the side panel):
```php
JobOfferDetail
├── all JobOffer fields
├── notes: Note[]
├── tasks: Task[]
├── timelineEvents: TimelineEvent[]
├── cv: ?CvSummary
├── suggestedNextAction: ?string   (from rules engine)
└── suggestedNextActionDueDate: ?DateTimeImmutable
```

### API Endpoints

```
# Job Offers
POST   /api/jobs                        Create job offer (manual)
POST   /api/jobs/import                 Import from URL
GET    /api/jobs/board                  Board view (grouped by status)
GET    /api/jobs/{id}                   Detail view
PUT    /api/jobs/{id}                   Update job offer fields
PATCH  /api/jobs/{id}/status            Change status
POST   /api/jobs/{id}/notes             Add note
DELETE /api/jobs/{id}/notes/{noteId}    Delete note
POST   /api/jobs/{id}/tasks             Add task
PATCH  /api/jobs/{id}/tasks/{taskId}    Complete/uncomplete task
POST   /api/jobs/{id}/timeline          Add manual timeline event
PUT    /api/jobs/{id}/next-action       Set next action + due date
PUT    /api/jobs/{id}/cv                Associate CV

# CVs
POST   /api/cvs                         Upload CV
GET    /api/cvs                         List user's CVs
DELETE /api/cvs/{id}                    Delete CV
```

### URL Meta Extraction

Backend service `UrlMetaExtractor`:

```php
class UrlMetaExtractor
{
    public function extract(string $url): UrlMetaData
    {
        // 1. Fetch URL (with timeout, size limit)
        // 2. Parse HTML for:
        //    - <title>
        //    - <meta name="description">
        //    - <meta property="og:title">
        //    - <meta property="og:description">
        //    - <meta property="og:image">
        // 3. Return extracted data
    }
}
```

**UrlMetaData**:
```php
UrlMetaData
├── title: ?string
├── description: ?string
├── image: ?string
├── url: string
```

For MVP: simple DOM parsing. Future: AI-powered structured extraction.

### Next Action Rules Engine

```php
class NextActionSuggester
{
    public function suggest(JobOffer $jobOffer): ?SuggestedAction
    {
        $now = new DateTimeImmutable();

        // Saved > 7 days, not applied
        if ($jobOffer->status() === JobOfferStatus::SAVED
            && $jobOffer->createdAt()->diff($now)->days > 7) {
            return new SuggestedAction('Apply to this position', $now->modify('+3 days'));
        }

        // Applied > 14 days, no response
        if ($jobOffer->status() === JobOfferStatus::APPLIED
            && $jobOffer->appliedAt()->diff($now)->days > 14) {
            return new SuggestedAction('Follow up with recruiter', $now->modify('+2 days'));
        }

        // Interview scheduled (future)
        if ($jobOffer->status() === JobOfferStatus::INTERVIEW) {
            return new SuggestedAction('Prepare for interview', $now->modify('+1 day'));
        }

        // Interview completed > 3 days
        // (needs a way to track interview completion — future enhancement)

        // Offer received
        if ($jobOffer->status() === JobOfferStatus::OFFER) {
            return new SuggestedAction('Review offer details', $now->modify('+5 days'));
        }

        return null;
    }
}
```

### Frontend Structure

```
frontend/app/[lang]/
├── pipeline/
│   ├── page.tsx                        Server page (i18n)
│   └── PipelineContent.tsx             Client component
├── cvs/
│   ├── page.tsx
│   └── CvsContent.tsx

frontend/components/pipeline/
├── KanbanBoard.tsx                     Board layout, columns
├── KanbanColumn.tsx                    Single column with drop zone
├── JobCard.tsx                         Draggable card
├── JobDetailPanel.tsx                  Slide-in panel
├── NextActionBadge.tsx                 Next action display + edit
├── NotesSection.tsx
├── TasksSection.tsx
├── TimelineSection.tsx
├── AddJobModal.tsx                     Form + URL import
├── BoardFilters.tsx                    Filter bar
└── QuickAddButton.tsx

frontend/components/cvs/
├── CvList.tsx
├── CvUploadForm.tsx
└── CvCard.tsx
```

### Drag-and-Drop

Use `@dnd-kit/core` (React, accessible, good DX).

```typescript
function handleDragEnd(event: DragEndEvent) {
  const { active, over } = event;
  if (!over) return;

  const jobId = active.id as string;
  const newStatus = over.id as JobOfferStatus;

  // Optimistic update
  updateJobStatus(jobId, newStatus);

  // API call
  apiClient.updateJobStatus(jobId, newStatus);
}
```

### Database Schema

```sql
CREATE TABLE job_offer (
    id UUID PRIMARY KEY,
    owner_id UUID NOT NULL,
    status VARCHAR(20) NOT NULL,
    title VARCHAR(255) NOT NULL,
    company VARCHAR(255) NOT NULL,
    location VARCHAR(255),
    salary_min INT,
    salary_max INT,
    salary_currency VARCHAR(3),
    description TEXT,
    url TEXT,
    source VARCHAR(255),
    employment_type VARCHAR(50),
    work_mode VARCHAR(50),
    skills JSONB NOT NULL DEFAULT '[]',
    recruiter_name VARCHAR(255),
    recruiter_email VARCHAR(255),
    recruiter_phone VARCHAR(50),
    cv_id UUID,
    next_action TEXT,
    next_action_due_date TIMESTAMP WITH TIME ZONE,
    created_at TIMESTAMP WITH TIME ZONE NOT NULL,
    updated_at TIMESTAMP WITH TIME ZONE NOT NULL,
    applied_at TIMESTAMP WITH TIME ZONE
);

CREATE TABLE job_offer_note (
    id UUID PRIMARY KEY,
    job_offer_id UUID NOT NULL REFERENCES job_offer(id) ON DELETE CASCADE,
    content TEXT NOT NULL,
    created_at TIMESTAMP WITH TIME ZONE NOT NULL,
    updated_at TIMESTAMP WITH TIME ZONE NOT NULL
);

CREATE TABLE job_offer_task (
    id UUID PRIMARY KEY,
    job_offer_id UUID NOT NULL REFERENCES job_offer(id) ON DELETE CASCADE,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    due_date TIMESTAMP WITH TIME ZONE,
    completed BOOLEAN NOT NULL DEFAULT FALSE,
    completed_at TIMESTAMP WITH TIME ZONE,
    created_at TIMESTAMP WITH TIME ZONE NOT NULL
);

CREATE TABLE job_offer_timeline_event (
    id UUID PRIMARY KEY,
    job_offer_id UUID NOT NULL REFERENCES job_offer(id) ON DELETE CASCADE,
    type VARCHAR(10) NOT NULL, -- 'auto' or 'manual'
    title VARCHAR(255) NOT NULL,
    description TEXT,
    occurred_at TIMESTAMP WITH TIME ZONE NOT NULL,
    due_date TIMESTAMP WITH TIME ZONE
);

CREATE TABLE cv (
    id UUID PRIMARY KEY,
    owner_id UUID NOT NULL,
    label VARCHAR(255) NOT NULL,
    file_path TEXT NOT NULL,
    original_filename VARCHAR(255) NOT NULL,
    mime_type VARCHAR(100) NOT NULL,
    file_size INT NOT NULL,
    created_at TIMESTAMP WITH TIME ZONE NOT NULL,
    updated_at TIMESTAMP WITH TIME ZONE NOT NULL
);

-- Indexes
CREATE INDEX idx_job_offer_owner ON job_offer(owner_id);
CREATE INDEX idx_job_offer_status ON job_offer(status);
CREATE INDEX idx_cv_owner ON cv(owner_id);
```

### Testing

**Backend (PHPSpec)**:
- JobOffer can be created with required fields
- JobOffer status transitions (valid and invalid)
- JobOffer records timeline events on status change
- Task can be completed
- Note can be added/deleted

**Frontend (Vitest)**:
- KanbanBoard renders columns correctly
- JobCard displays all required info
- Drag-and-drop triggers status change
- Detail panel opens/closes correctly
- Form validation for adding jobs

**E2E (Playwright)**:
- Create job manually → appears in Saved column
- Import job from URL → appears in Saved column
- Drag job from Saved to Applied → status changes
- Open job detail → all sections visible
- Add note/task → appears in timeline
- Upload CV → can associate with job

---

## Implementation Phases

| # | Phase | Scope | Est. Complexity |
|---|-------|-------|-----------------|
| 1 | JobOffer Domain | Aggregate, status rules, value objects, PHPSpec | High |
| 2 | JobOffer API (CRUD) | Create, update, list, detail, status change | Medium |
| 3 | Kanban Frontend | Board, columns, cards, drag-and-drop | High |
| 4 | Job Detail Backend | Notes, tasks, timeline, next action commands | Medium |
| 5 | Job Detail Frontend | Side panel with all sections | High |
| 6 | URL Import | Backend extractor + frontend flow | Medium |
| 7 | CV Management | Upload, list, associate | Medium |
| 8 | Filters | Board filter bar | Low |
| 9 | Next Action Rules | Rule engine + suggestions | Medium |

---

## Future Enhancements

- **AI-powered URL extraction**: Parse job description, extract skills, requirements
- **CV matching**: AI analyzes job offer and suggests best CV
- **Cover letter generation**: AI generates cover letter tailored to the job
- **Browser extension**: Auto-save job from job portals
- **Email integration**: Auto-detect recruiter emails and link to jobs
- **Calendar integration**: Sync interviews with external calendar
- **Analytics**: Response rates, time in each stage, success metrics
- **Collaboration**: Share jobs with mentors/peers for feedback

---

## Related

- [ADR 001: Domain-Driven Design](../adr/001-domain-driven-design.md)
- [ADR 003: CQRS](../adr/003-cqrs.md)
- [ADR 005: JobOffer Unified Aggregate](../adr/005-job-offer-unified-aggregate.md)
- [Onboarding Spec](./onboarding.md)
