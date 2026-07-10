=== Fenton Digital Badges ===
Contributors: Steve_Fenton
Tags: badges, open badges, credentials, certificates, linkedin
Requires at least: 6.2
Tested up to: 7.0
Requires PHP: 8.0
Stable tag: 0.1.8
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Issue, manage, and display Open Badges 1.0 credentials from your WordPress site.

== Description ==

Fenton Digital Badges lets you issue [Open Badges 1.0](https://github.com/mozilla/openbadges-specification/blob/master/Assertion/latest.md) credentials from WordPress. Define an issuing organization, create badge classes, award them in bulk via CSV, and give earners public attestation pages they can share or add to LinkedIn.

**Features**

* Configure an issuing organization (name, URL, logo, contact details)
* Create badge classes as a custom post type with image, criteria URL, and tags
* Issue badges in bulk from CSV (email required; name, evidence, and expiry optional)
* Public Open Badges JSON endpoints for issuer, badge class, and assertion
* Public attestation pages with share, embed, download, and LinkedIn Add to Profile
* Email lookup so earners can find badges issued to them
* Assertions list with revoke support
* Recipient emails are salted/hashed — plaintext emails are never stored

**Shortcodes**

* `[fenton_digital_badge id="123"]` — display a badge
* `[fenton_digital_badges_find]` — email lookup form (also available at `/badges/find/`)

**Public endpoints**

* Issuer JSON: `/ob/issuer.json`
* Badge class JSON: `/ob/badges/{id}.json`
* Assertion JSON: `/ob/assertions/{uid}.json`
* Attestation page: `/badges/assertion/{uid}/`
* Embed: `/badges/embed/{uid}/`

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/fenton-digital-badges`, or install the ZIP via **Plugins → Add New → Upload Plugin**.
2. Activate **Fenton Digital Badges** through the **Plugins** screen.
3. Go to **Badges → Settings** and configure your issuing organization (name and website URL are required).
4. Create a badge under **Badges → Add New** (featured image and criteria URL required).
5. Use **Badges → Issue Badges** to award badges from a CSV.

== Frequently Asked Questions ==

= What CSV format is required? =

Columns: `email` (required), `name`, `evidence`, `expires` (YYYY-MM-DD). A header row is optional — a single line like `you@example.com,Your Name` works.

= Are recipient emails stored? =

No. Emails are salted and hashed for the assertion identity and looked up via a separate HMAC. Plaintext emails are never stored.

= What happens if a recipient name is omitted? =

The attestation page still verifies the badge, but shows a generic completion message instead of naming the earner.

= Does this support Open Badges 2.0 / 3.0? =

This release implements Open Badges 1.0 JSON endpoints and assertions.

== Changelog ==

= 0.1.5 =
* Initial public release candidate for the WordPress.org plugin directory.

== Upgrade Notice ==

= 0.1.5 =
Initial release.
