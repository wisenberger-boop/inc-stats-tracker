# INC Stats Tracker

Tracks TYFCB (Thank You for Closed Business), referrals, connects, and member activity for INC reporting.

## Features

- Group roster view (read-only, sourced from BuddyBoss Group)
- TYFCB record entry and listing
- Referral record entry and listing
- Connect record entry and listing
- Basic admin reporting dashboard
- CSV import/export
- REST API for member summaries

## REST API

### GET /wp-json/inc-stats-tracker/v1/my-summary

Returns the logged-in user's fiscal-year summary of stats.

**Authentication**: Required (WordPress cookie or Application Password)

**Response**: JSON object with user's stats summary

## Version

1.0.9 - Consolidated the Group Stats KPI layout into Referrals, Closed Business, and Connects cards.

## Local Verification

Run the deterministic project checks from the repository root:

```powershell
powershell -NoProfile -File tools\verify-project.ps1
```

After packaging, include the ZIP inspection:

```powershell
powershell -NoProfile -File tools\verify-project.ps1 `
  -ZipPath build\releases\inc-stats-tracker-1.0.9.zip
```

These checks cover PHP syntax, release-version consistency, fiscal-year timezone regression
cases, runtime CSV staging hygiene, and release ZIP structure. They do not replace live
WordPress/BuddyBoss testing.

## Deployment

SSH/WP-CLI deployment uses explicit, separately configured Development and Production targets.
It is read-only by default and requires target-specific approval before live use. See
[`docs/deployment.md`](docs/deployment.md).
