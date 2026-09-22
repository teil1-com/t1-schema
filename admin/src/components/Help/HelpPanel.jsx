import React from 'react';
import { useVariables, useSettings, useUpdateSettings } from '../../hooks/useSchema';

/**
 * HelpPanel — Documentation for variables, usage guide, and tips.
 */
export default function HelpPanel() {
  const { data: variables = {} } = useVariables();
  const { data: settings } = useSettings();
  const updateSettings = useUpdateSettings();

  const categoryMeta = {
    post: {
      icon: '📄',
      title: wp.i18n.__('Post / Page Variables', 'teil1-schema-manager'),
      desc: wp.i18n.__('Dynamic data from the current post or page', 'teil1-schema-manager'),
    },
    author: {
      icon: '👤',
      title: wp.i18n.__('Author Variables', 'teil1-schema-manager'),
      desc: wp.i18n.__('Data about the post author', 'teil1-schema-manager'),
    },
    site: {
      icon: '🌐',
      title: wp.i18n.__('Site Variables', 'teil1-schema-manager'),
      desc: wp.i18n.sprintf(
        /* translators: %1$s: WordPress brand name. */
        wp.i18n.__('Global site information from %1$s settings', 'teil1-schema-manager'),
        'WordPress'
      ),
    },
    taxonomy: {
      icon: '🏷️',
      title: wp.i18n.__('Taxonomy Variables', 'teil1-schema-manager'),
      desc: wp.i18n.__('Categories, tags, and custom taxonomies', 'teil1-schema-manager'),
    },
    meta: {
      icon: '🔧',
      title: wp.i18n.__('Custom Meta Variables', 'teil1-schema-manager'),
      desc: wp.i18n.sprintf(
        /* translators: %1$s: WordPress post meta field name. */
        wp.i18n.__('Access any %1$s field by key', 'teil1-schema-manager'),
        'post_meta'
      ),
    },
    woocommerce: {
      icon: '🛒',
      title: wp.i18n.sprintf(
        /* translators: %1$s: WooCommerce brand name. */
        wp.i18n.__('%1$s Variables', 'teil1-schema-manager'),
        'WooCommerce'
      ),
      desc: wp.i18n.sprintf(
        /* translators: %1$s: WooCommerce brand name. */
        wp.i18n.__('Live product data — only shown when %1$s is active', 'teil1-schema-manager'),
        'WooCommerce'
      ),
    },
  };

  return (
    <div className="sp-space-y-6">
      {/* Quick Start Guide */}
      <div className="sp-rounded-xl sp-border sp-border-surface-3 sp-bg-white sp-shadow-bento">
        <div className="sp-border-b sp-border-surface-2 sp-px-6 sp-py-4">
          <h2 className="sp-text-base sp-font-semibold sp-text-ink-0">
            {wp.i18n.sprintf(
              /* translators: %1$s: Plugin name. */
              wp.i18n.__('📖 How %1$s Works', 'teil1-schema-manager'),
              'Teil1 Schema Manager'
            )}
          </h2>
        </div>
        <div className="sp-p-6 sp-space-y-6">
          {/* Concepts */}
          <div className="sp-grid sp-grid-cols-1 sp-gap-4 md:sp-grid-cols-2">
            <ConceptCard
              icon="🌍"
              title={wp.i18n.__('Global Schemas', 'teil1-schema-manager')}
              desc={wp.i18n.sprintf(
                /* translators: %1$s: Organization Schema.org type, %2$s: WebSite Schema.org type. */
                wp.i18n.__(
                  'Site-wide schemas (%1$s, %2$s) that apply to every page automatically. Manage these in the Dashboard tab.',
                  'teil1-schema-manager'
                ),
                'Organization',
                'WebSite'
              )}
            />
            <ConceptCard
              icon="📄"
              title={wp.i18n.__('Local Schemas', 'teil1-schema-manager')}
              desc={wp.i18n.sprintf(
                /* translators: %1$s: Article Schema.org type, %2$s: Product Schema.org type, %3$s: FAQ schema name. */
                wp.i18n.__(
                  'Page-specific schemas (%1$s, %2$s, %3$s) that apply to a single post or page. Manage these in the Pages tab.',
                  'teil1-schema-manager'
                ),
                'Article',
                'Product',
                'FAQ'
              )}
            />
            <ConceptCard
              icon="🔀"
              title={wp.i18n.__('Override Logic', 'teil1-schema-manager')}
              desc={wp.i18n.sprintf(
                /* translators: %1$s: @type JSON-LD key, %2$s: translated checkbox label. */
                wp.i18n.__(
                  'If a local schema has the same %1$s as a global one, the local schema replaces the global one on that page. Toggle this per-schema with the "%2$s" checkbox.',
                  'teil1-schema-manager'
                ),
                '@type',
                wp.i18n.__('Override global', 'teil1-schema-manager')
              )}
            />
            <ConceptCard
              icon="🏷️"
              title={wp.i18n.__('Dynamic Variables', 'teil1-schema-manager')}
              desc={wp.i18n.sprintf(
                /* translators: %1$s: {{variable}} token, %2$s: {{post_title}} token. */
                wp.i18n.__(
                  'Use %1$s tags instead of hardcoded values. These resolve to real data at render time — e.g. %2$s becomes the actual page title.',
                  'teil1-schema-manager'
                ),
                '{{variable}}',
                '{{post_title}}'
              )}
            />
          </div>

          {/* Workflow */}
          <div>
            <h3 className="sp-mb-3 sp-text-sm sp-font-semibold sp-text-ink-0">
              {wp.i18n.__('Typical Workflow', 'teil1-schema-manager')}
            </h3>
            <div className="sp-space-y-2">
              <Step
                n="1"
                text={wp.i18n.sprintf(
                  /* translators: %1$s: Organization Schema.org type, %2$s: WebSite Schema.org type. */
                  wp.i18n.__('Set up global %1$s and %2$s schemas in the Dashboard', 'teil1-schema-manager'),
                  'Organization',
                  'WebSite'
                )}
              />
              <Step
                n="2"
                text={wp.i18n.__(
                  'Go to the Pages tab and find the post you want to add structured data to',
                  'teil1-schema-manager'
                )}
              />
              <Step
                n="3"
                text={wp.i18n.sprintf(
                  /* translators: %1$s: translated Add Schema button label, %2$s: Article Schema.org type, %3$s: Product Schema.org type, %4$s: FAQ schema name. */
                  wp.i18n.__(
                    'Click "%1$s" → select a type (e.g. %2$s, %3$s, %4$s)',
                    'teil1-schema-manager'
                  ),
                  wp.i18n.__('Add Schema', 'teil1-schema-manager'),
                  'Article',
                  'Product',
                  'FAQ'
                )}
              />
              <Step
                n="4"
                text={wp.i18n.sprintf(
                  /* translators: %1$s: {{variables}} token. */
                  wp.i18n.__(
                    'Fill in properties using %1$s for dynamic data or plain text for static values',
                    'teil1-schema-manager'
                  ),
                  '{{variables}}'
                )}
              />
              <Step
                n="5"
                text={wp.i18n.__(
                  'Check the Health panel on the right for missing required/recommended properties',
                  'teil1-schema-manager'
                )}
              />
              <Step
                n="6"
                text={wp.i18n.sprintf(
                  /* translators: %1$s: JSON-LD data format name. */
                  wp.i18n.__('Save → verify the %1$s output in your page source', 'teil1-schema-manager'),
                  'JSON-LD'
                )}
              />
            </div>
          </div>

          {/* Warning vs Error */}
          <div>
            <h3 className="sp-mb-3 sp-text-sm sp-font-semibold sp-text-ink-0">
              {wp.i18n.__('Understanding Health Status', 'teil1-schema-manager')}
            </h3>
            <div className="sp-space-y-2">
              <div className="sp-flex sp-items-start sp-gap-3 sp-rounded-lg sp-bg-red-50 sp-p-3">
                <span className="sp-mt-0.5 sp-text-red-500">🚨</span>
                <div>
                  <p className="sp-text-sm sp-font-medium sp-text-red-700">
                    {wp.i18n.__('Errors', 'teil1-schema-manager')}
                  </p>
                  <p className="sp-text-xs sp-text-red-600">
                    {wp.i18n.sprintf(
                      /* translators: %1$s: Google Rich Results product name. */
                      wp.i18n.__(
                        "Missing required properties. Your schema won't qualify for %1$s without these.",
                        'teil1-schema-manager'
                      ),
                      'Google Rich Results'
                    )}
                  </p>
                </div>
              </div>
              <div className="sp-flex sp-items-start sp-gap-3 sp-rounded-lg sp-bg-yellow-50 sp-p-3">
                <span className="sp-mt-0.5 sp-text-yellow-500">⚠️</span>
                <div>
                  <p className="sp-text-sm sp-font-medium sp-text-yellow-700">
                    {wp.i18n.__('Warnings', 'teil1-schema-manager')}
                  </p>
                  <p className="sp-text-xs sp-text-yellow-600">
                    {wp.i18n.__(
                      'Missing recommended properties. The schema is technically valid, but adding these improves your chances of rich result display.',
                      'teil1-schema-manager'
                    )}
                  </p>
                </div>
              </div>
              <div className="sp-flex sp-items-start sp-gap-3 sp-rounded-lg sp-bg-green-50 sp-p-3">
                <span className="sp-mt-0.5 sp-text-green-500">✅</span>
                <div>
                  <p className="sp-text-sm sp-font-medium sp-text-green-700">
                    {wp.i18n.__('Valid', 'teil1-schema-manager')}
                  </p>
                  <p className="sp-text-xs sp-text-green-600">
                    {wp.i18n.sprintf(
                      /* translators: %1$s: Google Rich Result product name. */
                      wp.i18n.__(
                        'All required and recommended properties are set. Maximum chance of %1$s display.',
                        'teil1-schema-manager'
                      ),
                      'Google Rich Result'
                    )}
                  </p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      {/* Variable Reference */}
      <div className="sp-rounded-xl sp-border sp-border-surface-3 sp-bg-white sp-shadow-bento">
        <div className="sp-border-b sp-border-surface-2 sp-px-6 sp-py-4">
          <h2 className="sp-text-base sp-font-semibold sp-text-ink-0">
            {wp.i18n.__('🏷️ Variable Reference', 'teil1-schema-manager')}
          </h2>
          <p className="sp-mt-1 sp-text-sm sp-text-ink-3">
            {wp.i18n.sprintf(
              /* translators: %1$s: JSON-LD data format name. */
              wp.i18n.__(
                'Use these tags in any property value. They are resolved to real data when the %1$s is rendered.',
                'teil1-schema-manager'
              ),
              'JSON-LD'
            )}
          </p>
        </div>
        <div className="sp-p-6 sp-space-y-6">
          {Object.entries(variables).map(([category, vars]) => {
            if (Object.keys(vars).length === 0) {
              return null;
            }
            const meta = categoryMeta[category] || { icon: '📋', title: category, desc: '' };
            return (
              <div key={category}>
                <div className="sp-mb-3 sp-flex sp-items-center sp-gap-2">
                  <span>{meta.icon}</span>
                  <div>
                    <h3 className="sp-text-sm sp-font-semibold sp-text-ink-0">{meta.title}</h3>
                    <p className="sp-text-xs sp-text-ink-3">{meta.desc}</p>
                  </div>
                </div>
                <div className="sp-overflow-hidden sp-rounded-lg sp-border sp-border-surface-2">
                  <table className="sp-w-full">
                    <thead>
                      <tr className="sp-bg-surface-1">
                        <th className="sp-px-4 sp-py-2 sp-text-left sp-text-2xs sp-font-semibold sp-uppercase sp-tracking-wider sp-text-ink-3">
                          {wp.i18n.__('Variable', 'teil1-schema-manager')}
                        </th>
                        <th className="sp-px-4 sp-py-2 sp-text-left sp-text-2xs sp-font-semibold sp-uppercase sp-tracking-wider sp-text-ink-3">
                          {wp.i18n.__('Description', 'teil1-schema-manager')}
                        </th>
                        <th className="sp-px-4 sp-py-2 sp-text-left sp-text-2xs sp-font-semibold sp-uppercase sp-tracking-wider sp-text-ink-3">
                          {wp.i18n.__('Example Output', 'teil1-schema-manager')}
                        </th>
                      </tr>
                    </thead>
                    <tbody>
                      {Object.entries(vars).map(([tag, desc]) => (
                        <tr key={tag} className="sp-border-t sp-border-surface-2">
                          <td className="sp-px-4 sp-py-2.5">
                            <code className="sp-rounded sp-bg-brand-50 sp-px-1.5 sp-py-0.5 sp-font-mono sp-text-xs sp-text-brand-700">
                              {`{{${tag}}}`}
                            </code>
                          </td>
                          <td className="sp-px-4 sp-py-2.5 sp-text-sm sp-text-ink-1">{desc}</td>
                          <td className="sp-px-4 sp-py-2.5 sp-text-xs sp-text-ink-3 sp-italic">{getExample(tag)}</td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>
              </div>
            );
          })}
        </div>
      </div>

      {/* Settings */}
      <div className="sp-rounded-xl sp-border sp-border-surface-3 sp-bg-white sp-shadow-bento">
        <div className="sp-border-b sp-border-surface-2 sp-px-6 sp-py-4">
          <h2 className="sp-text-base sp-font-semibold sp-text-ink-0">
            {wp.i18n.__('⚙️ Settings', 'teil1-schema-manager')}
          </h2>
        </div>
        <div className="sp-divide-y sp-divide-surface-2">
          <SettingToggle
            label={wp.i18n.__('Suppress conflicting schema output', 'teil1-schema-manager')}
            desc={wp.i18n.sprintf(
              /* translators: %1$s: JSON-LD format, %2$s: WooCommerce brand, %3$s: Product type, %4$s: Review type, %5$s: BreadcrumbList type, %6$s: WebSite type, %7$s: plugin name. */
              wp.i18n.__(
                "Removes %1$s emitted by other plugins — or by %2$s's own %3$s/%4$s/%5$s/%6$s markup — wherever it would duplicate what %7$s outputs. Leave this off unless you actually see duplicate structured data on your pages.",
                'teil1-schema-manager'
              ),
              'JSON-LD',
              'WooCommerce',
              'Product',
              'Review',
              'BreadcrumbList',
              'WebSite',
              'Teil1 Schema Manager'
            )}
            checked={!!settings?.suppress_conflicts}
            disabled={!settings || updateSettings.isPending}
            onChange={(value) => updateSettings.mutate({ suppress_conflicts: value })}
          />
          <SettingToggle
            label={wp.i18n.__('Delete all data on uninstall', 'teil1-schema-manager')}
            desc={wp.i18n.__(
              'When the plugin is deleted, drop its database tables, options, and per-page schemas. Off means your schemas survive a reinstall.',
              'teil1-schema-manager'
            )}
            checked={!!settings?.delete_data_on_uninstall}
            disabled={!settings || updateSettings.isPending}
            onChange={(value) => updateSettings.mutate({ delete_data_on_uninstall: value })}
          />
        </div>
      </div>
    </div>
  );
}

function SettingToggle({ label, desc, checked, disabled, onChange }) {
  return (
    <label className="sp-flex sp-cursor-pointer sp-items-start sp-gap-3 sp-px-6 sp-py-4">
      <input
        type="checkbox"
        checked={checked}
        disabled={disabled}
        onChange={(e) => onChange(e.target.checked)}
        className="sp-mt-0.5 sp-h-4 sp-w-4 sp-flex-shrink-0 sp-cursor-pointer sp-rounded sp-border-surface-3 sp-text-brand-600"
      />
      <span>
        <span className="sp-block sp-text-sm sp-font-medium sp-text-ink-0">{label}</span>
        <span className="sp-mt-0.5 sp-block sp-text-xs sp-leading-relaxed sp-text-ink-2">{desc}</span>
      </span>
    </label>
  );
}

function ConceptCard({ icon, title, desc }) {
  return (
    <div className="sp-rounded-lg sp-border sp-border-surface-2 sp-p-4">
      <div className="sp-mb-2 sp-flex sp-items-center sp-gap-2">
        <span className="sp-text-xl">{icon}</span>
        <h4 className="sp-text-sm sp-font-semibold sp-text-ink-0">{title}</h4>
      </div>
      <p className="sp-text-xs sp-leading-relaxed sp-text-ink-2">{desc}</p>
    </div>
  );
}

function Step({ n, text }) {
  return (
    <div className="sp-flex sp-items-start sp-gap-3">
      <span className="sp-flex sp-h-5 sp-w-5 sp-flex-shrink-0 sp-items-center sp-justify-center sp-rounded-full sp-bg-brand-100 sp-text-2xs sp-font-bold sp-text-brand-700">
        {n}
      </span>
      <p className="sp-text-sm sp-text-ink-1">{text}</p>
    </div>
  );
}

function getExample(tag) {
  const examples = {
    post_title: wp.i18n.__('My Blog Post Title', 'teil1-schema-manager'),
    post_excerpt: wp.i18n.__('A short summary of the post…', 'teil1-schema-manager'),
    post_content: wp.i18n.__('Full post content as plain text', 'teil1-schema-manager'),
    post_date: '2026-05-04T12:00:00+02:00',
    post_modified: '2026-05-04T14:30:00+02:00',
    post_url: 'https://example.com/my-post/',
    post_id: '42',
    post_slug: 'my-post',
    post_type: 'post',
    featured_image_url: 'https://example.com/wp-content/uploads/hero.jpg',
    featured_image_alt: wp.i18n.__('Hero image description', 'teil1-schema-manager'),
    author_name: 'Max Mustermann',
    author_url: 'https://example.com/author/max/',
    author_description: wp.i18n.sprintf(
      /* translators: %1$s: WordPress brand name. */
      wp.i18n.__('Senior %1$s Developer', 'teil1-schema-manager'),
      'WordPress'
    ),
    author_avatar_url: 'https://example.com/wp-content/uploads/author.jpg',
    site_name: wp.i18n.__('My Website', 'teil1-schema-manager'),
    site_url: 'https://example.com/',
    site_description: wp.i18n.__('Your site tagline from Settings → General', 'teil1-schema-manager'),
    site_logo: 'https://example.com/wp-content/uploads/logo.svg',
    site_language: 'en-US',
    primary_category: 'Marketing',
    primary_category_url: 'https://example.com/category/marketing/',
    categories: 'Marketing, SEO, Growth',
    tags: 'schema, json-ld, structured-data',
    'meta:{key}': wp.i18n.__('(any custom post meta value)', 'teil1-schema-manager'),
    product_price: '29.90',
    product_regular_price: '34.90',
    product_sale_price: '29.90',
    product_currency: 'EUR',
    product_sku: 'WP-PENNANT-01',
    product_availability: 'https://schema.org/InStock',
    product_rating: '4.5',
    product_review_count: '12',
    product_brand: 'Acme',
  };
  return examples[tag] || '—';
}
