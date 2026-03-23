# INC Stats Tracker — Naming Conventions

## Plugin identity
| Item | Value |
|---|---|
| Plugin name | INC Stats Tracker |
| Slug | `inc-stats-tracker` |
| Text domain | `inc-stats-tracker` |
| Class prefix | `IST_` |
| Function prefix | `ist_` |
| Option prefix | `ist_` |
| Action/filter prefix | `ist_` |
| DB table prefix | `{wp_prefix}ist_` |

## File naming
| Type | Pattern | Example |
|---|---|---|
| Class files | `class-ist-{name}.php` | `class-ist-loader.php` |
| Helper/function files | `ist-{name}.php` | `ist-functions.php` |
| Templates | `tmpl-{name}.php` | `tmpl-dashboard.php` |
| CSS | `ist-{scope}.css` | `ist-admin.css` |
| JS | `ist-{scope}.js` | `ist-frontend.js` |

## PHP class naming
- PascalCase with `IST_` prefix
- One class per file
- File name must match: `class IST_Foo_Bar` → `class-ist-foo-bar.php`

## PHP function naming
- snake_case with `ist_` prefix
- Global functions live in `includes/ist-functions.php`

## Template variables
- Pass via `ist_get_template( $path, $vars )` using `compact()`
- Templates must not call services or models directly; receive data from admin/frontend controllers

## Nonce actions
- Pattern: `ist_{action}_{type}` — e.g. `ist_submit_tyfcb`, `ist_delete_referral`
