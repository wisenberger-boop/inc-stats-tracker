# INC Stats Tracker — Hooks Reference

All custom actions and filters follow the `ist_` prefix convention.

---

## Actions

### Form submission (admin-post.php)
| Hook | Handler | Description |
|---|---|---|
| `admin_post_ist_submit_tyfcb` | `IST_Forms::handle_tyfcb()` | Process TYFCB form POST |
| `admin_post_ist_submit_referral` | `IST_Forms::handle_referral()` | Process referral form POST |
| `admin_post_ist_submit_connect` | `IST_Forms::handle_connect()` | Process connect form POST |

### WordPress core hooks used
| Hook | Class/Method | Purpose |
|---|---|---|
| `admin_menu` | `IST_Admin::register_menus()` | Register admin submenu pages |
| `admin_enqueue_scripts` | `IST_Admin::enqueue_assets()` | Load admin CSS/JS |
| `init` | `IST_Frontend::register_shortcodes()` | Register shortcodes |
| `wp_enqueue_scripts` | `IST_Frontend::enqueue_assets()` | Load frontend CSS/JS |

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
