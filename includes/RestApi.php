<?php

namespace T1Schema;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * REST API endpoints for t1 Schema.
 *
 * @package T1Schema
 * @since   1.0.0
 */
class RestApi {

    private const NAMESPACE = 'teil1-schema-manager/v1';

    public function init(): void {
        add_action( 'rest_api_init', [ $this, 'register_routes' ] );
    }

    public function register_routes(): void {
        // --- Global Schema CRUD ---
        register_rest_route( self::NAMESPACE, '/globals', [
            [
                'methods'             => 'GET',
                'callback'            => [ $this, 'get_globals' ],
                'permission_callback' => [ $this, 'check_permissions' ],
            ],
            [
                'methods'             => 'POST',
                'callback'            => [ $this, 'create_global' ],
                'permission_callback' => [ $this, 'check_permissions' ],
            ],
        ] );

        register_rest_route( self::NAMESPACE, '/globals/(?P<id>\d+)', [
            [
                'methods'             => 'GET',
                'callback'            => [ $this, 'get_global' ],
                'permission_callback' => [ $this, 'check_permissions' ],
            ],
            [
                'methods'             => 'PUT',
                'callback'            => [ $this, 'update_global' ],
                'permission_callback' => [ $this, 'check_permissions' ],
            ],
            [
                'methods'             => 'DELETE',
                'callback'            => [ $this, 'delete_global' ],
                'permission_callback' => [ $this, 'check_permissions' ],
            ],
        ] );

        // --- Local Schema (per-post) ---
        register_rest_route( self::NAMESPACE, '/local/(?P<post_id>\d+)', [
            [
                'methods'             => 'GET',
                'callback'            => [ $this, 'get_local' ],
                'permission_callback' => [ $this, 'check_permissions' ],
            ],
            [
                'methods'             => 'PUT',
                'callback'            => [ $this, 'update_local' ],
                'permission_callback' => [ $this, 'check_permissions' ],
            ],
        ] );

        // --- Health ---
        register_rest_route( self::NAMESPACE, '/health', [
            'methods'             => 'GET',
            'callback'            => [ $this, 'get_health' ],
            'permission_callback' => [ $this, 'check_permissions' ],
        ] );

        register_rest_route( self::NAMESPACE, '/health/(?P<post_id>\d+)', [
            'methods'             => 'GET',
            'callback'            => [ $this, 'get_post_health' ],
            'permission_callback' => [ $this, 'check_permissions' ],
        ] );

        // --- Schema Types Registry ---
        register_rest_route( self::NAMESPACE, '/types', [
            'methods'             => 'GET',
            'callback'            => [ $this, 'get_types' ],
            'permission_callback' => [ $this, 'check_permissions' ],
        ] );

        // --- JSON-LD Parser ---
        register_rest_route( self::NAMESPACE, '/parse', [
            'methods'             => 'POST',
            'callback'            => [ $this, 'parse_jsonld' ],
            'permission_callback' => [ $this, 'check_permissions' ],
        ] );

        // --- Available Variables ---
        register_rest_route( self::NAMESPACE, '/variables', [
            'methods'             => 'GET',
            'callback'            => [ $this, 'get_variables' ],
            'permission_callback' => [ $this, 'check_permissions' ],
        ] );

        // --- Posts Browser ---
        register_rest_route( self::NAMESPACE, '/posts', [
            'methods'             => 'GET',
            'callback'            => [ $this, 'get_posts' ],
            'permission_callback' => [ $this, 'check_permissions' ],
        ] );

        // --- Post Types ---
        register_rest_route( self::NAMESPACE, '/post-types', [
            'methods'             => 'GET',
            'callback'            => [ $this, 'get_post_types' ],
            'permission_callback' => [ $this, 'check_permissions' ],
        ] );

        // --- Settings ---
        register_rest_route( self::NAMESPACE, '/settings', [
            [
                'methods'             => 'GET',
                'callback'            => [ $this, 'get_settings' ],
                'permission_callback' => [ $this, 'check_permissions' ],
            ],
            [
                'methods'             => 'PUT',
                'callback'            => [ $this, 'update_settings' ],
                'permission_callback' => [ $this, 'check_permissions' ],
            ],
        ] );

        // --- Schema Rules CRUD ---
        register_rest_route( self::NAMESPACE, '/rules', [
            [
                'methods'             => 'GET',
                'callback'            => [ $this, 'get_rules' ],
                'permission_callback' => [ $this, 'check_permissions' ],
            ],
            [
                'methods'             => 'POST',
                'callback'            => [ $this, 'create_rule' ],
                'permission_callback' => [ $this, 'check_permissions' ],
            ],
        ] );

        register_rest_route( self::NAMESPACE, '/rules/(?P<id>\d+)', [
            [
                'methods'             => 'GET',
                'callback'            => [ $this, 'get_rule' ],
                'permission_callback' => [ $this, 'check_permissions' ],
            ],
            [
                'methods'             => 'PUT',
                'callback'            => [ $this, 'update_rule' ],
                'permission_callback' => [ $this, 'check_permissions' ],
            ],
            [
                'methods'             => 'DELETE',
                'callback'            => [ $this, 'delete_rule' ],
                'permission_callback' => [ $this, 'check_permissions' ],
            ],
        ] );

        // --- Site Structure ---
        register_rest_route( self::NAMESPACE, '/site-structure', [
            'methods'             => 'GET',
            'callback'            => [ $this, 'get_site_structure' ],
            'permission_callback' => [ $this, 'check_permissions' ],
        ] );

        // --- Available Conditions ---
        register_rest_route( self::NAMESPACE, '/contexts', [
            'methods'             => 'GET',
            'callback'            => [ $this, 'get_contexts' ],
            'permission_callback' => [ $this, 'check_permissions' ],
        ] );

        // --- Schema Score ---
        register_rest_route( self::NAMESPACE, '/score', [
            'methods'             => 'GET',
            'callback'            => [ $this, 'get_score' ],
            'permission_callback' => [ $this, 'check_permissions' ],
        ] );

        // --- Recommended Rules (opt-in templates) ---
        register_rest_route( self::NAMESPACE, '/recommended-rules', [
            [
                'methods'             => 'GET',
                'callback'            => [ $this, 'get_recommended_rules' ],
                'permission_callback' => [ $this, 'check_permissions' ],
            ],
            [
                'methods'             => 'POST',
                'callback'            => [ $this, 'activate_recommended_rule' ],
                'permission_callback' => [ $this, 'check_permissions' ],
            ],
        ] );

        // --- Custom Variables ---
        register_rest_route( self::NAMESPACE, '/custom-variables', [
            [
                'methods'             => 'GET',
                'callback'            => [ $this, 'get_custom_variables' ],
                'permission_callback' => [ $this, 'check_permissions' ],
            ],
            [
                'methods'             => 'PUT',
                'callback'            => [ $this, 'update_custom_variables' ],
                'permission_callback' => [ $this, 'check_permissions' ],
            ],
        ] );
    }

    /**
     * Permission check: manage_options with filterable capability.
     */
    public function check_permissions(): bool {
        $cap = apply_filters( 't1schema_required_capability', 'manage_options' );
        return current_user_can( $cap );
    }

    // =========================================================================
    // Global Schema Endpoints
    // =========================================================================

    public function get_globals( \WP_REST_Request $request ): \WP_REST_Response {
        global $wpdb;
        $table = esc_sql( $wpdb->prefix . 't1schema_globals' );

        $rows = $wpdb->get_results( "SELECT * FROM {$table} ORDER BY created_at DESC", ARRAY_A ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name from $wpdb->prefix, no user input.

        $items = array_map( function ( array $row ): array {
            $row['schema_data'] = json_decode( $row['schema_data'], true );
            $row['id']          = (int) $row['id'];
            return $row;
        }, (array) $rows );

        return new \WP_REST_Response( $items, 200 );
    }

    public function get_global( \WP_REST_Request $request ): \WP_REST_Response {
        global $wpdb;
        $table = esc_sql( $wpdb->prefix . 't1schema_globals' );
        $id    = (int) $request->get_param( 'id' );

        $row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id ), ARRAY_A ); // phpcs:ignore

        if ( ! $row ) {
            return new \WP_REST_Response( [ 'message' => 'Schema not found.' ], 404 );
        }

        $row['schema_data'] = json_decode( $row['schema_data'], true );
        $row['id']          = (int) $row['id'];

        return new \WP_REST_Response( $row, 200 );
    }

    public function create_global( \WP_REST_Request $request ): \WP_REST_Response {
        global $wpdb;
        $table = esc_sql( $wpdb->prefix . 't1schema_globals' );

        $body        = $request->get_json_params();
        $schema_type = sanitize_text_field( $body['schema_type'] ?? '' );
        $schema_data = $body['schema_data'] ?? [];
        $status      = sanitize_text_field( $body['status'] ?? 'active' );

        if ( empty( $schema_type ) || empty( $schema_data ) ) {
            return new \WP_REST_Response( [ 'message' => 'schema_type and schema_data are required.' ], 400 );
        }

        $result = $wpdb->insert( $table, [
            'schema_type' => $schema_type,
            'schema_data' => wp_json_encode( $schema_data ),
            'status'      => $status,
        ], [ '%s', '%s', '%s' ] );

        if ( false === $result ) {
            return new \WP_REST_Response( [ 'message' => 'Failed to create schema.' ], 500 );
        }

        return new \WP_REST_Response( [
            'id'          => (int) $wpdb->insert_id,
            'schema_type' => $schema_type,
            'schema_data' => $schema_data,
            'status'      => $status,
        ], 201 );
    }

    public function update_global( \WP_REST_Request $request ): \WP_REST_Response {
        global $wpdb;
        $table = esc_sql( $wpdb->prefix . 't1schema_globals' );
        $id    = (int) $request->get_param( 'id' );

        $existing = $wpdb->get_row( $wpdb->prepare( "SELECT id FROM {$table} WHERE id = %d", $id ) ); // phpcs:ignore
        if ( ! $existing ) {
            return new \WP_REST_Response( [ 'message' => 'Schema not found.' ], 404 );
        }

        $body   = $request->get_json_params();
        $update = [];
        $format = [];

        if ( isset( $body['schema_type'] ) ) {
            $update['schema_type'] = sanitize_text_field( $body['schema_type'] );
            $format[]              = '%s';
        }
        if ( isset( $body['schema_data'] ) ) {
            $update['schema_data'] = wp_json_encode( $body['schema_data'] );
            $format[]              = '%s';
        }
        if ( isset( $body['status'] ) ) {
            $update['status'] = sanitize_text_field( $body['status'] );
            $format[]         = '%s';
        }

        if ( empty( $update ) ) {
            return new \WP_REST_Response( [ 'message' => 'No fields to update.' ], 400 );
        }

        $wpdb->update( $table, $update, [ 'id' => $id ], $format, [ '%d' ] );

        return $this->get_global( $request );
    }

    public function delete_global( \WP_REST_Request $request ): \WP_REST_Response {
        global $wpdb;
        $table = esc_sql( $wpdb->prefix . 't1schema_globals' );
        $id    = (int) $request->get_param( 'id' );

        $deleted = $wpdb->delete( $table, [ 'id' => $id ], [ '%d' ] );

        if ( ! $deleted ) {
            return new \WP_REST_Response( [ 'message' => 'Schema not found.' ], 404 );
        }

        return new \WP_REST_Response( [ 'deleted' => true ], 200 );
    }

    // =========================================================================
    // Local Schema Endpoints
    // =========================================================================

    public function get_local( \WP_REST_Request $request ): \WP_REST_Response {
        $post_id = (int) $request->get_param( 'post_id' );

        if ( ! get_post( $post_id ) ) {
            return new \WP_REST_Response( [ 'message' => 'Post not found.' ], 404 );
        }

        $raw     = get_post_meta( $post_id, '_t1schema_local', true );
        $schemas = $raw ? ( is_string( $raw ) ? json_decode( $raw, true ) : $raw ) : [];

        return new \WP_REST_Response( [
            'post_id' => $post_id,
            'title'   => get_the_title( $post_id ),
            'schemas' => is_array( $schemas ) ? $schemas : [],
        ], 200 );
    }

    public function update_local( \WP_REST_Request $request ): \WP_REST_Response {
        $post_id = (int) $request->get_param( 'post_id' );

        if ( ! get_post( $post_id ) ) {
            return new \WP_REST_Response( [ 'message' => 'Post not found.' ], 404 );
        }

        $body    = $request->get_json_params();
        $schemas = $body['schemas'] ?? [];

        if ( ! is_array( $schemas ) ) {
            return new \WP_REST_Response( [ 'message' => 'schemas must be an array.' ], 400 );
        }

        update_post_meta( $post_id, '_t1schema_local', wp_json_encode( $schemas ) );

        return new \WP_REST_Response( [
            'post_id' => $post_id,
            'schemas' => $schemas,
        ], 200 );
    }

    // =========================================================================
    // Health Endpoints
    // =========================================================================

    public function get_health( \WP_REST_Request $request ): \WP_REST_Response {
        global $wpdb;
        $g_table = esc_sql( $wpdb->prefix . 't1schema_globals' );
        $r_table = esc_sql( $wpdb->prefix . 't1schema_rules' );

        $report = [
            'globals'  => [],
            'rules'    => [],
            'summary'  => [ 'total' => 0, 'valid' => 0, 'warnings' => 0, 'errors' => 0, 'infos' => 0 ],
        ];

        // Validate globals.
        $globals = $wpdb->get_results( "SELECT * FROM {$g_table} WHERE status = 'active'", ARRAY_A ); // phpcs:ignore
        foreach ( (array) $globals as $row ) {
            $data = json_decode( $row['schema_data'], true );
            // Safety: ensure @type is present from DB column (same as Frontend).
            if ( is_array( $data ) && empty( $data['@type'] ) && ! empty( $row['schema_type'] ) ) {
                $data['@type'] = $row['schema_type'];
            }
            $health = SchemaValidator::validate( $data, 'global' );
            $report['globals'][] = [
                'id'     => (int) $row['id'],
                'type'   => $row['schema_type'],
                'layer'  => 'global',
                'health' => $health,
            ];
            $report['summary']['total']++;
            if ( $health['valid'] ) {
                $report['summary']['valid']++;
            }
            $report['summary']['warnings'] += count( $health['warnings'] ?? [] );
            $report['summary']['errors']   += count( $health['errors'] ?? [] );
            $report['summary']['infos']    += count( $health['infos'] ?? [] );
        }

        // Validate rules.
        if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $r_table ) ) ) {
            $rules = $wpdb->get_results( "SELECT * FROM {$r_table} WHERE status = 'active'", ARRAY_A ); // phpcs:ignore
            foreach ( (array) $rules as $row ) {
                $data = json_decode( $row['schema_data'], true );
                // Safety: ensure @type is present from DB column (same as Frontend).
                if ( is_array( $data ) && empty( $data['@type'] ) && ! empty( $row['schema_type'] ) ) {
                    $data['@type'] = $row['schema_type'];
                }
                $health = SchemaValidator::validate( $data, 'rule' );
                $report['rules'][] = [
                    'id'     => (int) $row['id'],
                    'type'   => $row['schema_type'],
                    'name'   => $row['rule_name'],
                    'layer'  => 'rule',
                    'health' => $health,
                ];
                $report['summary']['total']++;
                if ( $health['valid'] ) {
                    $report['summary']['valid']++;
                }
                $report['summary']['warnings'] += count( $health['warnings'] ?? [] );
                $report['summary']['errors']   += count( $health['errors'] ?? [] );
                $report['summary']['infos']    += count( $health['infos'] ?? [] );
            }
        }

        return new \WP_REST_Response( $report, 200 );
    }

    public function get_post_health( \WP_REST_Request $request ): \WP_REST_Response {
        global $wp_query, $post;
        
        $post_id = (int) $request->get_param( 'post_id' );
        $_post   = get_post( $post_id );
        
        if ( ! $_post ) {
            return new \WP_REST_Response( [ 'message' => 'Post not found.' ], 404 );
        }

        $raw     = get_post_meta( $post_id, '_t1schema_local', true );
        $locals  = $raw ? ( is_string( $raw ) ? json_decode( $raw, true ) : $raw ) : [];

        // --- Context Mocking for accurate validation ---
        $original_post  = $post;
        $original_query = clone $wp_query;
        
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
        
        if ( (int) get_option( 'page_on_front' ) === $post_id ) {
            $wp_query->is_front_page = true;
            $wp_query->is_home       = false;
        }

        $frontend = new Frontend();
        $merged_schemas = $frontend->assemble_schemas();

        // Must resolve variables + dedup BEFORE matching, same as Frontend::render_jsonld
        $merged_schemas = array_map( function( $s ) { unset( $s['_t1schema_meta'] ); return $s; }, $merged_schemas );
        $merged_schemas = VariableResolver::resolve( $merged_schemas, $post_id );

        // Deduplicate nodes with the same @id
        $by_id = [];
        $no_id = [];
        foreach ( $merged_schemas as $node ) {
            $nid = $node['@id'] ?? null;
            if ( $nid ) {
                $by_id[ $nid ] = isset( $by_id[ $nid ] ) ? array_merge( $by_id[ $nid ], $node ) : $node;
            } else {
                $no_id[] = $node;
            }
        }
        $merged_schemas = array_merge( array_values( $by_id ), $no_id );
        
        // Restore context
        $wp_query = $original_query;
        $post     = $original_post;
        wp_reset_postdata();

        // Resolve the local @id values too so we can match them
        $resolved_locals = VariableResolver::resolve( $locals, $post_id );

        $results = [];
        foreach ( (array) $resolved_locals as $i => $schema ) {
            $type = $schema['@type'] ?? 'Unknown';
            $id   = $schema['@id'] ?? null;
            
            // Map the local schema block to its merged representation in the graph
            $validation_target = $schema; // fallback to raw local if not found
            
            foreach ( $merged_schemas as $ms ) {
                if ( $id && isset( $ms['@id'] ) && $ms['@id'] === $id ) {
                    $validation_target = $ms;
                    break;
                } elseif ( ! $id && isset( $ms['@type'] ) && $ms['@type'] === $type ) {
                    $validation_target = $ms;
                    break;
                }
            }

            $results[] = [
                'index'  => $i,
                'type'   => $locals[ $i ]['@type'] ?? 'Unknown',
                'health' => SchemaValidator::validate( $validation_target ),
            ];
        }

        return new \WP_REST_Response( [ 'post_id' => $post_id, 'schemas' => $results ], 200 );
    }

    // =========================================================================
    // Posts Browser
    // =========================================================================

    public function get_posts( \WP_REST_Request $request ): \WP_REST_Response {
        $search    = sanitize_text_field( $request->get_param( 'search' ) ?? '' );
        $post_type = sanitize_text_field( $request->get_param( 'post_type' ) ?? '' );
        $per_page  = min( (int) ( $request->get_param( 'per_page' ) ?? 20 ), 100 );
        $page      = max( (int) ( $request->get_param( 'page' ) ?? 1 ), 1 );
        $filter    = sanitize_text_field( $request->get_param( 'filter' ) ?? '' ); // 'with_schema' | 'without_schema' | ''

        $args = [
            'post_status'    => 'publish',
            'posts_per_page' => $per_page,
            'paged'          => $page,
            'orderby'        => 'modified',
            'order'          => 'DESC',
        ];

        if ( $post_type ) {
            $args['post_type'] = $post_type;
        } else {
            // Include all public post types (not just post/page).
            $public_types = get_post_types( [ 'public' => true ], 'names' );
            unset( $public_types['attachment'] );
            $args['post_type'] = array_values( $public_types );
        }

        if ( $search ) {
            $args['s'] = $search;
        }

        // Filter by schema presence.
        if ( $filter === 'with_schema' ) {
            $args['meta_query'] = [
                [
                    'key'     => '_t1schema_local',
                    'compare' => 'EXISTS',
                ],
            ];
        } elseif ( $filter === 'without_schema' ) {
            $args['meta_query'] = [
                [
                    'key'     => '_t1schema_local',
                    'compare' => 'NOT EXISTS',
                ],
            ];
        }

        $query = new \WP_Query( $args );
        $items = [];

        foreach ( $query->posts as $post ) {
            $raw     = get_post_meta( $post->ID, '_t1schema_local', true );
            $schemas = $raw ? ( is_string( $raw ) ? json_decode( $raw, true ) : $raw ) : [];
            $schemas = is_array( $schemas ) ? $schemas : [];

            $types = [];
            foreach ( $schemas as $s ) {
                if ( ! empty( $s['@type'] ) ) {
                    $types[] = $s['@type'];
                }
            }

            $items[] = [
                'id'           => $post->ID,
                'title'        => get_the_title( $post ),
                'url'          => get_permalink( $post ),
                'post_type'    => $post->post_type,
                'modified'     => get_the_modified_date( 'c', $post ),
                'schema_count' => count( $schemas ),
                'schema_types' => $types,
                'edit_url'     => get_edit_post_link( $post->ID, 'raw' ),
            ];
        }

        return new \WP_REST_Response( [
            'posts'       => $items,
            'total'       => (int) $query->found_posts,
            'total_pages' => (int) $query->max_num_pages,
            'page'        => $page,
            'per_page'    => $per_page,
        ], 200 );
    }

    // =========================================================================
    // Utility Endpoints
    // =========================================================================

    public function get_post_types( \WP_REST_Request $request ): \WP_REST_Response {
        $types  = get_post_types( [ 'public' => true ], 'objects' );
        $result = [];

        foreach ( $types as $slug => $type_obj ) {
            if ( $slug === 'attachment' ) {
                continue;
            }

            $result[] = [
                'slug'  => $slug,
                'label' => $type_obj->labels->singular_name ?? $slug,
                'count' => (int) wp_count_posts( $slug )->publish,
            ];
        }

        return new \WP_REST_Response( $result, 200 );
    }

    public function get_types( \WP_REST_Request $request ): \WP_REST_Response {
        $registry = new SchemaRegistry();
        return new \WP_REST_Response( $registry->get_types(), 200 );
    }

    public function parse_jsonld( \WP_REST_Request $request ): \WP_REST_Response {
        $body = $request->get_json_params();
        $raw  = $body['jsonld'] ?? '';

        if ( empty( $raw ) || ! is_string( $raw ) ) {
            return new \WP_REST_Response( [ 'message' => 'jsonld string is required.' ], 400 );
        }

        $decoded = json_decode( $raw, true );
        if ( json_last_error() !== JSON_ERROR_NONE ) {
            return new \WP_REST_Response( [
                'message' => 'Invalid JSON: ' . json_last_error_msg(),
                'valid'   => false,
            ], 400 );
        }

        // Handle @graph arrays.
        $schemas = [];
        if ( isset( $decoded['@graph'] ) && is_array( $decoded['@graph'] ) ) {
            $schemas = $decoded['@graph'];
        } else {
            $schemas = [ $decoded ];
        }

        // Validate each.
        $results = [];
        foreach ( $schemas as $schema ) {
            $results[] = [
                'schema' => $schema,
                'health' => SchemaValidator::validate( $schema ),
            ];
        }

        return new \WP_REST_Response( [ 'valid' => true, 'schemas' => $results ], 200 );
    }

    public function get_variables( \WP_REST_Request $request ): \WP_REST_Response {
        return new \WP_REST_Response( VariableResolver::get_available_variables(), 200 );
    }

    public function get_settings( \WP_REST_Request $request ): \WP_REST_Response {
        return new \WP_REST_Response( [
            'delete_data_on_uninstall' => (bool) get_option( 't1schema_delete_data_on_uninstall', false ),
            'suppress_conflicts'       => (bool) get_option( 't1schema_suppress_conflicts', false ),
            'version'                  => T1SCHEMA_VERSION,
            'db_version'               => get_option( 't1schema_db_version', '0' ),
        ], 200 );
    }

    public function update_settings( \WP_REST_Request $request ): \WP_REST_Response {
        $body = $request->get_json_params();

        if ( isset( $body['delete_data_on_uninstall'] ) ) {
            update_option( 't1schema_delete_data_on_uninstall', (bool) $body['delete_data_on_uninstall'] );
        }

        if ( isset( $body['suppress_conflicts'] ) ) {
            update_option( 't1schema_suppress_conflicts', (bool) $body['suppress_conflicts'] );
        }

        return $this->get_settings( $request );
    }

    // =========================================================================
    // Custom Variables
    // =========================================================================

    public function get_custom_variables( \WP_REST_Request $request ): \WP_REST_Response {
        $vars = get_option( 't1schema_custom_variables', [] );
        if ( ! is_array( $vars ) ) {
            $vars = [];
        }
        return new \WP_REST_Response( $vars, 200 );
    }

    public function update_custom_variables( \WP_REST_Request $request ): \WP_REST_Response {
        $body = $request->get_json_params();

        if ( ! is_array( $body ) ) {
            return new \WP_REST_Response( [ 'error' => 'Expected an object of key-value pairs.' ], 400 );
        }

        // Sanitize: only string keys/values, strip {{}} from keys.
        $clean = [];
        foreach ( $body as $key => $value ) {
            $key = sanitize_key( $key );
            if ( $key && is_string( $value ) ) {
                $clean[ $key ] = sanitize_text_field( $value );
            }
        }

        update_option( 't1schema_custom_variables', $clean );

        return new \WP_REST_Response( $clean, 200 );
    }

    // =========================================================================
    // Schema Rules Endpoints
    // =========================================================================

    public function get_rules( \WP_REST_Request $request ): \WP_REST_Response {
        global $wpdb;
        $table = esc_sql( $wpdb->prefix . 't1schema_rules' );

        $rows = $wpdb->get_results( "SELECT * FROM {$table} ORDER BY priority ASC, created_at DESC", ARRAY_A ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name from $wpdb->prefix, no user input.

        $items = array_map( function ( array $row ): array {
            $row['id']          = (int) $row['id'];
            $row['priority']    = (int) $row['priority'];
            $row['schema_data'] = json_decode( $row['schema_data'], true );
            $row['conditions']  = json_decode( $row['conditions'], true );
            return $row;
        }, (array) $rows );

        return new \WP_REST_Response( $items, 200 );
    }

    public function get_rule( \WP_REST_Request $request ): \WP_REST_Response {
        global $wpdb;
        $table = esc_sql( $wpdb->prefix . 't1schema_rules' );
        $id    = (int) $request->get_param( 'id' );

        $row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id ), ARRAY_A ); // phpcs:ignore
        if ( ! $row ) {
            return new \WP_REST_Response( [ 'message' => 'Rule not found.' ], 404 );
        }

        $row['id']          = (int) $row['id'];
        $row['priority']    = (int) $row['priority'];
        $row['schema_data'] = json_decode( $row['schema_data'], true );
        $row['conditions']  = json_decode( $row['conditions'], true );

        return new \WP_REST_Response( $row, 200 );
    }

    public function create_rule( \WP_REST_Request $request ): \WP_REST_Response {
        global $wpdb;
        $table = esc_sql( $wpdb->prefix . 't1schema_rules' );

        $body        = $request->get_json_params();
        $rule_name   = sanitize_text_field( $body['rule_name'] ?? '' );
        $schema_type = sanitize_text_field( $body['schema_type'] ?? '' );
        $schema_data = $body['schema_data'] ?? [];
        $conditions  = $body['conditions'] ?? [];
        $priority    = (int) ( $body['priority'] ?? 10 );
        $status      = sanitize_text_field( $body['status'] ?? 'active' );

        if ( empty( $schema_type ) || empty( $conditions ) ) {
            return new \WP_REST_Response( [ 'message' => 'schema_type and conditions are required.' ], 400 );
        }

        // Auto-generate rule name if not provided.
        if ( empty( $rule_name ) ) {
            $rule_name = $this->generate_rule_name( $schema_type, $conditions );
        }

        $result = $wpdb->insert( $table, [
            'rule_name'   => $rule_name,
            'schema_type' => $schema_type,
            'schema_data' => wp_json_encode( $schema_data ),
            'conditions'  => wp_json_encode( $conditions ),
            'priority'    => $priority,
            'status'      => $status,
        ], [ '%s', '%s', '%s', '%s', '%d', '%s' ] );

        if ( false === $result ) {
            return new \WP_REST_Response( [ 'message' => 'Failed to create rule.' ], 500 );
        }

        return new \WP_REST_Response( [
            'id'          => (int) $wpdb->insert_id,
            'rule_name'   => $rule_name,
            'schema_type' => $schema_type,
            'schema_data' => $schema_data,
            'conditions'  => $conditions,
            'priority'    => $priority,
            'status'      => $status,
        ], 201 );
    }

    public function update_rule( \WP_REST_Request $request ): \WP_REST_Response {
        global $wpdb;
        $table = esc_sql( $wpdb->prefix . 't1schema_rules' );
        $id    = (int) $request->get_param( 'id' );

        $existing = $wpdb->get_row( $wpdb->prepare( "SELECT id FROM {$table} WHERE id = %d", $id ) ); // phpcs:ignore
        if ( ! $existing ) {
            return new \WP_REST_Response( [ 'message' => 'Rule not found.' ], 404 );
        }

        $body   = $request->get_json_params();
        $update = [];
        $format = [];

        if ( isset( $body['rule_name'] ) ) {
            $update['rule_name'] = sanitize_text_field( $body['rule_name'] );
            $format[]            = '%s';
        }
        if ( isset( $body['schema_type'] ) ) {
            $update['schema_type'] = sanitize_text_field( $body['schema_type'] );
            $format[]              = '%s';
        }
        if ( isset( $body['schema_data'] ) ) {
            $update['schema_data'] = wp_json_encode( $body['schema_data'] );
            $format[]              = '%s';
        }
        if ( isset( $body['conditions'] ) ) {
            $update['conditions'] = wp_json_encode( $body['conditions'] );
            $format[]             = '%s';
        }
        if ( isset( $body['priority'] ) ) {
            $update['priority'] = (int) $body['priority'];
            $format[]           = '%d';
        }
        if ( isset( $body['status'] ) ) {
            $update['status'] = sanitize_text_field( $body['status'] );
            $format[]         = '%s';
        }

        if ( empty( $update ) ) {
            return new \WP_REST_Response( [ 'message' => 'No fields to update.' ], 400 );
        }

        $wpdb->update( $table, $update, [ 'id' => $id ], $format, [ '%d' ] );

        return $this->get_rule( $request );
    }

    public function delete_rule( \WP_REST_Request $request ): \WP_REST_Response {
        global $wpdb;
        $table = esc_sql( $wpdb->prefix . 't1schema_rules' );
        $id    = (int) $request->get_param( 'id' );

        $deleted = $wpdb->delete( $table, [ 'id' => $id ], [ '%d' ] );

        if ( ! $deleted ) {
            return new \WP_REST_Response( [ 'message' => 'Rule not found.' ], 404 );
        }

        return new \WP_REST_Response( [ 'deleted' => true ], 200 );
    }

    /**
     * Auto-generate a readable rule name from its conditions.
     */
    private function generate_rule_name( string $type, array $conditions ): string {
        $parts = [];
        foreach ( $conditions as $c ) {
            $ct = $c['type'] ?? '';
            $cv = $c['value'] ?? '';
            if ( $cv ) {
                $parts[] = "{$ct}:{$cv}";
            } else {
                $parts[] = $ct;
            }
        }
        return "{$type} → " . implode( ' + ', $parts );
    }

    // =========================================================================
    // Site Structure & Contexts
    // =========================================================================

    public function get_site_structure( \WP_REST_Request $request ): \WP_REST_Response {
        $structure = [];

        // Load all rules for coverage mapping.
        global $wpdb;
        $rules_table = esc_sql( $wpdb->prefix . 't1schema_rules' );
        $rules_exist = (bool) $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $rules_table ) );
        $all_rules   = [];

        if ( $rules_exist ) {
            $rows = $wpdb->get_results( "SELECT * FROM {$rules_table} WHERE status = 'active'", ARRAY_A ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name from $wpdb->prefix, no user input.
            foreach ( (array) $rows as $row ) {
                $all_rules[] = [
                    'id'          => (int) $row['id'],
                    'rule_name'   => $row['rule_name'],
                    'schema_type' => $row['schema_type'],
                    'conditions'  => json_decode( $row['conditions'], true ),
                ];
            }
        }

        // Front page.
        $front_page_id = (int) get_option( 'page_on_front' );
        $structure[] = [
            'context'  => 'front_page',
            'label'    => 'Front Page',
            'icon'     => '🏠',
            'url'      => home_url( '/' ),
            'post_id'  => $front_page_id ?: null,
            'rules'    => $this->find_rules_for_context( $all_rules, [ 'type' => 'front_page' ] ),
            'children' => [],
        ];

        // Blog index.
        $blog_page_id = (int) get_option( 'page_for_posts' );
        $structure[] = [
            'context'  => 'blog',
            'label'    => 'Blog Index',
            'icon'     => '📝',
            'url'      => get_permalink( $blog_page_id ) ?: home_url( '/' ),
            'post_id'  => $blog_page_id ?: null,
            'rules'    => $this->find_rules_for_context( $all_rules, [ 'type' => 'archive', 'value' => 'blog' ] ),
            'children' => [],
        ];

        // Post types.
        $post_types = get_post_types( [ 'public' => true ], 'objects' );
        foreach ( $post_types as $slug => $pt ) {
            if ( $slug === 'attachment' ) {
                continue;
            }

            $entry = [
                'context'  => "post_type:{$slug}",
                'label'    => $pt->labels->name,
                'icon'     => $slug === 'page' ? '📄' : ( $slug === 'post' ? '📰' : '📁' ),
                'post_id'  => null,
                'rules'    => $this->find_rules_for_context( $all_rules, [ 'type' => 'singular', 'value' => $slug ] ),
                'children' => [],
            ];

            // Archive page for CPTs.
            if ( $pt->has_archive ) {
                $archive_url = get_post_type_archive_link( $slug );
                $entry['archive'] = [
                    'context' => "archive:{$slug}",
                    'label'   => "{$pt->labels->name} Archive",
                    'url'     => $archive_url ?: '',
                    'rules'   => $this->find_rules_for_context( $all_rules, [ 'type' => 'archive', 'value' => $slug ] ),
                ];
            }

            $entry['count'] = (int) wp_count_posts( $slug )->publish;
            $structure[] = $entry;
        }

        // Taxonomies.
        $taxonomies = get_taxonomies( [ 'public' => true ], 'objects' );
        foreach ( $taxonomies as $slug => $tax ) {
            $terms = get_terms( [ 'taxonomy' => $slug, 'hide_empty' => false, 'number' => 30 ] );
            if ( is_wp_error( $terms ) ) {
                $terms = [];
            }

            $children = [];
            foreach ( $terms as $term ) {
                $children[] = [
                    'context' => "taxonomy_term:{$slug}:{$term->slug}",
                    'label'   => $term->name,
                    'url'     => get_term_link( $term ),
                    'count'   => $term->count,
                    'rules'   => $this->find_rules_for_context( $all_rules, [ 'type' => 'taxonomy_term', 'value' => "{$slug}:{$term->slug}" ] ),
                ];
            }

            $structure[] = [
                'context'  => "taxonomy:{$slug}",
                'label'    => $tax->labels->name,
                'icon'     => '🏷️',
                'rules'    => $this->find_rules_for_context( $all_rules, [ 'type' => 'taxonomy', 'value' => $slug ] ),
                'children' => $children,
            ];
        }

        // Special pages.
        $structure[] = [
            'context'  => 'author',
            'label'    => 'Author Archives',
            'icon'     => '👤',
            'rules'    => $this->find_rules_for_context( $all_rules, [ 'type' => 'author' ] ),
            'children' => [],
        ];

        $structure[] = [
            'context'  => 'search',
            'label'    => 'Search Results',
            'icon'     => '🔍',
            'rules'    => $this->find_rules_for_context( $all_rules, [ 'type' => 'search' ] ),
            'children' => [],
        ];

        return new \WP_REST_Response( $structure, 200 );
    }

    /**
     * Find rules that would match a given context condition.
     */
    private function find_rules_for_context( array $all_rules, array $target ): array {
        $matched = [];
        foreach ( $all_rules as $rule ) {
            foreach ( $rule['conditions'] as $c ) {
                if ( $c['type'] === $target['type'] && ( empty( $target['value'] ) || ( $c['value'] ?? '' ) === $target['value'] ) ) {
                    $matched[] = [
                        'id'          => $rule['id'],
                        'rule_name'   => $rule['rule_name'],
                        'schema_type' => $rule['schema_type'],
                    ];
                    break;
                }
            }
        }
        return $matched;
    }

    public function get_contexts( \WP_REST_Request $request ): \WP_REST_Response {
        return new \WP_REST_Response( ContextDetector::get_available_conditions(), 200 );
    }

    // =========================================================================
    // Schema Score
    // =========================================================================

    /**
     * Calculate an objective schema quality score (0–100).
     *
     * Factors:
     *   - Coverage (40%): what % of site contexts have schema rules/globals
     *   - Health (30%): what % of schemas pass validation
     *   - Depth (20%): avg property completeness vs recommended
     *   - Diversity (10%): variety of schema types used
     */
    public function get_score( \WP_REST_Request $request ): \WP_REST_Response {
        global $wpdb;

        // --- Coverage (40%) ---
        $r_table = esc_sql( $wpdb->prefix . 't1schema_rules' );
        $rules   = $wpdb->get_results( "SELECT conditions, schema_type FROM {$r_table} WHERE status = 'active'", ARRAY_A ) ?: []; // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared

        $contexts = [];
        $post_types = get_post_types( [ 'public' => true ], 'objects' );
        foreach ( $post_types as $pt ) {
            if ( $pt->name === 'attachment' ) continue;
            $contexts[] = [ 'type' => 'singular', 'value' => $pt->name ];
            if ( $pt->has_archive ) {
                $contexts[] = [ 'type' => 'archive', 'value' => $pt->name ];
            }
        }
        $contexts[] = [ 'type' => 'front_page', 'value' => '' ];
        $contexts[] = [ 'type' => 'search', 'value' => '' ];
        $contexts[] = [ 'type' => '404', 'value' => '' ];

        $total_contexts  = count( $contexts );
        $covered_contexts = 0;
        foreach ( $contexts as $ctx ) {
            foreach ( $rules as $rule ) {
                $conds = json_decode( $rule['conditions'], true ) ?: [];
                foreach ( $conds as $c ) {
                    if ( $c['type'] === $ctx['type'] && ( ! $ctx['value'] || $c['value'] === $ctx['value'] ) ) {
                        $covered_contexts++;
                        continue 3;
                    }
                }
            }
        }
        // Globals always cover singular pages.
        $g_table = esc_sql( $wpdb->prefix . 't1schema_globals' );
        $global_count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$g_table} WHERE status = 'active'" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        if ( $global_count > 0 ) {
            $covered_contexts = max( $covered_contexts, 1 ); // At least front page covered.
        }

        $coverage_pct = $total_contexts > 0 ? ( $covered_contexts / $total_contexts ) * 100 : 0;

        // --- Health (30%) ---
        $all_schemas = [];
        $globals = $wpdb->get_results( "SELECT schema_data FROM {$g_table} WHERE status = 'active'", ARRAY_A ) ?: []; // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        foreach ( $globals as $r ) {
            $all_schemas[] = json_decode( $r['schema_data'], true );
        }
        $rule_rows = $wpdb->get_results( "SELECT schema_data FROM {$r_table} WHERE status = 'active'", ARRAY_A ) ?: []; // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        foreach ( $rule_rows as $r ) {
            $all_schemas[] = json_decode( $r['schema_data'], true );
        }

        $valid_count = 0;
        $total_count = count( $all_schemas );
        $total_props = 0;
        $filled_props = 0;
        $types_used  = [];

        $registry = new SchemaRegistry();
        $type_defs = $registry->get_types();

        foreach ( $all_schemas as $schema ) {
            if ( ! is_array( $schema ) ) continue;
            $health = SchemaValidator::validate( $schema );
            if ( $health['valid'] ) $valid_count++;

            $raw_type = $schema['@type'] ?? '';
            $type_key = SchemaValidator::normalize_type_key( $raw_type );
            if ( $type_key ) $types_used[ $type_key ] = true;

            // Depth: count recommended props filled (use primary type for lookup).
            $primary = is_array( $raw_type ) ? ( $raw_type[0] ?? '' ) : $raw_type;
            if ( isset( $type_defs[ $primary ]['properties'] ) ) {
                $props = $type_defs[ $primary ]['properties'];
                foreach ( $props as $prop_name => $prop_def ) {
                    if ( $prop_def['required'] ?? false || $prop_def['recommended'] ?? false ) {
                        $total_props++;
                        if ( ! empty( $schema[ $prop_name ] ) ) {
                            $filled_props++;
                        }
                    }
                }
            }
        }

        $health_pct = $total_count > 0 ? ( $valid_count / $total_count ) * 100 : 0;

        // --- Depth (20%) ---
        $depth_pct = $total_props > 0 ? ( $filled_props / $total_props ) * 100 : 0;

        // --- Diversity (10%) ---
        $max_diversity = min( count( $type_defs ), 8 ); // Cap at 8 types for 100%.
        $diversity_pct = $max_diversity > 0 ? min( ( count( $types_used ) / $max_diversity ) * 100, 100 ) : 0;

        // Weighted score.
        $score = round(
            ( $coverage_pct * 0.40 ) +
            ( $health_pct   * 0.30 ) +
            ( $depth_pct    * 0.20 ) +
            ( $diversity_pct * 0.10 )
        );

        $grade = match ( true ) {
            $score >= 90 => 'A',
            $score >= 75 => 'B',
            $score >= 60 => 'C',
            $score >= 40 => 'D',
            default      => 'F',
        };

        return new \WP_REST_Response( [
            'score'     => $score,
            'grade'     => $grade,
            'breakdown' => [
                'coverage'  => [ 'score' => round( $coverage_pct ), 'weight' => 40, 'detail' => "{$covered_contexts}/{$total_contexts} contexts" ],
                'health'    => [ 'score' => round( $health_pct ),   'weight' => 30, 'detail' => "{$valid_count}/{$total_count} schemas valid" ],
                'depth'     => [ 'score' => round( $depth_pct ),    'weight' => 20, 'detail' => "{$filled_props}/{$total_props} properties" ],
                'diversity' => [ 'score' => round( $diversity_pct ), 'weight' => 10, 'detail' => count( $types_used ) . ' types used' ],
            ],
            'schemas_total' => $total_count,
            'types_used'    => array_keys( $types_used ),
        ], 200 );
    }

    // =========================================================================
    // Recommended Rules
    // =========================================================================

    public function get_recommended_rules( \WP_REST_Request $request ): \WP_REST_Response {
        global $wpdb;
        $r_table = esc_sql( $wpdb->prefix . 't1schema_rules' );
        $existing = $wpdb->get_results( "SELECT conditions, schema_type FROM {$r_table}", ARRAY_A ) ?: []; // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared

        $templates = $this->get_rule_templates();

        // Mark which are already active.
        foreach ( $templates as &$t ) {
            $t['active'] = false;
            foreach ( $existing as $e ) {
                $conds = json_decode( $e['conditions'], true ) ?: [];
                if ( $e['schema_type'] === $t['schema_type'] && $conds == $t['conditions'] ) {
                    $t['active'] = true;
                    break;
                }
            }
        }

        return new \WP_REST_Response( $templates, 200 );
    }

    public function activate_recommended_rule( \WP_REST_Request $request ): \WP_REST_Response {
        global $wpdb;
        $r_table = esc_sql( $wpdb->prefix . 't1schema_rules' );
        $key     = $request->get_param( 'key' );

        $templates = $this->get_rule_templates();
        $template  = null;
        foreach ( $templates as $t ) {
            if ( $t['key'] === $key ) {
                $template = $t;
                break;
            }
        }

        if ( ! $template ) {
            return new \WP_REST_Response( [ 'error' => 'Unknown template key' ], 404 );
        }

        $wpdb->insert( $r_table, [
            'rule_name'   => $template['name'],
            'schema_type' => $template['schema_type'],
            'schema_data' => wp_json_encode( $template['schema_data'] ),
            'conditions'  => wp_json_encode( $template['conditions'] ),
            'priority'    => $template['priority'] ?? 10,
            'status'      => 'active',
        ], [ '%s', '%s', '%s', '%s', '%d', '%s' ] );

        return new \WP_REST_Response( [ 'id' => $wpdb->insert_id, 'name' => $template['name'] ], 201 );
    }

    private function get_rule_templates(): array {
        return [
            [
                'key'         => 'article_posts',
                'name'        => 'Article → All Blog Posts',
                'description' => 'Adds Article schema with headline, date, author, and image to every blog post.',
                'schema_type' => 'Article',
                'conditions'  => [ [ 'type' => 'singular', 'value' => 'post' ] ],
                'schema_data' => [
                    '@context'      => 'https://schema.org',
                    '@type'         => 'Article',
                    'headline'      => '{{post_title}}',
                    'datePublished' => '{{post_date}}',
                    'dateModified'  => '{{post_modified}}',
                    'author'        => [ '@type' => 'Person', 'name' => '{{author_name}}' ],
                    'image'         => '{{featured_image_url}}',
                ],
                'priority' => 10,
            ],
            [
                'key'         => 'webpage_pages',
                'name'        => 'WebPage → All Pages',
                'description' => 'Adds WebPage schema with name and URL to every static page.',
                'schema_type' => 'WebPage',
                'conditions'  => [ [ 'type' => 'singular', 'value' => 'page' ] ],
                'schema_data' => [
                    '@context'    => 'https://schema.org',
                    '@type'       => 'WebPage',
                    'name'        => '{{post_title}}',
                    'url'         => '{{post_url}}',
                    'description' => '{{post_excerpt}}',
                ],
                'priority' => 10,
            ],
            [
                'key'         => 'breadcrumb_singulars',
                'name'        => 'BreadcrumbList → All Singulars',
                'description' => 'Adds BreadcrumbList schema to every post and page for enhanced SERP display.',
                'schema_type' => 'BreadcrumbList',
                'conditions'  => [ [ 'type' => 'singular', 'value' => 'post' ] ],
                'schema_data' => [
                    '@context'        => 'https://schema.org',
                    '@type'           => 'BreadcrumbList',
                    'itemListElement' => [
                        [ '@type' => 'ListItem', 'position' => 1, 'name' => '{{site_name}}', 'item' => '{{site_url}}' ],
                        [ '@type' => 'ListItem', 'position' => 2, 'name' => '{{post_title}}', 'item' => '{{post_url}}' ],
                    ],
                ],
                'priority' => 15,
            ],
            [
                'key'         => 'collection_archives',
                'name'        => 'CollectionPage → Post Archives',
                'description' => 'Adds CollectionPage schema to the blog archive and category pages.',
                'schema_type' => 'CollectionPage',
                'conditions'  => [ [ 'type' => 'archive', 'value' => 'post' ] ],
                'schema_data' => [
                    '@context' => 'https://schema.org',
                    '@type'    => 'CollectionPage',
                    'name'     => '{{archive_title}}',
                    'url'      => '{{archive_url}}',
                ],
                'priority' => 10,
            ],
            [
                'key'         => 'search_action',
                'name'        => 'SearchResultsPage → Search',
                'description' => 'Adds SearchResultsPage schema to the WordPress search results page.',
                'schema_type' => 'SearchResultsPage',
                'conditions'  => [ [ 'type' => 'search', 'value' => '' ] ],
                'schema_data' => [
                    '@context' => 'https://schema.org',
                    '@type'    => 'SearchResultsPage',
                    'name'     => 'Search: {{search_query}}',
                    'url'      => '{{current_url}}',
                ],
                'priority' => 10,
            ],
        ];
    }
}
