<?php

namespace T1Schema;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Expands {{product_price}} into a proper price range for variable products.
 *
 * A variable WooCommerce product (size/color options, etc.) does not have one
 * price — it has one per variation. {{product_price}} alone can only resolve
 * to a single number, so on its own it would understate a variable product as
 * its cheapest variation. This class detects that case and rewrites the whole
 * Offer node into an AggregateOffer with lowPrice/highPrice/offerCount,
 * mirroring how WooCommerce's own structured data (WC_Structured_Data)
 * handles the same situation.
 *
 * When every variation shares the same active price, no expansion happens —
 * a plain Offer with {{product_price}} is already the correct, simpler shape.
 *
 * @package T1Schema
 * @since   2.2.0
 */
class WooCommerceOffers {

    /**
     * Walk the assembled (pre-variable-resolution) schema tree and upgrade
     * any Offer that uses {{product_price}} into an AggregateOffer, if the
     * current post is a variable product with more than one active price.
     *
     * Must run before VariableResolver::resolve() — it keys off the literal
     * {{product_price}} tag, and removes it entirely for expanded nodes so
     * there is nothing left for the resolver to substitute.
     *
     * @param array    $schemas Raw assembled schema nodes.
     * @param int|null $post_id Current post ID, or null off-singular.
     * @return array Schema nodes, with variable-product Offers expanded.
     */
    public static function expand( array $schemas, ?int $post_id ): array {
        if ( ! $post_id || ! function_exists( 'wc_get_product' ) ) {
            return $schemas;
        }

        $product = wc_get_product( $post_id );
        if ( ! is_a( $product, 'WC_Product' ) || ! $product->is_type( 'variable' ) ) {
            return $schemas;
        }

        $range = self::get_price_range( $product );
        if ( ! $range ) {
            // No variations, or every variation shares one active price —
            // the plain {{product_price}} Offer is already correct.
            return $schemas;
        }

        return array_map( fn( $node ) => self::expand_node( $node, $range ), $schemas );
    }

    /**
     * Compute the low/high/count price range for a variable product.
     *
     * Mirrors WC_Structured_Data::generate_product_data()'s own variable
     * product branch: get_variation_price(..., true) applies the site's tax
     * display settings the same way, and wc_format_decimal() matches
     * WooCommerce's own output formatting.
     *
     * @return array{lowPrice: string, highPrice: string, offerCount: int}|null
     *         Null when there is nothing to expand.
     */
    private static function get_price_range( \WC_Product $product ): ?array {
        $lowest  = $product->get_variation_price( 'min', true );
        $highest = $product->get_variation_price( 'max', true );

        if ( '' === $lowest || $lowest === $highest ) {
            return null;
        }

        $variation_prices = $product->get_variation_prices( true );
        $offer_count      = isset( $variation_prices['price'] ) ? count( $variation_prices['price'] ) : 0;

        return [
            'lowPrice'   => wc_format_decimal( $lowest, wc_get_price_decimals() ),
            'highPrice'  => wc_format_decimal( $highest, wc_get_price_decimals() ),
            'offerCount' => $offer_count,
        ];
    }

    /**
     * Recursively expand any Offer node using {{product_price}} found
     * anywhere in the tree (schemas commonly nest Offer inside Product.offers).
     *
     * @param mixed $node  Schema node, or a scalar leaf value.
     * @param array $range Output of get_price_range().
     */
    private static function expand_node( mixed $node, array $range ): mixed {
        if ( ! is_array( $node ) ) {
            return $node;
        }

        if ( self::uses_dynamic_price( $node ) ) {
            unset( $node['price'] );
            $node['@type']      = 'AggregateOffer';
            $node['lowPrice']   = $range['lowPrice'];
            $node['highPrice']  = $range['highPrice'];
            $node['offerCount'] = $range['offerCount'];
            return $node;
        }

        foreach ( $node as $key => $value ) {
            $node[ $key ] = self::expand_node( $value, $range );
        }

        return $node;
    }

    /**
     * Whether a node is an Offer whose price is (or contains) {{product_price}}.
     *
     * Scoped deliberately narrow: only nodes that actually use the dynamic
     * variable are touched, so hand-written static Offers are never altered.
     */
    private static function uses_dynamic_price( array $node ): bool {
        $types = (array) ( $node['@type'] ?? '' );
        if ( ! in_array( 'Offer', $types, true ) ) {
            return false;
        }

        $price = $node['price'] ?? '';
        return is_string( $price ) && str_contains( $price, '{{product_price}}' );
    }
}
