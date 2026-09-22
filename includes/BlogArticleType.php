<?php

namespace T1Schema;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Maps the canonical blog article node to its editorial Schema.org subtype.
 */
final class BlogArticleType {

    public const META_KEY = '_teil1_content_editorial_format';

    /**
     * @var list<string>
     */
    private const ARTICLE_TYPES = [ 'Article', 'NewsArticle', 'BlogPosting' ];

    public function init(): void {
        add_filter( 't1schema_resolved_schemas', [ $this, 'map' ], 10, 2 );
    }

    /**
     * @param list<array<string, mixed>> $schemas
     * @return list<array<string, mixed>>
     */
    public function map( array $schemas, ?int $post_id ): array {
        if ( ! $post_id || get_post_type( $post_id ) !== 'teil1_blog_post' ) {
            return $schemas;
        }

        $article_indexes = [];
        $canonical_indexes = [];
        $canonical_id = $this->normalize_article_id( (string) get_permalink( $post_id ) . '#article' );

        foreach ( $schemas as $index => $schema ) {
            $types = $this->types( $schema['@type'] ?? '' );
            if ( array_intersect( $types, self::ARTICLE_TYPES ) === [] ) {
                continue;
            }

            $article_indexes[] = $index;
            $node_id = $this->normalize_article_id( (string) ( $schema['@id'] ?? '' ) );
            if ( $node_id !== '' && $node_id === $canonical_id ) {
                $canonical_indexes[] = $index;
            }
        }

        $target_indexes = $canonical_indexes;
        if ( $target_indexes === [] && count( $article_indexes ) === 1 ) {
            $target_indexes = $article_indexes;
        }

        $target_type = self::schema_type_for_format(
            (string) get_post_meta( $post_id, self::META_KEY, true )
        );

        foreach ( $target_indexes as $index ) {
            $types = array_values( array_diff(
                $this->types( $schemas[ $index ]['@type'] ?? '' ),
                self::ARTICLE_TYPES
            ) );
            $types[] = $target_type;
            $types = array_values( array_unique( $types ) );
            $schemas[ $index ]['@type'] = count( $types ) === 1 ? $types[0] : $types;
        }

        return array_values( $schemas );
    }

    public static function schema_type_for_format( string $format ): string {
        return match ( sanitize_key( $format ) ) {
            'news'                  => 'NewsArticle',
            'ratgeber', 'anleitung' => 'BlogPosting',
            default                 => 'Article',
        };
    }

    /**
     * @return list<string>
     */
    private function types( mixed $type ): array {
        if ( is_array( $type ) ) {
            return array_values( array_filter( array_map( 'strval', $type ) ) );
        }

        return $type !== '' ? [ (string) $type ] : [];
    }

    private function normalize_article_id( string $id ): string {
        if ( $id === '' || ! str_ends_with( $id, '#article' ) ) {
            return $id;
        }

        $base = substr( $id, 0, -strlen( '#article' ) );

        return rtrim( $base, '/' ) . '/#article';
    }
}
