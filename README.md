# Digital Badges

WordPress plugin for issuing, managing, and displaying digital badges.

## Install

1. Copy this folder into `wp-content/plugins/digital-badges`
2. Activate **Digital Badges** in wp-admin → Plugins

## Structure

```
digital-badges.php          Bootstrap + plugin header
includes/                   Core classes (plugin, CPT, activate/deactivate)
admin/                      Admin UI, settings, assets
public/                     Front-end assets + shortcodes
uninstall.php               Cleanup on uninstall
```

## Stubs included

- Custom post type: `db_badge` (Badges)
- Admin settings submenu under Badges
- Shortcode: `[digital_badge id="123"]`
- Activation / deactivation rewrite flush
- Empty admin + public CSS/JS

## Requirements

- WordPress 6.0+
- PHP 8.0+
