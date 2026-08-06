<?php
/**
 * Plugin Name: Volksimmobilien Propstack
 * Description: Read-only Propstack sync with active listings, single-property pages and a sold-property gallery.
 * Version: 1.1.0
 * Author: volksimmobilien km GmbH
 * Text Domain: volks-propstack
 * Requires at least: 6.6
 * Requires PHP: 8.1
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'VOLKS_PROPSTACK_VERSION', '1.1.0' );
define( 'VOLKS_PROPSTACK_FILE', __FILE__ );
define( 'VOLKS_PROPSTACK_PATH', plugin_dir_path( __FILE__ ) );
define( 'VOLKS_PROPSTACK_URL', plugin_dir_url( __FILE__ ) );

require_once VOLKS_PROPSTACK_PATH . 'includes/class-volks-propstack-api.php';
require_once VOLKS_PROPSTACK_PATH . 'includes/class-volks-propstack-post-type.php';
require_once VOLKS_PROPSTACK_PATH . 'includes/class-volks-propstack-sync.php';
require_once VOLKS_PROPSTACK_PATH . 'includes/class-volks-propstack-frontend.php';
require_once VOLKS_PROPSTACK_PATH . 'includes/class-volks-propstack-admin.php';
require_once VOLKS_PROPSTACK_PATH . 'includes/class-volks-propstack-showcase.php';

/**
 * Start the integration after all plugins are available.
 */
function volks_propstack_boot() {
	Volks_Propstack_Post_Type::init();
	Volks_Propstack_Sync::init();
	Volks_Propstack_Frontend::init();
	Volks_Propstack_Admin::init();
	Volks_Propstack_Showcase::init();
	add_action( 'init', 'volks_propstack_maybe_upgrade', 99 );
}
add_action( 'plugins_loaded', 'volks_propstack_boot' );

/**
 * Flush rewrite rules once after plugin upgrades that add public routes.
 */
function volks_propstack_maybe_upgrade() {
	if ( VOLKS_PROPSTACK_VERSION === (string) get_option( 'volks_propstack_version', '' ) ) {
		return;
	}
	update_option( 'volks_propstack_version', VOLKS_PROPSTACK_VERSION, false );
	flush_rewrite_rules( false );
}

/**
 * Set safe defaults and register rewrite rules.
 */
function volks_propstack_activate() {
	add_option( 'volks_propstack_enabled', 0, '', false );
	add_option( 'volks_propstack_allowed_statuses', 'Vermarktung', '', false );
	add_option( 'volks_propstack_sold_statuses', 'Verkauft', '', false );
	add_option( 'volks_propstack_key_file', '', '', false );
	add_option( 'volks_propstack_showcase_ids', array(), '', false );
	update_option( 'volks_propstack_version', VOLKS_PROPSTACK_VERSION, false );

	Volks_Propstack_Post_Type::register();
	Volks_Propstack_Post_Type::register_taxonomies();
	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'volks_propstack_activate' );

/**
 * Remove scheduled work. Synced posts stay intact for a safe rollback.
 */
function volks_propstack_deactivate() {
	wp_clear_scheduled_hook( Volks_Propstack_Sync::CRON_HOOK );
	flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'volks_propstack_deactivate' );
