<?php
/**
 * t1 Schema — Uninstall handler.
 *
 * Fired when the plugin is deleted via the WordPress admin.
 * Conditionally wipes all data based on user preference.
 *
 * @package T1Schema
 * @since   1.0.0
 */

// Safety: only run via WordPress uninstall.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

/**
 * Only wipe data if the user has explicitly opted in.
 */
$t1schema_delete_data = get_option( 't1schema_delete_data_on_uninstall', false );

if ( $t1schema_delete_data ) {
    global $wpdb;

    // 1. Drop the plugin tables.
    foreach ( [ 't1schema_globals', 't1schema_rules' ] as $t1schema_table ) {
        $t1schema_table_name = esc_sql( $wpdb->prefix . $t1schema_table );
        $wpdb->query( "DROP TABLE IF EXISTS {$t1schema_table_name}" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL
    }

    // 2. Delete all post meta.
    $wpdb->query(
        "DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE '_t1schema_%'"
    ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL

    // 3. Delete all options.
    $wpdb->query(
        "DELETE FROM {$wpdb->options} WHERE option_name LIKE 't1schema_%'"
    ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL

    // 4. Clear any transients.
    $wpdb->query(
        "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_t1schema_%' OR option_name LIKE '_transient_timeout_t1schema_%'"
    ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL
}
