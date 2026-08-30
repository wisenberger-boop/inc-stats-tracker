# INC Stats Tracker — Hooks Reference

All custom actions and filters follow the `ist_` prefix convention.

---

## Actions

### Form submission (admin-post.php)
| Hook | Handler | Description |
|---|---|---|
| `admin_post_ist_submit_tyfcb` | `IST_Forms::handle_tyfcb()` | Process TYFCB form POST |
| `admin_post_ist_update_tyfcb` | `IST_Forms::handle_update_tyfcb()` | Update a member-owned TYFCB record |
| `admin_post_ist_submit_referral` | `IST_Forms::handle_referral()` | Process referral form POST |
| `admin_post_ist_submit_connect` | `IST_Forms::handle_connect()` | Process connect form POST |

Logged-out versions of these actions are registered with `admin_post_nopriv_*` and route to
`IST_Forms::handle_nopriv()`, which redirects to the WordPress login page.

### Administrative actions

| Hook | Handler | Protection |
|---|---|---|
| `admin_post_ist_save_settings` | `IST_Admin_Settings::handle_save_settings()` | `manage_options` + nonce |
| `admin_post_ist_delete_tyfcb` | `IST_Admin_TYFCB::handle_delete()` | plugin capability + record nonce |
| `admin_post_ist_delete_referral` | `IST_Admin_Referrals::handle_delete()` | plugin capability + record nonce |
| `admin_post_ist_delete_connect` | `IST_Admin_Connects::handle_delete()` | plugin capability + record nonce |
| `admin_post_ist_run_historical_import` | `IST_Admin_Import::handle_run_import()` | `manage_options` + nonce |
| `admin_post_ist_reset_import_hashes` | `IST_Admin_Import::handle_reset_hashes()` | `manage_options` + nonce |
| `admin_post_ist_mark_legacy_as_imported` | `IST_Admin_Import::handle_mark_legacy()` | `manage_options` + nonce |
| `admin_post_ist_purge_imported_records` | `IST_Admin_Import::handle_purge_imported()` | `manage_options` + nonce |

### WordPress core hooks used
| Hook | Class/Method | Purpose |
|---|---|---|
| `admin_menu` | `IST_Admin::register_menus()` | Register admin submenu pages |
| `admin_enqueue_scripts` | `IST_Admin::enqueue_assets()` | Load admin CSS/JS |
| `init` | `IST_Frontend::register_shortcodes()` | Register shortcodes |
| `wp_enqueue_scripts` | `IST_Frontend::enqueue_assets()` | Load frontend CSS/JS |
| `admin_init` | `IST_Activator::maybe_upgrade()` | Apply additive `dbDelta()` schema updates when `IST_VERSION` changes |
| `rest_api_init` | `IST_REST_API::register_routes()` | Register the authenticated read-only summary route |
| `bp_setup_nav` | `IST_Profile_Nav::register()` | Register the BuddyBoss member profile navigation |

## REST API

| Route | Method | Access | Description |
|---|---|---|---|
| `/wp-json/inc-stats-tracker/v1/my-summary` | `GET` | Logged-in WordPress user | Current user's fiscal-year totals and recent records |

---

## Custom action hooks (fire these from services for extensibility)
> To be added as services mature. Convention: `do_action( 'ist_{event}', $data )`

| Planned hook | When to fire |
|---|---|
| `ist_after_tyfcb_created` | After a TYFCB record is inserted |
| `ist_after_referral_created` | After a referral is inserted |
| `ist_after_connect_created` | After a connect is inserted |
| `ist_after_member_created` | After a member is created |

---

## Shortcodes
| Shortcode | Renders |
|---|---|
| `[ist_tyfcb_form]` | TYFCB submission form |
| `[ist_referral_form]` | Referral submission form |
| `[ist_connect_form]` | Connect submission form |
