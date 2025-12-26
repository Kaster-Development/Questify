<?php
/**
 * Plugin Name:       Questify
 * Plugin URI:        https://github.com/Kaster-Development/Questify
 * Description:       Intelligent FAQ chatbot with backend management and email integration
 * Version:           1.0.5
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
define('QUESTIFY_VERSION', '1.0.5');
define('QUESTIFY_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('QUESTIFY_PLUGIN_URL', plugin_dir_url(__FILE__));
define('QUESTIFY_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Plugin activation
 */
function activate_questify(): void {
    require_once QUESTIFY_PLUGIN_DIR . 'includes/class-chatbot-activator.php';
    Chatbot_Activator::activate();
}

/**
 * Plugin deactivation
 */
function deactivate_questify(): void {
    require_once QUESTIFY_PLUGIN_DIR . 'includes/class-chatbot-deactivator.php';
    Chatbot_Deactivator::deactivate();
}

register_activation_hook(__FILE__, 'activate_questify');
register_deactivation_hook(__FILE__, 'deactivate_questify');

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
    private ?Chatbot_Admin $admin = null;

    /**
     * Frontend instance
     */
    private ?Chatbot_Frontend $frontend = null;

    /**
     * AJAX instance
     */
    private ?Chatbot_Ajax $ajax = null;

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
        require_once QUESTIFY_PLUGIN_DIR . 'includes/class-chatbot-database.php';
        require_once QUESTIFY_PLUGIN_DIR . 'includes/class-chatbot-email.php';
        require_once QUESTIFY_PLUGIN_DIR . 'includes/class-chatbot-matcher.php';
        require_once QUESTIFY_PLUGIN_DIR . 'includes/class-chatbot-keyword-generator.php';
        require_once QUESTIFY_PLUGIN_DIR . 'includes/class-chatbot-ajax.php';

        // Admin classes
        if (is_admin()) {
            require_once QUESTIFY_PLUGIN_DIR . 'includes/class-chatbot-admin.php';
        }

        // Frontend classes
        if (!is_admin()) {
            require_once QUESTIFY_PLUGIN_DIR . 'includes/class-chatbot-frontend.php';
        }
    }

    /**
     * Set up localization
     */
    private function set_locale(): void {
        add_action('plugins_loaded', function() {
            load_plugin_textdomain(
                'questify',
                false,
                dirname(QUESTIFY_PLUGIN_BASENAME) . '/languages/'
            );
        });
    }

    /**
     * Define admin hooks
     */
    private function define_admin_hooks(): void {
        if (is_admin()) {
            $this->admin = new Chatbot_Admin();
        }
    }

    /**
     * Define public hooks
     */
    private function define_public_hooks(): void {
        if (!is_admin()) {
            $this->frontend = new Chatbot_Frontend();
        }
    }

    /**
     * Define AJAX hooks
     */
    private function define_ajax_hooks(): void {
        $this->ajax = new Chatbot_Ajax();
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
    if (get_transient('chatbot_activation_redirect')) {
        delete_transient('chatbot_activation_redirect');
        if (!isset($_GET['activate-multi'])) {
            wp_safe_redirect(admin_url('admin.php?page=chatbot-dashboard&welcome=1'));
            exit;
        }
    }
}
add_action('admin_init', 'questify_activation_redirect');
