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

1.0.6 - Added REST API endpoint for member summaries.