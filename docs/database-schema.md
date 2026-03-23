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

**Controlled vocabularies** (enforced by services, not DB constraints):

| Field | Values | Notes |
|---|---|---|
| `thank_you_to_type` | `member` \| `other` | Never inferred from NULL |
| `business_type` | `new` \| `repeat` | `''` permitted for historical import |
| `referral_type` | `inside` \| `outside` \| `tier-3` | Shared across TYFCB and Referrals; `''` for historical import |
| `status` (referrals) | `emailed` \| `gave-phone` \| `will-initiate` | Handoff method, NOT lifecycle; `''` for historical import |
| `meet_where` | `in-person` \| `zoom` \| `telephone` | `''` for historical import |

---

## `{prefix}ist_tyfcb`
Thank You for Closed Business records.

| Column | Type | Nullable | Notes |
|---|---|---|---|
| `id` | `BIGINT(20) UNSIGNED` | NOT NULL | Auto-increment PK |
| `submitted_by_user_id` | `BIGINT(20) UNSIGNED` | NOT NULL | WP user ID of the member reporting the closed business (the recipient of the business) |
| `submitted_by_name` | `VARCHAR(255)` | NOT NULL DEFAULT `''` | Snapshot of the reporting member's display name at insert time |
| `thank_you_to_type` | `VARCHAR(20)` | NOT NULL DEFAULT `'member'` | `member` = source is resolvable to a WP user; `other` = source cannot or should not be tied to a WP user ID. Always explicit; never inferred from NULL. |
| `thank_you_to_user_id` | `BIGINT(20) UNSIGNED` | NULL | WP user ID of the thanked source. Present when type = `member`; NULL when type = `other`. Does not require current group membership. |
| `thank_you_to_name` | `VARCHAR(255)` | NOT NULL DEFAULT `''` | Always populated. Snapshot of thanked user's display name when type = `member`; free-text source name when type = `other`. Write-once. |
| `amount` | `DECIMAL(10,2)` | NOT NULL DEFAULT `0.00` | Dollar value of the closed business. Raw input may include `$` and commas; `IST_Service_TYFCB::normalize_amount()` strips these before insert. |
| `business_type` | `VARCHAR(20)` | NOT NULL DEFAULT `''` | Whether the business is new or repeat. Values: `new` \| `repeat`. Required on new records; `''` accepted for historical import. |
| `referral_type` | `VARCHAR(20)` | NOT NULL DEFAULT `''` | How the business originated relative to the group. Values: `inside` \| `outside` \| `tier-3`. Required on new records; `''` for historical import. |
| `note` | `TEXT` | NULL | Optional comments. |
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
| `referral_type` | `VARCHAR(20)` | NOT NULL DEFAULT `''` | How the referral relates to the group. Values: `inside` \| `outside` \| `tier-3`. Required; `''` for historical import. |
| `status` | `VARCHAR(50)` | NOT NULL DEFAULT `''` | Handoff method — how the referral was passed. Values: `emailed` \| `gave-phone` \| `will-initiate`. This is NOT a lifecycle status. `''` for historical import. |
| `note` | `TEXT` | NULL | Referral details. Required on new records (form enforces); `''` accepted for historical import. |
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
| `meet_where` | `VARCHAR(50)` | NOT NULL DEFAULT `''` | Meeting medium. Values: `in-person` \| `zoom` \| `telephone`. Required; `''` for historical import. Powers the "How We Meet" pie chart. |
| `note` | `TEXT` | NULL | Topic of conversation. Optional. |
| `entry_date` | `DATE` | NOT NULL | User-supplied date of the connect meeting |
| `created_at` | `DATETIME` | NOT NULL DEFAULT CURRENT_TIMESTAMP | Auto-set insert timestamp. Audit only. |
| `created_by_user_id` | `BIGINT(20) UNSIGNED` | NOT NULL | WP user who entered the record |

**Indexes:** `member_user_id`, `entry_date`

---

## Versioning
The installed DB version is stored in `get_option('ist_db_version')`.
Run `dbDelta()` again on plugin update when the version changes.
Version is compared in the activator before running migrations.

**Migration note — `connect_type` column:** `dbDelta()` adds new columns but does NOT
drop or rename existing ones. Installations that previously had `connect_type` will retain
that column as an inert dead column until a future `ALTER TABLE {prefix}ist_connects DROP COLUMN connect_type`
migration is added. This is safe — the column is never read or written by any current code.

**Historical import note — TYFCB `thank_you_to_type`:** The legacy Google Form CSV
(`thankyouto` column) does not distinguish between member names and "Other" sources using
a separate field. The value "Outside source", "Outside referral", or any name that does not
match a WP user display name should be treated as `type='other'`. Resolving this requires
matching against `wp_users.display_name` and is handled as a dedicated import-cleanup pass,
not in `import_csv()`.
