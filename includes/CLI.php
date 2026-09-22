<?php

namespace T1Schema;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * WP-CLI commands for Teil1 Schema Manager.
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
            \WP_CLI::log( __( 'No global schemas found.', 'teil1-schema-manager' ) );
            return;
        }

        $format = $assoc_args['format'] ?? 'table';
        $items  = array_map( function ( array $row ) use ( $format ): array {
            $data = json_decode( $row['schema_data'], true );
            return [
                'ID'      => $row['id'],
                'Type'    => $row['schema_type'],
                'Status'  => 'table' === $format ? $this->translate_status_label( $row['status'] ) : $row['status'],
                'Name'    => $data['name'] ?? '—',
                'Created' => $row['created_at'],
            ];
        }, $rows );

        $this->format_items( $format, $items, [ 'ID', 'Type', 'Status', 'Name', 'Created' ] );
    }

    /**
     * Create a new global schema.
     *
     * ## OPTIONS
     *
     * <type>
     * : Schema.org type (e.g. Organization, WebSite, Article).
     *
     * [--schema-json=<json>]
     * : Full schema data as JSON string.
     *
     * [--json-file=<path>]
     * : Path to a JSON file containing schema data. Use this instead of
     *   --schema-json
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

        // Build schema data from --schema-json, --json-file, or empty.
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
            \WP_CLI::error( __( 'Failed to create schema.', 'teil1-schema-manager' ) );
            return;
        }

        \WP_CLI::success(
            sprintf(
                /* translators: 1: Schema.org type, 2: Schema ID. */
                __( 'Created %1$s schema (ID: %2$d).', 'teil1-schema-manager' ),
                $type,
                $wpdb->insert_id
            )
        );

        // Validate.
        $health = SchemaValidator::validate( $schema_data );
        if ( ! empty( $health['errors'] ) ) {
            $error_count = count( $health['errors'] );
            \WP_CLI::warning(
                sprintf(
                    /* translators: %1$d: Number of validation errors. */
                    _n( '%1$d validation error:', '%1$d validation errors:', $error_count, 'teil1-schema-manager' ),
                    $error_count
                )
            );
            foreach ( $health['errors'] as $e ) {
                \WP_CLI::log(
                    sprintf(
                        /* translators: %1$s: Validation error message. */
                        __( '  ✗ %1$s', 'teil1-schema-manager' ),
                        $e
                    )
                );
            }
        }
        if ( ! empty( $health['warnings'] ) ) {
            $warning_count = count( $health['warnings'] );
            \WP_CLI::log(
                sprintf(
                    /* translators: %1$d: Number of validation warnings. */
                    _n( '%1$d warning:', '%1$d warnings:', $warning_count, 'teil1-schema-manager' ),
                    $warning_count
                )
            );
            foreach ( $health['warnings'] as $w ) {
                \WP_CLI::log(
                    sprintf(
                        /* translators: %1$s: Validation warning message. */
                        __( '  ⚠ %1$s', 'teil1-schema-manager' ),
                        $w
                    )
                );
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
     * [--schema-json=<json>]
     * : Full schema data as JSON string (merges with existing data).
     *
     * [--json-file=<path>]
     * : Path to a JSON file containing schema data. Use this instead of
     *   --schema-json
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
            \WP_CLI::error(
                sprintf(
                    /* translators: %1$d: Schema ID. */
                    __( 'Schema ID %1$d not found.', 'teil1-schema-manager' ),
                    $id
                )
            );
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

        if ( ! empty( $assoc_args['schema-json'] ) || ! empty( $assoc_args['json-file'] ) ) {
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

        \WP_CLI::success(
            sprintf(
                /* translators: %1$d: Schema ID. */
                __( 'Updated schema ID %1$d.', 'teil1-schema-manager' ),
                $id
            )
        );
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
            \WP_CLI::error(
                sprintf(
                    /* translators: %1$d: Schema ID. */
                    __( 'Schema ID %1$d not found.', 'teil1-schema-manager' ),
                    $id
                )
            );
            return;
        }

        \WP_CLI::confirm(
            sprintf(
                /* translators: 1: Schema.org type, 2: Schema ID. */
                __( 'Delete %1$s schema (ID: %2$d)?', 'teil1-schema-manager' ),
                $row->schema_type,
                $id
            ),
            $assoc_args
        );

        $wpdb->delete( $table, [ 'id' => $id ], [ '%d' ] );
        \WP_CLI::success(
            sprintf(
                /* translators: %1$d: Schema ID. */
                __( 'Deleted schema ID %1$d.', 'teil1-schema-manager' ),
                $id
            )
        );
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
            \WP_CLI::error(
                sprintf(
                    /* translators: %1$d: Schema ID. */
                    __( 'Schema ID %1$d not found.', 'teil1-schema-manager' ),
                    $id
                )
            );
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
            \WP_CLI::error(
                sprintf(
                    /* translators: %1$d: Post ID. */
                    __( 'Post ID %1$d not found.', 'teil1-schema-manager' ),
                    $post_id
                )
            );
            return;
        }

        $raw     = get_post_meta( $post_id, '_t1schema_local', true );
        $schemas = $raw ? ( is_string( $raw ) ? json_decode( $raw, true ) : $raw ) : [];

        if ( empty( $schemas ) ) {
            \WP_CLI::log(
                sprintf(
                    /* translators: 1: Post title, 2: Post ID. */
                    __( 'No local schemas for \'%1$s\' (ID: %2$d).', 'teil1-schema-manager' ),
                    $post->post_title,
                    $post_id
                )
            );
            return;
        }

        \WP_CLI::log(
            sprintf(
                /* translators: 1: Post title, 2: Post ID. */
                __( 'Local schemas for \'%1$s\' (ID: %2$d):', 'teil1-schema-manager' ),
                $post->post_title,
                $post_id
            )
        );
        \WP_CLI::log( '' );

        $format = $assoc_args['format'] ?? 'table';
        $items = [];
        foreach ( $schemas as $i => $schema ) {
            $items[] = [
                '#'        => $i,
                'Type'     => $schema['@type'] ?? ( 'table' === $format ? _x( 'Unknown', 'schema type table value', 'teil1-schema-manager' ) : 'Unknown' ),
                'Override' => ( $schema['_t1schema_meta']['override_global'] ?? true )
                    ? ( 'table' === $format ? _x( 'Yes', 'boolean table value', 'teil1-schema-manager' ) : 'Yes' )
                    : ( 'table' === $format ? _x( 'No', 'boolean table value', 'teil1-schema-manager' ) : 'No' ),
                'Status'   => 'table' === $format
                    ? $this->translate_status_label( $schema['_t1schema_meta']['status'] ?? 'active' )
                    : ( $schema['_t1schema_meta']['status'] ?? 'active' ),
                'Props'    => count( array_filter( array_keys( $schema ), fn( $k ) => ! str_starts_with( $k, '@' ) && $k !== '_t1schema_meta' ) ),
            ];
        }

        $this->format_items( $format, $items, [ '#', 'Type', 'Override', 'Status', 'Props' ] );
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
     * [--schema-json=<json>]
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
     *     wp teil1-schema-manager set-local 42 Article --schema-json='{"headline":"{{post_title}}","datePublished":"{{post_date}}"}'
     *     wp t1-schema set-local 42 FAQPage --replace
     *     wp teil1-schema-manager set-local 99 Product --schema-json='{"name":"My Product","offers":{"@type":"Offer","price":"49.99","priceCurrency":"EUR"}}'
     *
     * @subcommand set-local
     */
    public function set_local( array $args, array $assoc_args ): void {
        $post_id = (int) $args[0];
        $type    = $args[1];

        $post = get_post( $post_id );
        if ( ! $post ) {
            \WP_CLI::error(
                sprintf(
                    /* translators: %1$d: Post ID. */
                    __( 'Post ID %1$d not found.', 'teil1-schema-manager' ),
                    $post_id
                )
            );
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

        if ( ! empty( $assoc_args['schema-json'] ) ) {
            $extra = json_decode( $assoc_args['schema-json'], true );
            if ( json_last_error() !== JSON_ERROR_NONE ) {
                \WP_CLI::error(
                    sprintf(
                        /* translators: %1$s: JSON parser error message. */
                        __( 'Invalid JSON: %1$s', 'teil1-schema-manager' ),
                        json_last_error_msg()
                    )
                );
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

        $schema_count = count( $schemas );
        \WP_CLI::success(
            sprintf(
                /* translators: 1: Schema.org type, 2: Post title, 3: Post ID, 4: Total number of local schemas. */
                _n(
                    'Added %1$s schema to \'%2$s\' (ID: %3$d). Total: %4$d schema.',
                    'Added %1$s schema to \'%2$s\' (ID: %3$d). Total: %4$d schemas.',
                    $schema_count,
                    'teil1-schema-manager'
                ),
                $type,
                $post->post_title,
                $post_id,
                $schema_count
            )
        );

        // Validate.
        $health = SchemaValidator::validate( $schema_data );
        if ( ! empty( $health['errors'] ) ) {
            foreach ( $health['errors'] as $e ) {
                \WP_CLI::log(
                    sprintf(
                        /* translators: %1$s: Validation error message. */
                        __( '  ✗ %1$s', 'teil1-schema-manager' ),
                        $e
                    )
                );
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
            \WP_CLI::error(
                sprintf(
                    /* translators: %1$d: Post ID. */
                    __( 'Post ID %1$d not found.', 'teil1-schema-manager' ),
                    $post_id
                )
            );
            return;
        }

        \WP_CLI::confirm(
            sprintf(
                /* translators: %1$s: Post title. */
                __( 'Clear all local schemas from \'%1$s\'?', 'teil1-schema-manager' ),
                $post->post_title
            ),
            $assoc_args
        );

        delete_post_meta( $post_id, '_t1schema_local' );
        \WP_CLI::success(
            sprintf(
                /* translators: %1$d: Post ID. */
                __( 'Cleared local schemas from post ID %1$d.', 'teil1-schema-manager' ),
                $post_id
            )
        );
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
        $format       = $assoc_args['format'] ?? 'table';

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

            if ( 'table' === $format ) {
                $status = $health['valid']
                    ? ( $warns > 0
                        ? _x( '⚠ Warnings', 'schema validation status', 'teil1-schema-manager' )
                        : ( $infos > 0
                            ? _x( 'ℹ Custom', 'schema validation status', 'teil1-schema-manager' )
                            : _x( '✓ Valid', 'schema validation status', 'teil1-schema-manager' ) ) )
                    : _x( '✗ Errors', 'schema validation status', 'teil1-schema-manager' );
            } else {
                $status = $health['valid'] ? ( $warns > 0 ? '⚠ Warnings' : ( $infos > 0 ? 'ℹ Custom' : '✓ Valid' ) ) : '✗ Errors';
            }

            $items[] = [
                'ID'       => $row['id'],
                'Layer'    => 'table' === $format ? _x( 'Global', 'schema layer table value', 'teil1-schema-manager' ) : 'Global',
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

                if ( 'table' === $format ) {
                    $status = $health['valid']
                        ? ( $warns > 0
                            ? _x( '⚠ Warnings', 'schema validation status', 'teil1-schema-manager' )
                            : ( $infos > 0
                                ? _x( 'ℹ Custom', 'schema validation status', 'teil1-schema-manager' )
                                : _x( '✓ Valid', 'schema validation status', 'teil1-schema-manager' ) ) )
                        : _x( '✗ Errors', 'schema validation status', 'teil1-schema-manager' );
                } else {
                    $status = $health['valid'] ? ( $warns > 0 ? '⚠ Warnings' : ( $infos > 0 ? 'ℹ Custom' : '✓ Valid' ) ) : '✗ Errors';
                }

                $items[] = [
                    'ID'       => $row['id'],
                    'Layer'    => 'table' === $format
                        ? sprintf(
                            /* translators: %1$s: Schema rule name. */
                            _x( 'Rule: %1$s', 'schema layer table value', 'teil1-schema-manager' ),
                            $row['rule_name']
                        )
                        : "Rule: {$row['rule_name']}",
                    'Type'     => $row['schema_type'],
                    'Status'   => $status,
                    'Errors'   => $errors,
                    'Warnings' => $warns,
                    'Infos'    => $infos,
                ];
            }
        }

        if ( empty( $items ) ) {
            \WP_CLI::log( __( 'No active schemas to check.', 'teil1-schema-manager' ) );
            return;
        }

        $item_count = count( $items );
        \WP_CLI::log(
            sprintf(
                /* translators: %1$d: Number of evaluated items. */
                _n( 'Total item evaluated: %1$d', 'Total items evaluated: %1$d', $item_count, 'teil1-schema-manager' ),
                $item_count
            )
        );
        \WP_CLI::log(
            sprintf(
                /* translators: %1$d: Number of validation errors. */
                _n( 'Total validation error: %1$d', 'Total validation errors: %1$d', $total_errors, 'teil1-schema-manager' ),
                $total_errors
            )
        );
        \WP_CLI::log(
            sprintf(
                /* translators: %1$d: Number of validation warnings. */
                _n( 'Total validation warning: %1$d', 'Total validation warnings: %1$d', $total_warns, 'teil1-schema-manager' ),
                $total_warns
            )
        );
        \WP_CLI::log(
            sprintf(
                /* translators: %1$d: Number of custom schema types. */
                _n( 'Total custom type (info): %1$d', 'Total custom types (infos): %1$d', $total_infos, 'teil1-schema-manager' ),
                $total_infos
            )
        );

        $this->format_items(
            $format,
            $items,
            [ 'ID', 'Layer', 'Type', 'Status', 'Errors', 'Warnings', 'Infos' ]
        );
        \WP_CLI::log( '' );

        $schema_summary = sprintf(
            /* translators: %1$d: Number of schemas. */
            _n( '%1$d schema', '%1$d schemas', $item_count, 'teil1-schema-manager' ),
            $item_count
        );
        $error_summary = sprintf(
            /* translators: %1$d: Number of validation errors. */
            _n( '%1$d error', '%1$d errors', $total_errors, 'teil1-schema-manager' ),
            $total_errors
        );
        $warning_summary = sprintf(
            /* translators: %1$d: Number of validation warnings. */
            _n( '%1$d warning', '%1$d warnings', $total_warns, 'teil1-schema-manager' ),
            $total_warns
        );
        \WP_CLI::log(
            sprintf(
                /* translators: 1: Schema count summary, 2: Error count summary, 3: Warning count summary. */
                __( 'Summary: %1$s, %2$s, %3$s.', 'teil1-schema-manager' ),
                $schema_summary,
                $error_summary,
                $warning_summary
            )
        );
    }

    /**
     * Validate local schemas for a specific post.
     */
    private function health_for_post( int $post_id, array $assoc_args ): void {
        $post = get_post( $post_id );
        if ( ! $post ) {
            \WP_CLI::error(
                sprintf(
                    /* translators: %1$d: Post ID. */
                    __( 'Post ID %1$d not found.', 'teil1-schema-manager' ),
                    $post_id
                )
            );
            return;
        }

        $raw     = get_post_meta( $post_id, '_t1schema_local', true );
        $schemas = $raw ? ( is_string( $raw ) ? json_decode( $raw, true ) : $raw ) : [];

        if ( empty( $schemas ) ) {
            \WP_CLI::log(
                sprintf(
                    /* translators: 1: Post title, 2: Post ID. */
                    __( 'No local schemas on "%1$s" (ID: %2$d).', 'teil1-schema-manager' ),
                    $post->post_title,
                    $post_id
                )
            );
            return;
        }

        $items        = [];
        $total_errors = 0;
        $total_warns  = 0;
        $format       = $assoc_args['format'] ?? 'table';

        foreach ( $schemas as $i => $schema ) {
            $meta   = $schema['_t1schema_meta'] ?? [];
            $status_flag = $meta['status'] ?? 'active';
            if ( $status_flag !== 'active' ) continue;

            $health = SchemaValidator::validate( $schema );
            $errors = count( $health['errors'] ?? [] );
            $warns  = count( $health['warnings'] ?? [] );

            $total_errors += $errors;
            $total_warns  += $warns;

            $type_raw = $schema['@type'] ?? ( 'table' === $format ? _x( 'Unknown', 'schema type table value', 'teil1-schema-manager' ) : 'Unknown' );
            $type_str = is_array( $type_raw ) ? implode( ' + ', $type_raw ) : $type_raw;
            $hstatus  = 'table' === $format
                ? ( $health['valid']
                    ? ( $warns > 0
                        ? _x( '⚠ Warnings', 'schema validation status', 'teil1-schema-manager' )
                        : _x( '✓ Valid', 'schema validation status', 'teil1-schema-manager' ) )
                    : _x( '✗ Errors', 'schema validation status', 'teil1-schema-manager' ) )
                : ( $health['valid'] ? ( $warns > 0 ? '⚠ Warnings' : '✓ Valid' ) : '✗ Errors' );

            $items[] = [
                'Index'    => $i,
                'Type'     => $type_str,
                'Status'   => $hstatus,
                'Errors'   => $errors,
                'Warnings' => $warns,
                'Details'  => implode( '; ', array_merge( $health['errors'] ?? [], $health['warnings'] ?? [] ) ),
            ];
        }

        \WP_CLI::log(
            sprintf(
                /* translators: 1: Post title, 2: Post ID. */
                __( 'Health for "%1$s" (ID: %2$d):', 'teil1-schema-manager' ),
                $post->post_title,
                $post_id
            )
        );
        \WP_CLI::log( '' );

        $this->format_items( $format, $items, [ 'Index', 'Type', 'Status', 'Errors', 'Warnings', 'Details' ] );

        \WP_CLI::log( '' );
        $item_count = count( $items );
        $schema_summary = sprintf(
            /* translators: %1$d: Number of schemas. */
            _n( '%1$d schema', '%1$d schemas', $item_count, 'teil1-schema-manager' ),
            $item_count
        );
        $error_summary = sprintf(
            /* translators: %1$d: Number of validation errors. */
            _n( '%1$d error', '%1$d errors', $total_errors, 'teil1-schema-manager' ),
            $total_errors
        );
        $warning_summary = sprintf(
            /* translators: %1$d: Number of validation warnings. */
            _n( '%1$d warning', '%1$d warnings', $total_warns, 'teil1-schema-manager' ),
            $total_warns
        );
        \WP_CLI::log(
            sprintf(
                /* translators: 1: Schema count summary, 2: Error count summary, 3: Warning count summary. */
                __( 'Summary: %1$s, %2$s, %3$s.', 'teil1-schema-manager' ),
                $schema_summary,
                $error_summary,
                $warning_summary
            )
        );
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
        $this->format_items( $format, $items, [ 'Type', 'Parent', 'Required', 'Recommended', 'Total Props' ] );
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

        $this->format_items( 'table', $items, [ 'Category', 'Variable', 'Desc' ] );
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
            \WP_CLI::log( __( 'No custom variables defined. Use `wp t1-schema set-var <key> <value>` to create one.', 'teil1-schema-manager' ) );
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
        $this->format_items( $format, $items, [ 'Key', 'Variable', 'Value' ] );
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
            \WP_CLI::error( __( 'Invalid key. Use lowercase letters, numbers, and underscores only.', 'teil1-schema-manager' ) );
            return;
        }

        $vars = get_option( 't1schema_custom_variables', [] );
        if ( ! is_array( $vars ) ) {
            $vars = [];
        }

        $is_update = isset( $vars[ $key ] );
        $vars[ $key ] = $value;
        update_option( 't1schema_custom_variables', $vars );

        if ( $is_update ) {
            \WP_CLI::success(
                sprintf(
                    /* translators: 1: Custom variable key, 2: Custom variable value. */
                    __( 'Updated custom variable: {{custom.%1$s}} → "%2$s"', 'teil1-schema-manager' ),
                    $key,
                    $value
                )
            );
        } else {
            \WP_CLI::success(
                sprintf(
                    /* translators: 1: Custom variable key, 2: Custom variable value. */
                    __( 'Created custom variable: {{custom.%1$s}} → "%2$s"', 'teil1-schema-manager' ),
                    $key,
                    $value
                )
            );
        }
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
            \WP_CLI::error(
                sprintf(
                    /* translators: %1$s: Custom variable key. */
                    __( 'Custom variable \'%1$s\' not found.', 'teil1-schema-manager' ),
                    $key
                )
            );
            return;
        }

        \WP_CLI::confirm(
            sprintf(
                /* translators: %1$s: Custom variable key. */
                __( 'Delete custom variable \'{{custom.%1$s}}\'?', 'teil1-schema-manager' ),
                $key
            ),
            $assoc_args
        );

        unset( $vars[ $key ] );
        update_option( 't1schema_custom_variables', $vars );
        \WP_CLI::success(
            sprintf(
                /* translators: %1$s: Custom variable key. */
                __( 'Deleted custom variable: {{custom.%1$s}}', 'teil1-schema-manager' ),
                $key
            )
        );
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
            \WP_CLI::error(
                sprintf(
                    /* translators: %1$s: JSON file path. */
                    __( 'File not found: %1$s', 'teil1-schema-manager' ),
                    $file
                )
            );
            return;
        }

        $content = file_get_contents( $file ); // phpcs:ignore
        $decoded = json_decode( $content, true );

        if ( json_last_error() !== JSON_ERROR_NONE ) {
            \WP_CLI::error(
                sprintf(
                    /* translators: %1$s: JSON parser error message. */
                    __( 'Invalid JSON file: %1$s', 'teil1-schema-manager' ),
                    json_last_error_msg()
                )
            );
            return;
        }

        $dry_run = isset( $assoc_args['dry-run'] );

        // Detect export format vs flat array.
        if ( isset( $decoded['globals'] ) || isset( $decoded['rules'] ) || isset( $decoded['locals'] ) ) {
            $this->import_export_format( $decoded, $dry_run );
        } elseif ( is_array( $decoded ) ) {
            $this->import_flat_array( $decoded, $dry_run );
        } else {
            \WP_CLI::error( __( 'Unrecognized JSON format. Expected export format or flat array.', 'teil1-schema-manager' ) );
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
                    \WP_CLI::warning( __( 'Skipping global: missing schema_type.', 'teil1-schema-manager' ) );
                    continue;
                }

                // Ensure @type is in schema_data.
                $schema_data['@context'] = 'https://schema.org';
                $schema_data['@type']    = $type;

                if ( $dry_run ) {
                    \WP_CLI::log(
                        sprintf(
                            /* translators: %1$s: Schema.org type. */
                            __( '[DRY RUN] Would create global: %1$s', 'teil1-schema-manager' ),
                            $type
                        )
                    );
                } else {
                    $wpdb->insert( $g_table, [
                        'schema_type' => $type,
                        'schema_data' => wp_json_encode( $schema_data ),
                        'status'      => $status,
                    ], [ '%s', '%s', '%s' ] );
                    \WP_CLI::log(
                        sprintf(
                            /* translators: 1: Schema.org type, 2: Schema ID. */
                            __( '✓ Created global %1$s (ID: %2$d)', 'teil1-schema-manager' ),
                            $type,
                            $wpdb->insert_id
                        )
                    );
                }
                $g_count++;
            }
        }

        // Import rules.
        if ( ! empty( $data['rules'] ) ) {
            $r_table = $wpdb->prefix . 't1schema_rules';
            foreach ( $data['rules'] as $r ) {
                $name       = $r['rule_name'] ?? _x( 'Imported Rule', 'default imported schema rule name', 'teil1-schema-manager' );
                $type       = $r['schema_type'] ?? '';
                $conditions = $r['conditions'] ?? [];
                $schema_data = $r['schema_data'] ?? [];
                $priority   = (int) ( $r['priority'] ?? 10 );

                if ( ! $type ) {
                    \WP_CLI::warning( __( 'Skipping rule: missing schema_type.', 'teil1-schema-manager' ) );
                    continue;
                }

                if ( $dry_run ) {
                    \WP_CLI::log(
                        sprintf(
                            /* translators: %1$s: Schema rule name. */
                            __( '[DRY RUN] Would create rule: %1$s', 'teil1-schema-manager' ),
                            $name
                        )
                    );
                } else {
                    $wpdb->insert( $r_table, [
                        'rule_name'   => $name,
                        'schema_type' => $type,
                        'schema_data' => wp_json_encode( $schema_data ),
                        'conditions'  => wp_json_encode( $conditions ),
                        'priority'    => $priority,
                        'status'      => 'active',
                    ], [ '%s', '%s', '%s', '%s', '%d', '%s' ] );
                    \WP_CLI::log(
                        sprintf(
                            /* translators: 1: Schema rule name, 2: Rule ID. */
                            __( '✓ Created rule \'%1$s\' (ID: %2$d)', 'teil1-schema-manager' ),
                            $name,
                            $wpdb->insert_id
                        )
                    );
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
                    \WP_CLI::warning(
                        sprintf(
                            /* translators: %1$d: Post ID. */
                            __( 'Post ID %1$d not found, skipping.', 'teil1-schema-manager' ),
                            $post_id
                        )
                    );
                    continue;
                }

                $schema_count = count( $schemas );
                if ( $dry_run ) {
                    \WP_CLI::log(
                        sprintf(
                            /* translators: 1: Number of local schemas, 2: Post title, 3: Post ID. */
                            _n(
                                '[DRY RUN] Would set %1$d local schema on \'%2$s\' (ID: %3$d)',
                                '[DRY RUN] Would set %1$d local schemas on \'%2$s\' (ID: %3$d)',
                                $schema_count,
                                'teil1-schema-manager'
                            ),
                            $schema_count,
                            $post->post_title,
                            $post_id
                        )
                    );
                } else {
                    update_post_meta( $post_id, '_t1schema_local', wp_json_encode( $schemas ) );
                    \WP_CLI::log(
                        sprintf(
                            /* translators: 1: Number of local schemas, 2: Post title, 3: Post ID. */
                            _n(
                                '✓ Set %1$d local schema on \'%2$s\' (ID: %3$d)',
                                '✓ Set %1$d local schemas on \'%2$s\' (ID: %3$d)',
                                $schema_count,
                                'teil1-schema-manager'
                            ),
                            $schema_count,
                            $post->post_title,
                            $post_id
                        )
                    );
                }
                $l_count++;
            }
        }

        $global_summary = sprintf(
            /* translators: %1$d: Number of global schemas. */
            _n( '%1$d global', '%1$d globals', $g_count, 'teil1-schema-manager' ),
            $g_count
        );
        $rule_summary = sprintf(
            /* translators: %1$d: Number of schema rules. */
            _n( '%1$d rule', '%1$d rules', $r_count, 'teil1-schema-manager' ),
            $r_count
        );
        $local_summary = sprintf(
            /* translators: %1$d: Number of posts with local schemas. */
            _n( '%1$d local', '%1$d locals', $l_count, 'teil1-schema-manager' ),
            $l_count
        );
        if ( $dry_run ) {
            \WP_CLI::success(
                sprintf(
                    /* translators: 1: Global schema count summary, 2: Rule count summary, 3: Local schema count summary. */
                    __( 'Would import: %1$s, %2$s, %3$s.', 'teil1-schema-manager' ),
                    $global_summary,
                    $rule_summary,
                    $local_summary
                )
            );
        } else {
            \WP_CLI::success(
                sprintf(
                    /* translators: 1: Global schema count summary, 2: Rule count summary, 3: Local schema count summary. */
                    __( 'Imported: %1$s, %2$s, %3$s.', 'teil1-schema-manager' ),
                    $global_summary,
                    $rule_summary,
                    $local_summary
                )
            );
        }
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
                \WP_CLI::warning( __( 'Skipping entry: missing post_id or type.', 'teil1-schema-manager' ) );
                continue;
            }

            $post = get_post( $post_id );
            if ( ! $post ) {
                \WP_CLI::warning(
                    sprintf(
                        /* translators: %1$d: Post ID. */
                        __( 'Post ID %1$d not found, skipping.', 'teil1-schema-manager' ),
                        $post_id
                    )
                );
                continue;
            }

            if ( $dry_run ) {
                \WP_CLI::log(
                    sprintf(
                        /* translators: 1: Schema.org type, 2: Post title, 3: Post ID. */
                        __( '[DRY RUN] Would add %1$s to \'%2$s\' (ID: %3$d).', 'teil1-schema-manager' ),
                        $type,
                        $post->post_title,
                        $post_id
                    )
                );
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

                \WP_CLI::log(
                    sprintf(
                        /* translators: 1: Schema.org type, 2: Post title, 3: Post ID. */
                        __( '✓ Added %1$s to \'%2$s\' (ID: %3$d).', 'teil1-schema-manager' ),
                        $type,
                        $post->post_title,
                        $post_id
                    )
                );
            }
            $count++;
        }

        if ( $dry_run ) {
            \WP_CLI::success(
                sprintf(
                    /* translators: %1$d: Number of schemas. */
                    _n( 'Would process %1$d schema.', 'Would process %1$d schemas.', $count, 'teil1-schema-manager' ),
                    $count
                )
            );
        } else {
            \WP_CLI::success(
                sprintf(
                    /* translators: %1$d: Number of schemas. */
                    _n( 'Processed %1$d schema.', 'Processed %1$d schemas.', $count, 'teil1-schema-manager' ),
                    $count
                )
            );
        }
    }

    /**
     * Parse JSON input from --schema-json or --json-file parameter.
     *
     * @return array|false Parsed data or false on error.
     */
    private function parse_json_input( array $assoc_args ) {
        // Prefer --json-file over --schema-json (avoids shell escaping issues).
        if ( ! empty( $assoc_args['json-file'] ) ) {
            $path = $assoc_args['json-file'];
            if ( ! file_exists( $path ) ) {
                \WP_CLI::error(
                    sprintf(
                        /* translators: %1$s: JSON file path. */
                        __( 'JSON file not found: %1$s', 'teil1-schema-manager' ),
                        $path
                    )
                );
                return false;
            }
            $content = file_get_contents( $path ); // phpcs:ignore
            $data    = json_decode( $content, true );
            if ( json_last_error() !== JSON_ERROR_NONE ) {
                \WP_CLI::error(
                    sprintf(
                        /* translators: %1$s: JSON parser error message. */
                        __( 'Invalid JSON file: %1$s', 'teil1-schema-manager' ),
                        json_last_error_msg()
                    )
                );
                return false;
            }
            return $data;
        }

        if ( ! empty( $assoc_args['schema-json'] ) ) {
            $data = json_decode( $assoc_args['schema-json'], true );
            if ( json_last_error() !== JSON_ERROR_NONE ) {
                \WP_CLI::error(
                    sprintf(
                        /* translators: %1$s: JSON parser error message. */
                        __( 'Invalid JSON: %1$s', 'teil1-schema-manager' ),
                        json_last_error_msg()
                    )
                );
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
            \WP_CLI::log( __( 'No schema rules found.', 'teil1-schema-manager' ) );
            return;
        }

        $format = $assoc_args['format'] ?? 'table';
        $items  = array_map( function ( array $row ) use ( $format ): array {
            $conds = json_decode( $row['conditions'], true ) ?: [];
            $labels = array_map( fn( $c ) => $c['type'] . ( $c['value'] ? ':' . $c['value'] : '' ), $conds );
            return [
                'ID'         => $row['id'],
                'Name'       => $row['rule_name'],
                'Type'       => $row['schema_type'],
                'Conditions' => implode( 'table' === $format ? _x( ' AND ', 'schema rule condition separator', 'teil1-schema-manager' ) : ' AND ', $labels )
                    ?: ( 'table' === $format ? _x( '(none)', 'empty schema rule conditions table value', 'teil1-schema-manager' ) : '(none)' ),
                'Priority'   => $row['priority'],
                'Status'     => 'table' === $format ? $this->translate_status_label( $row['status'] ) : $row['status'],
            ];
        }, $rows );

        $this->format_items( $format, $items, [ 'ID', 'Name', 'Type', 'Conditions', 'Priority', 'Status' ] );
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
     * [--schema-json=<json>]
     * : Schema properties as JSON string.
     *
     * [--priority=<priority>]
     * : Priority (lower = higher). Default: 10.
     *
     * ## EXAMPLES
     *
     *     wp teil1-schema-manager add-rule Article --conditions='[{"type":"singular","value":"post"}]' --schema-json='{"headline":"{{post_title}}"}'
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
            \WP_CLI::error(
                sprintf(
                    /* translators: %1$s: JSON parser error message. */
                    __( 'Invalid conditions JSON: %1$s', 'teil1-schema-manager' ),
                    json_last_error_msg()
                )
            );
            return;
        }

        $schema_data = [ '@context' => 'https://schema.org', '@type' => $type ];
        if ( ! empty( $assoc_args['schema-json'] ) ) {
            $extra = json_decode( $assoc_args['schema-json'], true );
            if ( json_last_error() !== JSON_ERROR_NONE ) {
                \WP_CLI::error(
                    sprintf(
                        /* translators: %1$s: JSON parser error message. */
                        __( 'Invalid schema JSON: %1$s', 'teil1-schema-manager' ),
                        json_last_error_msg()
                    )
                );
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
            \WP_CLI::error( __( 'Failed to create rule.', 'teil1-schema-manager' ) );
            return;
        }

        \WP_CLI::success(
            sprintf(
                /* translators: 1: Schema rule name, 2: Rule ID. */
                __( 'Created rule \'%1$s\' (ID: %2$d).', 'teil1-schema-manager' ),
                $name,
                $wpdb->insert_id
            )
        );
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
            \WP_CLI::error(
                sprintf(
                    /* translators: %1$d: Rule ID. */
                    __( 'Rule ID %1$d not found.', 'teil1-schema-manager' ),
                    $id
                )
            );
            return;
        }

        \WP_CLI::confirm(
            sprintf(
                /* translators: 1: Schema rule name, 2: Rule ID. */
                __( 'Delete rule \'%1$s\' (ID: %2$d)?', 'teil1-schema-manager' ),
                $row->rule_name,
                $id
            ),
            $assoc_args
        );
        $wpdb->delete( $table, [ 'id' => $id ], [ '%d' ] );
        \WP_CLI::success(
            sprintf(
                /* translators: %1$d: Rule ID. */
                __( 'Deleted rule ID %1$d.', 'teil1-schema-manager' ),
                $id
            )
        );
    }

    /**
     * Export all schemas (globals + rules + locals) as JSON on stdout.
     *
     * ## OPTIONS
     *
     * [--globals-only]
     * : Export only global schemas.
     *
     * [--rules-only]
     * : Export only schema rules.
     *
     * ## EXAMPLES
     *
     *     wp teil1-schema-manager export > backup.json
     *     wp teil1-schema-manager export --globals-only
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

        // Intentionally write only to stdout. Accepting a user-controlled file
        // path here would allow the CLI process to overwrite arbitrary writable
        // files. Shell redirection keeps filesystem access explicit:
        // wp teil1-schema-manager export > backup.json
        \WP_CLI::log( $json );
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
            \WP_CLI::error(
                sprintf(
                    /* translators: %1$d: Post ID. */
                    __( 'Post ID %1$d not found.', 'teil1-schema-manager' ),
                    $post_id
                )
            );
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
            $merged = apply_filters( 't1schema_resolved_schemas', $merged, $post_id );
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
        $schema_count = count( $merged );
        \WP_CLI::log(
            sprintf(
                /* translators: 1: Number of schemas, 2: Post title, 3: Post ID. */
                _n(
                    'Total: %1$d schema for "%2$s" (ID: %3$d).',
                    'Total: %1$d schemas for "%2$s" (ID: %3$d).',
                    $schema_count,
                    'teil1-schema-manager'
                ),
                $schema_count,
                $post->post_title,
                $post_id
            )
        );
        
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
        $format  = $assoc_args['format'] ?? 'table';

        // Front page.
        $total++;
        $fp_rules = $this->find_rules_for( $rules, 'front_page', '' );
        if ( $fp_rules ) $covered++;
        $items[] = [
            'Context' => 'table' === $format ? _x( '🏠 Front Page', 'site context table value', 'teil1-schema-manager' ) : '🏠 Front Page',
            'Type'    => 'front_page',
            'Schemas' => $fp_rules ?: '—',
            'Status'  => $fp_rules ? '✓' : '✗',
        ];

        // Post types.
        $post_types = get_post_types( [ 'public' => true ], 'objects' );
        foreach ( $post_types as $pt ) {
            if ( $pt->name === 'attachment' ) continue;
            $total++;
            $s_rules = $this->find_rules_for( $rules, 'singular', $pt->name );
            if ( $s_rules ) $covered++;
            $count   = wp_count_posts( $pt->name )->publish ?? 0;
            $items[] = [
                'Context' => 'table' === $format
                    ? sprintf(
                        /* translators: 1: Post type label, 2: Number of published posts. */
                        _x( '📄 %1$s (%2$d)', 'site context table value', 'teil1-schema-manager' ),
                        $pt->label,
                        $count
                    )
                    : "📄 {$pt->label} ({$count})",
                'Type'    => "singular:{$pt->name}",
                'Schemas' => $s_rules ?: '—',
                'Status'  => $s_rules ? '✓' : '✗',
            ];

            if ( $pt->has_archive ) {
                $total++;
                $a_rules = $this->find_rules_for( $rules, 'archive', $pt->name );
                if ( $a_rules ) $covered++;
                $items[] = [
                    'Context' => 'table' === $format
                        ? sprintf(
                            /* translators: %1$s: Post type label. */
                            _x( '  📋 %1$s Archive', 'site context table value', 'teil1-schema-manager' ),
                            $pt->label
                        )
                        : "  📋 {$pt->label} Archive",
                    'Type'    => "archive:{$pt->name}",
                    'Schemas' => $a_rules ?: '—',
                    'Status'  => $a_rules ? '✓' : '✗',
                ];
            }
        }

        // Taxonomies.
        $taxonomies = get_taxonomies( [ 'public' => true ], 'objects' );
        foreach ( $taxonomies as $tax ) {
            $total++;
            $t_rules = $this->find_rules_for( $rules, 'taxonomy', $tax->name );
            if ( $t_rules ) $covered++;
            $term_count = wp_count_terms( [ 'taxonomy' => $tax->name ] );
            $items[] = [
                'Context' => 'table' === $format
                    ? sprintf(
                        /* translators: 1: Taxonomy label, 2: Number of taxonomy terms. */
                        _x( '🏷️ %1$s (%2$d)', 'site context table value', 'teil1-schema-manager' ),
                        $tax->label,
                        $term_count
                    )
                    : "🏷️ {$tax->label} ({$term_count})",
                'Type'    => "taxonomy:{$tax->name}",
                'Schemas' => $t_rules ?: '—',
                'Status'  => $t_rules ? '✓' : '✗',
            ];
        }

        // Special pages.
        $special_pages = 'table' === $format
            ? [
                [ 'search', _x( '🔍 Search Results', 'site context table value', 'teil1-schema-manager' ) ],
                [ '404', _x( '⚠️  404 Page', 'site context table value', 'teil1-schema-manager' ) ],
                [ 'author', _x( '👤 Author Archives', 'site context table value', 'teil1-schema-manager' ) ],
                [ 'date', _x( '📅 Date Archives', 'site context table value', 'teil1-schema-manager' ) ],
            ]
            : [ [ 'search', '🔍 Search Results' ], [ '404', '⚠️  404 Page' ], [ 'author', '👤 Author Archives' ], [ 'date', '📅 Date Archives' ] ];
        foreach ( $special_pages as $sp ) {
            $total++;
            $sp_rules = $this->find_rules_for( $rules, $sp[0], '' );
            if ( $sp_rules ) $covered++;
            $items[] = [ 'Context' => $sp[1], 'Type' => $sp[0], 'Schemas' => $sp_rules ?: '—', 'Status' => $sp_rules ? '✓' : '✗' ];
        }

        $this->format_items( $format, $items, [ 'Context', 'Type', 'Schemas', 'Status' ] );
        \WP_CLI::log( '' );
        $pct = $total > 0 ? round( ( $covered / $total ) * 100 ) : 0;
        \WP_CLI::log(
            sprintf(
                /* translators: 1: Covered site contexts, 2: Total site contexts, 3: Coverage percentage. */
                _n(
                    'Coverage: %1$d/%2$d context (%3$d%%).',
                    'Coverage: %1$d/%2$d contexts (%3$d%%).',
                    $total,
                    'teil1-schema-manager'
                ),
                $covered,
                $total,
                $pct
            )
        );
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
     * Run diagnostics on the Teil1 Schema Manager installation.
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

        \WP_CLI::log( __( '🩺 Teil1 Schema Manager Doctor', 'teil1-schema-manager' ) );
        \WP_CLI::log( str_repeat( '─', 50 ) );

        // 1. Check database tables.
        \WP_CLI::log( "\n" . __( '1. Database tables:', 'teil1-schema-manager' ) );
        foreach ( [ 't1schema_globals', 't1schema_rules' ] as $t ) {
            $exists = $wpdb->get_var( "SHOW TABLES LIKE '{$wpdb->prefix}{$t}'" ); // phpcs:ignore
            if ( $exists ) {
                $count = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}{$t}" ); // phpcs:ignore
                \WP_CLI::log(
                    sprintf(
                        /* translators: 1: Database table name, 2: Number of rows. */
                        _n( '   ✓ %1$s (%2$d row)', '   ✓ %1$s (%2$d rows)', $count, 'teil1-schema-manager' ),
                        $t,
                        $count
                    )
                );
            } else {
                \WP_CLI::log(
                    sprintf(
                        /* translators: %1$s: Database table name. */
                        __( '   ✗ %1$s — MISSING! Deactivate and reactivate the plugin.', 'teil1-schema-manager' ),
                        $t
                    )
                );
                $issues++;
            }
        }

        // 2. Check for duplicate types in globals.
        \WP_CLI::log( "\n" . __( '2. Duplicate global types:', 'teil1-schema-manager' ) );
        $g_table = $wpdb->prefix . 't1schema_globals';
        $dupes   = $wpdb->get_results( "SELECT schema_type, COUNT(*) as cnt FROM {$g_table} WHERE status = 'active' GROUP BY schema_type HAVING cnt > 1", ARRAY_A ); // phpcs:ignore
        if ( empty( $dupes ) ) {
            \WP_CLI::log( __( '   ✓ No duplicates', 'teil1-schema-manager' ) );
        } else {
            foreach ( $dupes as $d ) {
                \WP_CLI::log(
                    sprintf(
                        /* translators: 1: Schema.org type, 2: Number of active global schemas. */
                        _n(
                            '   ⚠ %1$s has %2$d active global — only one will render',
                            '   ⚠ %1$s has %2$d active globals — only one will render',
                            $d['cnt'],
                            'teil1-schema-manager'
                        ),
                        $d['schema_type'],
                        $d['cnt']
                    )
                );
                $issues++;
            }
        }

        // 3. Check for conflicting plugins.
        \WP_CLI::log( "\n" . __( '3. Plugin conflicts:', 'teil1-schema-manager' ) );
        $conflicts = [
            'teil1-content/teil1-content.php' => _x( 'teil1-content (mu-plugin schema)', 'plugin conflict label', 'teil1-schema-manager' ),
            'schema-pro/schema-pro.php'       => _x( 'Schema Pro (wpschema.com)', 'plugin conflict label', 'teil1-schema-manager' ),
            'wp-seo-schema-pro/schema.php'    => _x( 'WP SEO Schema Pro', 'plugin conflict label', 'teil1-schema-manager' ),
        ];
        $found_conflict = false;
        foreach ( $conflicts as $file => $label ) {
            if ( is_plugin_active( $file ) || ( defined( 'WPMU_PLUGIN_DIR' ) && file_exists( WPMU_PLUGIN_DIR . '/' . $file ) ) ) {
                \WP_CLI::log(
                    sprintf(
                        /* translators: %1$s: Conflicting plugin name. */
                        __( '   ⚠ %1$s is active — may output duplicate JSON-LD', 'teil1-schema-manager' ),
                        $label
                    )
                );
                $issues++;
                $found_conflict = true;
            }
        }
        if ( function_exists( 'teil1_schema_output' ) ) {
            $suppressing = (bool) get_option( 't1schema_suppress_conflicts', false );
            \WP_CLI::log( $suppressing
                ? __( '   ⚠ teil1_schema_output() detected — suppression is ON, Teil1 Schema Manager removes it', 'teil1-schema-manager' )
                : __( '   ⚠ teil1_schema_output() detected — suppression is OFF, enable it in Settings if you see duplicates', 'teil1-schema-manager' )
            );
            $found_conflict = true;
        }
        if ( class_exists( '\WooCommerce' ) ) {
            $suppressing = (bool) apply_filters(
                't1schema_suppress_woocommerce_conflicts',
                (bool) get_option( 't1schema_suppress_conflicts', false )
            );
            \WP_CLI::log( $suppressing
                ? __( '   ⚠ WooCommerce detected — suppression is ON, Teil1 Schema Manager removes WooCommerce\'s own Product/Review/BreadcrumbList/WebSite markup wherever a configured rule covers the same type', 'teil1-schema-manager' )
                : __( '   ⚠ WooCommerce detected — suppression is OFF; if a configured rule also covers Product, Review, BreadcrumbList, or WebSite, enable it in Settings to avoid duplicate JSON-LD', 'teil1-schema-manager' )
            );
            $found_conflict = true;
        }
        if ( ! $found_conflict ) {
            \WP_CLI::log( __( '   ✓ No conflicts detected', 'teil1-schema-manager' ) );
        }

        // 4. Health across all layers.
        \WP_CLI::log( "\n" . __( '4. Schema health (all layers):', 'teil1-schema-manager' ) );
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
                \WP_CLI::log(
                    sprintf(
                        /* translators: 1: Global schema ID, 2: Schema.org type, 3: Number of validation errors. */
                        _n(
                            '   ✗ Global #%1$d (%2$s): %3$d error',
                            '   ✗ Global #%1$d (%2$s): %3$d errors',
                            $errors,
                            'teil1-schema-manager'
                        ),
                        $row['id'],
                        $row['schema_type'],
                        $errors
                    )
                );
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
                \WP_CLI::log(
                    sprintf(
                        /* translators: 1: Schema rule ID, 2: Schema.org type, 3: Number of validation errors. */
                        _n(
                            '   ✗ Rule #%1$d (%2$s): %3$d error',
                            '   ✗ Rule #%1$d (%2$s): %3$d errors',
                            $errors,
                            'teil1-schema-manager'
                        ),
                        $row['id'],
                        $row['schema_type'],
                        $errors
                    )
                );
            }
        }

        if ( $total_errors === 0 ) {
            \WP_CLI::log(
                sprintf(
                    /* translators: %1$d: Number of validation warnings. */
                    _n(
                        '   ✓ All schemas valid (%1$d warning)',
                        '   ✓ All schemas valid (%1$d warnings)',
                        $total_warns,
                        'teil1-schema-manager'
                    ),
                    $total_warns
                )
            );
        } else {
            $issues += $total_errors;
        }

        // 5. Orphaned local schemas.
        \WP_CLI::log( "\n" . __( '5. Orphaned local schemas:', 'teil1-schema-manager' ) );
        $orphans = $wpdb->get_results(
            "SELECT pm.post_id FROM {$wpdb->postmeta} pm LEFT JOIN {$wpdb->posts} p ON pm.post_id = p.ID WHERE pm.meta_key = '_t1schema_local' AND (p.ID IS NULL OR p.post_status = 'trash')",
            ARRAY_A
        ); // phpcs:ignore
        if ( empty( $orphans ) ) {
            \WP_CLI::log( __( '   ✓ No orphaned schemas', 'teil1-schema-manager' ) );
        } else {
            $ids          = array_column( $orphans, 'post_id' );
            $orphan_count = count( $orphans );
            \WP_CLI::log(
                sprintf(
                    /* translators: 1: Number of orphaned local schemas, 2: Comma-separated post IDs. */
                    _n(
                        '   ⚠ %1$d orphaned local schema on deleted posts: %2$s',
                        '   ⚠ %1$d orphaned local schemas on deleted posts: %2$s',
                        $orphan_count,
                        'teil1-schema-manager'
                    ),
                    $orphan_count,
                    implode( ', ', $ids )
                )
            );
            $issues++;
        }

        // Summary.
        \WP_CLI::log( "\n" . str_repeat( '─', 50 ) );
        if ( $issues === 0 ) {
            \WP_CLI::success( __( 'No issues found. Teil1 Schema Manager is healthy.', 'teil1-schema-manager' ) );
        } else {
            \WP_CLI::warning(
                sprintf(
                    /* translators: %1$d: Number of issues found. */
                    _n( '%1$d issue found.', '%1$d issues found.', $issues, 'teil1-schema-manager' ),
                    $issues
                )
            );
        }
    }

    /**
     * Format items while translating headings only for human-readable tables.
     *
     * Machine-readable formats retain their original field names and values.
     *
     * @param string $format Output format.
     * @param array  $items  Items to format.
     * @param array  $fields Item fields in display order.
     */
    private function format_items( string $format, array $items, array $fields ): void {
        if ( 'table' !== $format ) {
            \WP_CLI\Utils\format_items( $format, $items, $fields );
            return;
        }

        $table_fields = array_map( [ $this, 'translate_table_header' ], $fields );
        $table_items  = array_map(
            function ( array $item ) use ( $fields ): array {
                $table_item = [];
                foreach ( $fields as $field ) {
                    $table_item[ $this->translate_table_header( $field ) ] = $item[ $field ] ?? null;
                }
                return $table_item;
            },
            $items
        );

        \WP_CLI\Utils\format_items( 'table', $table_items, $table_fields );
    }

    /**
     * Translate a table column heading.
     *
     * @param string $header Original heading.
     * @return string Translated heading.
     */
    private function translate_table_header( string $header ): string {
        switch ( $header ) {
            case '#':
                return _x( '#', 'table column heading', 'teil1-schema-manager' );
            case 'ID':
                return _x( 'ID', 'table column heading', 'teil1-schema-manager' );
            case 'Index':
                return _x( 'Index', 'table column heading', 'teil1-schema-manager' );
            case 'Type':
                return _x( 'Type', 'table column heading', 'teil1-schema-manager' );
            case 'Status':
                return _x( 'Status', 'table column heading', 'teil1-schema-manager' );
            case 'Name':
                return _x( 'Name', 'table column heading', 'teil1-schema-manager' );
            case 'Created':
                return _x( 'Created', 'table column heading', 'teil1-schema-manager' );
            case 'Override':
                return _x( 'Override', 'table column heading', 'teil1-schema-manager' );
            case 'Props':
                return _x( 'Props', 'table column heading', 'teil1-schema-manager' );
            case 'Layer':
                return _x( 'Layer', 'table column heading', 'teil1-schema-manager' );
            case 'Errors':
                return _x( 'Errors', 'table column heading', 'teil1-schema-manager' );
            case 'Warnings':
                return _x( 'Warnings', 'table column heading', 'teil1-schema-manager' );
            case 'Infos':
                return _x( 'Infos', 'table column heading', 'teil1-schema-manager' );
            case 'Details':
                return _x( 'Details', 'table column heading', 'teil1-schema-manager' );
            case 'Parent':
                return _x( 'Parent', 'table column heading', 'teil1-schema-manager' );
            case 'Required':
                return _x( 'Required', 'table column heading', 'teil1-schema-manager' );
            case 'Recommended':
                return _x( 'Recommended', 'table column heading', 'teil1-schema-manager' );
            case 'Total Props':
                return _x( 'Total Props', 'table column heading', 'teil1-schema-manager' );
            case 'Category':
                return _x( 'Category', 'table column heading', 'teil1-schema-manager' );
            case 'Variable':
                return _x( 'Variable', 'table column heading', 'teil1-schema-manager' );
            case 'Desc':
                return _x( 'Desc', 'table column heading', 'teil1-schema-manager' );
            case 'Key':
                return _x( 'Key', 'table column heading', 'teil1-schema-manager' );
            case 'Value':
                return _x( 'Value', 'table column heading', 'teil1-schema-manager' );
            case 'Conditions':
                return _x( 'Conditions', 'table column heading', 'teil1-schema-manager' );
            case 'Priority':
                return _x( 'Priority', 'table column heading', 'teil1-schema-manager' );
            case 'Context':
                return _x( 'Context', 'table column heading', 'teil1-schema-manager' );
            case 'Schemas':
                return _x( 'Schemas', 'table column heading', 'teil1-schema-manager' );
            default:
                return $header;
        }
    }

    /**
     * Translate known human-readable status labels.
     *
     * @param string $status Stored status identifier.
     * @return string Translated status label.
     */
    private function translate_status_label( string $status ): string {
        switch ( $status ) {
            case 'active':
                return _x( 'Active', 'schema status', 'teil1-schema-manager' );
            case 'draft':
                return _x( 'Draft', 'schema status', 'teil1-schema-manager' );
            default:
                return $status;
        }
    }
}
