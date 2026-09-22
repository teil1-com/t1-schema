<?php

namespace T1Schema;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Detects the current WordPress page context.
 *
 * Returns a standardized context array that ConditionMatcher
 * can evaluate rules against.
 *
 * @package T1Schema
 * @since   1.2.0
 */
class ContextDetector {

    /**
     * Detect the current page context.
     *
     * @return array{type: string, subtype: string, post_type: string|null, taxonomy: string|null, term: string|null, author: string|null, post_id: int|null}
     */
    public static function detect(): array {
        $ctx = [
            'type'      => 'unknown',
            'subtype'   => '',
            'post_type' => null,
            'taxonomy'  => null,
            'term'      => null,
            'author'    => null,
            'post_id'   => null,
        ];

        if ( is_singular() ) {
            $ctx['type']      = 'singular';
            $ctx['post_type'] = get_post_type();
            $ctx['post_id']   = get_the_ID() ?: null;
            $ctx['subtype']   = $ctx['post_type'];
            return $ctx;
        }

        if ( is_front_page() ) {
            $ctx['type']    = 'front_page';
            $ctx['subtype'] = 'front_page';
            return $ctx;
        }

        if ( is_home() ) {
            $ctx['type']    = 'archive';
            $ctx['subtype'] = 'blog';
            return $ctx;
        }

        if ( is_post_type_archive() ) {
            $ctx['type']      = 'archive';
            $ctx['post_type'] = get_query_var( 'post_type' );
            if ( is_array( $ctx['post_type'] ) ) {
                $ctx['post_type'] = $ctx['post_type'][0] ?? '';
            }
            $ctx['subtype'] = $ctx['post_type'];
            return $ctx;
        }

        if ( is_category() || is_tag() || is_tax() ) {
            $ctx['type'] = 'taxonomy';
            $term        = get_queried_object();
            if ( $term instanceof \WP_Term ) {
                $ctx['taxonomy'] = $term->taxonomy;
                $ctx['term']     = $term->slug;
                $ctx['subtype']  = $term->taxonomy;
            }
            return $ctx;
        }

        if ( is_author() ) {
            $ctx['type'] = 'author';
            $author      = get_queried_object();
            if ( $author instanceof \WP_User ) {
                $ctx['author'] = $author->user_nicename;
                $ctx['subtype'] = $author->user_nicename;
            }
            return $ctx;
        }

        if ( is_search() ) {
            $ctx['type']    = 'search';
            $ctx['subtype'] = 'search';
            return $ctx;
        }

        if ( is_404() ) {
            $ctx['type']    = '404';
            $ctx['subtype'] = '404';
            return $ctx;
        }

        if ( is_date() ) {
            $ctx['type']    = 'date';
            $ctx['subtype'] = 'date';
            return $ctx;
        }

        if ( is_archive() ) {
            $ctx['type']    = 'archive';
            $ctx['subtype'] = 'archive';
            return $ctx;
        }

        return $ctx;
    }

    /**
     * Get all possible context conditions for the condition builder UI.
     *
     * @return array
     */
    public static function get_available_conditions(): array {
        $conditions = [];

        // Template-based.
        $conditions[] = [ 'type' => 'front_page', 'label' => _x( 'Front Page', 'condition label', 'teil1-schema-manager' ), 'group' => _x( 'Template', 'condition group', 'teil1-schema-manager' ) ];
        $conditions[] = [ 'type' => 'search',     'label' => _x( 'Search Results', 'condition label', 'teil1-schema-manager' ), 'group' => _x( 'Template', 'condition group', 'teil1-schema-manager' ) ];
        $conditions[] = [ 'type' => '404',         'label' => _x( '404 Page', 'condition label', 'teil1-schema-manager' ), 'group' => _x( 'Template', 'condition group', 'teil1-schema-manager' ) ];
        $conditions[] = [ 'type' => 'date',        'label' => _x( 'Date Archives', 'condition label', 'teil1-schema-manager' ), 'group' => _x( 'Template', 'condition group', 'teil1-schema-manager' ) ];
        $conditions[] = [ 'type' => 'author',      'label' => _x( 'All Author Archives', 'condition label', 'teil1-schema-manager' ), 'group' => _x( 'Template', 'condition group', 'teil1-schema-manager' ) ];
        $conditions[] = [ 'type' => 'blog',        'label' => _x( 'Blog Index', 'condition label', 'teil1-schema-manager' ), 'group' => _x( 'Template', 'condition group', 'teil1-schema-manager' ) ];

        // Singular post types.
        $post_types = get_post_types( [ 'public' => true ], 'objects' );
        foreach ( $post_types as $slug => $pt ) {
            if ( $slug === 'attachment' ) {
                continue;
            }
            $conditions[] = [
                'type'  => 'singular',
                'value' => $slug,
                'label' => sprintf(
                    /* translators: %s: localized post type label supplied by WordPress. */
                    _x( 'All %1$s (single)', 'condition label', 'teil1-schema-manager' ),
                    $pt->labels->name
                ),
                'group' => _x( 'Single Content', 'condition group', 'teil1-schema-manager' ),
            ];
        }

        // Post type archives.
        $archive_types = get_post_types( [ 'public' => true, 'has_archive' => true ], 'objects' );
        foreach ( $archive_types as $slug => $pt ) {
            $conditions[] = [
                'type'  => 'archive',
                'value' => $slug,
                'label' => sprintf(
                    /* translators: %s: localized post type label supplied by WordPress. */
                    _x( '%1$s Archive', 'condition label', 'teil1-schema-manager' ),
                    $pt->labels->name
                ),
                'group' => _x( 'Archives', 'condition group', 'teil1-schema-manager' ),
            ];
        }

        // Taxonomies.
        $taxonomies = get_taxonomies( [ 'public' => true ], 'objects' );
        foreach ( $taxonomies as $slug => $tax ) {
            $conditions[] = [
                'type'  => 'taxonomy',
                'value' => $slug,
                'label' => sprintf(
                    /* translators: %s: localized taxonomy label supplied by WordPress. */
                    _x( 'All %1$s Archives', 'condition label', 'teil1-schema-manager' ),
                    $tax->labels->name
                ),
                'group' => _x( 'Taxonomies', 'condition group', 'teil1-schema-manager' ),
            ];

            // Individual terms.
            $terms = get_terms( [
                'taxonomy'   => $slug,
                'hide_empty' => false,
                'number'     => 50,
            ] );

            if ( ! is_wp_error( $terms ) ) {
                foreach ( $terms as $term ) {
                    $conditions[] = [
                        'type'  => 'taxonomy_term',
                        'value' => "{$slug}:{$term->slug}",
                        'label' => sprintf(
                            /* translators: 1: localized taxonomy label supplied by WordPress; 2: taxonomy term name. */
                            _x( '%1$s: %2$s', 'condition label', 'teil1-schema-manager' ),
                            $tax->labels->singular_name,
                            $term->name
                        ),
                        'group' => _x( 'Taxonomies', 'condition group', 'teil1-schema-manager' ),
                    ];
                }
            }
        }

        // Authors.
        $authors = get_users( [ 'who' => 'authors', 'number' => 50 ] );
        foreach ( $authors as $author ) {
            $conditions[] = [
                'type'  => 'author_specific',
                'value' => $author->user_nicename,
                'label' => sprintf(
                    /* translators: %s: author display name. */
                    _x( 'Author: %1$s', 'condition label', 'teil1-schema-manager' ),
                    $author->display_name
                ),
                'group' => _x( 'Authors', 'condition group', 'teil1-schema-manager' ),
            ];
        }

        // Parent-page hierarchy (children of a specific page).
        $hierarchical_types = get_post_types( [ 'public' => true, 'hierarchical' => true ], 'names' );
        if ( ! empty( $hierarchical_types ) ) {
            $parent_pages = get_posts( [
                'post_type'      => array_values( $hierarchical_types ),
                'post_status'    => 'publish',
                'posts_per_page' => 100,
                'post_parent'    => 0, // top-level only
                'orderby'        => 'title',
                'order'          => 'ASC',
            ] );

            foreach ( $parent_pages as $page ) {
                $conditions[] = [
                    'type'  => 'child_of_page',
                    'value' => (string) $page->ID,
                    'label' => sprintf(
                        /* translators: %s: parent page title. */
                        _x( 'Children of: %1$s', 'condition label', 'teil1-schema-manager' ),
                        $page->post_title
                    ),
                    'group' => _x( 'Page Hierarchy', 'condition group', 'teil1-schema-manager' ),
                ];

                // Also add second-level parents (e.g. /services/seo/ children).
                $children = get_posts( [
                    'post_type'      => $page->post_type,
                    'post_status'    => 'publish',
                    'post_parent'    => $page->ID,
                    'posts_per_page' => 50,
                    'orderby'        => 'title',
                    'order'          => 'ASC',
                ] );

                foreach ( $children as $child ) {
                    // Only add if this child itself has children.
                    $grandchild_count = (int) get_children( [
                        'post_parent' => $child->ID,
                        'post_type'   => $child->post_type,
                        'numberposts' => 1,
                    ] );
                    if ( $grandchild_count > 0 ) {
                        $conditions[] = [
                            'type'  => 'child_of_page',
                            'value' => (string) $child->ID,
                            'label' => sprintf(
                                /* translators: 1: top-level parent page title; 2: child page title. */
                                _x( 'Children of: %1$s → %2$s', 'condition label', 'teil1-schema-manager' ),
                                $page->post_title,
                                $child->post_title
                            ),
                            'group' => _x( 'Page Hierarchy', 'condition group', 'teil1-schema-manager' ),
                        ];
                    }
                }
            }
        }

        return $conditions;
    }
}
