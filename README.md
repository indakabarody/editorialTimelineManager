# Editorial Timeline Manager Plugin for OJS 3.3

**Editorial Timeline Manager** is a professional plugin for **Open Journal Systems (OJS) 3.3** designed to inspect, correct, and retroactively adjust editorial workflow dates, file upload timestamps, peer review timelines, editorial decisions, and stage discussions.

Copyright (c) 2026 Indaka Barody  
Distributed under the GNU General Public License v3 (GPLv3).

---

## Background & Problem Statement

In standard OJS 3.3:

1. **File Timestamps**: When files are uploaded to OJS (during Submission, Review, Revision, Copyediting, or Production), OJS records the server's current timestamp in `submission_files.created_at`. OJS provides **no user interface** in the editorial dashboard to edit these timestamps retroactively. When journals migrate historical papers, back-issues, or process papers where the editorial review took place earlier, the dates shown on file lists (e.g. _August 5, 2026_, _May 31, 2026_) remain fixed to the physical upload date.
2. **Review Assignment Timeline**: A reviewer assignment displays a completion timestamp (e.g. _Completed: 2026-08-05 05:01 AM_) stored in `review_assignments.date_completed`. Changing this date without aligning `date_assigned`, `date_notified`, and `date_confirmed` creates an illogical timeline where review completion precedes assignment.
3. **Stage Discussions (Queries & Notes)**: Production and stage discussions display timestamps from `queries.date_posted` and `notes.date_created`, completely separate from file timestamps.
4. **Editorial Decisions**: Decisions (_Revisions Required_, _Send to Review_, _Accept Submission_) store decision timestamps in `edit_decisions.date_decided`.

Until now, editors had to manually execute raw SQL queries via phpMyAdmin to fix these timestamps.

**Editorial Timeline Manager** solves this problem completely by providing an intuitive, safe, transaction-protected user interface directly inside OJS 3.3 to modify timestamps directly in the database.

---

## Key Features

- **Comprehensive File Timestamp Management (`submission_files`)**:
  - View and edit `created_at` (upload date) and `updated_at` (modification date) for all workflow files:
    - Submission Files (`file_stage = 2`)
    - Review Files (`file_stage = 4`)
    - Reviewer Files / Attachments (`file_stage = 5`)
    - Revisions (`file_stage = 15`)
    - Copyediting Draft Files (`file_stage = 6`)
    - Copyedited Files (`file_stage = 9`)
    - Production Ready Files (`file_stage = 11`)
    - Galleys / Proof Files (`file_stage = 10`)
  - **Bulk Stage Setter**: Set all files belonging to a specific stage (or all files in the submission) to a uniform date in one click.
  - Option to update only `created_at` or synchronize `updated_at`.
  - Quick timestamp replicator between files.

- **Peer Review Timeline Alignment (`review_assignments`)**:
  - Full control over review dates per reviewer:
    - Date Assigned (`date_assigned`)
    - Date Notified (`date_notified`)
    - Date Confirmed (`date_confirmed`)
    - Date Due (`date_due`)
    - Date Completed (`date_completed`)
    - Date Acknowledged (`date_acknowledged`)
  - **Auto-Chronology Alignment**: Automatically aligns the review sequence (`Assigned` &le; `Notified` &le; `Confirmed` &le; `Completed` &le; `Acknowledged`) with logical intervals.
  - **Real-Time Chronology Warnings**: Detects and highlights any timeline paradoxes (e.g., review completed before assignment).

- **Editorial Decisions Timeline (`edit_decisions`)**:
  - Adjust decision dates (`date_decided`) for decisions across all review rounds and stages.

- **Stage Discussions & Notes Timeline (`queries` & `notes`)**:
  - Adjust timestamps for discussion threads (`queries.date_posted`) and individual discussion notes (`notes.date_created`, `notes.date_modified`).

- **Core Submission & Publication Dates**:
  - Edit Submission Date (`submissions.date_submitted`).
  - Edit Publication Date (`publications.date_published`).

- **Batch Timeline Shift / Offset**:
  - Shift an entire submission's timeline backward or forward by &plusmn; _N_ days, preserving the natural relative intervals between all events.

- **Activity Log Manager (`event_log` & `event_log_settings`)**:
  - Dedicated tab to view, inspect, modify, and delete submission activity log entries.
  - Edit log timestamps (`date_logged`), attribute actions to any journal or submission user (`user_id` / username), and customize event messages.
  - 1-click direct database updates, entry deletion, adding new log entries, and batch cleanup.
  - Timeline date adjustments do not write unwanted clutter to the activity log.

- **Safety & Atomic Database Transactions**:
  - All database writes are executed inside atomic transactions (`Capsule::connection()->transaction(...)`).
  - Strict role authorization (Journal Managers, Sub-Editors, and Site Administrators).

---

## Installation

### Method 1: Directory Installation

1. Copy or clone this plugin directory into your OJS installation:
   ```bash
   cp -r editorialTimelineManager /path/to/ojs/plugins/generic/editorialTimelineManager
   ```
2. Ensure appropriate file permissions:
   ```bash
   chown -R www-data:www-data /path/to/ojs/plugins/generic/editorialTimelineManager
   ```
3. Log in to OJS as Journal Manager or Site Administrator.
4. Navigate to **Settings > Website > Plugins > Installed Plugins**.
5. Locate **Editorial Timeline Manager** under **Generic Plugins** and enable it by checking the box.

---

## Usage

### 1. Within Submission Workflow

When viewing any submission in the editorial workflow (`workflow/index/{submissionId}`):

- A new **Editorial Timeline** tab appears in the workflow tab navigation bar.
- Click the tab to load the timeline manager for the current submission.

### 2. From Plugin Settings

- Navigate to **Settings > Website > Plugins**.
- Under **Editorial Timeline Manager**, click **Timeline Manager**.
- A submission search dialog opens:
  - Enter a Submission ID (e.g., `12345`) or search by title.
  - Click **Manage** next to any submission to open its timeline manager.

### 3. Dedicated URL

Journal Managers can also access the interface directly via:

```
https://your-journal-domain.com/index.php/{journal_path}/editorialTimeline/manage?submissionId=12345
```

---

## Compatibility

- **OJS Version**: Open Journal Systems 3.3.0-0 through 3.3.0-23
- **PHP Version**: PHP 7.3, 7.4, 8.0, 8.1, 8.2+
- **Database**: MySQL 5.7+, MariaDB 10.2+, PostgreSQL 9.6+
- **Locales Included**: English (`en_US`), Indonesian (`id_ID`)

---

## License

This plugin is free and open-source software licensed under the **GNU General Public License version 3 (GPLv3)**.
Copyright &copy; 2026 Indaka Barody.
