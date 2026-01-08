=== Questify ===
Contributors: steffenka
Requires at least: 6.5
Tested up to: 6.9
Requires PHP: 8.2
Stable tag: 1.0.7
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

# Questify

Intelligent FAQ chatbot for WordPress with backend management, email integration, and analytics dashboard.

## Features

- ✅ **Intelligent FAQ Management** - Create, edit, and manage FAQs with keywords
- ✅ **Automatic Matching** - Advanced algorithm finds matching answers without AI
- ✅ **Floating Chat Widget** - Modern, responsive chat interface
- ✅ **Email Notifications** - Automatic notification for new inquiries with Reply-To
- ✅ **Analytics Dashboard** - Detailed statistics and analytics with Chart.js
- ✅ **GDPR Compliant** - IP anonymization, data deletion, GDPR checkbox
- ✅ **Rate Limiting** - Protection against abuse
- ✅ **Fully Customizable** - Colors, positions, texts, sizes

## Installation

1. Upload the `questify` plugin directory to the `/wp-content/plugins/` directory
2. Activate the plugin through the WordPress admin menu "Plugins"
3. Go to "FAQ Chatbot" > "Settings" to configure the plugin
4. Create your first FAQs under "Questions & Answers"

## System Requirements

- **WordPress:** 6.5 or higher
- **PHP:** 8.2 or higher
- **MySQL:** 5.7+ or MariaDB 10.3+
- **Browser:** Chrome, Firefox, Safari, Edge (current versions)

## Usage

### Creating FAQs

1. Navigate to **FAQ Chatbot** > **Questions & Answers**
2. Click **Add New**
3. Enter the question, answer, and optional keywords
4. Click **Publish**

**Tip:** Keywords significantly improve matching accuracy! Add synonyms, alternative spellings, and common typos.

### Customizing Settings

Under **FAQ Chatbot** > **Settings** you can:

- **General:** Customize greeting text, placeholder, messages
- **Design:** Change colors, position (right/left), chat size
- **Email:** Configure notification emails, send test email
- **Advanced:** Rate limiting, GDPR, auto-embedding, debug mode
- **Matching:** Minimum score, fuzzy matching, stopwords

### Shortcode

You can also embed the chat using a shortcode:

```php
[wp_faq_chat]
```

With parameters:

```php
[wp_faq_chat position="left" color="#FF5722" size="large"]
```

## Matching Algorithm

The chatbot uses a multi-layered matching algorithm:

1. **Exact Keyword Matching** (+30 points per match)
2. **Fuzzy Matching** with Levenshtein distance (+20 points)
3. **Bigram Matching** (+10 points per match)
4. **Overall Similarity** with similar_text (+25 points max)
5. **Word Matches** (+5 points per word)

**Thresholds:**
- ≥ 80 points: Very confident match (direct answer)
- 60-79 points: Probable match (answer with alternatives)
- < 60 points: No match (contact form)

## Hooks & Filters

### Actions

```php
// After plugin activation
do_action('questify_activated');

// After plugin deactivation
do_action('questify_deactivated');

// After FAQ save
do_action('questify_faq_saved', $faq_id, $data);
```

### Filters

```php
// Adjust matching score
apply_filters('chatbot_matching_score', $score, $question, $faq);

// Change minimum score
apply_filters('chatbot_min_score', $min_score);

// Customize email template
apply_filters('chatbot_email_template', $template, $inquiry);

// Filter stopwords
apply_filters('chatbot_stopwords', $stopwords);
```

## Development

### Directory Structure

```
wp-faq-chat/
├── wp-faq-chat.php          # Main plugin file
├── includes/                 # Core classes
├── admin/                    # Admin area
│   ├── css/
│   ├── js/
│   └── views/
├── public/                   # Frontend
│   ├── css/
│   └── js/
├── languages/                # Translations
└── assets/                   # Plugin icons
```

### Class Overview

- `Chatbot_Activator` - Plugin activation
- `Chatbot_Deactivator` - Plugin deactivation
- `Chatbot_Database` - Database operations
- `Chatbot_Matcher` - FAQ matching algorithm
- `Chatbot_Email` - Email sending
- `Chatbot_Ajax` - AJAX handlers
- `Chatbot_Admin` - Admin area
- `Chatbot_Frontend` - Frontend display

## Support

- **Documentation:** [https://kaster-development.de/wp-faq-chat/docs](https://kaster-development.de/wp-faq-chat/docs)
- **Support:** [https://kaster-development.de/support](https://kaster-development.de/support)
- **GitHub:** Issues and pull requests are welcome

## License

This plugin is licensed under GPL v2 or later. See [LICENSE.txt](LICENSE.txt) for details.

## Credits

- **Developed by:** [Steffen Kaster](https://kaster-development.de)
- **Company:** Kaster Development
- **Chart.js:** [https://www.chartjs.org/](https://www.chartjs.org/)

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for complete version history.

---

**© 2025 Kaster Development.**
