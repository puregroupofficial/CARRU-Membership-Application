<?php
/**
 * The plugin bootstrap file
 *
 * @link              https://caaru.ca
 * @since             2.3.0
 * @package           CAARU_Membership
 *
 * @wordpress-plugin
 * Plugin Name:       CAARU Membership Application Suite
 * Plugin URI:        https://caaru.ca
 * Description:       A complete suite for managing CAARU membership applications.
 * Version:           2.3.0
 * Author:            CAARU (Engineered by Gemini)
 * Author URI:        https://caaru.ca
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       caaru-membership
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Define constants
 */
define( 'CAARU_MEMBERSHIP_VERSION', '2.3.0' );
define( 'CAARU_MEMBERSHIP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'CAARU_MEMBERSHIP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * The core plugin class.
 */
require CAARU_MEMBERSHIP_PLUGIN_DIR . 'includes/class-caaru-membership.php';

/**
 * Begins execution of the plugin.
 */
function run_caaru_membership() {
    $plugin = new CAARU_Membership();
    $plugin->run();
}
run_caaru_membership();

