<?php
/**
 * Keyword-Generator-Klasse
 *
 * @package Questify
 * @since 1.0.0
 */

// Direkten Zugriff verhindern
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Klasse für automatische Keyword-Generierung
 */
class Questi_Keyword_Generator {

    /**
     * Singleton-Instanz
     */
    private static ?Questi_Keyword_Generator $instance = null;

    /**
     * Stoppwörter (häufige Wörter, die ignoriert werden)
     */
    private array $stopwords = [
        'der', 'die', 'das', 'den', 'dem', 'des',
        'ein', 'eine', 'einer', 'eines', 'einem', 'einen',
        'und', 'oder', 'aber', 'doch', 'sondern',
        'ist', 'sind', 'war', 'waren', 'sein', 'hat', 'haben',
        'wird', 'werden', 'wurde', 'wurden',
        'kann', 'können', 'muss', 'müssen', 'soll', 'sollen',
        'für', 'von', 'zu', 'mit', 'bei', 'nach', 'vor',
        'in', 'an', 'auf', 'aus', 'über', 'unter',
        'wann', 'wie', 'was', 'wo', 'wer', 'warum',
        'gibt', 'es', 'ich', 'sie', 'ihr', 'mein', 'dein',
        'dieser', 'diese', 'dieses', 'jener', 'jene', 'jenes',
        'welcher', 'welche', 'welches',
        'auch', 'noch', 'schon', 'immer', 'gerne', 'gern',
        'mal', 'nur', 'sehr', 'viel', 'mehr', 'weniger',
        'ja', 'nein', 'nicht', 'kein', 'keine', 'keiner',
        'alle', 'alles', 'jeder', 'jede', 'jedes',
        'man', 'uns', 'euch', 'ihnen', 'ihm', 'ihr',
        'hallo', 'guten', 'tag', 'morgen', 'abend',
        'bitte', 'danke', 'vielen', 'dank',
    ];

    /**
     * Unwichtige Wörter in Fragen
     */
    private array $question_words = [
        'wann', 'wie', 'was', 'wo', 'wer', 'warum', 'wieso', 'weshalb',
        'gibt', 'gibts', 'gibt\'s', 'haben', 'hat', 'kann', 'könnte',
        'möchte', 'möglich', 'bitte', 'sagen', 'erklären',
        'wollte', 'würde', 'fragen', 'wissen', 'frage',
    ];

    /**
     * Wichtige Themenwörter die immer als Keyword erkannt werden sollten
     */
    private array $important_topics = [
        // Öffnungszeiten
        'öffnungszeiten', 'öffnungszeit', 'geöffnet', 'geschlossen', 'offen',
        'heiligabend', 'silvester', 'feiertag', 'feiertage', 'wochenende',
        'montag', 'dienstag', 'mittwoch', 'donnerstag', 'freitag', 'samstag', 'sonntag',
        // Preise & Bezahlung
        'preis', 'preise', 'kosten', 'kostet', 'euro', 'bezahlen', 'zahlung',
        'karte', 'kartenzahlung', 'bar', 'barzahlung', 'ec', 'kreditkarte',
        'gutschein', 'gutscheine', 'rabatt', 'ermäßigung', 'ermäßigt',
        'fünferkarte', 'zehnerkarte', 'mehrfachkarte', 'jahreskarte', 'saisonkarte',
        // Ausrüstung
        'helm', 'helme', 'schutzhelm', 'schlittschuh', 'schlittschuhe',
        'handschuh', 'handschuhe', 'ausrüstung', 'ausleihen', 'leihen', 'mieten',
        'größe', 'größen', 'schuhgröße', 'nummer',
        // Kurse & Training
        'kurs', 'kurse', 'lernen', 'unterricht', 'anfänger', 'training',
        'eiskunstlauf', 'eishockey', 'eislaufen',
        // Veranstaltungen
        'veranstaltung', 'veranstaltungen', 'party', 'geburtstag', 'gruppe', 'gruppen',
        'event', 'events', 'feier', 'feiern', 'reservierung', 'reservieren',
        // Service
        'schleifen', 'schärfen', 'geschliffen', 'reparatur',
        'parken', 'parkplatz', 'anfahrt', 'adresse',
        'kinder', 'kind', 'erwachsene', 'alter', 'mindestalter',
        // Kontakt
        'telefon', 'email', 'kontakt', 'erreichbar', 'ansprechpartner',
    ];

    /**
     * Zusammengesetzte Keywords (Phrasen)
     */
    private array $keyword_phrases = [
        'öffnungszeiten' => ['wann offen', 'wann geöffnet', 'wann auf', 'wann zu'],
        'kartenzahlung' => ['mit karte', 'karte zahlen', 'karte bezahlen', 'ec karte', 'kreditkarte'],
        'gutschein' => ['zum verschenken', 'geschenk', 'verschenken'],
        'schlittschuhverleih' => ['schlittschuhe ausleihen', 'schlittschuhe leihen', 'schuhe leihen'],
        'helmverleih' => ['helm ausleihen', 'helm leihen', 'helme leihen'],
    ];

    /**
     * Singleton-Methode
     */
    public static function get_instance(): Questi_Keyword_Generator {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Konstruktor
     */
    private function __construct() {
        // Stoppwörter aus Einstellungen laden (falls vorhanden)
        $custom_stopwords = get_option('questi_stopwords', '');
        if (!empty($custom_stopwords)) {
            $custom = array_map('trim', explode(',', $custom_stopwords));
            $this->stopwords = array_unique(array_merge($this->stopwords, $custom));
        }
    }

    /**
     * Generiert Keywords automatisch aus einer Frage
     *
     * @param string $question Die FAQ-Frage
     * @param string $answer Die FAQ-Antwort (optional, für zusätzliche Keywords)
     * @return string Kommaseparierte Keywords
     * @since 1.0.0
     */
    public function generate_keywords(string $question, string $answer = ''): string {
        $keywords = [];
        $combined_text = $question . ' ' . wp_strip_all_tags($answer);
        $combined_lower = mb_strtolower($combined_text, 'UTF-8');

        // 0. Wichtige Themen-Keywords zuerst prüfen (höchste Priorität)
        foreach ($this->important_topics as $topic) {
            if (str_contains($combined_lower, $topic)) {
                $keywords[] = $topic;
            }
        }

        // 0.1 Phrasen-Keywords prüfen
        foreach ($this->keyword_phrases as $keyword => $phrases) {
            foreach ($phrases as $phrase) {
                if (str_contains($combined_lower, $phrase)) {
                    $keywords[] = $keyword;
                    break;
                }
            }
        }

        // 1. Keywords aus Frage extrahieren
        $question_keywords = $this->extract_keywords_from_text($question);
        $keywords = array_merge($keywords, $question_keywords);

        // 2. Keywords aus Antwort extrahieren (optional, nur wichtige Wörter)
        if (!empty($answer)) {
            // HTML entfernen
            $answer_text = wp_strip_all_tags($answer);
            $answer_keywords = $this->extract_keywords_from_text($answer_text, 8); // Max 8 aus Antwort (erhöht von 5)
            $keywords = array_merge($keywords, $answer_keywords);
        }

        // 3. Singular/Plural-Varianten hinzufügen
        $variations = [];
        foreach ($keywords as $keyword) {
            $variations = array_merge($variations, $this->get_word_variations($keyword));
        }
        $keywords = array_merge($keywords, $variations);

        // 4. Duplikate entfernen und sortieren
        $keywords = array_unique($keywords);
        $keywords = array_filter($keywords); // Leere Einträge entfernen

        // 5. Wichtige Themen-Keywords nach vorne sortieren, dann nach Länge
        usort($keywords, function($a, $b) {
            $a_important = in_array($a, $this->important_topics);
            $b_important = in_array($b, $this->important_topics);
            
            if ($a_important && !$b_important) return -1;
            if (!$a_important && $b_important) return 1;
            
            return strlen($b) - strlen($a);
        });

        // 6. Auf maximal 25 Keywords begrenzen (erhöht von 20)
        $keywords = array_slice($keywords, 0, 25);

        return implode(', ', $keywords);
    }

    /**
     * Extrahiert wichtige Keywords aus einem Text
     *
     * @param string $text Der Text
     * @param int $max_keywords Maximale Anzahl Keywords (0 = alle)
     * @return array Array mit Keywords
     * @since 1.0.0
     */
    private function extract_keywords_from_text(string $text, int $max_keywords = 0): array {
        // Text normalisieren
        $text = $this->normalize_text($text);

        // In Wörter aufteilen
        $words = explode(' ', $text);

        // Filtern
        $keywords = [];
        foreach ($words as $word) {
            // Mindestlänge: 3 Zeichen
            if (strlen($word) < 3) {
                continue;
            }

            // Stoppwörter ignorieren
            if (in_array($word, $this->stopwords)) {
                continue;
            }

            // Fragewörter ignorieren
            if (in_array($word, $this->question_words)) {
                continue;
            }

            // Nur Buchstaben und Zahlen
            if (!preg_match('/^[a-zäöüß0-9]+$/i', $word)) {
                continue;
            }

            $keywords[] = $word;
        }

        // Duplikate entfernen
        $keywords = array_unique($keywords);

        // Häufigkeit zählen und sortieren
        $word_counts = array_count_values($keywords);
        arsort($word_counts);

        // Top-Keywords zurückgeben
        if ($max_keywords > 0) {
            $keywords = array_slice(array_keys($word_counts), 0, $max_keywords);
        } else {
            $keywords = array_keys($word_counts);
        }

        return $keywords;
    }

    /**
     * Erzeugt Singular/Plural-Varianten eines Wortes
     *
     * @param string $word Das Wort
     * @return array Array mit Varianten
     * @since 1.0.0
     */
    private function get_word_variations(string $word): array {
        $variations = [];

        // Einfache deutsche Plural-Regeln
        // Diese sind nicht perfekt, decken aber viele Fälle ab

// Endet auf 'e' → 'n' anhängen (Schule → Schulen)
        if (substr($word, -1) === 'e') {
            $variations[] = $word . 'n';
        }

        // Endet auf Konsonant → 'en' anhängen (Klasse → Klassen)
        if (!in_array(substr($word, -1), ['a', 'e', 'i', 'o', 'u', 'ä', 'ö', 'ü'])) {
            $variations[] = $word . 'en';
        }

// Endet auf 'en' → 'e' entfernen (Schulen → Schule)
        if (substr($word, -2) === 'en' && strlen($word) > 3) {
            $variations[] = substr($word, 0, -2);
            $variations[] = substr($word, 0, -1);
        }

        // Endet auf 'n' → entfernen (Schulen → Schule)
        if (substr($word, -1) === 'n' && strlen($word) > 3) {
            $variations[] = substr($word, 0, -1);
        }

// Endet auf 's' → 's' entfernen (Autos → Auto)
        if (substr($word, -1) === 's' && strlen($word) > 3) {
            $variations[] = substr($word, 0, -1);
        }

        // Endet auf 'er' → 'er' entfernen (Lehrer → Lehr, aber auch Lehrer)
        if (substr($word, -2) === 'er' && strlen($word) > 4) {
            $variations[] = substr($word, 0, -2);
        }

        // Umlaute (ä → a, ö → o, ü → u)
        if (strpos($word, 'ä') !== false || strpos($word, 'ö') !== false || strpos($word, 'ü') !== false) {
            $umlaut_variant = str_replace(
                ['ä', 'ö', 'ü'],
                ['a', 'o', 'u'],
                $word
            );
            $variations[] = $umlaut_variant;
        }

        // Keine Umlaute (a → ä, o → ö, u → ü) - nur bei kurzen Wörtern
        if (strlen($word) <= 8) {
            if (strpos($word, 'a') !== false) {
                $variations[] = str_replace('a', 'ä', $word);
            }
            if (strpos($word, 'o') !== false) {
                $variations[] = str_replace('o', 'ö', $word);
            }
            if (strpos($word, 'u') !== false) {
                $variations[] = str_replace('u', 'ü', $word);
            }
        }

        return $variations;
    }

    /**
     * Normalisiert Text für die Verarbeitung
     *
     * @param string $text Der Text
     * @return string Normalisierter Text
     * @since 1.0.0
     */
    private function normalize_text(string $text): string {
        // Zu Kleinbuchstaben
        $text = mb_strtolower($text, 'UTF-8');

        // Sonderzeichen entfernen (außer Umlaute, ß und Leerzeichen)
        $text = preg_replace('/[^a-zäöüß0-9\s]/', ' ', $text);

        // Mehrfache Leerzeichen entfernen
        $text = preg_replace('/\s+/', ' ', $text);

        // Trimmen
        $text = trim($text);

        return $text;
    }

    /**
     * Generiert einen Preview der Keywords (für Admin-UI)
     *
     * @param string $question Die Frage
     * @param string $answer Die Antwort
     * @return array Array mit Keywords und deren Score
     * @since 1.0.0
     */
    public function preview_keywords(string $question, string $answer = ''): array {
        $keywords_string = $this->generate_keywords($question, $answer);
        $keywords = array_map('trim', explode(',', $keywords_string));

        $preview = [];
        foreach ($keywords as $keyword) {
            $preview[] = [
                'keyword' => $keyword,
                'length' => strlen($keyword),
                'from_question' => stripos($question, $keyword) !== false,
                'from_answer' => !empty($answer) && stripos($answer, $keyword) !== false,
            ];
        }

        return $preview;
    }
}
