<?php
/**
 * Plugin Name:       t1 Schema
 * Plugin URI:        https://github.com/teil1-com/t1-schema
 * Description:       High-performance Schema.org JSON-LD markup with granular control. SaaS-grade visual editor for SEO professionals.
 * Version:           2.1.0
 * Requires at least: 6.0
 * Requires PHP:      8.0
 * Author:            teil1 development
 * Author URI:        https://teil1.com
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       t1-schema
 * Domain Path:       /languages
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Plugin constants.
 */
define( 'T1SCHEMA_VERSION', '2.1.0' );
// Tracks the table schema, not the release. Bump only when tables change.
define( 'T1SCHEMA_DB_VERSION', '1.5.0' );
define( 'T1SCHEMA_FILE', __FILE__ );
define( 'T1SCHEMA_PATH', plugin_dir_path( __FILE__ ) );
define( 'T1SCHEMA_URL', plugin_dir_url( __FILE__ ) );
define( 'T1SCHEMA_BASENAME', plugin_basename( __FILE__ ) );

/**
 * PSR-4 style autoloader for T1Schema classes.
 *
 * Maps T1Schema\{ClassName} → includes/{ClassName}.php
 */
spl_autoload_register( function ( string $class ) {
    $prefix    = 'T1Schema\\';
    $base_dir  = T1SCHEMA_PATH . 'includes/';

    if ( strncmp( $prefix, $class, strlen( $prefix ) ) !== 0 ) {
        return;
    }

    $relative_class = substr( $class, strlen( $prefix ) );
    $file           = $base_dir . str_replace( '\\', '/', $relative_class ) . '.php';

    if ( file_exists( $file ) ) {
        require $file;
    }
} );

/**
 * Activation hook.
 */
register_activation_hook( __FILE__, function () {
    $activator = new T1Schema\Activator();
    $activator->activate();
} );

/**
 * Deactivation hook.
 */
register_deactivation_hook( __FILE__, function () {
    $deactivator = new T1Schema\Deactivator();
    $deactivator->deactivate();
} );

/**
 * Boot the plugin after WordPress is fully loaded.
 */
add_action( 'plugins_loaded', function () {

    // Auto-migrate: create new tables if DB version is outdated.
    $current_db = get_option( 't1schema_db_version', '0' );
    if ( version_compare( $current_db, T1SCHEMA_DB_VERSION, '<' ) ) {
        $activator = new T1Schema\Activator();
        $activator->activate();
    }

    // Admin panel (menu + assets).
    if ( is_admin() ) {
        $admin = new T1Schema\Admin();
        $admin->init();

        // Post editor meta box.
        $meta_box = new T1Schema\MetaBox();
        $meta_box->init();
    }

    // REST API endpoints.
    $rest_api = new T1Schema\RestApi();
    $rest_api->init();

    // Frontend JSON-LD output.
    if ( ! is_admin() ) {
        $frontend = new T1Schema\Frontend();
        $frontend->init();

        // Admin bar schema indicator (frontend only, for admins).
        $admin_bar = new T1Schema\AdminBar();
        $admin_bar->init();

        // WooCommerce: suppress its own structured data where it would
        // duplicate what t1 Schema is already rendering. Shares $frontend
        // so the overlap check and the actual render use one assembly pass.
        $wc_compat = new T1Schema\WooCommerceCompat( $frontend );
        $wc_compat->init();
    }

    /**
     * Conflict suppression (mu-plugin case).
     *
     * When enabled, removes another plugin's schema output so the page does
     * not end up with duplicate JSON-LD in <head>. Opt-in only: it is off
     * until the site owner turns it on in Settings, because it changes the
     * behaviour of software t1 Schema does not own.
     *
     * The WooCommerce equivalent is handled separately by WooCommerceCompat
     * above, since it needs to run per-request against the current page's
     * assembled schemas rather than once at boot.
     *
     * @since 1.0.0
     */
    add_action( 'init', function () {
        /**
         * Whether to suppress conflicting schema output from other plugins.
         *
         * @since 1.5.0
         * @param bool $suppress Defaults to the 't1schema_suppress_conflicts' option (off).
         */
        if ( apply_filters( 't1schema_suppress_conflicts', (bool) get_option( 't1schema_suppress_conflicts', false ) ) ) {
            if ( function_exists( 'teil1_schema_output' ) ) {
                remove_action( 'wp_head', 'teil1_schema_output' );
            }
        }

        /**
         * Fires after t1 Schema has initialized.
         *
         * @since 1.0.0
         */
        do_action( 't1schema_loaded' );
    }, 99 );

    /**
     * WP-CLI commands.
     */
    if ( defined( 'WP_CLI' ) && WP_CLI ) {
        \WP_CLI::add_command( 't1-schema', T1Schema\CLI::class );
    }

} );
