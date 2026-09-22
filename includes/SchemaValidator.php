<?php

namespace T1Schema;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Schema validator.
 *
 * Validates schema objects against Schema.org type rules
 * and Google Rich Results requirements.
 *
 * @package T1Schema
 * @since   1.0.0
 */
class SchemaValidator {

    /**
     * Validate a schema object.
     *
     * Supports multi-type @type (array), e.g. ["Organization", "ProfessionalService"].
     * Validation runs against all recognized types in the array.
     *
     * When $context is 'rule', recommended property warnings are downgraded to
     * infos, since rules apply to multiple posts and recommended properties may
     * be set per-post via local overrides.
     *
     * @param array  $schema  Schema data to validate.
     * @param string $context Validation context: 'local', 'global', or 'rule'.
     * @return array { valid: bool, errors: string[], warnings: string[], infos: string[] }
     */
    public static function validate( array $schema, string $context = 'local' ): array {
        $errors   = [];
        $warnings = [];
        $infos    = [];

        // 1. Check @type exists.
        if ( empty( $schema['@type'] ) ) {
            $errors[] = __( 'Missing required @type property.', 'teil1-schema-manager' );
            return [ 'valid' => false, 'errors' => $errors, 'warnings' => $warnings, 'infos' => $infos ];
        }

        $raw_type = $schema['@type'];
        $types    = is_array( $raw_type ) ? $raw_type : [ $raw_type ];
        $registry = new SchemaRegistry();

        // Collect types that exist in our curated registry vs valid list vs unknown.
        $known_types   = array_filter( $types, fn( $t ) => $registry->has_type( $t ) );
        $unknown_types = array_diff( $types, $known_types );

        // Warn/error about unknown types.
        foreach ( $unknown_types as $ut ) {
            if ( $registry->is_valid_schema_org_type( $ut ) ) {
                $infos[] = sprintf(
                    /* translators: %s: Schema.org type name. */
                    __( "Type '%s' is a valid custom Schema.org type. Property validation is skipped.", 'teil1-schema-manager' ),
                    $ut
                );
            } else {
                $errors[] = sprintf(
                    /* translators: %s: Schema.org type name. */
                    __( "Type '%s' is not recognized as a valid Schema.org type.", 'teil1-schema-manager' ),
                    $ut
                );
            }
        }

        // If no known curated types, return early.
        if ( empty( $known_types ) ) {
            return [ 'valid' => empty( $errors ), 'errors' => $errors, 'warnings' => $warnings, 'infos' => $infos ];
        }

        // 2. Check required properties across ALL known types (union).
        $checked_required    = [];
        $checked_recommended = [];

        foreach ( $known_types as $type ) {
            $required = $registry->get_required_properties( $type );
            foreach ( $required as $prop_name => $prop_def ) {
                if ( isset( $checked_required[ $prop_name ] ) ) {
                    continue; // Already reported.
                }
                $checked_required[ $prop_name ] = true;

                if ( ! isset( $schema[ $prop_name ] ) || $schema[ $prop_name ] === '' ) {
                    $type_label = is_array( $raw_type ) ? implode( ' + ', $raw_type ) : $raw_type;
                    $errors[]   = sprintf(
                        /* translators: %1$s: Schema.org property name. %2$s: Schema.org type name. */
                        __( "Missing required property: '%1\$s' for type '%2\$s'.", 'teil1-schema-manager' ),
                        $prop_name,
                        $type_label
                    );
                }
            }

            // 3. Check recommended properties.
            $all_props = $registry->get_properties( $type );
            foreach ( $all_props as $prop_name => $prop_def ) {
                if ( isset( $checked_recommended[ $prop_name ] ) ) {
                    continue;
                }
                $checked_recommended[ $prop_name ] = true;

                if ( ( $prop_def['recommended'] ?? false ) && ( ! isset( $schema[ $prop_name ] ) || $schema[ $prop_name ] === '' ) ) {
                    $type_label = is_array( $raw_type ) ? implode( ' + ', $raw_type ) : $raw_type;
                    if ( $context === 'rule' ) {
                        // On rule level, recommended properties may be set per-post via local overrides.
                        $infos[] = sprintf(
                            /* translators: %1$s: Schema.org property name. %2$s: Schema.org type name. */
                            __( "Missing recommended property: '%1\$s' for type '%2\$s'. May be set per-post.", 'teil1-schema-manager' ),
                            $prop_name,
                            $type_label
                        );
                    } else {
                        $warnings[] = sprintf(
                            /* translators: %1$s: Schema.org property name. %2$s: Schema.org type name. */
                            __( "Missing recommended property: '%1\$s' for type '%2\$s'.", 'teil1-schema-manager' ),
                            $prop_name,
                            $type_label
                        );
                    }
                }
            }
        }

        // 4. Check @context exists (for root-level schemas).
        if ( ! isset( $schema['@context'] ) ) {
            $warnings[] = __( 'Missing @context. Will be added automatically at output.', 'teil1-schema-manager' );
        }

        return [
            'valid'    => empty( $errors ),
            'errors'   => $errors,
            'warnings' => $warnings,
            'infos'    => $infos,
        ];
    }

    /**
     * Normalize an @type value to a string key for comparison/dedup.
     *
     * For arrays, returns a sorted, pipe-joined string: "Organization|ProfessionalService".
     * For strings, returns the string as-is.
     *
     * @param mixed $type The @type value (string or array).
     * @return string Normalized type key.
     */
    public static function normalize_type_key( $type ): string {
        if ( is_array( $type ) ) {
            $sorted = $type;
            sort( $sorted );
            return implode( '|', $sorted );
        }
        return (string) $type;
    }
}
