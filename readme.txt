=== Fenton Digital Badges ===
Contributors: Steve_Fenton
Tags: badges, open badges, credentials, certificates, linkedin
Requires at least: 6.2
Tested up to: 7.0
Requires PHP: 8.0
Stable tag: 0.1.37
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Issue, manage, and display Open Badges 1.0 credentials from your WordPress site.

== Description ==

Fenton Digital Badges lets you issue [Open Badges 1.0](https://github.com/mozilla/openbadges-specification/blob/master/Assertion/latest.md) credentials from WordPress. Define an issuing organization, create badge classes, award them in bulk via CSV, and give earners public attestation pages they can share or add to LinkedIn.

**Features**

* Configure an issuing organization (name, URL, logo, contact details)
* Create badge classes as a custom post type with image, criteria URL, earn URL, and tags
* Issue badges in bulk from CSV (email required; name, evidence, and expiry optional)
* Email earners when a badge is issued, with a link to their attestation page
* Public Open Badges JSON endpoints for issuer, badge class, and assertion
* Public attestation pages with share, download, and LinkedIn Add to Profile
* Issue emails include a one-time link so earners can add their name, and a secure link to stop future notifications
* Assertions list with revoke, restore, and delete (revoked only)
* Assertion filters on **Badges → Assertions** — filter by badge type, search by recipient email (hashed lookup; no email is stored), and choose items per page
* **Badges → Stats** — issued, claimed, and claimed % totals, plus a per–badge-type breakdown table
* Recipient emails are salted/hashed — plaintext emails are not stored on assertions (unsubscribe opt-outs are an exception)

You can add templates for the badge pages using `/wp-admin/site-editor.php?p=%2Ftemplate` on your site.

* Click "Add Template"
* Select "Single item: Badge" or "Archive: Badge"

To control the layout of `/badges/assertion/{uid}/`, create a Page, optionally add `[fendigibadge_attestation]`, then choose that page under **Badges → Settings**. Edit the page’s template in the Site Editor. Themes can also override the markup with `fendigibadge/attestation.php`.

**Shortcodes**

* `[fendigibadge id="123"]` — display a badge
* `[fendigibadge_attestation]` — certificate markup on `/badges/assertion/{uid}/` when using a page template

**Public endpoints**

* Issuer JSON: `/ob/issuer.json`
* Badge class JSON: `/ob/badges/{id}.json`
* Assertion JSON: `/ob/assertions/{uid}.json`
* Attestation page: `/badges/assertion/{uid}/`
* Claim name: `/badges/claim-name/{token}/`
* Unsubscribe: `/badges/unsubscribe/{token}/`

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

No, not on assertions. Emails are salted and hashed for the assertion identity and looked up via a separate HMAC. The only plaintext emails kept are addresses that opted out of badge notifications via the unsubscribe link.

= How does an earner add their name to a badge? =

If a badge is issued without a name, the issue email includes a one-time link to add it. The link asks the earner to confirm the name before saving, then stops working. The attestation page still verifies the badge even if no name is ever added, showing a generic completion message instead.

= How do I stop badge notification emails? =

Each issue email ends with a “Stop all future notifications” link. Using it adds that address to an unsubscribe list (only when the address still matches at least one badge). Later issue notifications for that address are ignored.

= Does this support Open Badges 2.0 / 3.0? =

This release implements Open Badges 1.0 JSON endpoints and assertions.

== Screenshots ==

1. Badges settings — configure your issuing organization.
2. Issue badges — award credentials from a CSV upload.
3. Attestation page — public certificate view for earners.

== Changelog ==

= 0.1.37 =
* Added assertion filters to **Badges → Assertions** — filter by badge type, search by recipient email (hashed lookup; no email is sent or stored), and choose items per page.
* Added **Badges → Stats** with issued, claimed, and claimed % summary boxes and a per–badge-type breakdown table.

= 0.1.36 =
* Removed the public "find my badges" email lookup form and its `/badges/find/` endpoint, `[fendigibadge_find]` shortcode, and Find badges page setting. This was the only public, unauthenticated endpoint that could trigger an email; badge notification emails are now only sent when a WordPress user issues a badge. The one-time name-claim link and unsubscribe link are unaffected.
* Added an Earn URL field to badges (the page where someone can go to earn the badge). It's shown as a link wherever `[fendigibadge]` renders the badge, and as a button on the public attestation page.

= 0.1.29 =
* Tightened find-badges rate limits: 8 submissions per IP per 10 minutes, and one lookup per email every 30 minutes.

= 0.1.28 =
* Find-badges emails include a secure “Stop all future notifications” unsubscribe link.
* Unsubscribed addresses are ignored before email lookup hashing.

= 0.1.23 =
* Find-badges emails include a one-time link to add a missing recipient name, with confirm-before-save.

= 0.1.19 =
* Renamed internal prefixes to `fendigibadge` for WordPress.org plugin review compliance.

= 0.1.5 =
* Initial public release candidate for the WordPress.org plugin directory.

== Upgrade Notice ==

= 0.1.5 =
Initial release.
