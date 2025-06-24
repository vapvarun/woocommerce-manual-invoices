<?php
/**
 * Plugin Name: WooCommerce Manual Invoices Pro
 * Plugin URI: https://wbcomdesigns.com/woocommerce-manual-invoices
 * Description: Create manual invoices and send "Pay Now" links to customers using WooCommerce's checkout and payment infrastructure. Fully compatible with High-Performance Order Storage (HPOS).
 * Version: 1.0.0
 * Author: Wbcom Designs
 * Author URI: https://wbcomdesigns.com
 * Text Domain: wc-manual-invoices
 * Domain Path: /languages
 * Requires at least: 6.0
 * Tested up to: 6.4
 * WC requires at least: 8.0
 * WC tested up to: 8.5
 * Requires PHP: 8.0
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Network: false
 * Update URI: false
 *
 * @package WooCommerce_Manual_Invoices
 * @version 1.0.0
 * @author Wbcom Designs
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

// Define constants.
define( 'WC_MANUAL_INVOICES_VERSION', '1.0.0' );
define( 'WC_MANUAL_INVOICES_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'WC_MANUAL_INVOICES_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'WC_MANUAL_INVOICES_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

// Load plugin core.
require_once WC_MANUAL_INVOICES_PLUGIN_PATH . 'includes/class-plugin-loader.php';

// Initialize plugin.
\WC_Manual_Invoices\Plugin_Loader::getInstance();
