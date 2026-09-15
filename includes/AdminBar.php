<?php

namespace T1Schema;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Admin Bar integration — shows an icon with active schemas on hover.
 *
 * Hooks into Frontend's render pipeline to capture the schemas that were
 * actually assembled, then displays them in the admin bar.
 *
 * @package T1Schema
 * @since   1.2.0
 */
class AdminBar {

    /** @var array Captured schemas from the current page render. */
    private static array $captured_schemas = [];

    public function init(): void {
        // Note: no capability check here — init() fires at plugins_loaded,
        // before WordPress has loaded the current user. Each callback
        // checks current_user_can() individually.

        // Capture schemas from the actual render pipeline.
        add_filter( 't1schema_jsonld_output', [ $this, 'capture_from_render' ], 1, 2 );

        // Render admin bar items.
        add_action( 'admin_bar_menu', [ $this, 'add_menu' ], 100 );
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_styles' ] );
    }

    /**
     * Filter hook: captures the schemas that were actually rendered.
     * Passes through without modification.
     */
    public function capture_from_render( string $output, array $schemas ): string {
        foreach ( $schemas as $schema ) {
            $raw_type = $schema['@type'] ?? 'Unknown';
            $type     = is_array( $raw_type ) ? implode( ' + ', $raw_type ) : $raw_type;
            unset( $schema['_t1schema_meta'], $schema['@context'] );

            $props = count( array_filter(
                array_keys( $schema ),
                fn( $k ) => ! str_starts_with( $k, '@' )
            ) );

            self::$captured_schemas[] = [
                'type'  => $type,
                'props' => $props,
            ];
        }
        return $output;
    }

    /**
     * Add Teil1 Schema Manager node to the admin bar.
     */
    public function add_menu( \WP_Admin_Bar $wp_admin_bar ): void {
        if ( is_admin() || ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $count      = count( self::$captured_schemas );
        $count_mod  = $count > 0 ? 't1schema-ab-count--active' : 't1schema-ab-count--empty';
        $editor_url = admin_url( 'admin.php?page=' . T1SCHEMA_ADMIN_SLUG );

        $wp_admin_bar->add_node( [
            'id'    => T1SCHEMA_ADMIN_SLUG,
            'title' => '<span class="t1schema-ab-count ' . esc_attr( $count_mod ) . '">🔮 ' . esc_html( (string) $count ) . '</span>',
            'href'  => $editor_url,
            'meta'  => [
                'title' => sprintf(
                    /* translators: %d: number of active schemas. */
                    __( 'Teil1 Schema Manager: %d active schema(s) on this page', 'teil1-schema-manager' ),
                    $count
                ),
            ],
        ] );

        if ( $count === 0 ) {
            $wp_admin_bar->add_node( [
                'parent' => T1SCHEMA_ADMIN_SLUG,
                'id'     => 't1schema-empty',
                'title'  => '<span class="t1schema-ab-muted">' . esc_html__( 'No schemas active on this page', 'teil1-schema-manager' ) . '</span>',
                'href'   => $editor_url,
            ] );
            return;
        }

        foreach ( self::$captured_schemas as $i => $schema ) {
            $dot_mod = $this->get_type_modifier( $schema['type'] );
            $wp_admin_bar->add_node( [
                'parent' => T1SCHEMA_ADMIN_SLUG,
                'id'     => "t1schema-schema-{$i}",
                'title'  => '<span class="t1schema-ab-dot t1schema-ab-dot--' . esc_attr( $dot_mod ) . '"></span>'
                          . '<strong>' . esc_html( $schema['type'] ) . '</strong>'
                          . '<span class="t1schema-ab-props">' . sprintf(
                              /* translators: %d: number of properties. */
                              esc_html__( '%d props', 'teil1-schema-manager' ),
                              $schema['props']
                          ) . '</span>',
                'href'   => $editor_url,
            ] );
        }

        $wp_admin_bar->add_node( [
            'parent' => T1SCHEMA_ADMIN_SLUG,
            'id'     => 't1schema-dashboard',
            'title'  => '<span class="t1schema-ab-dashboard">' . esc_html__( '→ Open Dashboard', 'teil1-schema-manager' ) . '</span>',
            'href'   => $editor_url,
        ] );
    }

    /**
     * Enqueue admin-bar styles through the WordPress style API.
     */
    public function enqueue_styles(): void {
        if ( is_admin() || ! current_user_can( 'manage_options' ) || ! is_admin_bar_showing() ) {
            return;
        }

        wp_enqueue_style(
            't1schema-admin-bar',
            T1SCHEMA_URL . 'css/admin-bar.css',
            [],
            T1SCHEMA_VERSION
        );
    }

    /**
     * Map a Schema.org type to a CSS modifier for the admin-bar dot.
     */
    private function get_type_modifier( string $type ): string {
        return match ( true ) {
            in_array( $type, [ 'Organization', 'LocalBusiness' ], true ) => 'organization',
            in_array( $type, [ 'Article', 'BlogPosting' ], true )        => 'article',
            in_array( $type, [ 'Product', 'Offer' ], true )              => 'product',
            in_array( $type, [ 'FAQPage', 'HowTo' ], true )              => 'faq',
            in_array( $type, [ 'WebSite', 'WebPage' ], true )            => 'website',
            in_array( $type, [ 'BreadcrumbList' ], true )                => 'breadcrumb',
            default                                                      => 'other',
        };
    }
}
