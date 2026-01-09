<?php
/**
 * FAQ-Matching-Algorithmus-Klasse
 *
 * @package Questify
 * @since 1.0.0
 */

// Direkten Zugriff verhindern
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Klasse für FAQ-Matching (ohne KI)
 */
class Questi_Matcher {

    /**
     * Singleton-Instanz
     */
    private static ?Questi_Matcher $instance = null;

    /**
     * Database-Instanz
     */
    private Questi_Database $db;

    /**
     * Stoppwörter
     */
    private array $stopwords = [];

    /**
     * Einstellungen
     */
    private int $min_score;
    private int $confident_score; // Neuer Schwellenwert für sichere Matches
    private bool $fuzzy_matching;
    private int $levenshtein_threshold;

    /**
     * Singleton-Methode
     */
    public static function get_instance(): Questi_Matcher {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Konstruktor
     */
    private function __construct() {
        $this->db = Questi_Database::get_instance();
        $this->load_settings();
    }

    /**
     * Lädt Einstellungen
     *
     * @return void
     * @since 1.0.0
     */
    private function load_settings(): void {
        $this->min_score = (int) get_option('questi_min_score', 60);
        $this->confident_score = (int) get_option('questi_confident_score', 85); // Neuer Schwellenwert
        $this->fuzzy_matching = (bool) get_option('questi_fuzzy_matching', true);
        $this->levenshtein_threshold = (int) get_option('questi_levenshtein_threshold', 3);

        $stopwords_string = get_option('questi_stopwords', 'der, die, das, und, oder, aber, ist, sind, haben, sein, ein, eine, mit, von, zu, für, auf, an, in, aus');
        $this->stopwords = array_map('trim', explode(',', $stopwords_string));
    }

    /**
     * Findet beste Antwort für Frage
     *
     * @param string $question Benutzerfrage
     * @return array|null [faq => object, score => int, alternatives => array, needs_disambiguation => bool, low_confidence => bool] oder null
     * @since 1.0.0
     */
    public function find_best_match(string $question): ?array {
        if (empty(trim($question))) {
            return null;
        }

        // Frage normalisieren
        $normalized_question = $this->normalize_text($question);
        $normalized_question = $this->remove_stop_words($normalized_question);
        
        // Wichtige Themenwörter aus der Originalfrage extrahieren
        $topic_words = $this->extract_topic_words($question);

        // Alle aktiven FAQs holen
        $faqs = $this->db->get_active_faqs();

        if (empty($faqs)) {
            return null;
        }

        // Score für jede FAQ berechnen
        $scored_faqs = [];
        foreach ($faqs as $faq) {
            $score_data = $this->calculate_score_advanced($normalized_question, $topic_words, $faq);
            if ($score_data['score'] >= $this->min_score) {
                $scored_faqs[] = [
                    'faq' => $faq,
                    'score' => $score_data['score'],
                    'keyword_match' => $score_data['keyword_match'],
                    'topic_match' => $score_data['topic_match'],
                ];
            }
        }

        if (empty($scored_faqs)) {
            return null;
        }

        // Nach Score sortieren
        usort($scored_faqs, function($a, $b) {
            return $b['score'] <=> $a['score'];
        });

        // Beste Antwort
        $best = $scored_faqs[0];
        
        // LOW CONFIDENCE CHECK:
        // Wenn der Score unter dem confident_score liegt UND
        // kein direkter Keyword-Match vorliegt, ist das unsicher
        $low_confidence = false;
        if ($best['score'] < $this->confident_score && !$best['keyword_match']) {
            $low_confidence = true;
        }
        
        // Wenn kein Themen-Match vorliegt, ist das ebenfalls unsicher
        if (!$best['topic_match'] && $best['score'] < 100) {
            $low_confidence = true;
        }

        // Alternative Vorschläge sammeln (wenn Score-Differenz < 20)
        // WICHTIG: Größeres Fenster für bessere Disambiguierung bei ähnlichen FAQs
        $alternatives = [];
        for ($i = 1; $i < count($scored_faqs); $i++) {
            $score_diff = $best['score'] - $scored_faqs[$i]['score'];

            // Wenn Score sehr nah beieinander (< 20 Punkte Unterschied)
            // ODER wenn mehrere FAQs über dem Mindest-Score liegen
            if ($score_diff < 20) {
                $alternatives[] = $scored_faqs[$i];
            }
        }

        // Disambiguierung nötig?
        // NEUE LOGIK: Aggressivere Disambiguierung
        // - Wenn beste Antwort Score < 85 UND es gibt Alternativen
        // - ODER wenn es 2+ FAQs mit Score > min_score gibt (unabhängig vom besten Score)
        $needs_disambiguation = false;

        if (count($scored_faqs) >= 2) {
            // Fall 1: Unsicherer Match mit Alternativen
            if ($best['score'] < 85 && count($alternatives) > 0) {
                $needs_disambiguation = true;
            }
            // Fall 2: Mehrere gute Matches (beide > 70)
            elseif (count($alternatives) > 0 && $alternatives[0]['score'] >= 70) {
                $needs_disambiguation = true;
            }
        }

        // Intentionally no debug logging (avoid error_log() in production plugins).

        return [
            'faq' => $best['faq'],
            'score' => $best['score'],
            'alternatives' => $alternatives,
            'needs_disambiguation' => $needs_disambiguation,
            'low_confidence' => $low_confidence,
            'keyword_match' => $best['keyword_match'],
            'topic_match' => $best['topic_match'],
        ];
    }

    /**
     * Extrahiert wichtige Themenwörter aus der Frage
     *
     * @param string $question Originalfrage
     * @return array Themenwörter
     * @since 1.0.0
     */
    private function extract_topic_words(string $question): array {
        $question_lower = mb_strtolower($question, 'UTF-8');
        
        // Wichtige Themen-Kategorien
        $topic_patterns = [
            'oeffnungszeiten' => ['öffnungszeiten', 'geöffnet', 'offen', 'geschlossen', 'wann', 'uhrzeit', 'heiligabend', 'silvester', 'feiertag', 'wochenende'],
            'preise' => ['preis', 'kosten', 'euro', 'bezahlen', 'zahlen', 'karte', 'bar', 'gutschein', 'rabatt', 'ermäßigung'],
            'gutscheine' => ['gutschein', 'geschenk', 'verschenken', 'fünferkarte', 'zehnerkarte', 'mehrfachkarte'],
            'ausruestung' => ['helm', 'schutzhelm', 'schlittschuh', 'handschuh', 'ausrüstung', 'ausleihen', 'leihen', 'mieten'],
            'kurse' => ['kurs', 'lernen', 'unterricht', 'anfänger', 'training', 'eiskunstlauf', 'eishockey'],
            'veranstaltungen' => ['veranstaltung', 'party', 'geburtstag', 'gruppe', 'event', 'feier'],
            'schleifen' => ['schleifen', 'schärfen', 'geschliffen'],
            'groessen' => ['größe', 'schuhgröße', 'nummer'],
        ];
        
        $found_topics = [];
        foreach ($topic_patterns as $topic => $patterns) {
            foreach ($patterns as $pattern) {
                if (str_contains($question_lower, $pattern)) {
                    $found_topics[$topic] = true;
                    break;
                }
            }
        }
        
        return array_keys($found_topics);
    }

    /**
     * Berechnet erweiterten Matching-Score mit Themen-Check
     *
     * @param string $user_question Normalisierte Benutzerfrage
     * @param array $topic_words Themenwörter aus der Frage
     * @param object $faq FAQ-Objekt
     * @return array ['score' => int, 'keyword_match' => bool, 'topic_match' => bool]
     * @since 1.0.0
     */
    private function calculate_score_advanced(string $user_question, array $topic_words, object $faq): array {
        $score = 0;
        $keyword_match = false;
        $topic_match = false;

        // FAQ-Frage und Keywords normalisieren
        $faq_question = $this->normalize_text($faq->question);
        $faq_question = $this->remove_stop_words($faq_question);
        
        // Volltext der FAQ (Frage + Antwort + Keywords) für Themen-Check
        $faq_fulltext = mb_strtolower($faq->question . ' ' . $faq->answer . ' ' . ($faq->keywords ?? ''), 'UTF-8');

        // THEMEN-CHECK: Prüfen ob FAQ zum Thema der Frage passt
        if (!empty($topic_words)) {
            $topic_patterns = [
                'oeffnungszeiten' => ['öffnungszeiten', 'geöffnet', 'offen', 'geschlossen', 'uhrzeit', 'heiligabend', 'silvester', 'feiertag'],
                'preise' => ['preis', 'kosten', 'euro', 'bezahlen', 'zahlen', 'karte', 'bar'],
                'gutscheine' => ['gutschein', 'geschenk', 'fünferkarte', 'zehnerkarte', 'mehrfachkarte'],
                'ausruestung' => ['helm', 'schutzhelm', 'handschuh', 'ausrüstung', 'ausleihen', 'leihen', 'mieten'],
                'kurse' => ['kurs', 'lernen', 'unterricht', 'anfänger', 'training'],
                'veranstaltungen' => ['veranstaltung', 'party', 'geburtstag', 'gruppe', 'event', 'feier'],
                'schleifen' => ['schleifen', 'schärfen', 'geschliffen'],
                'groessen' => ['größe', 'schuhgröße', 'nummer'],
            ];
            
            foreach ($topic_words as $topic) {
                if (isset($topic_patterns[$topic])) {
                    foreach ($topic_patterns[$topic] as $pattern) {
                        if (str_contains($faq_fulltext, $pattern)) {
                            $topic_match = true;
                            $score += 40; // Bonus für Themen-Match
                            break 2;
                        }
                    }
                }
            }
        } else {
            // Wenn keine spezifischen Themenwörter gefunden wurden, ist topic_match neutral
            $topic_match = true;
        }

        // 1. Exaktes Keyword-Matching (+30 Punkte pro Match)
        if (!empty($faq->keywords)) {
            $keywords = array_map('trim', explode(',', $faq->keywords));
            foreach ($keywords as $keyword) {
                $keyword_normalized = $this->normalize_text($keyword);
                if (!empty($keyword_normalized) && str_contains($user_question, $keyword_normalized)) {
                    $score += 30;
                    $keyword_match = true;
                }
            }
        }

        // 2. Fuzzy Keyword-Matching (+15 Punkte bei Levenshtein <= Threshold) - reduziert von 20
        if ($this->fuzzy_matching && !empty($faq->keywords)) {
            $user_words = explode(' ', $user_question);
            $keywords = array_map('trim', explode(',', $faq->keywords));

            foreach ($keywords as $keyword) {
                $keyword_normalized = $this->normalize_text($keyword);
                foreach ($user_words as $user_word) {
                    if (strlen($user_word) >= 4 && strlen($keyword_normalized) >= 4) {
                        $distance = levenshtein($user_word, $keyword_normalized);
                        if ($distance > 0 && $distance <= $this->levenshtein_threshold) {
                            $score += 15;
                            break;
                        }
                    }
                }
            }
        }

        // 3. Bigram-Matching (+8 Punkte pro Match) - reduziert von 10
        $user_bigrams = $this->get_bigrams($user_question);
        $faq_bigrams = $this->get_bigrams($faq_question);
        $common_bigrams = array_intersect($user_bigrams, $faq_bigrams);
        $score += count($common_bigrams) * 8;

        // 4. Gesamtähnlichkeit (+20 Punkte max) - reduziert von 25
        similar_text($user_question, $faq_question, $percent);
        $score += (int) (($percent / 100) * 20);

        // 5. Wort-Übereinstimmungen (+3 Punkte pro Wort) - reduziert von 5
        // Nur für Wörter mit mindestens 4 Buchstaben (um kurze Füllwörter zu ignorieren)
        $user_words = array_filter(explode(' ', $user_question), fn($w) => strlen($w) >= 4);
        $faq_words = array_filter(explode(' ', $faq_question), fn($w) => strlen($w) >= 4);
        $common_words = array_intersect($user_words, $faq_words);
        $score += count($common_words) * 3;

        return [
            'score' => $score,
            'keyword_match' => $keyword_match,
            'topic_match' => $topic_match,
        ];
    }

    /**
     * Berechnet Matching-Score (Legacy-Methode für Kompatibilität)
     *
     * @param string $user_question Normalisierte Benutzerfrage
     * @param object $faq FAQ-Objekt
     * @return int Score (0-100+)
     * @since 1.0.0
     */
    private function calculate_score(string $user_question, object $faq): int {
        $result = $this->calculate_score_advanced($user_question, [], $faq);
        return $result['score'];
    }

    /**
     * Normalisiert Text
     *
     * @param string $text Zu normalisierender Text
     * @return string
     * @since 1.0.0
     */
    private function normalize_text(string $text): string {
        // 1. Zu Kleinbuchstaben
        $text = mb_strtolower($text, 'UTF-8');

        // 2. Umlaute normalisieren (für besseres Matching)
        $text = str_replace(
            ['ä', 'ö', 'ü', 'ß', 'Ä', 'Ö', 'Ü'],
            ['ae', 'oe', 'ue', 'ss', 'ae', 'oe', 'ue'],
            $text
        );

        // 3. Sonderzeichen entfernen (außer Leerzeichen)
        $text = preg_replace('/[^a-z0-9\s]/', '', $text);

        // 4. Mehrfache Leerzeichen entfernen
        $text = preg_replace('/\s+/', ' ', $text);

        // 5. Trimmen
        $text = trim($text);

        return $text;
    }

    /**
     * Entfernt Stoppwörter
     *
     * @param string $text Text
     * @return string
     * @since 1.0.0
     */
    private function remove_stop_words(string $text): string {
        $words = explode(' ', $text);
        $filtered = array_diff($words, $this->stopwords);
        return implode(' ', $filtered);
    }

    /**
     * Erstellt Bigrams (Wortpaare)
     *
     * @param string $text Text
     * @return array
     * @since 1.0.0
     */
    private function get_bigrams(string $text): array {
        $words = explode(' ', $text);
        $bigrams = [];

        for ($i = 0; $i < count($words) - 1; $i++) {
            $bigrams[] = $words[$i] . ' ' . $words[$i + 1];
        }

        return $bigrams;
    }

    /**
     * Validiert Matching-Qualität
     *
     * @param int $score Matching-Score
     * @return string Qualität: 'high', 'medium', 'low'
     * @since 1.0.0
     */
    public function get_match_quality(int $score): string {
        if ($score >= 80) {
            return 'high'; // Sehr sicherer Match
        } elseif ($score >= 60) {
            return 'medium'; // Wahrscheinlicher Match
        } else {
            return 'low'; // Unsicherer Match
        }
    }
}
