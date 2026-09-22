import React from 'react';

/**
 * RichSnippet — Google SERP mockup preview showing how the
 * current schema would appear as a rich result.
 */
export default function RichSnippet({ schemaData }) {
  const type = schemaData?.['@type'] || '';
  const name = schemaData?.name || schemaData?.headline || '';
  const description = schemaData?.description || '';
  const url = schemaData?.url || '';
  const image = schemaData?.image || schemaData?.logo || '';

  const isVariable = (val) => typeof val === 'string' && val.startsWith('{{');
  const display = (val, fallback = '…') => {
    if (!val) return fallback;
    if (isVariable(val)) return val.replace(/\{\{|\}\}/g, '');
    return val;
  };

  if (!type) {
    return (
      <div className="sp-rounded-xl sp-border sp-border-surface-3 sp-bg-white sp-p-6 sp-shadow-bento">
        <div className="sp-text-center sp-text-sm sp-text-ink-3">
          <span className="sp-mb-2 sp-block sp-text-2xl">🔍</span>
          {wp.i18n.__('Select a schema type to see the preview', 'teil1-schema-manager')}
        </div>
      </div>
    );
  }

  return (
    <div className="sp-rounded-xl sp-border sp-border-surface-3 sp-bg-white sp-shadow-bento">
      <div className="sp-border-b sp-border-surface-2 sp-px-4 sp-py-3">
        <h3 className="sp-text-xs sp-font-semibold sp-uppercase sp-tracking-wider sp-text-ink-3">
          {wp.i18n.__('Rich Result Preview', 'teil1-schema-manager')}
        </h3>
      </div>

      <div className="sp-p-4">
        {/* Google SERP mockup */}
        <div className="sp-rounded-lg sp-border sp-border-surface-2 sp-bg-white sp-p-4">
          {/* Breadcrumb / URL */}
          <div className="sp-mb-1 sp-flex sp-items-center sp-gap-1">
            <div className="sp-flex sp-h-5 sp-w-5 sp-items-center sp-justify-center sp-rounded-full sp-bg-surface-2">
              <span className="sp-text-2xs">🌐</span>
            </div>
            <span className="sp-text-xs sp-text-ink-2">
              {display(url, 'example.com')}
            </span>
          </div>

          {/* Title */}
          <h4 className="sp-mb-1 sp-text-base sp-font-medium sp-leading-snug sp-text-blue-700">
            {display(name, wp.i18n.__('Page Title', 'teil1-schema-manager'))}
          </h4>

          {/* Description */}
          <p className="sp-text-sm sp-leading-relaxed sp-text-ink-2">
            {display(
              description,
              wp.i18n.__('A description of this page will appear here…', 'teil1-schema-manager')
            ).substring(0, 160)}
          </p>

          {/* Type-specific rich result elements */}
          {type === 'Organization' && (
            <div className="sp-mt-3 sp-flex sp-items-center sp-gap-2 sp-border-t sp-border-surface-2 sp-pt-3">
              {image && (
                <div className="sp-h-8 sp-w-8 sp-rounded sp-bg-surface-2 sp-text-center sp-text-xs sp-leading-8">
                  {isVariable(image) ? '🏢' : '📷'}
                </div>
              )}
              <div className="sp-text-xs sp-text-ink-3">
                Organization · {display(name)}
              </div>
            </div>
          )}

          {(type === 'Article' || type === 'BlogPosting') && (
            <div className="sp-mt-3 sp-flex sp-items-center sp-gap-3 sp-border-t sp-border-surface-2 sp-pt-3 sp-text-xs sp-text-ink-3">
              <span>
                📅 {display(
                  schemaData?.datePublished,
                  wp.i18n._x('Date', 'rich result preview fallback', 'teil1-schema-manager')
                )}
              </span>
              <span>
                ✍️ {display(
                  schemaData?.author?.name || schemaData?.author,
                  wp.i18n._x('Author', 'rich result preview fallback', 'teil1-schema-manager')
                )}
              </span>
            </div>
          )}

          {type === 'Product' && schemaData?.offers && (
            <div className="sp-mt-3 sp-flex sp-items-center sp-gap-3 sp-border-t sp-border-surface-2 sp-pt-3">
              <span className="sp-text-sm sp-font-semibold sp-text-green-600">
                {schemaData.offers?.priceCurrency || '€'} {display(schemaData.offers?.price, '—')}
              </span>
              <span className="sp-text-xs sp-text-ink-3">
                {schemaData.offers?.availability
                  ? wp.i18n.__('✓ In stock', 'teil1-schema-manager')
                  : ''}
              </span>
            </div>
          )}

          {type === 'FAQPage' && (
            <div className="sp-mt-3 sp-space-y-1 sp-border-t sp-border-surface-2 sp-pt-3">
              <div className="sp-text-xs sp-font-medium sp-text-ink-1">
                {wp.i18n.__('People also ask', 'teil1-schema-manager')}
              </div>
              <div className="sp-rounded sp-border sp-border-surface-2 sp-px-3 sp-py-1.5 sp-text-xs sp-text-ink-2">
                {wp.i18n.__('▸ First question…', 'teil1-schema-manager')}
              </div>
              <div className="sp-rounded sp-border sp-border-surface-2 sp-px-3 sp-py-1.5 sp-text-xs sp-text-ink-2">
                {wp.i18n.__('▸ Second question…', 'teil1-schema-manager')}
              </div>
            </div>
          )}

          {type === 'Event' && (
            <div className="sp-mt-3 sp-flex sp-items-center sp-gap-3 sp-border-t sp-border-surface-2 sp-pt-3 sp-text-xs sp-text-ink-3">
              <span>
                📅 {display(
                  schemaData?.startDate,
                  wp.i18n._x('Start date', 'rich result preview fallback', 'teil1-schema-manager')
                )}
              </span>
              <span>
                📍 {typeof schemaData?.location === 'string'
                  ? display(schemaData.location)
                  : wp.i18n._x('Location', 'rich result preview fallback', 'teil1-schema-manager')}
              </span>
            </div>
          )}
        </div>

        {/* Badge */}
        <div className="sp-mt-3 sp-text-center sp-text-2xs sp-text-ink-4">
          {wp.i18n.sprintf(
            /* translators: %1$s: Schema.org type identifier. */
            wp.i18n.__('Preview based on %1$s schema', 'teil1-schema-manager'),
            type
          )}
        </div>
      </div>
    </div>
  );
}
