<?php

namespace T1Schema;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Prevents duplicate JSON-LD when WooCommerce is active.
 *
 * WooCommerce ships its own structured data (Product, Review, BreadcrumbList,
 * WebSite) via WC_Structured_Data, on by default with no setting to turn it
 * off. If a site also configures a t1 Schema rule or local override for one
 * of those same types, the page ends up with two separate <script
 * type="application/ld+json"> blocks describing the same thing — one from
 * WooCommerce in wp_footer, one from t1 Schema in wp_head.
 *
 * This only silences the WooCommerce types that t1 Schema is actually about
 * to emit on the current request. Types t1 Schema does not cover (e.g. Order,
 * which has no t1 Schema equivalent) are left untouched.
 *
 * @package T1Schema
 * @since   2.1.0
 */
class WooCommerceCompat {

    /**
     * Schema.org type => WooCommerce filter that can zero out that type's markup.
     *
     * @see \WC_Structured_Data
     */
    private const WC_TYPE_FILTERS = [
        'Product'        => 'woocommerce_structured_data_product',
        'Review'         => 'woocommerce_structured_data_review',
        'BreadcrumbList' => 'woocommerce_structured_data_breadcrumblist',
        'WebSite'        => 'woocommerce_structured_data_website',
    ];

    private Frontend $frontend;

    /**
     * @param Frontend $frontend Shared instance, so the overlap check reuses
     *                           the same assemble_schemas() call the actual
     *                           render will make, rather than querying twice.
     */
    public function __construct( Frontend $frontend ) {
        $this->frontend = $frontend;
    }

    public function init(): void {
        if ( ! class_exists( '\WooCommerce' ) ) {
            return;
        }

        // Runs after the main query resolves (so ContextDetector's conditional
        // tags work) but well before WooCommerce's own generators fire during
        // template rendering, and before Frontend::render_jsonld() on wp_head.
        add_action( 'wp', [ $this, 'maybe_suppress' ] );
    }

    /**
     * Add a `__return_empty_array` filter for each WooCommerce structured
     * data type that t1 Schema is also about to render on this request.
     */
    public function maybe_suppress(): void {
        if ( is_admin() || ! $this->is_enabled() ) {
            return;
        }

        $t1_types = $this->get_t1_schema_types();

        foreach ( self::WC_TYPE_FILTERS as $type => $filter ) {
            if ( isset( $t1_types[ $type ] ) ) {
                add_filter( $filter, '__return_empty_array' );
            }
        }
    }

    /**
     * Whether suppression is turned on.
     *
     * Reuses the same site-wide setting as the mu-plugin conflict handling
     * (Help → Settings → "Suppress conflicting schema output"), since both
     * solve the same problem: another source's schema duplicating ours.
     * Filterable independently for sites that want to decouple the two.
     */
    private function is_enabled(): bool {
        return (bool) apply_filters(
            't1schema_suppress_woocommerce_conflicts',
            (bool) get_option( 't1schema_suppress_conflicts', false )
        );
    }

    /**
     * Flatten the @type values t1 Schema will render on this request into a
     * lookup set, e.g. ['Product' => true, 'BreadcrumbList' => true].
     */
    private function get_t1_schema_types(): array {
        $types = [];

        foreach ( $this->frontend->assemble_schemas() as $schema ) {
            $raw = $schema['@type'] ?? '';
            foreach ( is_array( $raw ) ? $raw : [ $raw ] as $type ) {
                if ( $type !== '' ) {
                    $types[ $type ] = true;
                }
            }
        }

        return $types;
    }
}
