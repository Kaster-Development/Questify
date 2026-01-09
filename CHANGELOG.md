# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/).



## [1.0.7] - 2026-01-09

### Changed
- **Asset-Enqueuing refaktorisiert**: Inline-Styles und -Scripts durch WordPress-konforme `wp_enqueue_*` Funktionen ersetzt
- **Namespace-Refaktorisierung**: Alle Bezeichner von `chatbot_` auf `questi_` Präfix umgestellt (Klassen, Optionen, AJAX-Actions, Hooks, Shortcodes)

### Fixed
- **Klassenname**: `Chatbot_Database` zu `Questi_Database` in allen Admin-Views korrigiert (`dashboard.php`, `analytics.php`, `faqs-list.php`, `faq-edit.php`, `inquiries-list.php`, `inquiry-detail.php`)
- **UTF-8 Encoding**: Doppelt-kodierte Zeichen in 9 Dateien repariert – deutsche Umlaute (ä, ö, ü, ß) und Emojis (📊, ✓, ⚠, 👍, 😊, 📁, 📋, 🎉, 💡, →) werden wieder korrekt angezeigt
- **FAQ-Import**: Nonce-Validierung korrigiert – Groß-/Kleinschreibung (`Questi_Admin_ajax` → `questi_admin_ajax`) führte zu "Sicherheitsprüfung fehlgeschlagen" Fehler
- **Security**: Nonce- und Berechtigungsprüfung für Bulk-Actions hinzugefügt
- **Readme/Docs**: Contributors-Username und Support-E-Mail aktualisiert
- **Dokumentation**: README.md und DEPENDENCIES.md korrigiert – Shortcode `[questi_faq_chat]`, Klassennamen `Questi_*`, Filter-Präfix `questi_*`, Verzeichnisstruktur `questify/` und Hauptdatei `questify.php` aktualisiert
- **Code Quality**: Gemischte Zeilenendungen und UTF-8 BOM aus Admin-Views entfernt (`about.php`, `dashboard.php`, `inquiries-list.php`)

### Removed
- **WordPress.org Assets**: Plugin-Icons (`icon-128x128.png`, `icon-256x256.png`) aus dem Plugin-Code entfernt – diese werden separat via SVN hochgeladen

## [1.0.6] - 2025-12-26

### Fixed
- **WPCS/PluginCheck**: Diverse Code-Quality- und Security-Fixes (Sanitization/Escaping, PrefixAllGlobals) in Admin-Views und Backend-Logik.
- **Admin & AJAX**: Robustere Eingabevalidierung (u.a. `absint()`, `wp_unslash()`, strengere `in_array()`-Checks) sowie sichereres Handling von Import-Uploads.
- **Database Layer**: SQL-Handling konsolidiert (prepared nur bei Platzhaltern) und Scanner-Fehlalarme mit eng begrenzten, begründeten `phpcs:ignore`-Anmerkungen dokumentiert.
- **Email**: Betreff/Output-Härtung (u.a. Sanitization des Absendernamens, `wp_strip_all_tags()`), Debug-Logging entfernt.

### Changed
- **Analytics**: Ersetzt externes Chart.js CDN durch gebündelte, minimalistische Charts (reduziert externe Abhängigkeiten).
- **Docs**: WordPress-Kompatibilität in der README aktualisiert ("Tested up to").

---

## [1.0.5] - 2025-01-26

### Fixed
- **AJAX Handler**: Fixed nonce validation for non-logged-in users
  - `check_ajax_referer()` now uses `false` as third parameter
  - Prevents `wp_die()` on failed nonce check
  - Returns friendly JSON error message instead
  - Affects all frontend AJAX handlers: `handle_get_answer()`, `handle_send_inquiry()`, `handle_get_faq_by_id()`, `handle_rate_answer()`
- **Frontend**: Chat now works for non-logged-in visitors
  - No more "Connection error" message for regular visitors
  - Error message "Security check failed" with hint to reload page

### Changed
- **Security**: Improved error handling for nonce validation
  - Admin AJAX handlers keep hard nonce check (only for logged-in admins)
  - Frontend AJAX handlers use soft nonce check (for all visitors)

---

## [1.0.4] - 2025-01-25

### Added
- **Disambiguation Feature**: Intelligent follow-up when multiple similar FAQs exist
  - Shows selection buttons with multiple matching questions
  - Threshold-based detection (Score < 85 or alternative ≥ 70)
  - New AJAX endpoints for selection logic (`chatbot_get_faq_by_id`)
  - Frontend UI with clickable choice buttons
  - Validation: Only active FAQs with at least 2 options
  - Automatic fallback to direct answer if < 2 options
- **Translations**: New strings for disambiguation feature (de_DE, en_US)
  - "I found multiple matching answers. Which of these questions do you mean?"
  - "Invalid FAQ ID."
  - "FAQ not found or inactive."

### Changed
- **Matcher**: Extended matching logic for better disambiguation detection
  - Score difference threshold increased from 15 to 20
  - Second condition: Alternative with score ≥ 70 triggers disambiguation
  - Max 4 options (1 best + 3 alternatives)
- **Frontend CSS**: New styles for disambiguation buttons
  - Hover effects with color and position animation
  - Responsive button layout with automatic line wrapping
  - Arrow icon on hover (→)
- **Database**: Alias method `get_faq_by_id()` for compatibility

### Removed
- **Rate Limiting**: Completely removed (feature + UI + default options)
- **Console Debug Logging**: All `console.log()` statements removed from chatbot-script.js

### Fixed
- **AJAX**: Fallback logic when disambiguation returns < 2 options (prevents empty answers)
- **Validation**: Only active FAQs with complete data in selection options

---

## [1.0.3] - 2025-01-22

### Removed
- **Email System**: Reply-To header completely removed (no company name in emails anymore)
- **Email System**: Removal of `chatbot_feedback_email` and `chatbot_company_name` options
- Hardcoded email address `info@kaster-development.de` removed from code
- Hardcoded company name "Steffen Kaster - Kaster Development" removed from code

### Changed
- **Email System**: Notification emails now only use WordPress system emails (no Reply-To anymore)

---

## [1.0.2] - 2025-01-22

### Added
- **Design Settings**: Header text color adjustable (for chat title in header)
- **Translations**: New strings for header text color (de_DE, en_US)

### Changed
- **Frontend**: Chat header title (`#chatbot-header h3`) now uses CSS variable `--chatbot-header-text-color`
- **CSS**: Separate color variable for header text (default: #ffffff)

---

## [1.0.1] - 2025-01-22

### Added
- **Design Settings**: Separate setting for chat header color
- **Design Settings**: Customizable chat title in header
- **Translations**: New strings for header color and chat title (de_DE, en_US)

### Changed
- **Frontend**: Chat header now uses own CSS variable `--chatbot-header-color`
- **Frontend**: Chat title is dynamically loaded from settings
- **Translations**: Line numbers updated in POT/PO files

### Fixed
- **Translations**: Added missing description "Color for buttons and accents"

---

## [1.0.0] - 2025-01-22

### Added

#### Core Functionality
- Intelligent FAQ matching without AI
- Multi-layered matching algorithm with scoring system
- Session-based conversation management
- Rate limiting for protection against abuse
- Full GDPR compliance with IP anonymization

#### Admin Area
- Dashboard with live statistics
- FAQ management (CRUD) with WP_List_Table
- Inquiry management with status tracking
- Analytics dashboard with Chart.js integration
- Comprehensive settings page with 5 tabs
- CSV export for inquiries
- Test email function

#### Frontend
- Floating chat widget (positionable right/left)
- Responsive design for all devices
- Animated typing indicator
- Session persistence with LocalStorage
- Offline detection
- "Was this helpful?" feedback system
- Contact form when answers are missing

#### Email System
- HTML email templates
- Reply-To header for direct responses
- Automatic notifications for new inquiries
- Multiple recipients supported

#### Matching Algorithm
- Exact keyword matching
- Fuzzy matching with Levenshtein distance
- Bigram matching
- Text similarity analysis
- Configurable stopwords
- Adjustable minimum score

#### Security & Performance
- Nonce validation for all AJAX requests
- SQL injection protection through prepared statements
- Input sanitization and output escaping
- Transient caching for FAQs
- Database indexes for fast queries

### Technical Details

- Minimum requirements: WordPress 6.5, PHP 8.2
- Use of modern PHP 8.2 features (typed properties, match expressions)
- Chart.js 4.4.1 for analytics
- Full WordPress Coding Standards
- OOP architecture with singleton pattern

### Database

- 3 new tables: `chatbot_faqs`, `chatbot_inquiries`, `chatbot_conversations`
- FULLTEXT index for keywords
- Multiple performance indexes
- Automatic database optimization

### Documentation

- Complete README.md
- Inline PHPDoc for all classes and methods
- Hooks & filters documented
- Developer documentation

### Known Limitations

- No AI integration in version 1.0 (planned for v2.0)
- No multilingual support (planned for v1.1)
- No license management (planned for Pro version)

---

## [Unreleased]

### Planned for v1.1
- Multilingual support (WPML/Polylang)
- Import/Export of FAQs (JSON)
- Widget support
- Extended analytics (conversion tracking)
- Dark mode for frontend

### Planned for v2.0
- ChatGPT/OpenAI integration (optional)
- WhatsApp integration
- Live chat function
- Chatbot training mode
- A/B testing for FAQs

---

**[1.0.0]** - First public release - January 22, 2025
