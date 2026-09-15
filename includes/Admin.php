<?php

namespace T1Schema;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Admin panel handler.
 *
 * Registers the admin menu page and enqueues the React app assets.
 *
 * @package T1Schema
 * @since   1.0.0
 */
class Admin {

    public function init(): void {
        add_action( 'admin_menu', [ $this, 'register_menu' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
    }

    /**
     * Register the top-level admin menu page.
     */
    public function register_menu(): void {
        add_menu_page(
            __( 'Teil1 Schema Manager', 'teil1-schema-manager' ),
            __( 'Teil1 Schema Manager', 'teil1-schema-manager' ),
            apply_filters( 't1schema_required_capability', 'manage_options' ),
            T1SCHEMA_ADMIN_SLUG,
            [ $this, 'render_page' ],
            'dashicons-code-standards',
            81
        );
    }

    /**
     * Render the admin page container.
     * The React app mounts to #t1schema-root.
     */
    public function render_page(): void {
        echo '<div id="t1schema-root"></div>';
    }

    /**
     * Enqueue React app assets only on the t1 Schema page.
     *
     * @param string $hook_suffix Current admin page hook.
     */
    public function enqueue_assets( string $hook_suffix ): void {
        if ( $hook_suffix !== 'toplevel_page_' . T1SCHEMA_ADMIN_SLUG ) {
            return;
        }

        $manifest_path = T1SCHEMA_PATH . 'assets/manifest.json';
        $asset_url     = T1SCHEMA_URL . 'assets/';

        // Production: load from manifest.
        if ( file_exists( $manifest_path ) ) {
            $manifest = wp_json_file_decode( $manifest_path, [ 'associative' => true ] );

            $js_file  = $manifest['src/main.jsx']['file'] ?? 'app.js';
            $css_files = $manifest['src/main.jsx']['css'] ?? [];

            wp_enqueue_script(
                't1schema-app',
                $asset_url . $js_file,
                [],
                T1SCHEMA_VERSION,
                true
            );

            foreach ( $css_files as $css_file ) {
                wp_enqueue_style(
                    't1schema-app-' . basename( $css_file, '.css' ),
                    $asset_url . $css_file,
                    [],
                    T1SCHEMA_VERSION
                );
            }
        } elseif ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) {
            // Development only: load from Vite dev server.
            // This block is excluded from production builds and only runs
            // when SCRIPT_DEBUG is explicitly enabled in wp-config.php.
            $dev_server = 'http://localhost:5173';

            wp_enqueue_script(
                't1schema-vite-client',
                $dev_server . '/@vite/client',
                [],
                null, // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion
                false
            );

            wp_enqueue_script(
                't1schema-app',
                $dev_server . '/src/main.jsx',
                [],
                null, // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion
                true
            );
        }

        // Vite outputs ES modules — add type="module" to all t1schema scripts.
        add_filter( 'script_loader_tag', function ( string $tag, string $handle ) {
            if ( in_array( $handle, [ 't1schema-vite-client', 't1schema-app' ], true ) ) {
                return str_replace( ' src=', ' type="module" src=', $tag );
            }
            return $tag;
        }, 10, 2 );

        // Localize config for the React app.
        wp_localize_script( 't1schema-app', 't1SchemaConfig', [
            'restUrl'   => rest_url( 'teil1-schema-manager/v1/' ),
            'nonce'     => wp_create_nonce( 'wp_rest' ),
            'adminUrl'  => admin_url(),
            'pluginUrl' => T1SCHEMA_URL,
            'version'   => T1SCHEMA_VERSION,
            'siteName'  => get_bloginfo( 'name' ),
            'siteUrl'   => home_url( '/' ),
        ] );
    }
}
