<?php

namespace T1Schema;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Handles plugin activation.
 *
 * Creates the custom database table and sets default options.
 *
 * @package T1Schema
 * @since   1.0.0
 */
class Activator {

    /**
     * Run activation routines.
     *
     * @return void
     */
    public function activate(): void {
        // Migrate data from old SchemaPro install if present.
        $this->migrate_from_schemapro();

        $this->create_tables();
        $this->set_default_options();
        $this->maybe_seed_defaults();

        // Flush rewrite rules for any custom endpoints.
        flush_rewrite_rules();
    }

    /**
     * Create the t1schema_globals table using dbDelta.
     *
     * @return void
     */
    private function create_tables(): void {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        // Global schemas table (existing).
        $globals_table = $wpdb->prefix . 't1schema_globals';
        $sql_globals   = "CREATE TABLE {$globals_table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            schema_type varchar(100) NOT NULL,
            schema_data longtext NOT NULL,
            status varchar(20) NOT NULL DEFAULT 'active',
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY idx_schema_type (schema_type),
            KEY idx_status (status)
        ) {$charset_collate};";
        dbDelta( $sql_globals );

        // Schema rules table (v1.2).
        $rules_table = $wpdb->prefix . 't1schema_rules';
        $sql_rules   = "CREATE TABLE {$rules_table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            rule_name varchar(255) NOT NULL,
            schema_type varchar(100) NOT NULL,
            schema_data longtext NOT NULL,
            conditions longtext NOT NULL,
            priority int NOT NULL DEFAULT 10,
            status varchar(20) NOT NULL DEFAULT 'active',
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY idx_status (status),
            KEY idx_priority (priority)
        ) {$charset_collate};";
        dbDelta( $sql_rules );

        update_option( 't1schema_db_version', T1SCHEMA_DB_VERSION );
    }

    /**
     * Set default plugin options.
     *
     * @return void
     */
    private function set_default_options(): void {
        add_option( 't1schema_delete_data_on_uninstall', false );
        add_option( 't1schema_suppress_conflicts', false );
        add_option( 't1schema_db_version', T1SCHEMA_DB_VERSION );
    }

    /**
     * Seed default Organization schema from site info if the table is empty.
     *
     * @return void
     */
    private function maybe_seed_defaults(): void {
        global $wpdb;

        $table_name = esc_sql( $wpdb->prefix . 't1schema_globals' );
        $count      = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table_name}" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL

        if ( $count > 0 ) {
            return;
        }

        // Seed a default Organization schema.
        $default_org = [
            '@context' => 'https://schema.org',
            '@type'    => 'Organization',
            'name'     => '{{site_name}}',
            'url'      => '{{site_url}}',
            'logo'     => '{{site_logo}}',
        ];

        $wpdb->insert(
            $table_name,
            [
                'schema_type' => 'Organization',
                'schema_data' => wp_json_encode( $default_org ),
                'status'      => 'active',
            ],
            [ '%s', '%s', '%s' ]
        );

        // Seed a default WebSite schema.
        $default_website = [
            '@context' => 'https://schema.org',
            '@type'    => 'WebSite',
            'name'     => '{{site_name}}',
            'url'      => '{{site_url}}',
        ];

        $wpdb->insert(
            $table_name,
            [
                'schema_type' => 'WebSite',
                'schema_data' => wp_json_encode( $default_website ),
                'status'      => 'active',
            ],
            [ '%s', '%s', '%s' ]
        );
    }

    /**
     * Migrate data from the old SchemaPro plugin if present.
     *
     * Handles:
     * 1. Table rename: schemapro_globals → t1schema_globals, schemapro_rules → t1schema_rules
     * 2. Options: schemapro_* → t1schema_*
     * 3. Post meta: _schemapro_local → _t1schema_local
     *
     * Only runs once — guarded by a migration flag.
     */
    private function migrate_from_schemapro(): void {
        // Already migrated — skip.
        if ( get_option( 't1schema_migrated_from_schemapro' ) ) {
            return;
        }

        global $wpdb;

        $old_globals = esc_sql( $wpdb->prefix . 'schemapro_globals' );
        $new_globals = esc_sql( $wpdb->prefix . 't1schema_globals' );
        $old_rules   = esc_sql( $wpdb->prefix . 'schemapro_rules' );
        $new_rules   = esc_sql( $wpdb->prefix . 't1schema_rules' );

        // 1. Rename tables (only if old exists and new doesn't).
        $old_globals_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $old_globals ) );
        $new_globals_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $new_globals ) );

        if ( $old_globals_exists && ! $new_globals_exists ) {
            $wpdb->query( "ALTER TABLE `{$old_globals}` RENAME TO `{$new_globals}`" ); // phpcs:ignore
        }

        $old_rules_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $old_rules ) );
        $new_rules_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $new_rules ) );

        if ( $old_rules_exists && ! $new_rules_exists ) {
            $wpdb->query( "ALTER TABLE `{$old_rules}` RENAME TO `{$new_rules}`" ); // phpcs:ignore
        }

        // 2. Migrate options.
        $option_map = [
            'schemapro_db_version'                => 't1schema_db_version',
            'schemapro_delete_data_on_uninstall'   => 't1schema_delete_data_on_uninstall',
            'schemapro_custom_variables'           => 't1schema_custom_variables',
        ];

        foreach ( $option_map as $old_key => $new_key ) {
            $old_value = get_option( $old_key );
            if ( false !== $old_value && ! get_option( $new_key ) ) {
                update_option( $new_key, $old_value );
                delete_option( $old_key );
            }
        }

        // 3. Migrate post meta: _schemapro_local → _t1schema_local.
        $wpdb->query(
            "UPDATE {$wpdb->postmeta} SET meta_key = '_t1schema_local' WHERE meta_key = '_schemapro_local'"
        ); // phpcs:ignore

        // 4. Clean up old transients.
        $wpdb->query(
            "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_schemapro_%' OR option_name LIKE '_transient_timeout_schemapro_%'"
        ); // phpcs:ignore

        // Set migration flag — never run again.
        update_option( 't1schema_migrated_from_schemapro', true );
    }
}
