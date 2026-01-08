<?php
/**
 * Plugin Name:       Questify
 * Plugin URI:        https://github.com/Kaster-Development/Questify
 * Description:       Intelligent FAQ chatbot with backend management and email integration
 * Version:           1.0.7
 * Requires at least: 6.5
 * Requires PHP:      8.2
 * Author:            Steffen Kaster
 * Author URI:        https://kaster-development.de
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       questify
 * Domain Path:       /languages
 *
 * @package Questify
 * @author Steffen Kaster
 * @copyright 2025 Kaster Development
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('QUESTIFY_VERSION', '1.0.6');
define('QUESTIFY_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('QUESTIFY_PLUGIN_URL', plugin_dir_url(__FILE__));
define('QUESTIFY_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Plugin activation
 */
function questify_activate(): void {
    require_once QUESTIFY_PLUGIN_DIR . 'includes/class-questi-activator.php';
    Questi_Activator::activate();
}

/**
 * Plugin deactivation
 */
function questify_deactivate(): void {
    require_once QUESTIFY_PLUGIN_DIR . 'includes/class-questi-deactivator.php';
    Questi_Deactivator::deactivate();
}

register_activation_hook(__FILE__, 'questify_activate');
register_deactivation_hook(__FILE__, 'questify_deactivate');

/**
 * Main plugin class
 */
class Questify {

    /**
     * Singleton instance
     */
    private static ?Questify $instance = null;

    /**
     * Admin instance
     */
    private ?Questi_Admin $admin = null;

    /**
     * Frontend instance
     */
    private ?Questi_Frontend $frontend = null;

    /**
     * AJAX instance
     */
    private ?Questi_Ajax $ajax = null;

    /**
     * Singleton method
     */
    public static function get_instance(): Questify {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        $this->load_dependencies();
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_public_hooks();
        $this->define_ajax_hooks();
    }

    /**
     * Load all required files
     */
    private function load_dependencies(): void {
        // Core classes
        require_once QUESTIFY_PLUGIN_DIR . 'includes/class-questi-database.php';
        require_once QUESTIFY_PLUGIN_DIR . 'includes/class-questi-email.php';
        require_once QUESTIFY_PLUGIN_DIR . 'includes/class-questi-matcher.php';
        require_once QUESTIFY_PLUGIN_DIR . 'includes/class-questi-keyword-generator.php';
        require_once QUESTIFY_PLUGIN_DIR . 'includes/class-questi-ajax.php';

        // Admin classes
        if (is_admin()) {
            require_once QUESTIFY_PLUGIN_DIR . 'includes/class-questi-admin.php';
        }

        // Frontend classes
        if (!is_admin()) {
            require_once QUESTIFY_PLUGIN_DIR . 'includes/class-questi-frontend.php';
        }
    }

    /**
     * Set up localization
     */
    private function set_locale(): void {
        // Translation files are loaded automatically on WordPress.org.
    }

    /**
     * Define admin hooks
     */
    private function define_admin_hooks(): void {
        if (is_admin()) {
            $this->admin = new Questi_Admin();
        }
    }

    /**
     * Define public hooks
     */
    private function define_public_hooks(): void {
        if (!is_admin()) {
            $this->frontend = new Questi_Frontend();
        }
    }

    /**
     * Define AJAX hooks
     */
    private function define_ajax_hooks(): void {
        $this->ajax = new Questi_Ajax();
    }
}

/**
 * Initialize plugin
 */
function questify_init(): void {
    Questify::get_instance();
}
add_action('plugins_loaded', 'questify_init');

/**
 * Activation redirect
 */
function questify_activation_redirect(): void {
    if (get_transient('questi_activation_redirect')) {
        delete_transient('questi_activation_redirect');
        if (!isset($_GET['activate-multi'])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Core flag, no action based on user input.
            wp_safe_redirect(admin_url('admin.php?page=questi-dashboard&welcome=1'));
            exit;
        }
    }
}
add_action('admin_init', 'questify_activation_redirect');
