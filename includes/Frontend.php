<?php

namespace T1Schema;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Frontend JSON-LD output handler.
 *
 * Assembles schemas from three layers:
 *   1. Site-wide globals (no conditions)
 *   2. Conditional rules (matched against current context)
 *   3. Local overrides (per-post via post_meta)
 *
 * Local > Rule > Global priority per @type.
 *
 * @package T1Schema
 * @since   1.0.0 (rewritten 1.2.0)
 */
class Frontend {

    /**
     * Per-request cache for assemble_schemas().
     *
     * Also called by WooCommerceCompat (on the `wp` hook) to decide which of
     * WooCommerce's own structured data types would collide with ours, ahead
     * of the actual render on `wp_head`. Query state does not change within
     * a request, so it is safe to compute once and reuse.
     */
    private ?array $assembled_cache = null;

    public function init(): void {
        add_action( 'wp_head', [ $this, 'render_jsonld' ], 1 );
    }

    public function render_jsonld(): void {
        try {
            $schemas = $this->assemble_schemas();
            if ( empty( $schemas ) ) {
                return;
            }

            // Resolve variables BEFORE dedup so @id values like {{post_url}}#term
            // can be matched against literal URLs from local schemas.
            $schemas = $this->strip_internal_meta( $schemas );
            $post_id = $this->get_current_post_id();

            // Must run before variable resolution — it keys off the literal
            // {{product_price}} tag to upgrade a variable product's Offer
            // into an AggregateOffer price range.
            $schemas = WooCommerceOffers::expand( $schemas, $post_id );

            $schemas = VariableResolver::resolve( $schemas, $post_id );

            // Deduplicate nodes with the same @id (e.g. Rule + Local for same entity).
            $schemas = $this->merge_by_id( $schemas );

            $schemas = $this->remove_empty_values( $schemas );

            if ( empty( $schemas ) ) {
                return;
            }

            $output = $this->build_output( $schemas );
            $output = apply_filters( 't1schema_jsonld_output', $output, $schemas );

            if ( ! empty( $output ) ) {
                echo $output . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            }
        } catch ( \Throwable $e ) {
            // Never crash the frontend. Log and continue.
            if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                error_log( '[t1 Schema] render_jsonld error: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine() ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
                echo '<!-- t1 Schema error: ' . esc_html( $e->getMessage() ) . ' -->' . "\n";
            }
        }
    }

    /**
     * Assemble schemas from all three layers with priority resolution.
     *
     * Priority: Local Override > Schema Rule > Site-Wide Global.
     * Within the same layer, later items override earlier ones per @type.
     */
    public function assemble_schemas(): array {
        if ( $this->assembled_cache !== null ) {
            return $this->assembled_cache;
        }

        // Layer 1: Site-wide globals.
        $globals = $this->get_global_schemas();

        // Layer 2: Conditional rules.
        $context = ContextDetector::detect();
        $rules   = $this->get_matching_rules( $context );

        // Layer 3: Local overrides (per-post).
        $locals = $this->get_local_schemas();

        // Build type index: track which types are overridden by higher layers.
        // Uses normalize_type_key to handle both string and array @type values.
        $local_types = [];
        foreach ( $locals as $schema ) {
            $type = $schema['@type'] ?? '';
            if ( $type && ( $schema['_t1schema_meta']['override_global'] ?? true ) ) {
                $local_types[ SchemaValidator::normalize_type_key( $type ) ] = true;
            }
        }

        $rule_types = [];
        foreach ( $rules as $schema ) {
            $type = $schema['@type'] ?? '';
            if ( $type ) {
                $rule_types[ SchemaValidator::normalize_type_key( $type ) ] = true;
            }
        }

        // Filter globals: remove types overridden by rules or locals.
        $filtered_globals = array_filter( $globals, function ( array $s ) use ( $local_types, $rule_types ): bool {
            $key = SchemaValidator::normalize_type_key( $s['@type'] ?? '' );
            return ! isset( $local_types[ $key ] ) && ! isset( $rule_types[ $key ] );
        } );

        // Filter rules: remove types overridden by locals.
        $filtered_rules = array_filter( $rules, function ( array $s ) use ( $local_types ): bool {
            $key = SchemaValidator::normalize_type_key( $s['@type'] ?? '' );
            return ! isset( $local_types[ $key ] );
        } );

        $schemas = array_merge(
            array_values( $filtered_globals ),
            array_values( $filtered_rules ),
            $locals
        );

        // Auto-generate BreadcrumbList from page hierarchy (if not already present).
        if ( apply_filters( 't1schema_auto_breadcrumbs', true ) ) {
            $has_breadcrumbs = false;
            foreach ( $schemas as $s ) {
                if ( ( $s['@type'] ?? '' ) === 'BreadcrumbList' ) {
                    $has_breadcrumbs = true;
                    break;
                }
            }
            if ( ! $has_breadcrumbs ) {
                $breadcrumbs = $this->build_breadcrumbs();
                if ( $breadcrumbs ) {
                    $schemas[] = $breadcrumbs;
                }
            }
        }

        $this->assembled_cache = $schemas;
        return $schemas;
    }

    /**
     * Merge nodes that share the same @id.
     *
     * Later nodes (locals) overwrite properties from earlier nodes (rules/globals).
     * Must be called AFTER variable resolution so {{post_url}} is already expanded.
     */
    private function merge_by_id( array $schemas ): array {
        $by_id = [];
        $no_id = [];

        foreach ( $schemas as $node ) {
            $id = $node['@id'] ?? null;
            if ( $id ) {
                if ( isset( $by_id[ $id ] ) ) {
                    $by_id[ $id ] = array_merge( $by_id[ $id ], $node );
                } else {
                    $by_id[ $id ] = $node;
                }
            } else {
                $no_id[] = $node;
            }
        }

        return array_merge( array_values( $by_id ), $no_id );
    }

    /**
     * Get global schemas (rows without conditions).
     */
    private function get_global_schemas(): array {
        global $wpdb;
        $table = esc_sql( $wpdb->prefix . 't1schema_globals' );

        if ( ! $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) ) {
            return [];
        }

        $rows = $wpdb->get_results(
            "SELECT schema_type, schema_data FROM {$table} WHERE status = 'active'", ARRAY_A
        ); // phpcs:ignore

        $schemas = [];
        foreach ( (array) $rows as $row ) {
            $d = json_decode( $row['schema_data'], true );
            if ( is_array( $d ) ) {
                // Safety: ensure @type is always present from DB column.
                if ( empty( $d['@type'] ) && ! empty( $row['schema_type'] ) ) {
                    $d['@type'] = $row['schema_type'];
                }
                $schemas[] = $d;
            }
        }
        return $schemas;
    }

    /**
     * Get rules that match the current page context.
     *
     * Rules are loaded from the t1schema_rules table, sorted by priority,
     * and filtered through ConditionMatcher.
     */
    private function get_matching_rules( array $context ): array {
        global $wpdb;
        $table = esc_sql( $wpdb->prefix . 't1schema_rules' );

        if ( ! $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) ) {
            return [];
        }

        $rows = $wpdb->get_results(
            "SELECT schema_data, conditions FROM {$table} WHERE status = 'active' ORDER BY priority ASC",
            ARRAY_A
        ); // phpcs:ignore

        $matched = [];
        foreach ( (array) $rows as $row ) {
            $conditions = json_decode( $row['conditions'], true );
            if ( ! is_array( $conditions ) ) {
                continue;
            }

            if ( ConditionMatcher::matches( $conditions, $context ) ) {
                $data = json_decode( $row['schema_data'], true );
                if ( is_array( $data ) ) {
                    $matched[] = $data;
                }
            }
        }

        return $matched;
    }

    /**
     * Get local schemas from post_meta (only on singular pages).
     */
    private function get_local_schemas(): array {
        $post_id = $this->get_current_post_id();
        if ( ! $post_id ) {
            return [];
        }

        $raw     = get_post_meta( $post_id, '_t1schema_local', true );
        $decoded = is_string( $raw ) ? json_decode( $raw, true ) : $raw;

        if ( ! is_array( $decoded ) ) {
            return [];
        }

        return array_filter( $decoded, fn( array $s ) => ( $s['_t1schema_meta']['status'] ?? 'active' ) === 'active' );
    }

    /**
     * Auto-generate BreadcrumbList schema from WordPress page hierarchy.
     *
     * Fires on all singular pages. Builds the breadcrumb trail from
     * Home → ancestors → current page using get_post_ancestors().
     *
     * @since 1.4.9
     * @return array|null BreadcrumbList schema or null if not applicable.
     */
    private function build_breadcrumbs(): ?array {
        if ( ! is_singular() ) {
            return null;
        }

        $post = get_queried_object();
        if ( ! $post instanceof \WP_Post ) {
            return null;
        }

        $items    = [];
        $position = 1;

        // Home (always first).
        $items[] = [
            '@type'    => 'ListItem',
            'position' => $position++,
            'name'     => 'Home',
            'item'     => home_url( '/' ),
        ];

        if ( is_post_type_hierarchical( $post->post_type ) ) {
            // Hierarchical: use page ancestors (e.g. Services → Marketing → SEO).
            $ancestors = array_reverse( get_post_ancestors( $post->ID ) );
            if ( empty( $ancestors ) ) {
                return null; // Top-level page — no meaningful breadcrumb trail.
            }

            foreach ( $ancestors as $ancestor_id ) {
                $items[] = [
                    '@type'    => 'ListItem',
                    'position' => $position++,
                    'name'     => get_the_title( $ancestor_id ),
                    'item'     => get_permalink( $ancestor_id ),
                ];
            }
        } else {
            // Non-hierarchical CPT: use archive page as middle step (e.g. Blog → Post).
            $post_type_obj = get_post_type_object( $post->post_type );
            $archive_url   = get_post_type_archive_link( $post->post_type );

            if ( ! $archive_url ) {
                return null; // No archive — no meaningful breadcrumb trail.
            }

            $archive_label = $post_type_obj->labels->name ?? $post_type_obj->label ?? '';

            $items[] = [
                '@type'    => 'ListItem',
                'position' => $position++,
                'name'     => $archive_label,
                'item'     => $archive_url,
            ];
        }

        // Current page (last item omits 'item' per Google best practice).
        $items[] = [
            '@type'    => 'ListItem',
            'position' => $position,
            'name'     => get_the_title( $post ),
        ];

        return [
            '@type'           => 'BreadcrumbList',
            'itemListElement' => $items,
        ];
    }

    private function get_current_post_id(): ?int {
        return is_singular() ? ( get_the_ID() ?: null ) : null;
    }

    private function strip_internal_meta( array $schemas ): array {
        return array_map( function ( array $s ) {
            unset( $s['_t1schema_meta'] );
            return $s;
        }, $schemas );
    }

    private function remove_empty_values( array $data ): array {
        $out = [];
        foreach ( $data as $k => $v ) {
            if ( is_array( $v ) ) {
                $v = $this->remove_empty_values( $v );
                if ( ! empty( $v ) ) {
                    $out[ $k ] = $v;
                }
            } elseif ( $v !== '' && $v !== null ) {
                $out[ $k ] = $v;
            }
        }
        return $out;
    }

    private function build_output( array $schemas ): string {
        if ( count( $schemas ) === 1 ) {
            $json_data = reset( $schemas );
        } else {
            $items = array_map( function ( array $s ) {
                unset( $s['@context'] );
                return $s;
            }, $schemas );

            $json_data = [
                '@context' => 'https://schema.org',
                '@graph'   => array_values( $items ),
            ];
        }

        $json = wp_json_encode( $json_data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG );

        if ( ! $json ) {
            return '';
        }

        /**
         * Filters the complete JSON-LD output HTML.
         *
         * @since 1.0.0
         * @param string $html    The full <script> tag.
         * @param array  $schemas Array of schema data objects.
         */
        $html = '<script type="application/ld+json" id="t1schema-jsonld">' . $json . '</script>';
        return apply_filters( 't1schema_jsonld_output', $html, $items ?? [ $json_data ] );
    }
}
