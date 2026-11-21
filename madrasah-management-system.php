<?php
/**
 * Plugin Name: ফরমান আলী মণ্ডল রওজাতুল আতফাল মাদরাসা ব্যবস্থাপনা সিস্টেম
 * Plugin URI: https://pantheon.io
 * Description: ফরমান আলী মণ্ডল রওজাতুল আতফাল মাদরাসার শিক্ষার্থী, হাজিরা, বেতন, পরীক্ষার ফি, শিক্ষক হাজিরা, শিক্ষক বেতন ও মাদ্রাসার সার্বিক হিসাব ব্যবস্থাপনা।
 * Version: 1.0.0
 * Author: Antigravity
 * Author URI: https://pantheon.io
 * Text Domain: madrasah-management
 * Domain Path: /languages
 * License: GPL v2 or later
 */

defined( 'ABSPATH' ) || exit;

// Define plugin constants
define( 'MMS_VERSION', '1.0.0' );
define( 'MMS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'MMS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'MMS_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

// Include necessary class files
require_once MMS_PLUGIN_DIR . 'includes/class-mms-db.php';
require_once MMS_PLUGIN_DIR . 'includes/class-mms-admin.php';
require_once MMS_PLUGIN_DIR . 'includes/class-mms-ajax.php';
require_once MMS_PLUGIN_DIR . 'includes/class-mms-receipt.php';

/**
 * Main Plugin Class
 */
class Madrasah_Management_System {

    private static $instance = null;

    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        register_activation_hook( __FILE__, array( 'MMS_DB', 'create_tables' ) );
        add_action( 'plugins_loaded', array( $this, 'init_plugin' ) );
    }

    public function init_plugin() {
        MMS_Admin::init();
        MMS_Ajax::init();
    }
}

// Start the plugin
Madrasah_Management_System::get_instance();
