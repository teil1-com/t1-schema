<?php

namespace T1Schema;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Resolves dynamic variable tags in schema data.
 *
 * Converts {{post_title}}, {{site_url}} etc. into actual values
 * at render time for the current page context.
 *
 * @package T1Schema
 * @since   1.0.0
 */
class VariableResolver {

    /**
     * Available variable definitions grouped by category.
     *
     * @return array<string, array<string, string>> Category => [tag => description].
     */
    public static function get_available_variables(): array {
        return [
            'post' => [
                'post_title'          => __( 'Post/Page title', 'teil1-schema-manager' ),
                'post_excerpt'        => __( 'Post excerpt', 'teil1-schema-manager' ),
                'post_content'        => __( 'Full post content (plain text)', 'teil1-schema-manager' ),
                'post_date'           => sprintf(
                    /* translators: %s: ISO date standard version. */
                    __( 'Publish date (%1$s)', 'teil1-schema-manager' ),
                    'ISO 8601'
                ),
                'post_modified'       => sprintf(
                    /* translators: %s: ISO date standard version. */
                    __( 'Last modified date (%1$s)', 'teil1-schema-manager' ),
                    'ISO 8601'
                ),
                'post_url'            => __( 'Post permalink', 'teil1-schema-manager' ),
                'post_id'             => _x( 'Post ID', 'variable description', 'teil1-schema-manager' ),
                'post_slug'           => __( 'Post slug', 'teil1-schema-manager' ),
                'post_type'           => _x( 'Post type', 'variable description', 'teil1-schema-manager' ),
                'featured_image_url'  => __( 'Featured image URL (full size)', 'teil1-schema-manager' ),
                'featured_image_alt'  => __( 'Featured image alt text', 'teil1-schema-manager' ),
            ],
            'author' => [
                'author_name'         => __( 'Author display name', 'teil1-schema-manager' ),
                'author_url'          => __( 'Author posts URL', 'teil1-schema-manager' ),
                'author_description'  => __( 'Author bio/description', 'teil1-schema-manager' ),
                'author_avatar_url'   => __( 'Author avatar URL', 'teil1-schema-manager' ),
            ],
            'site' => [
                'site_name'           => __( 'Site title', 'teil1-schema-manager' ),
                'site_url'            => __( 'Site home URL', 'teil1-schema-manager' ),
                'site_description'    => __( 'Site tagline', 'teil1-schema-manager' ),
                'site_logo'           => __( 'Custom logo URL', 'teil1-schema-manager' ),
                'site_language'       => __( 'Site language code', 'teil1-schema-manager' ),
            ],
            'taxonomy' => [
                'primary_category'       => __( 'Primary category name', 'teil1-schema-manager' ),
                'primary_category_url'   => __( 'Primary category URL', 'teil1-schema-manager' ),
                'categories'             => __( 'Comma-separated category names', 'teil1-schema-manager' ),
                'tags'                   => __( 'Comma-separated tag names', 'teil1-schema-manager' ),
            ],
            'archive' => [
                'term_name'              => __( 'Current taxonomy term name (on archive)', 'teil1-schema-manager' ),
                'term_description'       => __( 'Current taxonomy term description', 'teil1-schema-manager' ),
                'term_url'               => __( 'Current taxonomy term URL', 'teil1-schema-manager' ),
                'archive_title'          => __( 'Archive page title', 'teil1-schema-manager' ),
                'archive_url'            => __( 'Current archive URL', 'teil1-schema-manager' ),
                'search_query'           => __( 'Current search query', 'teil1-schema-manager' ),
            ],
            'meta' => [
                'meta:{key}'          => sprintf(
                    /* translators: %s: literal {key} placeholder used in variable names. */
                    __( 'Custom post meta value (replace %1$s with meta key)', 'teil1-schema-manager' ),
                    '{key}'
                ),
            ],
            'woocommerce' => self::get_woocommerce_catalog(),
            'custom'      => self::get_custom_variable_catalog(),
        ];
    }

    /**
     * WooCommerce variable catalog. Empty when WooCommerce is not active, so
     * the Variable Picker and Help reference do not advertise tags that
     * would always resolve to an empty string.
     */
    private static function get_woocommerce_catalog(): array {
        if ( ! class_exists( '\WooCommerce' ) ) {
            return [];
        }

        return [
            'product_price'         => sprintf(
                /* translators: 1: Schema.org Offer.price property; 2: Schema.org AggregateOffer type. */
                __( 'Current price (sale price if on sale, else regular price). In an %1$s, auto-upgrades to an %2$s range for variable products', 'teil1-schema-manager' ),
                'Offer.price',
                'AggregateOffer'
            ),
            'product_regular_price' => __( 'Regular (non-sale) price', 'teil1-schema-manager' ),
            'product_sale_price'    => __( 'Sale price, or empty if not on sale', 'teil1-schema-manager' ),
            'product_currency'      => sprintf(
                /* translators: %s: example ISO currency code. */
                __( 'Store currency code (e.g. %1$s)', 'teil1-schema-manager' ),
                'EUR'
            ),
            'product_sku'           => __( 'Product SKU', 'teil1-schema-manager' ),
            'product_availability'  => sprintf(
                /* translators: %s: example Schema.org availability URL. */
                __( 'Stock status as a schema.org URL (e.g. %1$s)', 'teil1-schema-manager' ),
                'https://schema.org/InStock'
            ),
            'product_rating'        => __( 'Average rating, or empty with no reviews', 'teil1-schema-manager' ),
            'product_review_count'  => __( 'Number of approved reviews', 'teil1-schema-manager' ),
            'product_brand'         => __( 'First term from the Product Brand taxonomy, if set', 'teil1-schema-manager' ),
        ];
    }

    /**
     * Get user-defined custom variables for the catalog.
     */
    private static function get_custom_variable_catalog(): array {
        $vars = get_option( 't1schema_custom_variables', [] );
        if ( ! is_array( $vars ) || empty( $vars ) ) {
            return [
                'custom.{key}' => __( 'User-defined site constant (create in Dashboard → Settings)', 'teil1-schema-manager' ),
            ];
        }

        $catalog = [];
        foreach ( $vars as $key => $value ) {
            $preview = mb_strlen( $value ) > 30 ? mb_substr( $value, 0, 30 ) . '…' : $value;
            $catalog[ "custom.{$key}" ] = sprintf(
                /* translators: %s: preview of a user-defined site constant. */
                __( 'Site constant: %1$s', 'teil1-schema-manager' ),
                $preview
            );
        }
        return $catalog;
    }

    /**
     * Resolve all {{variable}} tags in a schema data string or array.
     *
     * @param mixed    $data    Schema data (string, array, or nested).
     * @param int|null $post_id Post ID context. Null for global schemas.
     * @return mixed   Resolved data with variables replaced.
     */
    public static function resolve( mixed $data, ?int $post_id = null ): mixed {
        if ( is_string( $data ) ) {
            return self::resolve_string( $data, $post_id );
        }

        if ( is_array( $data ) ) {
            $resolved = [];
            foreach ( $data as $key => $value ) {
                $resolved[ $key ] = self::resolve( $value, $post_id );
            }
            return $resolved;
        }

        return $data;
    }

    /**
     * Resolve variable tags in a single string.
     *
     * @param string   $text    String potentially containing {{tags}}.
     * @param int|null $post_id Post ID context.
     * @return string  Resolved string.
     */
    private static function resolve_string( string $text, ?int $post_id ): string {
        return preg_replace_callback(
            '/\{\{([a-z0-9_:.]+(?:\{[^}]*\})?)\}\}/',
            function ( array $matches ) use ( $post_id ) {
                return self::get_variable_value( $matches[1], $post_id );
            },
            $text
        ) ?? $text;
    }

    /**
     * Get the value for a specific variable tag.
     *
     * @param string   $tag     Variable tag name (without braces).
     * @param int|null $post_id Post ID context.
     * @return string  Resolved value or empty string.
     */
    private static function get_variable_value( string $tag, ?int $post_id ): string {
        $post = $post_id ? get_post( $post_id ) : null;

        // Handle meta:{key} pattern.
        if ( str_starts_with( $tag, 'meta:' ) ) {
            $meta_key = substr( $tag, 5 );
            if ( $post_id && $meta_key ) {
                return (string) get_post_meta( $post_id, $meta_key, true );
            }
            return '';
        }

        // Handle custom.{key} pattern — user-defined site constants.
        if ( str_starts_with( $tag, 'custom.' ) ) {
            $var_key = substr( $tag, 7 );
            $custom  = get_option( 't1schema_custom_variables', [] );
            return (string) ( $custom[ $var_key ] ?? '' );
        }

        $value = match ( $tag ) {
            // Post variables.
            'post_title'         => $post ? get_the_title( $post ) : '',
            'post_excerpt'       => $post ? wp_strip_all_tags( get_the_excerpt( $post ) ) : '',
            'post_content'       => $post ? wp_strip_all_tags( $post->post_content ) : '',
            'post_date'          => $post ? get_the_date( 'c', $post ) : '',
            'post_modified'      => $post ? get_the_modified_date( 'c', $post ) : '',
            'post_url'           => $post ? get_permalink( $post ) : '',
            'post_id'            => $post ? (string) $post->ID : '',
            'post_slug'          => $post ? $post->post_name : '',
            'post_type'          => $post ? $post->post_type : '',
            'featured_image_url' => $post_id ? (string) get_the_post_thumbnail_url( $post_id, 'full' ) : '',
            'featured_image_alt' => self::get_featured_image_alt( $post_id ),

            // Author variables (filterable via t1schema_author_* hooks).
            'author_name'        => $post ? (string) apply_filters( 't1schema_author_name', get_the_author_meta( 'display_name', $post->post_author ), $post ) : '',
            'author_url'         => $post ? (string) apply_filters( 't1schema_author_url', get_author_posts_url( $post->post_author ), $post ) : '',
            'author_description' => $post ? get_the_author_meta( 'description', $post->post_author ) : '',
            'author_avatar_url'  => $post ? (string) apply_filters( 't1schema_author_avatar_url', get_avatar_url( $post->post_author, [ 'size' => 96 ] ), $post ) : '',

            // Site variables.
            'site_name'          => get_bloginfo( 'name' ),
            'site_url'           => home_url( '/' ),
            'site_description'   => get_bloginfo( 'description' ),
            'site_logo'          => self::get_site_logo_url(),
            'site_language'      => get_bloginfo( 'language' ),

            // Taxonomy variables.
            'primary_category'     => self::get_primary_category_name( $post_id ),
            'primary_category_url' => self::get_primary_category_url( $post_id ),
            'categories'           => $post_id ? self::get_term_names( $post_id, 'category' ) : '',
            'tags'                 => $post_id ? self::get_term_names( $post_id, 'post_tag' ) : '',

            // Archive / context variables.
            'term_name'            => self::get_current_term_name(),
            'term_description'     => self::get_current_term_description(),
            'term_url'             => self::get_current_term_url(),
            'archive_title'        => self::get_archive_title(),
            'archive_url'          => self::get_archive_url(),
            'search_query'         => get_search_query(),

            // WooCommerce variables (empty when WooCommerce is inactive or
            // the current post is not a product).
            'product_price'         => self::get_wc_product_field( $post_id, 'price' ),
            'product_regular_price' => self::get_wc_product_field( $post_id, 'regular_price' ),
            'product_sale_price'    => self::get_wc_product_field( $post_id, 'sale_price' ),
            'product_currency'      => self::get_wc_product_field( $post_id, 'currency' ),
            'product_sku'           => self::get_wc_product_field( $post_id, 'sku' ),
            'product_availability'  => self::get_wc_product_field( $post_id, 'availability' ),
            'product_rating'        => self::get_wc_product_field( $post_id, 'rating' ),
            'product_review_count'  => self::get_wc_product_field( $post_id, 'review_count' ),
            'product_brand'         => self::get_wc_product_field( $post_id, 'brand' ),

            default => '',
        };

        /**
         * Filter the resolved value of a dynamic variable.
         *
         * @since 1.0.0
         *
         * @param string   $value   The resolved value.
         * @param string   $tag     The variable tag name.
         * @param int|null $post_id The post ID context.
         */
        return (string) apply_filters( 't1schema_resolve_variable', $value, $tag, $post_id );
    }

    /**
     * Resolve a single WooCommerce product field.
     *
     * Returns '' whenever WooCommerce is inactive, there is no post context,
     * or the post is not a WC_Product — the same "empty when unavailable"
     * convention every other variable in this resolver follows.
     *
     * @param int|null $post_id Post ID context.
     * @param string   $field   One of: price, regular_price, sale_price,
     *                          currency, sku, availability, rating,
     *                          review_count, brand.
     */
    private static function get_wc_product_field( ?int $post_id, string $field ): string {
        if ( ! $post_id || ! function_exists( 'wc_get_product' ) ) {
            return '';
        }

        $product = wc_get_product( $post_id );
        if ( ! is_a( $product, 'WC_Product' ) ) {
            return '';
        }

        switch ( $field ) {
            case 'price':
                return (string) $product->get_price();

            case 'regular_price':
                return (string) $product->get_regular_price();

            case 'sale_price':
                return $product->is_on_sale() ? (string) $product->get_sale_price() : '';

            case 'currency':
                return function_exists( 'get_woocommerce_currency' ) ? get_woocommerce_currency() : '';

            case 'sku':
                return (string) $product->get_sku();

            case 'availability':
                if ( ! $product->is_in_stock() ) {
                    return 'https://schema.org/OutOfStock';
                }
                return 'onbackorder' === $product->get_stock_status()
                    ? 'https://schema.org/BackOrder'
                    : 'https://schema.org/InStock';

            case 'rating':
                // Mirrors WooCommerce's own gate so a t1 Schema AggregateRating
                // agrees with what WooCommerce itself would consider valid.
                if ( ! $product->get_rating_count() || ! self::wc_review_ratings_enabled() ) {
                    return '';
                }
                return (string) $product->get_average_rating();

            case 'review_count':
                if ( ! $product->get_rating_count() || ! self::wc_review_ratings_enabled() ) {
                    return '';
                }
                return (string) $product->get_review_count();

            case 'brand':
                if ( ! taxonomy_exists( 'product_brand' ) ) {
                    return '';
                }
                $terms = get_the_terms( $post_id, 'product_brand' );
                return ( ! is_wp_error( $terms ) && ! empty( $terms ) ) ? $terms[0]->name : '';

            default:
                return '';
        }
    }

    /**
     * Wrapper around wc_review_ratings_enabled() for a single null-safety
     * check point, since it is only defined once WooCommerce has loaded.
     */
    private static function wc_review_ratings_enabled(): bool {
        return function_exists( 'wc_review_ratings_enabled' ) && wc_review_ratings_enabled();
    }

    /**
     * Get the featured image alt text.
     *
     * @param int|null $post_id Post ID.
     * @return string  Alt text or empty string.
     */
    private static function get_featured_image_alt( ?int $post_id ): string {
        if ( ! $post_id ) {
            return '';
        }

        $thumbnail_id = get_post_thumbnail_id( $post_id );
        if ( ! $thumbnail_id ) {
            return '';
        }

        return (string) get_post_meta( $thumbnail_id, '_wp_attachment_image_alt', true );
    }

    /**
     * Get the site custom logo URL.
     *
     * @return string Logo URL or empty string.
     */
    private static function get_site_logo_url(): string {
        $custom_logo_id = get_theme_mod( 'custom_logo' );
        if ( ! $custom_logo_id ) {
            return '';
        }

        $image = wp_get_attachment_image_url( $custom_logo_id, 'full' );
        return $image ? $image : '';
    }

    /**
     * Get the primary category name for a post.
     *
     * @param int|null $post_id Post ID.
     * @return string  Category name or empty string.
     */
    private static function get_primary_category_name( ?int $post_id ): string {
        if ( ! $post_id ) {
            return '';
        }

        $categories = get_the_category( $post_id );
        return ! empty( $categories ) ? $categories[0]->name : '';
    }

    /**
     * Get the primary category URL for a post.
     *
     * @param int|null $post_id Post ID.
     * @return string  Category URL or empty string.
     */
    private static function get_primary_category_url( ?int $post_id ): string {
        if ( ! $post_id ) {
            return '';
        }

        $categories = get_the_category( $post_id );
        if ( empty( $categories ) ) {
            return '';
        }

        $link = get_category_link( $categories[0]->term_id );
        return $link ? $link : '';
    }

    /**
     * Get comma-separated term names for a taxonomy.
     *
     * @param int    $post_id  Post ID.
     * @param string $taxonomy Taxonomy slug.
     * @return string Comma-separated names.
     */
    private static function get_term_names( int $post_id, string $taxonomy ): string {
        $terms = get_the_terms( $post_id, $taxonomy );
        if ( is_wp_error( $terms ) || empty( $terms ) ) {
            return '';
        }

        return implode( ', ', wp_list_pluck( $terms, 'name' ) );
    }

    /**
     * Get the current taxonomy term name (on archive pages).
     */
    private static function get_current_term_name(): string {
        $obj = get_queried_object();
        return ( $obj instanceof \WP_Term ) ? $obj->name : '';
    }

    /**
     * Get the current taxonomy term description.
     */
    private static function get_current_term_description(): string {
        $obj = get_queried_object();
        return ( $obj instanceof \WP_Term ) ? $obj->description : '';
    }

    /**
     * Get the current taxonomy term URL.
     */
    private static function get_current_term_url(): string {
        $obj = get_queried_object();
        if ( ! ( $obj instanceof \WP_Term ) ) {
            return '';
        }
        $link = get_term_link( $obj );
        return is_wp_error( $link ) ? '' : $link;
    }

    /**
     * Get the archive page title.
     */
    private static function get_archive_title(): string {
        if ( is_category() || is_tag() || is_tax() ) {
            $obj = get_queried_object();
            return ( $obj instanceof \WP_Term ) ? $obj->name : '';
        }
        if ( is_post_type_archive() ) {
            return post_type_archive_title( '', false ) ?: '';
        }
        if ( is_author() ) {
            $obj = get_queried_object();
            return ( $obj instanceof \WP_User ) ? $obj->display_name : '';
        }
        return '';
    }

    /**
     * Get the current archive URL.
     */
    private static function get_archive_url(): string {
        if ( is_category() || is_tag() || is_tax() ) {
            return self::get_current_term_url();
        }
        if ( is_post_type_archive() ) {
            $pt = get_query_var( 'post_type' );
            if ( is_array( $pt ) ) {
                $pt = $pt[0] ?? '';
            }
            $link = get_post_type_archive_link( $pt );
            return $link ?: '';
        }
        if ( is_author() ) {
            $obj = get_queried_object();
            return ( $obj instanceof \WP_User ) ? get_author_posts_url( $obj->ID ) : '';
        }
        $request_uri = isset( $_SERVER['REQUEST_URI'] )
            ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) )
            : '/';

        return home_url( $request_uri );
    }
}
