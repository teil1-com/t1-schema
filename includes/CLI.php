<?php

namespace T1Schema;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * WP-CLI commands for t1 Schema.
 *
 * Usage: wp t1-schema <subcommand>
 *
 * @package T1Schema
 * @since   1.1.0
 */
class CLI {

    /**
     * List all global schemas.
     *
     * ## OPTIONS
     *
     * [--format=<format>]
     * : Output format. Default: table.
     * ---
     * default: table
     * options:
     *   - table
     *   - json
     *   - csv
     *   - yaml
     * ---
     *
     * ## EXAMPLES
     *
     *     wp t1-schema globals
     *     wp t1-schema globals --format=json
     *
     * @subcommand globals
     */
    public function globals( array $args, array $assoc_args ): void {
        global $wpdb;
        $table = $wpdb->prefix . 't1schema_globals';

        $rows = $wpdb->get_results( "SELECT * FROM {$table} ORDER BY created_at DESC", ARRAY_A ); // phpcs:ignore

        if ( empty( $rows ) ) {
            \WP_CLI::log( 'No global schemas found.' );
            return;
        }

        $items = array_map( function ( array $row ): array {
            $data = json_decode( $row['schema_data'], true );
            return [
                'ID'      => $row['id'],
                'Type'    => $row['schema_type'],
                'Status'  => $row['status'],
                'Name'    => $data['name'] ?? '—',
                'Created' => $row['created_at'],
            ];
        }, $rows );

        $format = $assoc_args['format'] ?? 'table';
        \WP_CLI\Utils\format_items( $format, $items, [ 'ID', 'Type', 'Status', 'Name', 'Created' ] );
    }

    /**
     * Create a new global schema.
     *
     * ## OPTIONS
     *
     * <type>
     * : Schema.org type (e.g. Organization, WebSite, Article).
     *
     * [--json=<json>]
     * : Full schema data as JSON string.
     *
     * [--json-file=<path>]
     * : Path to a JSON file containing schema data. Use this instead of --json
     *   when values contain {{variables}} or special characters.
     *
     * [--name=<name>]
     * : Schema name property (shortcut for simple schemas).
     *
     * [--url=<url>]
     * : Schema url property (shortcut for simple schemas).
     *
     * [--status=<status>]
     * : Schema status. Default: active.
     * ---
     * default: active
     * options:
     *   - active
     *   - draft
     * ---
     *
     * ## EXAMPLES
     *
     *     wp t1-schema create Organization --name="My Company" --url="https://example.com"
     *     wp t1-schema create Article --json-file=article-schema.json
     *     wp t1-schema create WebSite --name="My Site" --url="https://example.com"
     *
     * @subcommand create
     */
    public function create( array $args, array $assoc_args ): void {
        global $wpdb;
        $table = $wpdb->prefix . 't1schema_globals';

        $type   = $args[0];
        $status = $assoc_args['status'] ?? 'active';

        // Build schema data from --json, --json-file, or empty.
        $schema_data = $this->parse_json_input( $assoc_args );
        if ( false === $schema_data ) {
            return;
        }

        // Apply shortcut properties.
        $schema_data['@context'] = 'https://schema.org';
        $schema_data['@type']    = $type;

        if ( ! empty( $assoc_args['name'] ) ) {
            $schema_data['name'] = $assoc_args['name'];
        }
        if ( ! empty( $assoc_args['url'] ) ) {
            $schema_data['url'] = $assoc_args['url'];
        }

        $result = $wpdb->insert( $table, [
            'schema_type' => $type,
            'schema_data' => wp_json_encode( $schema_data ),
            'status'      => $status,
        ], [ '%s', '%s', '%s' ] );

        if ( false === $result ) {
            \WP_CLI::error( 'Failed to create schema.' );
            return;
        }

        \WP_CLI::success( "Created {$type} schema (ID: {$wpdb->insert_id})." );

        // Validate.
        $health = SchemaValidator::validate( $schema_data );
        if ( ! empty( $health['errors'] ) ) {
            \WP_CLI::warning( count( $health['errors'] ) . ' validation error(s):' );
            foreach ( $health['errors'] as $e ) {
                \WP_CLI::log( "  ✗ {$e}" );
            }
        }
        if ( ! empty( $health['warnings'] ) ) {
            \WP_CLI::log( count( $health['warnings'] ) . ' warning(s):' );
            foreach ( $health['warnings'] as $w ) {
                \WP_CLI::log( "  ⚠ {$w}" );
            }
        }
    }

    /**
     * Update an existing global schema.
     *
     * ## OPTIONS
     *
     * <id>
     * : Schema ID to update.
     *
     * [--json=<json>]
     * : Full schema data as JSON string (merges with existing data).
     *
     * [--json-file=<path>]
     * : Path to a JSON file containing schema data. Use this instead of --json
     *   when values contain {{variables}} or special characters.
     *
     * [--name=<name>]
     * : Update the name property.
     *
     * [--url=<url>]
     * : Update the url property.
     *
     * [--status=<status>]
     * : Update the status (active/draft).
     *
     * ## EXAMPLES
     *
     *     wp t1-schema update 1 --name="New Name"
     *     wp t1-schema update 2 --status=draft
     *     wp t1-schema update 1 --json-file=updated-schema.json
     *
     * @subcommand update
     */
    public function update( array $args, array $assoc_args ): void {
        global $wpdb;
        $table = $wpdb->prefix . 't1schema_globals';
        $id    = (int) $args[0];

        $row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id ), ARRAY_A ); // phpcs:ignore
        if ( ! $row ) {
            \WP_CLI::error( "Schema ID {$id} not found." );
            return;
        }

        $update = [];
        $format = [];

        if ( ! empty( $assoc_args['status'] ) ) {
            $update['status'] = $assoc_args['status'];
            $format[]         = '%s';
        }

        // Handle data updates.
        $data = json_decode( $row['schema_data'], true );

        if ( ! empty( $assoc_args['json'] ) || ! empty( $assoc_args['json-file'] ) ) {
            $new_data = $this->parse_json_input( $assoc_args );
            if ( false === $new_data ) {
                return;
            }
            $data = array_merge( $data, $new_data );
        }

        if ( ! empty( $assoc_args['name'] ) ) {
            $data['name'] = $assoc_args['name'];
        }
        if ( ! empty( $assoc_args['url'] ) ) {
            $data['url'] = $assoc_args['url'];
        }

        $update['schema_data'] = wp_json_encode( $data );
        $format[]              = '%s';

        $wpdb->update( $table, $update, [ 'id' => $id ], $format, [ '%d' ] );

        \WP_CLI::success( "Updated schema ID {$id}." );
    }

    /**
     * Delete a global schema.
     *
     * ## OPTIONS
     *
     * <id>
     * : Schema ID to delete.
     *
     * [--yes]
     * : Skip confirmation prompt.
     *
     * ## EXAMPLES
     *
     *     wp t1-schema delete 3
     *     wp t1-schema delete 3 --yes
     *
     * @subcommand delete
     */
    public function delete( array $args, array $assoc_args ): void {
        global $wpdb;
        $table = $wpdb->prefix . 't1schema_globals';
        $id    = (int) $args[0];

        $row = $wpdb->get_row( $wpdb->prepare( "SELECT schema_type FROM {$table} WHERE id = %d", $id ) ); // phpcs:ignore
        if ( ! $row ) {
            \WP_CLI::error( "Schema ID {$id} not found." );
            return;
        }

        \WP_CLI::confirm( "Delete {$row->schema_type} schema (ID: {$id})?", $assoc_args );

        $wpdb->delete( $table, [ 'id' => $id ], [ '%d' ] );
        \WP_CLI::success( "Deleted schema ID {$id}." );
    }

    /**
     * Show the full JSON-LD data for a global schema.
     *
     * ## OPTIONS
     *
     * <id>
     * : Schema ID to inspect.
     *
     * [--resolved]
     * : Resolve all {{variable}} tags using the current site context.
     *
     * ## EXAMPLES
     *
     *     wp t1-schema inspect 1
     *     wp t1-schema inspect 1 --resolved
     *
     * @subcommand inspect
     */
    public function inspect( array $args, array $assoc_args ): void {
        global $wpdb;
        $table = $wpdb->prefix . 't1schema_globals';
        $id    = (int) $args[0];

        $row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id ), ARRAY_A ); // phpcs:ignore
        if ( ! $row ) {
            \WP_CLI::error( "Schema ID {$id} not found." );
            return;
        }

        $data = json_decode( $row['schema_data'], true );

        if ( isset( $assoc_args['resolved'] ) ) {
            $data = VariableResolver::resolve( $data, null );
        }

        \WP_CLI::log( wp_json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) );
    }

    /**
     * List local schemas for a specific post.
     *
     * ## OPTIONS
     *
     * <post_id>
     * : Post ID to check.
     *
     * [--format=<format>]
     * : Output format. Default: table.
     *
     * ## EXAMPLES
     *
     *     wp t1-schema local 42
     *     wp t1-schema local 42 --format=json
     *
     * @subcommand local
     */
    public function local( array $args, array $assoc_args ): void {
        $post_id = (int) $args[0];
        $post    = get_post( $post_id );

        if ( ! $post ) {
            \WP_CLI::error( "Post ID {$post_id} not found." );
            return;
        }

        $raw     = get_post_meta( $post_id, '_t1schema_local', true );
        $schemas = $raw ? ( is_string( $raw ) ? json_decode( $raw, true ) : $raw ) : [];

        if ( empty( $schemas ) ) {
            \WP_CLI::log( "No local schemas for '{$post->post_title}' (ID: {$post_id})." );
            return;
        }

        \WP_CLI::log( "Local schemas for '{$post->post_title}' (ID: {$post_id}):" );
        \WP_CLI::log( '' );

        $items = [];
        foreach ( $schemas as $i => $schema ) {
            $items[] = [
                '#'        => $i,
                'Type'     => $schema['@type'] ?? 'Unknown',
                'Override' => ( $schema['_t1schema_meta']['override_global'] ?? true ) ? 'Yes' : 'No',
                'Status'   => $schema['_t1schema_meta']['status'] ?? 'active',
                'Props'    => count( array_filter( array_keys( $schema ), fn( $k ) => ! str_starts_with( $k, '@' ) && $k !== '_t1schema_meta' ) ),
            ];
        }

        $format = $assoc_args['format'] ?? 'table';
        \WP_CLI\Utils\format_items( $format, $items, [ '#', 'Type', 'Override', 'Status', 'Props' ] );
    }

    /**
     * Set a local schema on a post.
     *
     * ## OPTIONS
     *
     * <post_id>
     * : Post ID to set schema on.
     *
     * <type>
     * : Schema.org type (e.g. Article, Product, FAQPage).
     *
     * [--json=<json>]
     * : Schema properties as JSON string.
     *
     * [--replace]
     * : Replace all existing local schemas instead of appending.
     *
     * [--no-override]
     * : Don't override global schema of same type.
     *
     * ## EXAMPLES
     *
     *     wp t1-schema set-local 42 Article --json='{"headline":"{{post_title}}","datePublished":"{{post_date}}"}'
     *     wp t1-schema set-local 42 FAQPage --replace
     *     wp t1-schema set-local 99 Product --json='{"name":"My Product","offers":{"@type":"Offer","price":"49.99","priceCurrency":"EUR"}}'
     *
     * @subcommand set-local
     */
    public function set_local( array $args, array $assoc_args ): void {
        $post_id = (int) $args[0];
        $type    = $args[1];

        $post = get_post( $post_id );
        if ( ! $post ) {
            \WP_CLI::error( "Post ID {$post_id} not found." );
            return;
        }

        // Build schema data.
        $schema_data = [
            '@context'        => 'https://schema.org',
            '@type'           => $type,
            '_t1schema_meta' => [
                'override_global' => ! isset( $assoc_args['no-override'] ),
                'status'          => 'active',
            ],
        ];

        if ( ! empty( $assoc_args['json'] ) ) {
            $extra = json_decode( $assoc_args['json'], true );
            if ( json_last_error() !== JSON_ERROR_NONE ) {
                \WP_CLI::error( 'Invalid JSON: ' . json_last_error_msg() );
                return;
            }
            $schema_data = array_merge( $schema_data, $extra );
            // Ensure meta is preserved.
            $schema_data['_t1schema_meta'] = [
                'override_global' => ! isset( $assoc_args['no-override'] ),
                'status'          => 'active',
            ];
        }

        // Get existing or start fresh.
        if ( isset( $assoc_args['replace'] ) ) {
            $schemas = [];
        } else {
            $raw     = get_post_meta( $post_id, '_t1schema_local', true );
            $schemas = $raw ? ( is_string( $raw ) ? json_decode( $raw, true ) : $raw ) : [];
            $schemas = is_array( $schemas ) ? $schemas : [];
        }

        $schemas[] = $schema_data;
        update_post_meta( $post_id, '_t1schema_local', wp_json_encode( $schemas ) );

        \WP_CLI::success( "Added {$type} schema to '{$post->post_title}' (ID: {$post_id}). Total: " . count( $schemas ) . ' schema(s).' );

        // Validate.
        $health = SchemaValidator::validate( $schema_data );
        if ( ! empty( $health['errors'] ) ) {
            foreach ( $health['errors'] as $e ) {
                \WP_CLI::log( "  ✗ {$e}" );
            }
        }
    }

    /**
     * Clear all local schemas from a post.
     *
     * ## OPTIONS
     *
     * <post_id>
     * : Post ID to clear schemas from.
     *
     * [--yes]
     * : Skip confirmation.
     *
     * ## EXAMPLES
     *
     *     wp t1-schema clear-local 42
     *
     * @subcommand clear-local
     */
    public function clear_local( array $args, array $assoc_args ): void {
        $post_id = (int) $args[0];
        $post    = get_post( $post_id );

        if ( ! $post ) {
            \WP_CLI::error( "Post ID {$post_id} not found." );
            return;
        }

        \WP_CLI::confirm( "Clear all local schemas from '{$post->post_title}'?", $assoc_args );

        delete_post_meta( $post_id, '_t1schema_local' );
        \WP_CLI::success( "Cleared local schemas from post ID {$post_id}." );
    }

    /**
     * Run health check on all global schemas.
     *
     * ## OPTIONS
     *
     * [--format=<format>]
     * : Output format. Default: table.
     *
     * [--post=<post_id>]
     * : Validate schemas for a specific post (local overrides).
     *
     * ## EXAMPLES
     *
     *     wp t1-schema health
     *     wp t1-schema health --post=42
     *     wp t1-schema health --format=json
     *
     * @subcommand health
     */
    public function health( array $args, array $assoc_args ): void {
        global $wpdb;

        // Per-post validation mode.
        if ( isset( $assoc_args['post'] ) ) {
            $this->health_for_post( (int) $assoc_args['post'], $assoc_args );
            return;
        }

        $g_table = $wpdb->prefix . 't1schema_globals';
        $r_table = $wpdb->prefix . 't1schema_rules';

        $items        = [];
        $total_errors = 0;
        $total_warns  = 0;
        $total_infos  = 0;

        // Validate globals.
        $globals = $wpdb->get_results( "SELECT * FROM {$g_table} WHERE status = 'active'", ARRAY_A ); // phpcs:ignore
        foreach ( (array) $globals as $row ) {
            $data   = json_decode( $row['schema_data'], true );
            $health = SchemaValidator::validate( $data );
            $errors = count( $health['errors'] ?? [] );
            $warns  = count( $health['warnings'] ?? [] );
            $infos  = count( $health['infos'] ?? [] );

            $total_errors += $errors;
            $total_warns  += $warns;
            $total_infos  += $infos;

            $status = $health['valid'] ? ( $warns > 0 ? '⚠ Warnings' : ( $infos > 0 ? 'ℹ Custom' : '✓ Valid' ) ) : '✗ Errors';

            $items[] = [
                'ID'       => $row['id'],
                'Layer'    => 'Global',
                'Type'     => $row['schema_type'],
                'Status'   => $status,
                'Errors'   => $errors,
                'Warnings' => $warns,
                'Infos'    => $infos,
            ];
        }

        // Validate rules.
        if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $r_table ) ) ) {
            $rules = $wpdb->get_results( "SELECT * FROM {$r_table} WHERE status = 'active'", ARRAY_A ); // phpcs:ignore
            foreach ( (array) $rules as $row ) {
                $data   = json_decode( $row['schema_data'], true );
                $health = SchemaValidator::validate( $data );
                $errors = count( $health['errors'] ?? [] );
                $warns  = count( $health['warnings'] ?? [] );
                $infos  = count( $health['infos'] ?? [] );

                $total_errors += $errors;
                $total_warns  += $warns;
                $total_infos  += $infos;

                $status = $health['valid'] ? ( $warns > 0 ? '⚠ Warnings' : ( $infos > 0 ? 'ℹ Custom' : '✓ Valid' ) ) : '✗ Errors';

                $items[] = [
                    'ID'       => $row['id'],
                    'Layer'    => "Rule: {$row['rule_name']}",
                    'Type'     => $row['schema_type'],
                    'Status'   => $status,
                    'Errors'   => $errors,
                    'Warnings' => $warns,
                    'Infos'    => $infos,
                ];
            }
        }

        if ( empty( $items ) ) {
            \WP_CLI::log( 'No active schemas to check.' );
            return;
        }

        $format = $assoc_args['format'] ?? 'table';

        \WP_CLI::log( "Total items evaluated: " . count( $items ) );
        \WP_CLI::log( "Total validation errors: " . $total_errors );
        \WP_CLI::log( "Total validation warnings: " . $total_warns );
        \WP_CLI::log( "Total custom types (infos): " . $total_infos );

        \WP_CLI\Utils\format_items(
            $format,
            $items,
            [ 'ID', 'Layer', 'Type', 'Status', 'Errors', 'Warnings', 'Infos' ]
        );
        \WP_CLI::log( '' );
        \WP_CLI::log( sprintf(
            'Summary: %d schema(s), %d error(s), %d warning(s).',
            count( $items ),
            $total_errors,
            $total_warns
        ) );
    }

    /**
     * Validate local schemas for a specific post.
     */
    private function health_for_post( int $post_id, array $assoc_args ): void {
        $post = get_post( $post_id );
        if ( ! $post ) {
            \WP_CLI::error( "Post ID {$post_id} not found." );
            return;
        }

        $raw     = get_post_meta( $post_id, '_t1schema_local', true );
        $schemas = $raw ? ( is_string( $raw ) ? json_decode( $raw, true ) : $raw ) : [];

        if ( empty( $schemas ) ) {
            \WP_CLI::log( "No local schemas on \"{$post->post_title}\" (ID: {$post_id})." );
            return;
        }

        $items        = [];
        $total_errors = 0;
        $total_warns  = 0;

        foreach ( $schemas as $i => $schema ) {
            $meta   = $schema['_t1schema_meta'] ?? [];
            $status_flag = $meta['status'] ?? 'active';
            if ( $status_flag !== 'active' ) continue;

            $health = SchemaValidator::validate( $schema );
            $errors = count( $health['errors'] ?? [] );
            $warns  = count( $health['warnings'] ?? [] );

            $total_errors += $errors;
            $total_warns  += $warns;

            $type_raw = $schema['@type'] ?? 'Unknown';
            $type_str = is_array( $type_raw ) ? implode( ' + ', $type_raw ) : $type_raw;
            $hstatus  = $health['valid'] ? ( $warns > 0 ? '⚠ Warnings' : '✓ Valid' ) : '✗ Errors';

            $items[] = [
                'Index'    => $i,
                'Type'     => $type_str,
                'Status'   => $hstatus,
                'Errors'   => $errors,
                'Warnings' => $warns,
                'Details'  => implode( '; ', array_merge( $health['errors'] ?? [], $health['warnings'] ?? [] ) ),
            ];
        }

        \WP_CLI::log( sprintf( 'Health for "%s" (ID: %d):', $post->post_title, $post_id ) );
        \WP_CLI::log( '' );

        $format = $assoc_args['format'] ?? 'table';
        \WP_CLI\Utils\format_items( $format, $items, [ 'Index', 'Type', 'Status', 'Errors', 'Warnings', 'Details' ] );

        \WP_CLI::log( '' );
        \WP_CLI::log( sprintf(
            'Summary: %d schema(s), %d error(s), %d warning(s).',
            count( $items ),
            $total_errors,
            $total_warns
        ) );
    }

    /**
     * List all available Schema.org types.
     *
     * ## OPTIONS
     *
     * [--format=<format>]
     * : Output format. Default: table.
     *
     * ## EXAMPLES
     *
     *     wp t1-schema types
     *
     * @subcommand types
     */
    public function types( array $args, array $assoc_args ): void {
        $registry = new SchemaRegistry();
        $types    = $registry->get_types();

        $items = [];
        foreach ( $types as $name => $def ) {
            $required    = array_filter( $def['properties'] ?? [], fn( $p ) => $p['required'] ?? false );
            $recommended = array_filter( $def['properties'] ?? [], fn( $p ) => ( $p['recommended'] ?? false ) && ! ( $p['required'] ?? false ) );

            $items[] = [
                'Type'        => $name,
                'Parent'      => $def['parent'] ?? '—',
                'Required'    => count( $required ),
                'Recommended' => count( $recommended ),
                'Total Props' => count( $def['properties'] ?? [] ),
            ];
        }

        $format = $assoc_args['format'] ?? 'table';
        \WP_CLI\Utils\format_items( $format, $items, [ 'Type', 'Parent', 'Required', 'Recommended', 'Total Props' ] );
    }

    /**
     * List all available dynamic variables.
     *
     * ## EXAMPLES
     *
     *     wp t1-schema variables
     *
     * @subcommand variables
     */
    public function variables( array $args, array $assoc_args ): void {
        $vars  = VariableResolver::get_available_variables();
        $items = [];

        foreach ( $vars as $category => $tags ) {
            foreach ( $tags as $tag => $description ) {
                $items[] = [
                    'Category' => $category,
                    'Variable' => "{{" . $tag . "}}",
                    'Desc'     => $description,
                ];
            }
        }

        \WP_CLI\Utils\format_items( 'table', $items, [ 'Category', 'Variable', 'Desc' ] );
    }

    /**
     * List all custom variables (site constants).
     *
     * ## OPTIONS
     *
     * [--format=<format>]
     * : Output format. Default: table.
     * ---
     * default: table
     * options:
     *   - table
     *   - json
     *   - csv
     *   - yaml
     * ---
     *
     * ## EXAMPLES
     *
     *     wp t1-schema vars
     *     wp t1-schema vars --format=json
     *
     * @subcommand vars
     */
    public function vars( array $args, array $assoc_args ): void {
        $vars = get_option( 't1schema_custom_variables', [] );
        if ( ! is_array( $vars ) ) {
            $vars = [];
        }

        if ( empty( $vars ) ) {
            \WP_CLI::log( 'No custom variables defined. Use `wp t1-schema set-var <key> <value>` to create one.' );
            return;
        }

        $items = [];
        foreach ( $vars as $key => $value ) {
            $items[] = [
                'Key'      => $key,
                'Variable' => "{{custom.{$key}}}",
                'Value'    => $value,
            ];
        }

        $format = $assoc_args['format'] ?? 'table';
        \WP_CLI\Utils\format_items( $format, $items, [ 'Key', 'Variable', 'Value' ] );
    }

    /**
     * Set a custom variable (site constant).
     *
     * Creates or updates a custom variable that can be used as {{custom.<key>}}
     * in any schema property.
     *
     * ## OPTIONS
     *
     * <key>
     * : Variable key (lowercase, underscores allowed).
     *
     * <value>
     * : Variable value.
     *
     * ## EXAMPLES
     *
     *     wp t1-schema set-var phone "+43 1 234 5678"
     *     wp t1-schema set-var address "Musterstraße 1, 1010 Wien"
     *     wp t1-schema set-var logo_url "https://example.com/logo.svg"
     *
     * @subcommand set-var
     */
    public function set_var( array $args, array $assoc_args ): void {
        $key   = sanitize_key( $args[0] );
        $value = sanitize_text_field( $args[1] );

        if ( ! $key ) {
            \WP_CLI::error( 'Invalid key. Use lowercase letters, numbers, and underscores only.' );
            return;
        }

        $vars = get_option( 't1schema_custom_variables', [] );
        if ( ! is_array( $vars ) ) {
            $vars = [];
        }

        $is_update = isset( $vars[ $key ] );
        $vars[ $key ] = $value;
        update_option( 't1schema_custom_variables', $vars );

        $verb = $is_update ? 'Updated' : 'Created';
        \WP_CLI::success( "{$verb} custom variable: {{custom.{$key}}} → \"{$value}\"" );
    }

    /**
     * Delete a custom variable.
     *
     * ## OPTIONS
     *
     * <key>
     * : Variable key to delete.
     *
     * [--yes]
     * : Skip confirmation.
     *
     * ## EXAMPLES
     *
     *     wp t1-schema delete-var phone
     *
     * @subcommand delete-var
     */
    public function delete_var( array $args, array $assoc_args ): void {
        $key  = sanitize_key( $args[0] );
        $vars = get_option( 't1schema_custom_variables', [] );

        if ( ! is_array( $vars ) || ! isset( $vars[ $key ] ) ) {
            \WP_CLI::error( "Custom variable '{$key}' not found." );
            return;
        }

        \WP_CLI::confirm( "Delete custom variable '{{custom.{$key}}}'?", $assoc_args );

        unset( $vars[ $key ] );
        update_option( 't1schema_custom_variables', $vars );
        \WP_CLI::success( "Deleted custom variable: {{custom.{$key}}}" );
    }

    /**
     * Import schemas from a JSON file.
     *
     * Accepts two formats:
     *   1. Export format: {"globals": [...], "rules": [...], "locals": [...]}
     *   2. Flat array: [{"post_id": 42, "type": "Article", "data": {...}}]
     *
     * ## OPTIONS
     *
     * <file>
     * : Path to a JSON file.
     *
     * [--dry-run]
     * : Show what would be done without making changes.
     *
     * ## EXAMPLES
     *
     *     wp t1-schema import backup.json
     *     wp t1-schema import backup.json --dry-run
     *
     * @subcommand import
     */
    public function import( array $args, array $assoc_args ): void {
        $file = $args[0];

        if ( ! file_exists( $file ) ) {
            \WP_CLI::error( "File not found: {$file}" );
            return;
        }

        $content = file_get_contents( $file ); // phpcs:ignore
        $decoded = json_decode( $content, true );

        if ( json_last_error() !== JSON_ERROR_NONE ) {
            \WP_CLI::error( 'Invalid JSON file: ' . json_last_error_msg() );
            return;
        }

        $dry_run = isset( $assoc_args['dry-run'] );

        // Detect export format vs flat array.
        if ( isset( $decoded['globals'] ) || isset( $decoded['rules'] ) || isset( $decoded['locals'] ) ) {
            $this->import_export_format( $decoded, $dry_run );
        } elseif ( is_array( $decoded ) ) {
            $this->import_flat_array( $decoded, $dry_run );
        } else {
            \WP_CLI::error( 'Unrecognized JSON format. Expected export format or flat array.' );
        }
    }

    /**
     * Import from the export format: {globals, rules, locals}.
     */
    private function import_export_format( array $data, bool $dry_run ): void {
        global $wpdb;
        $g_count = 0;
        $r_count = 0;
        $l_count = 0;

        // Import globals.
        if ( ! empty( $data['globals'] ) ) {
            $g_table = $wpdb->prefix . 't1schema_globals';
            foreach ( $data['globals'] as $g ) {
                $schema_data = $g['schema_data'] ?? [];
                $type        = $g['schema_type'] ?? $schema_data['@type'] ?? '';
                $status      = $g['status'] ?? 'active';

                if ( ! $type ) {
                    \WP_CLI::warning( 'Skipping global: missing schema_type.' );
                    continue;
                }

                // Ensure @type is in schema_data.
                $schema_data['@context'] = 'https://schema.org';
                $schema_data['@type']    = $type;

                if ( $dry_run ) {
                    \WP_CLI::log( "[DRY RUN] Would create global: {$type}" );
                } else {
                    $wpdb->insert( $g_table, [
                        'schema_type' => $type,
                        'schema_data' => wp_json_encode( $schema_data ),
                        'status'      => $status,
                    ], [ '%s', '%s', '%s' ] );
                    \WP_CLI::log( "✓ Created global {$type} (ID: {$wpdb->insert_id})" );
                }
                $g_count++;
            }
        }

        // Import rules.
        if ( ! empty( $data['rules'] ) ) {
            $r_table = $wpdb->prefix . 't1schema_rules';
            foreach ( $data['rules'] as $r ) {
                $name       = $r['rule_name'] ?? 'Imported Rule';
                $type       = $r['schema_type'] ?? '';
                $conditions = $r['conditions'] ?? [];
                $schema_data = $r['schema_data'] ?? [];
                $priority   = (int) ( $r['priority'] ?? 10 );

                if ( ! $type ) {
                    \WP_CLI::warning( 'Skipping rule: missing schema_type.' );
                    continue;
                }

                if ( $dry_run ) {
                    \WP_CLI::log( "[DRY RUN] Would create rule: {$name}" );
                } else {
                    $wpdb->insert( $r_table, [
                        'rule_name'   => $name,
                        'schema_type' => $type,
                        'schema_data' => wp_json_encode( $schema_data ),
                        'conditions'  => wp_json_encode( $conditions ),
                        'priority'    => $priority,
                        'status'      => 'active',
                    ], [ '%s', '%s', '%s', '%s', '%d', '%s' ] );
                    \WP_CLI::log( "✓ Created rule '{$name}' (ID: {$wpdb->insert_id})" );
                }
                $r_count++;
            }
        }

        // Import locals.
        if ( ! empty( $data['locals'] ) ) {
            foreach ( $data['locals'] as $l ) {
                $post_id = (int) ( $l['post_id'] ?? 0 );
                $schemas = $l['schemas'] ?? [];

                if ( ! $post_id || empty( $schemas ) ) {
                    continue;
                }

                $post = get_post( $post_id );
                if ( ! $post ) {
                    \WP_CLI::warning( "Post ID {$post_id} not found, skipping." );
                    continue;
                }

                if ( $dry_run ) {
                    \WP_CLI::log( "[DRY RUN] Would set " . count( $schemas ) . " local schema(s) on '{$post->post_title}' (ID: {$post_id})" );
                } else {
                    update_post_meta( $post_id, '_t1schema_local', wp_json_encode( $schemas ) );
                    \WP_CLI::log( "✓ Set " . count( $schemas ) . " local schema(s) on '{$post->post_title}' (ID: {$post_id})" );
                }
                $l_count++;
            }
        }

        $verb = $dry_run ? 'Would import' : 'Imported';
        \WP_CLI::success( "{$verb}: {$g_count} global(s), {$r_count} rule(s), {$l_count} local(s)." );
    }

    /**
     * Import from flat array format: [{post_id, type, data}].
     */
    private function import_flat_array( array $entries, bool $dry_run ): void {
        $count = 0;

        foreach ( $entries as $entry ) {
            $post_id = $entry['post_id'] ?? null;
            $type    = $entry['type'] ?? $entry['@type'] ?? null;
            $data    = $entry['data'] ?? $entry;

            if ( ! $post_id || ! $type ) {
                \WP_CLI::warning( 'Skipping entry: missing post_id or type.' );
                continue;
            }

            $post = get_post( $post_id );
            if ( ! $post ) {
                \WP_CLI::warning( "Post ID {$post_id} not found, skipping." );
                continue;
            }

            if ( $dry_run ) {
                \WP_CLI::log( "[DRY RUN] Would add {$type} to '{$post->post_title}' (ID: {$post_id})." );
            } else {
                $schema = array_merge( $data, [
                    '@context'        => 'https://schema.org',
                    '@type'           => $type,
                    '_t1schema_meta' => [ 'override_global' => true, 'status' => 'active' ],
                ] );

                $raw      = get_post_meta( $post_id, '_t1schema_local', true );
                $existing = $raw ? ( is_string( $raw ) ? json_decode( $raw, true ) : $raw ) : [];
                $existing = is_array( $existing ) ? $existing : [];

                $existing[] = $schema;
                update_post_meta( $post_id, '_t1schema_local', wp_json_encode( $existing ) );

                \WP_CLI::log( "✓ Added {$type} to '{$post->post_title}' (ID: {$post_id})." );
            }
            $count++;
        }

        $verb = $dry_run ? 'Would process' : 'Processed';
        \WP_CLI::success( "{$verb} {$count} schema(s)." );
    }

    /**
     * Parse JSON input from --json or --json-file parameter.
     *
     * @return array|false Parsed data or false on error.
     */
    private function parse_json_input( array $assoc_args ) {
        // Prefer --json-file over --json (avoids shell escaping issues).
        if ( ! empty( $assoc_args['json-file'] ) ) {
            $path = $assoc_args['json-file'];
            if ( ! file_exists( $path ) ) {
                \WP_CLI::error( "JSON file not found: {$path}" );
                return false;
            }
            $content = file_get_contents( $path ); // phpcs:ignore
            $data    = json_decode( $content, true );
            if ( json_last_error() !== JSON_ERROR_NONE ) {
                \WP_CLI::error( 'Invalid JSON file: ' . json_last_error_msg() );
                return false;
            }
            return $data;
        }

        if ( ! empty( $assoc_args['json'] ) ) {
            $data = json_decode( $assoc_args['json'], true );
            if ( json_last_error() !== JSON_ERROR_NONE ) {
                \WP_CLI::error( 'Invalid JSON: ' . json_last_error_msg() );
                return false;
            }
            return $data;
        }

        return [];
    }

    /**
     * List all schema rules.
     *
     * ## OPTIONS
     *
     * [--format=<format>]
     * : Output format. Default: table.
     *
     * ## EXAMPLES
     *
     *     wp t1-schema rules
     *     wp t1-schema rules --format=json
     *
     * @subcommand rules
     */
    public function rules( array $args, array $assoc_args ): void {
        global $wpdb;
        $table = $wpdb->prefix . 't1schema_rules';
        $rows  = $wpdb->get_results( "SELECT * FROM {$table} ORDER BY priority ASC", ARRAY_A ); // phpcs:ignore

        if ( empty( $rows ) ) {
            \WP_CLI::log( 'No schema rules found.' );
            return;
        }

        $items = array_map( function ( array $row ): array {
            $conds = json_decode( $row['conditions'], true ) ?: [];
            $labels = array_map( fn( $c ) => $c['type'] . ( $c['value'] ? ':' . $c['value'] : '' ), $conds );
            return [
                'ID'         => $row['id'],
                'Name'       => $row['rule_name'],
                'Type'       => $row['schema_type'],
                'Conditions' => implode( ' AND ', $labels ) ?: '(none)',
                'Priority'   => $row['priority'],
                'Status'     => $row['status'],
            ];
        }, $rows );

        $format = $assoc_args['format'] ?? 'table';
        \WP_CLI\Utils\format_items( $format, $items, [ 'ID', 'Name', 'Type', 'Conditions', 'Priority', 'Status' ] );
    }

    /**
     * Create a new schema rule.
     *
     * ## OPTIONS
     *
     * <type>
     * : Schema.org type (e.g. Article, CollectionPage).
     *
     * --conditions=<conditions>
     * : JSON array of conditions, e.g. '[{"type":"singular","value":"post"}]'
     *
     * [--name=<name>]
     * : Rule name. Auto-generated if omitted.
     *
     * [--json=<json>]
     * : Schema properties as JSON string.
     *
     * [--priority=<priority>]
     * : Priority (lower = higher). Default: 10.
     *
     * ## EXAMPLES
     *
     *     wp t1-schema add-rule Article --conditions='[{"type":"singular","value":"post"}]' --json='{"headline":"{{post_title}}"}'
     *     wp t1-schema add-rule CollectionPage --conditions='[{"type":"archive","value":"portfolio"}]' --name="Portfolio Archive"
     *
     * @subcommand add-rule
     */
    public function add_rule( array $args, array $assoc_args ): void {
        global $wpdb;
        $table = $wpdb->prefix . 't1schema_rules';
        $type  = $args[0];

        $conditions = json_decode( $assoc_args['conditions'] ?? '[]', true );
        if ( json_last_error() !== JSON_ERROR_NONE ) {
            \WP_CLI::error( 'Invalid conditions JSON: ' . json_last_error_msg() );
            return;
        }

        $schema_data = [ '@context' => 'https://schema.org', '@type' => $type ];
        if ( ! empty( $assoc_args['json'] ) ) {
            $extra = json_decode( $assoc_args['json'], true );
            if ( json_last_error() !== JSON_ERROR_NONE ) {
                \WP_CLI::error( 'Invalid schema JSON: ' . json_last_error_msg() );
                return;
            }
            $schema_data = array_merge( $schema_data, $extra );
        }

        $cond_labels = array_map( fn( $c ) => $c['type'] . ( $c['value'] ? ':' . $c['value'] : '' ), $conditions );
        $name = $assoc_args['name'] ?? $type . ' → ' . implode( ' + ', $cond_labels );

        $result = $wpdb->insert( $table, [
            'rule_name'   => $name,
            'schema_type' => $type,
            'schema_data' => wp_json_encode( $schema_data ),
            'conditions'  => wp_json_encode( $conditions ),
            'priority'    => (int) ( $assoc_args['priority'] ?? 10 ),
            'status'      => 'active',
        ], [ '%s', '%s', '%s', '%s', '%d', '%s' ] );

        if ( false === $result ) {
            \WP_CLI::error( 'Failed to create rule.' );
            return;
        }

        \WP_CLI::success( "Created rule '{$name}' (ID: {$wpdb->insert_id})." );
    }

    /**
     * Delete a schema rule.
     *
     * ## OPTIONS
     *
     * <id>
     * : Rule ID to delete.
     *
     * [--yes]
     * : Skip confirmation.
     *
     * @subcommand delete-rule
     */
    public function delete_rule( array $args, array $assoc_args ): void {
        global $wpdb;
        $table = $wpdb->prefix . 't1schema_rules';
        $id    = (int) $args[0];

        $row = $wpdb->get_row( $wpdb->prepare( "SELECT rule_name FROM {$table} WHERE id = %d", $id ) ); // phpcs:ignore
        if ( ! $row ) {
            \WP_CLI::error( "Rule ID {$id} not found." );
            return;
        }

        \WP_CLI::confirm( "Delete rule '{$row->rule_name}' (ID: {$id})?", $assoc_args );
        $wpdb->delete( $table, [ 'id' => $id ], [ '%d' ] );
        \WP_CLI::success( "Deleted rule ID {$id}." );
    }

    /**
     * Export all schemas (globals + rules + locals) to a JSON file.
     *
     * ## OPTIONS
     *
     * [<file>]
     * : Output file path. Defaults to stdout.
     *
     * [--globals-only]
     * : Export only global schemas.
     *
     * [--rules-only]
     * : Export only schema rules.
     *
     * ## EXAMPLES
     *
     *     wp t1-schema export > backup.json
     *     wp t1-schema export /path/to/backup.json
     *     wp t1-schema export --globals-only
     *
     * @subcommand export
     */
    public function export( array $args, array $assoc_args ): void {
        global $wpdb;
        $output = [];

        $globals_only = isset( $assoc_args['globals-only'] );
        $rules_only   = isset( $assoc_args['rules-only'] );
        $export_all   = ! $globals_only && ! $rules_only;

        // Globals.
        if ( $export_all || $globals_only ) {
            $g_table = $wpdb->prefix . 't1schema_globals';
            $rows    = $wpdb->get_results( "SELECT * FROM {$g_table}", ARRAY_A ); // phpcs:ignore
            $output['globals'] = array_map( function ( $r ) {
                $r['schema_data'] = json_decode( $r['schema_data'], true );
                return $r;
            }, $rows ?: [] );
        }

        // Rules.
        if ( $export_all || $rules_only ) {
            $r_table = $wpdb->prefix . 't1schema_rules';
            $rows    = $wpdb->get_results( "SELECT * FROM {$r_table}", ARRAY_A ); // phpcs:ignore
            $output['rules'] = array_map( function ( $r ) {
                $r['schema_data'] = json_decode( $r['schema_data'], true );
                $r['conditions']  = json_decode( $r['conditions'], true );
                return $r;
            }, $rows ?: [] );
        }

        // Locals.
        if ( $export_all ) {
            $meta_rows = $wpdb->get_results(
                "SELECT post_id, meta_value FROM {$wpdb->postmeta} WHERE meta_key = '_t1schema_local'",
                ARRAY_A
            ); // phpcs:ignore
            $output['locals'] = array_map( function ( $r ) {
                return [
                    'post_id' => (int) $r['post_id'],
                    'title'   => get_the_title( $r['post_id'] ),
                    'schemas' => json_decode( $r['meta_value'], true ) ?: [],
                ];
            }, $meta_rows ?: [] );
        }

        $json = wp_json_encode( $output, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );

        if ( ! empty( $args[0] ) ) {
            file_put_contents( $args[0], $json ); // phpcs:ignore
            $counts = [];
            if ( isset( $output['globals'] ) ) $counts[] = count( $output['globals'] ) . ' globals';
            if ( isset( $output['rules'] ) )   $counts[] = count( $output['rules'] ) . ' rules';
            if ( isset( $output['locals'] ) )  $counts[] = count( $output['locals'] ) . ' locals';
            \WP_CLI::success( 'Exported ' . implode( ', ', $counts ) . " to {$args[0]}." );
        } else {
            \WP_CLI::log( $json );
        }
    }

    /**
     * Render the final merged JSON-LD for a specific post.
     *
     * Shows exactly what would appear in <head> — globals + matching rules + local overrides,
     * with full priority resolution and variable expansion.
     *
     * ## OPTIONS
     *
     * <post_id>
     * : Post ID to render.
     *
     * [--raw]
     * : Show raw templates without resolving {{variables}}.
     *
     * [--layers]
     * : Show each layer separately before merging (debug mode).
     *
     * ## EXAMPLES
     *
     *     wp t1-schema render 42
     *     wp t1-schema render 42 --raw
     *     wp t1-schema render 42 --layers
     *
     * @subcommand render
     */
    public function render( array $args, array $assoc_args ): void {
        global $wpdb, $wp_query, $post;
        
        $post_id = (int) $args[0];
        $_post   = get_post( $post_id );

        if ( ! $_post ) {
            \WP_CLI::error( "Post ID {$post_id} not found." );
            return;
        }

        // --- Context Mocking ---
        // We MUST simulate a frontend request so ContextDetector can evaluate rules properly.
        $original_query = $wp_query;
        $original_post  = $post;

        $post = $_post;
        setup_postdata( $post );
        
        $wp_query = new \WP_Query( [
            'p'         => $post_id,
            'post_type' => 'any',
        ] );
        
        $wp_query->is_singular       = true;
        $wp_query->is_single         = true;
        $wp_query->queried_object    = $post;
        $wp_query->queried_object_id = $post_id;
        
        // Handle front page specifically
        if ( (int) get_option( 'page_on_front' ) === $post_id ) {
            $wp_query->is_front_page = true;
            $wp_query->is_home       = false;
        }

        // --- Schema Assembly (mirrors Frontend::render_jsonld pipeline) ---
        $frontend = new Frontend();
        $merged   = $frontend->assemble_schemas();

        // Strip internal meta before output
        $merged = array_map( function( $s ) { unset( $s['_t1schema_meta'] ); return $s; }, $merged );

        // Resolve variables unless --raw is passed
        $resolve = ! isset( $assoc_args['raw'] );
        if ( $resolve ) {
            $merged = WooCommerceOffers::expand( $merged, $post_id );
            $merged = VariableResolver::resolve( $merged, $post_id );
        }

        // Deduplicate nodes with the same @id (Rule + Local merge)
        $by_id = [];
        $no_id = [];
        foreach ( $merged as $node ) {
            $id = $node['@id'] ?? null;
            if ( $id ) {
                $by_id[ $id ] = isset( $by_id[ $id ] ) ? array_merge( $by_id[ $id ], $node ) : $node;
            } else {
                $no_id[] = $node;
            }
        }
        $merged = array_merge( array_values( $by_id ), $no_id );

        $graph = [ '@context' => 'https://schema.org', '@graph' => array_values( $merged ) ];
        \WP_CLI::log( wp_json_encode( $graph, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) );

        \WP_CLI::log( '' );
        \WP_CLI::log( sprintf( 'Total: %d schema(s) for "%s" (ID: %d).', count( $merged ), $post->post_title, $post_id ) );
        
        // Restore the global query state we replaced above.
        $wp_query = $original_query;
        $post     = $original_post;
        wp_reset_postdata();
    }

    /**
     * Show schema coverage across all site contexts.
     *
     * ## OPTIONS
     *
     * [--format=<format>]
     * : Output format. Default: table.
     *
     * ## EXAMPLES
     *
     *     wp t1-schema coverage
     *     wp t1-schema coverage --format=json
     *
     * @subcommand coverage
     */
    public function coverage( array $args, array $assoc_args ): void {
        global $wpdb;
        $r_table = $wpdb->prefix . 't1schema_rules';
        $rules   = $wpdb->get_results( "SELECT * FROM {$r_table} WHERE status = 'active'", ARRAY_A ) ?: []; // phpcs:ignore

        $items   = [];
        $covered = 0;
        $total   = 0;

        // Front page.
        $total++;
        $fp_rules = $this->find_rules_for( $rules, 'front_page', '' );
        if ( $fp_rules ) $covered++;
        $items[] = [ 'Context' => '🏠 Front Page', 'Type' => 'front_page', 'Schemas' => $fp_rules ?: '—', 'Status' => $fp_rules ? '✓' : '✗' ];

        // Post types.
        $post_types = get_post_types( [ 'public' => true ], 'objects' );
        foreach ( $post_types as $pt ) {
            if ( $pt->name === 'attachment' ) continue;
            $total++;
            $s_rules = $this->find_rules_for( $rules, 'singular', $pt->name );
            if ( $s_rules ) $covered++;
            $count   = wp_count_posts( $pt->name )->publish ?? 0;
            $items[] = [ 'Context' => "📄 {$pt->label} ({$count})", 'Type' => "singular:{$pt->name}", 'Schemas' => $s_rules ?: '—', 'Status' => $s_rules ? '✓' : '✗' ];

            if ( $pt->has_archive ) {
                $total++;
                $a_rules = $this->find_rules_for( $rules, 'archive', $pt->name );
                if ( $a_rules ) $covered++;
                $items[] = [ 'Context' => "  📋 {$pt->label} Archive", 'Type' => "archive:{$pt->name}", 'Schemas' => $a_rules ?: '—', 'Status' => $a_rules ? '✓' : '✗' ];
            }
        }

        // Taxonomies.
        $taxonomies = get_taxonomies( [ 'public' => true ], 'objects' );
        foreach ( $taxonomies as $tax ) {
            $total++;
            $t_rules = $this->find_rules_for( $rules, 'taxonomy', $tax->name );
            if ( $t_rules ) $covered++;
            $term_count = wp_count_terms( [ 'taxonomy' => $tax->name ] );
            $items[] = [ 'Context' => "🏷️ {$tax->label} ({$term_count})", 'Type' => "taxonomy:{$tax->name}", 'Schemas' => $t_rules ?: '—', 'Status' => $t_rules ? '✓' : '✗' ];
        }

        // Special pages.
        foreach ( [ [ 'search', '🔍 Search Results' ], [ '404', '⚠️  404 Page' ], [ 'author', '👤 Author Archives' ], [ 'date', '📅 Date Archives' ] ] as $sp ) {
            $total++;
            $sp_rules = $this->find_rules_for( $rules, $sp[0], '' );
            if ( $sp_rules ) $covered++;
            $items[] = [ 'Context' => $sp[1], 'Type' => $sp[0], 'Schemas' => $sp_rules ?: '—', 'Status' => $sp_rules ? '✓' : '✗' ];
        }

        $format = $assoc_args['format'] ?? 'table';
        \WP_CLI\Utils\format_items( $format, $items, [ 'Context', 'Type', 'Schemas', 'Status' ] );
        \WP_CLI::log( '' );
        $pct = $total > 0 ? round( ( $covered / $total ) * 100 ) : 0;
        \WP_CLI::log( "Coverage: {$covered}/{$total} contexts ({$pct}%)." );
    }

    /**
     * Helper: find rule types matching a context.
     */
    private function find_rules_for( array $rules, string $type, string $value ): string {
        $found = [];
        foreach ( $rules as $rule ) {
            $conditions = json_decode( $rule['conditions'], true ) ?: [];
            foreach ( $conditions as $cond ) {
                if ( $cond['type'] === $type && ( ! $value || $cond['value'] === $value ) ) {
                    $found[] = $rule['schema_type'];
                }
            }
        }
        return implode( ', ', array_unique( $found ) );
    }

    /**
     * Run diagnostics on the t1 Schema installation.
     *
     * Checks: database tables, orphaned data, plugin conflicts, schema health across all layers.
     *
     * ## EXAMPLES
     *
     *     wp t1-schema doctor
     *
     * @subcommand doctor
     */
    public function doctor( array $args, array $assoc_args ): void {
        global $wpdb;
        $issues = 0;

        \WP_CLI::log( '🩺 t1 Schema Doctor' );
        \WP_CLI::log( str_repeat( '─', 50 ) );

        // 1. Check database tables.
        \WP_CLI::log( "\n1. Database tables:" );
        foreach ( [ 't1schema_globals', 't1schema_rules' ] as $t ) {
            $exists = $wpdb->get_var( "SHOW TABLES LIKE '{$wpdb->prefix}{$t}'" ); // phpcs:ignore
            if ( $exists ) {
                $count = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}{$t}" ); // phpcs:ignore
                \WP_CLI::log( "   ✓ {$t} ({$count} rows)" );
            } else {
                \WP_CLI::log( "   ✗ {$t} — MISSING! Deactivate and reactivate the plugin." );
                $issues++;
            }
        }

        // 2. Check for duplicate types in globals.
        \WP_CLI::log( "\n2. Duplicate global types:" );
        $g_table = $wpdb->prefix . 't1schema_globals';
        $dupes   = $wpdb->get_results( "SELECT schema_type, COUNT(*) as cnt FROM {$g_table} WHERE status = 'active' GROUP BY schema_type HAVING cnt > 1", ARRAY_A ); // phpcs:ignore
        if ( empty( $dupes ) ) {
            \WP_CLI::log( '   ✓ No duplicates' );
        } else {
            foreach ( $dupes as $d ) {
                \WP_CLI::log( "   ⚠ {$d['schema_type']} has {$d['cnt']} active globals — only one will render" );
                $issues++;
            }
        }

        // 3. Check for conflicting plugins.
        \WP_CLI::log( "\n3. Plugin conflicts:" );
        $conflicts = [
            'teil1-content/teil1-content.php' => 'teil1-content (mu-plugin schema)',
            'schema-pro/schema-pro.php'       => 'Schema Pro (wpschema.com)',
            'wp-seo-schema-pro/schema.php'    => 'WP SEO Schema Pro',
        ];
        $found_conflict = false;
        foreach ( $conflicts as $file => $label ) {
            if ( is_plugin_active( $file ) || ( defined( 'WPMU_PLUGIN_DIR' ) && file_exists( WPMU_PLUGIN_DIR . '/' . $file ) ) ) {
                \WP_CLI::log( "   ⚠ {$label} is active — may output duplicate JSON-LD" );
                $issues++;
                $found_conflict = true;
            }
        }
        if ( function_exists( 'teil1_schema_output' ) ) {
            $suppressing = (bool) get_option( 't1schema_suppress_conflicts', false );
            \WP_CLI::log( $suppressing
                ? '   ⚠ teil1_schema_output() detected — suppression is ON, t1 Schema removes it'
                : '   ⚠ teil1_schema_output() detected — suppression is OFF, enable it in Settings if you see duplicates'
            );
            $found_conflict = true;
        }
        if ( class_exists( '\WooCommerce' ) ) {
            $suppressing = (bool) apply_filters(
                't1schema_suppress_woocommerce_conflicts',
                (bool) get_option( 't1schema_suppress_conflicts', false )
            );
            \WP_CLI::log( $suppressing
                ? '   ⚠ WooCommerce detected — suppression is ON, t1 Schema removes WooCommerce\'s own Product/Review/BreadcrumbList/WebSite markup wherever a t1 Schema rule covers the same type'
                : '   ⚠ WooCommerce detected — suppression is OFF; if a t1 Schema rule also covers Product, Review, BreadcrumbList, or WebSite, enable it in Settings to avoid duplicate JSON-LD'
            );
            $found_conflict = true;
        }
        if ( ! $found_conflict ) {
            \WP_CLI::log( '   ✓ No conflicts detected' );
        }

        // 4. Health across all layers.
        \WP_CLI::log( "\n4. Schema health (all layers):" );
        $total_errors = 0;
        $total_warns  = 0;

        // Globals.
        $globals = $wpdb->get_results( "SELECT * FROM {$g_table} WHERE status = 'active'", ARRAY_A ) ?: []; // phpcs:ignore
        foreach ( $globals as $row ) {
            $data   = json_decode( $row['schema_data'], true );
            $health = SchemaValidator::validate( $data );
            $errors = count( $health['errors'] ?? [] );
            $warns  = count( $health['warnings'] ?? [] );
            $total_errors += $errors;
            $total_warns  += $warns;
            if ( $errors > 0 ) {
                \WP_CLI::log( "   ✗ Global #{$row['id']} ({$row['schema_type']}): {$errors} error(s)" );
            }
        }

        // Rules.
        $r_table    = $wpdb->prefix . 't1schema_rules';
        $rule_rows  = $wpdb->get_results( "SELECT * FROM {$r_table} WHERE status = 'active'", ARRAY_A ) ?: []; // phpcs:ignore
        foreach ( $rule_rows as $row ) {
            $data   = json_decode( $row['schema_data'], true );
            $health = SchemaValidator::validate( $data );
            $errors = count( $health['errors'] ?? [] );
            $warns  = count( $health['warnings'] ?? [] );
            $total_errors += $errors;
            $total_warns  += $warns;
            if ( $errors > 0 ) {
                \WP_CLI::log( "   ✗ Rule #{$row['id']} ({$row['schema_type']}): {$errors} error(s)" );
            }
        }

        if ( $total_errors === 0 ) {
            \WP_CLI::log( "   ✓ All schemas valid ({$total_warns} warning(s))" );
        }

        // 5. Orphaned local schemas.
        \WP_CLI::log( "\n5. Orphaned local schemas:" );
        $orphans = $wpdb->get_results(
            "SELECT pm.post_id FROM {$wpdb->postmeta} pm LEFT JOIN {$wpdb->posts} p ON pm.post_id = p.ID WHERE pm.meta_key = '_t1schema_local' AND (p.ID IS NULL OR p.post_status = 'trash')",
            ARRAY_A
        ); // phpcs:ignore
        if ( empty( $orphans ) ) {
            \WP_CLI::log( '   ✓ No orphaned schemas' );
        } else {
            $ids = array_column( $orphans, 'post_id' );
            \WP_CLI::log( '   ⚠ ' . count( $orphans ) . ' orphaned local schema(s) on deleted posts: ' . implode( ', ', $ids ) );
            $issues++;
        }

        // Summary.
        \WP_CLI::log( "\n" . str_repeat( '─', 50 ) );
        if ( $issues === 0 ) {
            \WP_CLI::success( 'No issues found. t1 Schema is healthy.' );
        } else {
            \WP_CLI::warning( "{$issues} issue(s) found." );
        }
    }
}
