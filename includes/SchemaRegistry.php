<?php

namespace T1Schema;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Schema.org type registry.
 *
 * Provides type definitions, property listings, and hierarchy
 * information from the curated schema-types.json data file.
 *
 * @package T1Schema
 * @since   1.0.0
 */
class SchemaRegistry {

    /** @var array<string, array> Cached curated type definitions. */
    private array $types = [];

    /** @var array<string> Cache of all valid Schema.org type names. */
    private array $valid_types = [];

    public function __construct() {
        $this->load();
    }

    /**
     * Load type definitions from the JSON data file.
     */
    private function load(): void {
        $file = T1SCHEMA_PATH . 'data/schema-types.json';
        if ( ! file_exists( $file ) ) {
            return;
        }

        $data = wp_json_file_decode( $file, [ 'associative' => true ] );
        if ( is_array( $data ) ) {
            $this->types = $data;
        }

        $valid_file = T1SCHEMA_PATH . 'data/valid-types.json';
        if ( file_exists( $valid_file ) ) {
            $valid_data = wp_json_file_decode( $valid_file, [ 'associative' => true ] );
            if ( is_array( $valid_data ) ) {
                // Strip namespace prefixes (e.g. "schema:CollectionPage" → "CollectionPage")
                // so lookups by unprefixed type name work correctly.
                $this->valid_types = array_map( function ( string $entry ): string {
                    $parts = explode( ':', $entry, 2 );
                    return count( $parts ) === 2 ? $parts[1] : $entry;
                }, $valid_data );
            }
        }
    }

    /**
     * Get all available types with their metadata.
     *
     * @return array<string, array>
     */
    public function get_types(): array {
        return $this->types;
    }

    /**
     * Get type metadata localized for presentation in the admin interface.
     *
     * Schema.org type and property identifiers remain canonical.
     *
     * @return array<string, array>
     */
    public function get_localized_types(): array {
        return SchemaTypeTranslations::localize( $this->types );
    }

    /**
     * Get a flat list of type names.
     *
     * @return array<string>
     */
    public function get_type_names(): array {
        return array_keys( $this->types );
    }

    /**
     * Get properties for a specific type.
     *
     * @param string $type Schema.org type name.
     * @return array Properties with required/recommended flags.
     */
    public function get_properties( string $type ): array {
        return $this->types[ $type ]['properties'] ?? [];
    }

    /**
     * Get the parent type in the hierarchy.
     *
     * @param string $type Schema.org type name.
     * @return string|null Parent type or null.
     */
    public function get_parent( string $type ): ?string {
        return $this->types[ $type ]['parent'] ?? null;
    }

    /**
     * Get required properties for a specific type.
     *
     * @param string $type Schema.org type name.
     * @return array<string, array>
     */
    public function get_required_properties( string $type ): array {
        $props = $this->get_properties( $type );
        return array_filter( $props, fn( array $p ) => ( $p['required'] ?? false ) === true );
    }

    /**
     * Check if a type exists in the registry.
     *
     * @param string $type Schema.org type name.
     * @return bool
     */
    public function has_type( string $type ): bool {
        return isset( $this->types[ $type ] );
    }

    /**
     * Check if a type is ANY valid Schema.org type (curated or not).
     *
     * @param string $type Schema.org type name.
     * @return bool
     */
    public function is_valid_schema_org_type( string $type ): bool {
        // Fallback to true if the valid-types file didn't load properly, 
        // to avoid breaking things unnecessarily.
        if ( empty( $this->valid_types ) ) {
            return true;
        }
        return in_array( $type, $this->valid_types, true ) || $this->has_type( $type );
    }
}
