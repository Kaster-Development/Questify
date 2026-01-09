# Dependencies

Documentation of all external dependencies of the Questify plugin.

## Frontend Libraries

### Chart.js

- **Version:** 4.4.1
- **Usage:** Visualization of statistics in the analytics dashboard
- **CDN:** https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js
- **License:** MIT License
- **Website:** https://www.chartjs.org/
- **Purpose:** Creating line, bar, and pie charts for analytics

**Note:** Chart.js is only loaded in the admin area on the analytics page.

### jQuery

- **Version:** WordPress Core (varies by WordPress version)
- **Usage:** DOM manipulation, AJAX requests, event handling
- **Source:** Integrated in WordPress
- **License:** MIT License
- **Website:** https://jquery.com/
- **Purpose:** JavaScript framework for frontend and admin

**Note:** jQuery is already included in WordPress and is not loaded separately.

## WordPress Core Functions

The plugin uses the following WordPress core features:

### Admin Area

- **WP_List_Table** - For table lists
- **wp_editor()** - WordPress WYSIWYG editor for FAQ answers
- **WordPress Color Picker** - Color selection in settings area
- **WordPress Admin Notices** - Notifications in admin
- **WordPress Transients API** - Caching
- **WordPress Options API** - Save settings
- **WordPress Nonce** - Security tokens

### Frontend

- **wp_enqueue_scripts** - Asset loading
- **wp_localize_script** - JavaScript localization
- **Shortcode API** - [questi_faq_chat] shortcode

### Email

- **wp_mail()** - Email sending
- **WordPress Email Headers** - HTML emails, Reply-To

### Database

- **$wpdb** - WordPress Database Class
- **dbDelta()** - Database schema updates

## PHP Extensions

Required PHP extensions:

- **mbstring** - Multibyte string functions (for umlauts)
- **mysqli** or **mysqlnd** - MySQL connection
- **json** - JSON encoding/decoding
- **filter** - Input filtering

## No Additional External Dependencies

The plugin intentionally has **no additional external dependencies** and uses no:

- ❌ npm/Node.js packages
- ❌ Composer dependencies
- ❌ Additional JavaScript frameworks
- ❌ CSS frameworks (pure CSS)
- ❌ API keys or external services

## License Information

### Used Open Source Software

| Software | Version | License | Purpose |
|----------|---------|---------|---------|
| Chart.js | 4.4.1 | MIT | Analytics visualization |
| jQuery | WP Core | MIT | JavaScript framework |
| WordPress | 6.5+ | GPL v2+ | CMS platform |

## Browser Compatibility

The plugin is compatible with:

- ✅ Chrome (current version)
- ✅ Firefox (current version)
- ✅ Safari (current version)
- ✅ Edge (current version)
- ✅ Opera (current version)

**Minimum Requirements:**
- ES6 JavaScript support
- CSS3 support (Flexbox, Grid)
- LocalStorage-API
- Fetch API oder XMLHttpRequest

## CDN-Fallback

Falls der Chart.js-CDN nicht erreichbar ist, wird:
- Das Analytics-Dashboard angezeigt, aber ohne Diagramme
- Eine entsprechende Warnung im Admin-Bereich angezeigt
- Die Grundfunktionalität des Plugins bleibt erhalten

## Aktualisierungen

Alle externen Dependencies werden manuell aktualisiert durch:
1. Überprüfung der neuen Version auf Breaking Changes
2. Test im Staging-Environment
3. Update der Versionsnummer in diesem Dokument
4. Update im Plugin-Code

**Letzte Überprüfung:** 22. Januar 2025

## Support & Updates

Bei Problemen mit Dependencies:
- Überprüfen Sie die Browser-Konsole auf Fehler
- Aktivieren Sie den Debug-Modus in den Plugin-Einstellungen
- Kontaktieren Sie den Support unter https://kaster-development.de/support

---

**© 2025 Kaster Development**
