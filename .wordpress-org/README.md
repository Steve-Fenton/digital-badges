# WordPress.org plugin assets

Files in this folder are deployed to the plugin’s SVN `assets/` directory (not `trunk/`) when you run the **Deploy to WordPress.org** workflow.

Replace the placeholder images before your first public release.

## Required filenames

| File | Size | Purpose |
|------|------|---------|
| `icon-128x128.png` | 128×128 | Plugin icon (required) |
| `icon-256x256.png` | 256×256 | Retina plugin icon (recommended) |
| `icon.svg` | vector | Optional SVG icon (takes precedence when present) |
| `banner-772x250.png` | 772×250 | Plugin page banner (recommended) |
| `banner-1544x500.png` | 1544×500 | Retina banner (recommended) |
| `screenshot-1.png` … | any | Must match `== Screenshots ==` lines in `readme.txt` |

## Tips

- Use lowercase filenames only.
- Prefer JPG for banners and screenshots to keep file sizes small (under 4 MB for banners, 10 MB for screenshots).
- Add one `screenshot-N.png` for each screenshot line in `readme.txt`; the line text becomes the caption on WordPress.org.
- For RTL locales, you can add variants such as `banner-772x250-rtl.png`.

Reference: [Plugin assets handbook](https://developer.wordpress.org/plugins/wordpress-org/plugin-assets/)
