# Fenton Digital Badges

WordPress plugin for issuing, managing, and displaying [Open Badges 1.0](https://github.com/mozilla/openbadges-specification/blob/master/Assertion/latest.md) credentials.

## Install

1. Copy this folder into `wp-content/plugins/fenton-digital-badges`
2. Activate **Fenton Digital Badges** in wp-admin → Plugins
3. Open **Badges → Settings** and configure the issuing organization
4. Create badges (featured image required), then use **Issue Badges** with a CSV

## Architecture

```mermaid
flowchart LR
  Admin[Admin Settings + CPT + Issue CSV]
  IssuerJSON["/ob/issuer.json"]
  BadgeJSON["/ob/badges/{id}.json"]
  AssertJSON["/ob/assertions/{uid}.json"]
  IssueMail[Issue email]
  Claim["Name claim (one-time link)"]
  Attest[Attestation HTML page]
  LinkedIn[LinkedIn Add Certification]

  Admin --> IssuerJSON
  Admin --> BadgeJSON
  Admin --> AssertJSON
  Admin --> IssueMail
  IssueMail --> Claim
  IssueMail --> Attest
  Attest --> LinkedIn
```

| Open Badges object | Storage | Public URL |
|--------------------|---------|------------|
| IssuerOrganization | `fendigibadge_issuer` option | `/ob/issuer.json` |
| BadgeClass | `fendigibadge_badge` CPT + meta | `/ob/badges/{id}.json` |
| Assertion | `{prefix}fendigibadge_assertions` table | `/ob/assertions/{uid}.json` |

## Open Badges endpoints

| Resource | URL |
|----------|-----|
| Issuer | `/ob/issuer.json` |
| BadgeClass | `/ob/badges/{id}.json` |
| Assertion | `/ob/assertions/{uid}.json` |
| Attestation | `/badges/assertion/{uid}/` or `[fendigibadge_attestation]` (optional page template via **Badges → Settings**) |

Theme overrides for plugin views: `fendigibadge/{view}.php` (e.g. `attestation.php`, `claim-name.php`).

There is no public endpoint that triggers an email — badge notification emails are only sent when a WordPress user issues a badge (from **Badges → Issue Badges**).

## Issuing

CSV columns: `email` (required), `name`, `evidence`, `expires` (YYYY-MM-DD). A header row is optional — a single line like `you@example.com,Your Name` works.

Email addresses are salted/hashed for the assertion identity and looked up via a separate HMAC. Plaintext emails are never stored on assertions. When a badge is issued without a name, the issue email includes a one-time link the earner can use to add their name.

## Structure

```
fenton-digital-badges.php          Bootstrap + plugin header
readme.txt                  WordPress.org plugin directory listing
license.txt                 GPLv2 (or later)
includes/                   Core classes (issuer, assertions, OB endpoints)
admin/                      Admin UI, settings, assets
public/                     Front-end assets, shortcodes, views
uninstall.php               Cleanup on uninstall
```

## Package

```bash
./package.sh              # bump patch, sync versions, create ZIP
./package.sh --bump minor # bump minor instead
./package.sh --set 1.0.0  # set an explicit version
./package.sh --no-bump    # package without changing the version
```

Creates `dist/fenton-digital-badges-{version}.zip` for manual install or WordPress.org submission. Updates `fenton-digital-badges.php` and `readme.txt` Stable tag when bumping.

## Requirements

- WordPress 6.2+
- PHP 8.0+
