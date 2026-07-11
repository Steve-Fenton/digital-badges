=== Fenton Digital Badges ===
Contributors: Steve_Fenton
Tags: badges, open badges, credentials, certificates, linkedin
Requires at least: 6.2
Tested up to: 7.0
Requires PHP: 8.0
Stable tag: 0.1.24
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
* Public attestation pages with share, download, and LinkedIn Add to Profile
* Email lookup so earners can request links to badges issued to them (avoids revealing whether an address has badges)
* Assertions list with revoke, restore, and delete (revoked only)
* Recipient emails are salted/hashed — plaintext emails are never stored

You can add templates for the badge pages using `/wp-admin/site-editor.php?p=%2Ftemplate` on your site.

* Click "Add Template"
* Select "Single item: Badge" or "Archive: Badge"

To control the layout of `/badges/find/` or `/badges/assertion/{uid}/`, create a Page, optionally add `[fendigibadge_find]` or `[fendigibadge_attestation]`, then choose that page under **Badges → Settings**. Edit the page’s template in the Site Editor. Themes can also override the markup with `fendigibadge/find.php` or `fendigibadge/attestation.php`.

**Shortcodes**

* `[fendigibadge id="123"]` — display a badge
* `[fendigibadge_find]` — email lookup form (also available at `/badges/find/`)
* `[fendigibadge_attestation]` — certificate markup on `/badges/assertion/{uid}/` when using a page template

**Public endpoints**

* Issuer JSON: `/ob/issuer.json`
* Badge class JSON: `/ob/badges/{id}.json`
* Assertion JSON: `/ob/assertions/{uid}.json`
* Attestation page: `/badges/assertion/{uid}/`
* Find badges: `/badges/find/`
* Claim name: `/badges/claim-name/{token}/`

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

= How does the find badges form work? =

Enter the email used when the badge was issued. The form always shows the same confirmation message so it does not reveal whether an address has badges. If matches exist, attestation URLs are emailed to that address.

= What happens if a recipient name is omitted? =

The attestation page still verifies the badge, but shows a generic completion message instead of naming the earner. When the earner uses Find your badges, the email includes a one-time link to claim the certificate and add their name. The link asks them to confirm the name before saving, then stops working.

= Does this support Open Badges 2.0 / 3.0? =

This release implements Open Badges 1.0 JSON endpoints and assertions.

== Changelog ==

= 0.1.23 =
* Find-badges emails include a one-time link to add a missing recipient name, with confirm-before-save.

= 0.1.19 =
* Renamed internal prefixes to `fendigibadge` for WordPress.org plugin review compliance.

= 0.1.5 =
* Initial public release candidate for the WordPress.org plugin directory.

== Upgrade Notice ==

= 0.1.5 =
Initial release.
