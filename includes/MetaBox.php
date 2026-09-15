<?php

namespace T1Schema;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Post editor meta box for t1 Schema.
 *
 * Shows which local schemas are attached to the post and how healthy they
 * are, then hands off to the full editor. Deliberately read-only: editing
 * happens in one place so there is a single save path for schema data.
 *
 * @package T1Schema
 * @since   1.1.0
 */
class MetaBox {

    public function init(): void {
        add_action( 'add_meta_boxes', [ $this, 'register' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_styles' ] );
    }

    /**
     * Register the meta box for all public post types.
     */
    public function register(): void {
        $cap = apply_filters( 't1schema_required_capability', 'manage_options' );
        if ( ! current_user_can( $cap ) ) {
            return;
        }

        $post_types = get_post_types( [ 'public' => true ], 'names' );

        foreach ( $post_types as $post_type ) {
            if ( $post_type === 'attachment' ) {
                continue;
            }

            add_meta_box(
                't1schema-local-schemas',
                __( '🔮 Teil1 Schema Manager — Local Schemas', 'teil1-schema-manager' ),
                [ $this, 'render' ],
                $post_type,
                'side',
                'default'
            );
        }
    }

    /**
     * Render the meta box content.
     *
     * @param \WP_Post $post Current post.
     */
    public function render( \WP_Post $post ): void {
        $raw     = get_post_meta( $post->ID, '_t1schema_local', true );
        $schemas = $raw ? ( is_string( $raw ) ? json_decode( $raw, true ) : $raw ) : [];
        $schemas = is_array( $schemas ) ? $schemas : [];

        // Namespaced so it cannot be mistaken for core's $_GET['post'].
        $editor_url = add_query_arg(
            [
                'page'    => T1SCHEMA_ADMIN_SLUG,
                't1_post' => $post->ID,
            ],
            admin_url( 'admin.php' )
        );

        echo '<div class="t1schema-metabox">';

        if ( ! empty( $schemas ) ) {
            echo '<div class="t1schema-metabox__list">';
            foreach ( $schemas as $schema ) {
                $this->render_schema_item( $schema );
            }
            echo '</div>';
        } else {
            echo '<p class="t1schema-metabox__empty">' . esc_html__( 'No local schemas on this page.', 'teil1-schema-manager' ) . '<br>' . esc_html__( 'Global schemas still apply.', 'teil1-schema-manager' ) . '</p>';
        }

        echo '<div class="t1schema-metabox__footer">';
        echo '<a href="' . esc_url( $editor_url ) . '" class="t1schema-metabox__link" target="_blank" rel="noopener noreferrer">';
        echo empty( $schemas )
            ? esc_html__( 'Add a schema →', 'teil1-schema-manager' )
            : esc_html__( 'Edit in Teil1 Schema Manager →', 'teil1-schema-manager' );
        echo '</a>';
        echo '</div>';

        echo '</div>';
    }

    /**
     * Render one schema summary row.
     *
     * @param array $schema Stored schema.
     */
    private function render_schema_item( array $schema ): void {
        $type     = $schema['@type'] ?? 'Unknown';
        $type     = is_array( $type ) ? ( $type[0] ?? 'Unknown' ) : $type;
        $override = $schema['_t1schema_meta']['override_global'] ?? true;

        $health    = SchemaValidator::validate( $schema );
        $error_cnt = count( $health['errors'] ?? [] );
        $warn_cnt  = count( $health['warnings'] ?? [] );
        $info_cnt  = count( $health['infos'] ?? [] );

        if ( ! $health['valid'] ) {
            $status_class = 't1schema-status--error';
            /* translators: %d: number of validation errors. */
            $status_label = sprintf( _n( '%d error', '%d errors', $error_cnt, 'teil1-schema-manager' ), $error_cnt );
        } elseif ( $warn_cnt > 0 ) {
            $status_class = 't1schema-status--warning';
            /* translators: %d: number of validation warnings. */
            $status_label = sprintf( _n( '%d warning', '%d warnings', $warn_cnt, 'teil1-schema-manager' ), $warn_cnt );
        } elseif ( $info_cnt > 0 ) {
            $status_class = 't1schema-status--info';
            $status_label = __( 'Valid (Custom)', 'teil1-schema-manager' );
        } else {
            $status_class = 't1schema-status--valid';
            $status_label = __( 'Valid', 'teil1-schema-manager' );
        }

        echo '<div class="t1schema-metabox__item">';
        echo '<div class="t1schema-metabox__item-header">';
        echo '<span class="t1schema-metabox__type">' . esc_html( $type ) . '</span>';
        echo '<span class="t1schema-metabox__badge ' . esc_attr( $status_class ) . '">' . esc_html( $status_label ) . '</span>';
        echo '</div>';

        if ( ! empty( $health['errors'] ) || ! empty( $health['warnings'] ) || ! empty( $health['infos'] ) ) {
            echo '<div class="t1schema-metabox__issues">';
            foreach ( $health['errors'] as $err ) {
                echo '<div class="t1schema-metabox__issue t1schema-metabox__issue--error">✗ ' . esc_html( $err ) . '</div>';
            }
            foreach ( array_slice( $health['warnings'], 0, 3 ) as $warn ) {
                echo '<div class="t1schema-metabox__issue t1schema-metabox__issue--warning">⚠ ' . esc_html( $warn ) . '</div>';
            }
            if ( $warn_cnt > 3 ) {
                echo '<div class="t1schema-metabox__issue t1schema-metabox__issue--warning">' . esc_html(
                    /* translators: %d: number of additional warnings not shown. */
                    sprintf( __( '… and %d more', 'teil1-schema-manager' ), $warn_cnt - 3 )
                ) . '</div>';
            }
            foreach ( array_slice( $health['infos'], 0, 3 ) as $info ) {
                echo '<div class="t1schema-metabox__issue t1schema-metabox__issue--info">ℹ ' . esc_html( $info ) . '</div>';
            }
            echo '</div>';
        }

        echo '<div class="t1schema-metabox__item-meta">';
        echo '<span class="t1schema-metabox__flag">';
        echo $override
            ? esc_html__( '↑ Overrides global', 'teil1-schema-manager' )
            : esc_html__( '∥ Coexists with global', 'teil1-schema-manager' );
        echo '</span>';
        echo '</div>';
        echo '</div>';
    }

    /**
     * Enqueue meta box styles.
     *
     * @param string $hook_suffix Current admin page.
     */
    public function enqueue_styles( string $hook_suffix ): void {
        if ( ! in_array( $hook_suffix, [ 'post.php', 'post-new.php' ], true ) ) {
            return;
        }

        wp_add_inline_style( 'wp-admin', $this->get_inline_css() );
    }

    /**
     * Get meta box CSS.
     *
     * @return string CSS string.
     */
    private function get_inline_css(): string {
        return '
        .t1schema-metabox { font-size: 12px; }
        .t1schema-metabox__list { margin-bottom: 12px; }
        .t1schema-metabox__item {
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 8px 10px;
            margin-bottom: 6px;
            background: #fafbfc;
        }
        .t1schema-metabox__item-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 6px;
            margin-bottom: 4px;
        }
        .t1schema-metabox__type {
            font-weight: 600;
            color: #1e293b;
        }
        .t1schema-metabox__badge {
            font-size: 10px;
            font-weight: 600;
            padding: 1px 6px;
            border-radius: 10px;
            white-space: nowrap;
        }
        .t1schema-status--valid { background: #dcfce7; color: #166534; }
        .t1schema-status--warning { background: #fef3c7; color: #92400e; }
        .t1schema-status--error { background: #fee2e2; color: #991b1b; }
        .t1schema-status--info { background: #dbeafe; color: #1e40af; }
        .t1schema-metabox__issues {
            margin: 4px 0;
            font-size: 11px;
        }
        .t1schema-metabox__issue {
            padding: 2px 0;
            line-height: 1.3;
        }
        .t1schema-metabox__issue--error { color: #dc2626; }
        .t1schema-metabox__issue--warning { color: #d97706; }
        .t1schema-metabox__issue--info { color: #2563eb; }
        .t1schema-metabox__item-meta {
            margin-top: 4px;
        }
        .t1schema-metabox__flag {
            font-size: 10px;
            color: #94a3b8;
        }
        .t1schema-metabox__empty {
            text-align: center;
            color: #94a3b8;
            font-style: italic;
            padding: 12px 0;
            margin: 0;
            line-height: 1.5;
        }
        .t1schema-metabox__footer {
            border-top: 1px solid #e2e8f0;
            padding-top: 10px;
            margin-top: 10px;
            text-align: center;
        }
        .t1schema-metabox__link {
            color: #4263eb;
            text-decoration: none;
            font-weight: 500;
            font-size: 12px;
        }
        .t1schema-metabox__link:hover { text-decoration: underline; }
        ';
    }
}
