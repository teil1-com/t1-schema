import React, { useState } from 'react';

/**
 * HealthDetail — expandable display of validation warnings and errors.
 * Used in both the Dashboard and the Schema editors.
 */
export default function HealthDetail({ health, type }) {
  const [expanded, setExpanded] = useState(true);

  if (!health) return null;

  const errors = health.errors || [];
  const warnings = health.warnings || [];
  const infos = health.infos || [];
  const isValid = health.valid !== false;
  const hasIssues = errors.length > 0 || warnings.length > 0 || infos.length > 0;
  const displayType = type || wp.i18n.__( 'Schema', 'teil1-schema-manager' );

  if (!hasIssues) {
    return (
      <div className="sp-rounded-xl sp-border sp-border-green-200 sp-bg-green-50 sp-p-4 sp-shadow-bento">
        <div className="sp-flex sp-items-center sp-gap-2">
          <span className="sp-text-green-600">✓</span>
          <span className="sp-text-sm sp-font-medium sp-text-green-700">
            {wp.i18n.sprintf(
              /* translators: %1$s: Schema.org type identifier or the generic word "Schema". */
              wp.i18n.__( '%1$s is valid — no issues found', 'teil1-schema-manager' ),
              displayType
            )}
          </span>
        </div>
      </div>
    );
  }

  return (
    <div className={`sp-rounded-xl sp-border sp-shadow-bento ${
      errors.length > 0
        ? 'sp-border-red-200 sp-bg-white'
        : 'sp-border-yellow-200 sp-bg-white'
    }`}>
      {/* Header */}
      <button
        onClick={() => setExpanded(!expanded)}
        className="sp-flex sp-w-full sp-items-center sp-justify-between sp-px-4 sp-py-3 sp-text-left"
      >
        <div className="sp-flex sp-items-center sp-gap-2">
          <span className={errors.length > 0 ? 'sp-text-red-500' : warnings.length > 0 ? 'sp-text-yellow-500' : 'sp-text-blue-500'}>
            {errors.length > 0 ? '🚨' : warnings.length > 0 ? '⚠️' : 'ℹ️'}
          </span>
          <span className="sp-text-xs sp-font-semibold sp-uppercase sp-tracking-wider sp-text-ink-3">
            {wp.i18n.sprintf(
              /* translators: %1$s: Schema.org type identifier or the generic word "Schema". */
              wp.i18n.__( 'Health: %1$s', 'teil1-schema-manager' ),
              displayType
            )}
          </span>
        </div>
        <div className="sp-flex sp-items-center sp-gap-2">
          {errors.length > 0 && (
            <span className="sp-rounded-full sp-bg-red-100 sp-px-2 sp-py-0.5 sp-text-2xs sp-font-semibold sp-text-red-600">
              {wp.i18n.sprintf(
                /* translators: %1$d: Number of validation errors. */
                wp.i18n._n(
                  '%1$d error',
                  '%1$d errors',
                  errors.length,
                  'teil1-schema-manager'
                ),
                errors.length
              )}
            </span>
          )}
          {warnings.length > 0 && (
            <span className="sp-rounded-full sp-bg-yellow-100 sp-px-2 sp-py-0.5 sp-text-2xs sp-font-semibold sp-text-yellow-600">
              {wp.i18n.sprintf(
                /* translators: %1$d: Number of validation warnings. */
                wp.i18n._n(
                  '%1$d warning',
                  '%1$d warnings',
                  warnings.length,
                  'teil1-schema-manager'
                ),
                warnings.length
              )}
            </span>
          )}
          {infos.length > 0 && errors.length === 0 && warnings.length === 0 && (
            <span className="sp-rounded-full sp-bg-blue-100 sp-px-2 sp-py-0.5 sp-text-2xs sp-font-semibold sp-text-blue-600">
              {wp.i18n.__( 'Valid (Custom)', 'teil1-schema-manager' )}
            </span>
          )}
          <svg
            className={`sp-h-4 sp-w-4 sp-text-ink-4 sp-transition-transform ${expanded ? 'sp-rotate-180' : ''}`}
            viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"
          >
            <path d="m6 9 6 6 6-6" />
          </svg>
        </div>
      </button>

      {/* Detail */}
      {expanded && (
        <div className="sp-border-t sp-border-surface-2 sp-px-4 sp-py-3 sp-space-y-2">
          {errors.map((error, i) => (
            <div key={`e-${i}`} className="sp-flex sp-items-start sp-gap-2 sp-rounded-lg sp-bg-red-50 sp-px-3 sp-py-2">
              <span className="sp-mt-0.5 sp-text-xs sp-text-red-400">●</span>
              <div>
                <p className="sp-text-sm sp-text-red-700">{error}</p>
                <p className="sp-mt-0.5 sp-text-xs sp-text-red-400">
                  {getFixSuggestion(error)}
                </p>
              </div>
            </div>
          ))}

          {warnings.map((warning, i) => (
            <div key={`w-${i}`} className="sp-flex sp-items-start sp-gap-2 sp-rounded-lg sp-bg-yellow-50 sp-px-3 sp-py-2">
              <span className="sp-mt-0.5 sp-text-xs sp-text-yellow-400">●</span>
              <div>
                <p className="sp-text-sm sp-text-yellow-700">{warning}</p>
                <p className="sp-mt-0.5 sp-text-xs sp-text-yellow-500">
                  {getFixSuggestion(warning)}
                </p>
              </div>
            </div>
          ))}

          {infos.map((info, i) => (
            <div key={`i-${i}`} className="sp-flex sp-items-start sp-gap-2 sp-rounded-lg sp-bg-blue-50 sp-px-3 sp-py-2">
              <span className="sp-mt-0.5 sp-text-xs sp-text-blue-400">●</span>
              <div>
                <p className="sp-text-sm sp-text-blue-700">{info}</p>
                <p className="sp-mt-0.5 sp-text-xs sp-text-blue-500">
                  {getFixSuggestion(info)}
                </p>
              </div>
            </div>
          ))}

          <p className="sp-pt-1 sp-text-2xs sp-text-ink-4">
            {wp.i18n.__(
              'Based on Google Rich Results requirements. Fixing errors is mandatory for eligibility; warnings improve quality.',
              'teil1-schema-manager'
            )}
          </p>
        </div>
      )}
    </div>
  );
}

/**
 * Provide actionable fix suggestions for common issues.
 */
function getFixSuggestion(message) {
  if (message.includes("'name'")) {
    /* translators: {{site_name}} and {{post_title}} are dynamic variable tokens. */
    return wp.i18n.__( 'Tip: Use {{site_name}} or {{post_title}} as a dynamic value', 'teil1-schema-manager' );
  }
  if (message.includes("'url'")) {
    /* translators: {{site_url}} and {{post_url}} are dynamic variable tokens. */
    return wp.i18n.__( 'Tip: Use {{site_url}} or {{post_url}}', 'teil1-schema-manager' );
  }
  if (message.includes("'logo'")) {
    /* translators: {{site_logo}} is a dynamic variable token. */
    return wp.i18n.__( 'Tip: Use {{site_logo}} to pull from theme settings', 'teil1-schema-manager' );
  }
  if (message.includes("'image'")) {
    /* translators: {{featured_image_url}} is a dynamic variable token. */
    return wp.i18n.__( 'Tip: Use {{featured_image_url}} for the post thumbnail', 'teil1-schema-manager' );
  }
  if (message.includes("'headline'")) {
    /* translators: {{post_title}} is a dynamic variable token. */
    return wp.i18n.__( 'Tip: Use {{post_title}} for the article headline', 'teil1-schema-manager' );
  }
  if (message.includes("'datePublished'")) {
    /* translators: {{post_date}} is a dynamic variable token. */
    return wp.i18n.__( 'Tip: Use {{post_date}} for auto-dated articles', 'teil1-schema-manager' );
  }
  if (message.includes("'author'")) {
    /* translators: Person is a Schema.org type and {{author_name}} is a dynamic variable token. */
    return wp.i18n.__( 'Tip: Use a nested Person object or {{author_name}}', 'teil1-schema-manager' );
  }
  if (message.includes("'description'")) {
    /* translators: {{post_excerpt}} and {{site_description}} are dynamic variable tokens. */
    return wp.i18n.__( 'Tip: Use {{post_excerpt}} or {{site_description}}', 'teil1-schema-manager' );
  }
  if (message.includes("'sameAs'")) {
    return wp.i18n.__( 'Add your social media profile URLs as an array', 'teil1-schema-manager' );
  }
  if (message.includes("'address'")) {
    /* translators: PostalAddress is a Schema.org type and JSON is a technical format name. */
    return wp.i18n.__( 'Add a nested PostalAddress object via JSON import', 'teil1-schema-manager' );
  }
  if (message.includes("'telephone'")) {
    return wp.i18n.__( 'Add your business phone number', 'teil1-schema-manager' );
  }
  if (message.includes('@context')) {
    return wp.i18n.__( 'This is added automatically when rendered — no action needed', 'teil1-schema-manager' );
  }
  if (message.includes('valid custom Schema.org type')) {
    return wp.i18n.__( 'Google may support this type, but validation is limited to syntax checks.', 'teil1-schema-manager' );
  }
  return wp.i18n.__( 'Set this property in the editor above to resolve', 'teil1-schema-manager' );
}
