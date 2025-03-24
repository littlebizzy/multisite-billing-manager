# Multisite Billing Manager

Billing for Multisite networks

## Changelog

### 1.2.3
- adjusted CSS selector to more reliably hide billing submenu
- replaced one use of `$_REQUEST` with `$_GET` for clarity
- removed one ternary operator for consistency
- added `Tested up to` plugin header
- added `Update URI` plugin header
- added `Text Domain` plugin header
- changed default GitHub branch to `master` not `main`

### 1.2.2
- added `Requires PHP` plugin header

### 1.2.1
- fixed Billing tab link in Edit Site (Network Admin) interface
- adding basic Billing setting page for child sites under `/wp-admin/index.php?page=billing`
- standardized billing plan labels for consistency

### 1.2.0
- improved security with sanitization, nonces, escapes, etc.
- better handling of site IDs
- various code cleanup
- added disable wordpress.org snippet

### 1.1.0
- added VIP level

### 1.0.0
- support for Git Updater
- Free, Basic, Premium levels
- payments not supported yet (hopefully soon)
