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
        $this->fuzzy_matching = (bool) get_option('questi_fuzzy_matching', true);
        $this->levenshtein_threshold = (int) get_option('questi_levenshtein_threshold', 3);

        $stopwords_string = get_option('questi_stopwords', 'der, die, das, und, oder, aber, ist, sind, haben, sein, ein, eine, mit, von, zu, für, auf, an, in, aus');
        $this->stopwords = array_map('trim', explode(',', $stopwords_string));
    }

    /**
     * Findet beste Antwort für Frage
     *
     * @param string $question Benutzerfrage
     * @return array|null [faq => object, score => int, alternatives => array, needs_disambiguation => bool] oder null
     * @since 1.0.0
     */
    public function find_best_match(string $question): ?array {
        if (empty(trim($question))) {
            return null;
        }

        // Frage normalisieren
        $normalized_question = $this->normalize_text($question);
        $normalized_question = $this->remove_stop_words($normalized_question);

        // Alle aktiven FAQs holen
        $faqs = $this->db->get_active_faqs();

        if (empty($faqs)) {
            return null;
        }

        // Score für jede FAQ berechnen
        $scored_faqs = [];
        foreach ($faqs as $faq) {
            $score = $this->calculate_score($normalized_question, $faq);
            if ($score >= $this->min_score) {
                $scored_faqs[] = [
                    'faq' => $faq,
                    'score' => $score,
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

        // Alternative Vorschläge sammeln (wenn Score-Differenz < 20)
        // WICHTIG: GröÖŸeres Fenster für bessere Disambiguierung bei ähnlichen FAQs
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
        ];
    }

    /**
     * Berechnet Matching-Score
     *
     * @param string $user_question Normalisierte Benutzerfrage
     * @param object $faq FAQ-Objekt
     * @return int Score (0-100+)
     * @since 1.0.0
     */
    private function calculate_score(string $user_question, object $faq): int {
        $score = 0;

        // FAQ-Frage und Keywords normalisieren
        $faq_question = $this->normalize_text($faq->question);
        $faq_question = $this->remove_stop_words($faq_question);

        // 1. Exaktes Keyword-Matching (+30 Punkte pro Match)
        if (!empty($faq->keywords)) {
            $keywords = array_map('trim', explode(',', $faq->keywords));
            foreach ($keywords as $keyword) {
                $keyword = $this->normalize_text($keyword);
                if (str_contains($user_question, $keyword)) {
                    $score += 30;
                }
            }
        }

        // 2. Fuzzy Keyword-Matching (+20 Punkte bei Levenshtein <= Threshold)
        if ($this->fuzzy_matching && !empty($faq->keywords)) {
            $user_words = explode(' ', $user_question);
            $keywords = array_map('trim', explode(',', $faq->keywords));

            foreach ($keywords as $keyword) {
                $keyword = $this->normalize_text($keyword);
                foreach ($user_words as $user_word) {
                    if (strlen($user_word) >= 4 && strlen($keyword) >= 4) {
                        $distance = levenshtein($user_word, $keyword);
                        if ($distance <= $this->levenshtein_threshold) {
                            $score += 20;
                            break;
                        }
                    }
                }
            }
        }

        // 3. Bigram-Matching (+10 Punkte pro Match)
        $user_bigrams = $this->get_bigrams($user_question);
        $faq_bigrams = $this->get_bigrams($faq_question);
        $common_bigrams = array_intersect($user_bigrams, $faq_bigrams);
        $score += count($common_bigrams) * 10;

        // 4. Gesamtähnlichkeit (+25 Punkte max)
        similar_text($user_question, $faq_question, $percent);
        $score += (int) (($percent / 100) * 25);

        // 5. Wort-Übereinstimmungen (+5 Punkte pro Wort)
        $user_words = explode(' ', $user_question);
        $faq_words = explode(' ', $faq_question);
        $common_words = array_intersect($user_words, $faq_words);
        $score += count($common_words) * 5;

        return $score;
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
            ['ä', 'ö', 'ü', 'ÖŸ', 'Ö„', 'Ö–', 'Ü'],
            ['ae', 'oe', 'ue', 'ss', 'ae', 'oe', 'ue'],
            $text
        );

        // 3. Sonderzeichen entfernen (auÖŸer Leerzeichen)
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
