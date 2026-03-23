# INC Stats Tracker — Database Schema

Tables are created by `IST_Activator::create_tables()` using `dbDelta()`.

**Identity layer:** All person references use `wp_users.ID` directly.
No plugin-managed member table exists. BuddyBoss Group membership defines
the active member set at runtime; it is not stored in plugin tables.

**Date fields:**
- `entry_date DATE` — user-supplied date of the business/reporting event. Used for all
  date-range filters and reports. Required on all stat records.
- `created_at DATETIME` — auto-set MySQL insert timestamp. Audit and entry-order use only.
  Never used as the reporting date.

**Snapshot fields (`_name` columns):**
Written once at insert time and never updated. Preserve the display name of any person
referenced by a `_user_id` column. Used as fallback in report views when a WP user
account has been deleted.

---

## `{prefix}ist_tyfcb`
Thank You for Closed Business records.

| Column | Type | Nullable | Notes |
|---|---|---|---|
| `id` | `BIGINT(20) UNSIGNED` | NOT NULL | Auto-increment PK |
| `submitted_by_user_id` | `BIGINT(20) UNSIGNED` | NOT NULL | WP user ID of the member reporting the closed business (the recipient of the business) |
| `submitted_by_name` | `VARCHAR(255)` | NOT NULL DEFAULT `''` | Snapshot of the reporting member's display name at insert time |
| `thank_you_to_type` | `VARCHAR(20)` | NOT NULL DEFAULT `'member'` | `member` = source is resolvable to a WP user; `other` = source cannot or should not be tied to a WP user ID. Always explicit; never inferred from NULL. |
| `thank_you_to_user_id` | `BIGINT(20) UNSIGNED` | NULL | WP user ID of the thanked source. Present when type = `member`; NULL when type = `other`. Does not require current group membership — past members with active WP accounts are valid. |
| `thank_you_to_name` | `VARCHAR(255)` | NOT NULL DEFAULT `''` | Always populated. Snapshot of thanked user's display name when type = `member`; free-text source name when type = `other`. Write-once. |
| `amount` | `DECIMAL(10,2)` | NOT NULL DEFAULT `0.00` | Dollar value of the closed business |
| `note` | `TEXT` | NULL | Optional |
| `entry_date` | `DATE` | NOT NULL | User-supplied date of the business event. Primary reporting date field. |
| `created_at` | `DATETIME` | NOT NULL DEFAULT CURRENT_TIMESTAMP | Auto-set insert timestamp. Audit only. |
| `created_by_user_id` | `BIGINT(20) UNSIGNED` | NOT NULL | WP user who physically entered the record. May differ from `submitted_by_user_id` when an admin enters on behalf. |

**Indexes:** `submitted_by_user_id`, `thank_you_to_user_id`, `entry_date`

---

## `{prefix}ist_referrals`
Referral records.

| Column | Type | Nullable | Notes |
|---|---|---|---|
| `id` | `BIGINT(20) UNSIGNED` | NOT NULL | Auto-increment PK |
| `referred_by_user_id` | `BIGINT(20) UNSIGNED` | NOT NULL | WP user ID of the group member who gave the referral |
| `referred_by_name` | `VARCHAR(255)` | NOT NULL DEFAULT `''` | Snapshot at insert time |
| `referred_to_name` | `VARCHAR(255)` | NOT NULL DEFAULT `''` | Always populated. Name of the person or business receiving the referral |
| `referred_to_user_id` | `BIGINT(20) UNSIGNED` | NULL | Nullable. WP user ID if the recipient is a group member. Schema support only; not surfaced in MVP frontend. |
| `status` | `VARCHAR(50)` | NOT NULL DEFAULT `'open'` | `open` \| `closed` \| `converted` |
| `note` | `TEXT` | NULL | Optional |
| `entry_date` | `DATE` | NOT NULL | User-supplied date of the referral event |
| `created_at` | `DATETIME` | NOT NULL DEFAULT CURRENT_TIMESTAMP | Auto-set insert timestamp. Audit only. |
| `created_by_user_id` | `BIGINT(20) UNSIGNED` | NOT NULL | WP user who entered the record |

**Indexes:** `referred_by_user_id`, `entry_date`

---

## `{prefix}ist_connects`
Connect (one-to-one or group meeting) records.

| Column | Type | Nullable | Notes |
|---|---|---|---|
| `id` | `BIGINT(20) UNSIGNED` | NOT NULL | Auto-increment PK |
| `member_user_id` | `BIGINT(20) UNSIGNED` | NOT NULL | WP user ID of the group member logging the connect |
| `member_display_name` | `VARCHAR(255)` | NOT NULL DEFAULT `''` | Snapshot at insert time |
| `connected_with_name` | `VARCHAR(255)` | NOT NULL DEFAULT `''` | Always populated. Name of the other party |
| `connected_with_user_id` | `BIGINT(20) UNSIGNED` | NULL | Nullable. WP user ID if the other party is a group member. Schema support only; not surfaced in MVP frontend. |
| `connect_type` | `VARCHAR(50)` | NOT NULL DEFAULT `'one-to-one'` | `one-to-one` \| `group` |
| `note` | `TEXT` | NULL | Optional |
| `entry_date` | `DATE` | NOT NULL | User-supplied date of the connect meeting |
| `created_at` | `DATETIME` | NOT NULL DEFAULT CURRENT_TIMESTAMP | Auto-set insert timestamp. Audit only. |
| `created_by_user_id` | `BIGINT(20) UNSIGNED` | NOT NULL | WP user who entered the record |

**Indexes:** `member_user_id`, `entry_date`

---

## Versioning
The installed DB version is stored in `get_option('ist_db_version')`.
Run `dbDelta()` again on plugin update when the version changes.
Version is compared in the activator before running migrations.
