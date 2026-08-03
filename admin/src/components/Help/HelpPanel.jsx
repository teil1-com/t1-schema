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
    post: { icon: '📄', title: 'Post / Page Variables', desc: 'Dynamic data from the current post or page' },
    author: { icon: '👤', title: 'Author Variables', desc: 'Data about the post author' },
    site: { icon: '🌐', title: 'Site Variables', desc: 'Global site information from WordPress settings' },
    taxonomy: { icon: '🏷️', title: 'Taxonomy Variables', desc: 'Categories, tags, and custom taxonomies' },
    meta: { icon: '🔧', title: 'Custom Meta Variables', desc: 'Access any post_meta field by key' },
    woocommerce: { icon: '🛒', title: 'WooCommerce Variables', desc: 'Live product data — only shown when WooCommerce is active' },
  };

  return (
    <div className="sp-space-y-6">
      {/* Quick Start Guide */}
      <div className="sp-rounded-xl sp-border sp-border-surface-3 sp-bg-white sp-shadow-bento">
        <div className="sp-border-b sp-border-surface-2 sp-px-6 sp-py-4">
          <h2 className="sp-text-base sp-font-semibold sp-text-ink-0">📖 How t1 Schema Works</h2>
        </div>
        <div className="sp-p-6 sp-space-y-6">
          {/* Concepts */}
          <div className="sp-grid sp-grid-cols-1 sp-gap-4 md:sp-grid-cols-2">
            <ConceptCard
              icon="🌍"
              title="Global Schemas"
              desc="Site-wide schemas (Organization, WebSite) that apply to every page automatically. Manage these in the Dashboard tab."
            />
            <ConceptCard
              icon="📄"
              title="Local Schemas"
              desc="Page-specific schemas (Article, Product, FAQ) that apply to a single post or page. Manage these in the Pages tab."
            />
            <ConceptCard
              icon="🔀"
              title="Override Logic"
              desc='If a local schema has the same @type as a global one, the local schema replaces the global one on that page. Toggle this per-schema with the "Override global" checkbox.'
            />
            <ConceptCard
              icon="🏷️"
              title="Dynamic Variables"
              desc='Use {{variable}} tags instead of hardcoded values. These resolve to real data at render time — e.g. {{post_title}} becomes the actual page title.'
            />
          </div>

          {/* Workflow */}
          <div>
            <h3 className="sp-mb-3 sp-text-sm sp-font-semibold sp-text-ink-0">Typical Workflow</h3>
            <div className="sp-space-y-2">
              <Step n="1" text="Set up global Organization and WebSite schemas in the Dashboard" />
              <Step n="2" text="Go to the Pages tab and find the post you want to add structured data to" />
              <Step n="3" text='Click "Add Schema" → select a type (e.g. Article, Product, FAQ)' />
              <Step n="4" text='Fill in properties using {{variables}} for dynamic data or plain text for static values' />
              <Step n="5" text="Check the Health panel on the right for missing required/recommended properties" />
              <Step n="6" text="Save → verify the JSON-LD output in your page source" />
            </div>
          </div>

          {/* Warning vs Error */}
          <div>
            <h3 className="sp-mb-3 sp-text-sm sp-font-semibold sp-text-ink-0">Understanding Health Status</h3>
            <div className="sp-space-y-2">
              <div className="sp-flex sp-items-start sp-gap-3 sp-rounded-lg sp-bg-red-50 sp-p-3">
                <span className="sp-mt-0.5 sp-text-red-500">🚨</span>
                <div>
                  <p className="sp-text-sm sp-font-medium sp-text-red-700">Errors</p>
                  <p className="sp-text-xs sp-text-red-600">Missing required properties. Your schema won't qualify for Google Rich Results without these.</p>
                </div>
              </div>
              <div className="sp-flex sp-items-start sp-gap-3 sp-rounded-lg sp-bg-yellow-50 sp-p-3">
                <span className="sp-mt-0.5 sp-text-yellow-500">⚠️</span>
                <div>
                  <p className="sp-text-sm sp-font-medium sp-text-yellow-700">Warnings</p>
                  <p className="sp-text-xs sp-text-yellow-600">Missing recommended properties. The schema is technically valid, but adding these improves your chances of rich result display.</p>
                </div>
              </div>
              <div className="sp-flex sp-items-start sp-gap-3 sp-rounded-lg sp-bg-green-50 sp-p-3">
                <span className="sp-mt-0.5 sp-text-green-500">✅</span>
                <div>
                  <p className="sp-text-sm sp-font-medium sp-text-green-700">Valid</p>
                  <p className="sp-text-xs sp-text-green-600">All required and recommended properties are set. Maximum chance of Google Rich Result display.</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      {/* Variable Reference */}
      <div className="sp-rounded-xl sp-border sp-border-surface-3 sp-bg-white sp-shadow-bento">
        <div className="sp-border-b sp-border-surface-2 sp-px-6 sp-py-4">
          <h2 className="sp-text-base sp-font-semibold sp-text-ink-0">🏷️ Variable Reference</h2>
          <p className="sp-mt-1 sp-text-sm sp-text-ink-3">
            Use these tags in any property value. They are resolved to real data when the JSON-LD is rendered.
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
                        <th className="sp-px-4 sp-py-2 sp-text-left sp-text-2xs sp-font-semibold sp-uppercase sp-tracking-wider sp-text-ink-3">Variable</th>
                        <th className="sp-px-4 sp-py-2 sp-text-left sp-text-2xs sp-font-semibold sp-uppercase sp-tracking-wider sp-text-ink-3">Description</th>
                        <th className="sp-px-4 sp-py-2 sp-text-left sp-text-2xs sp-font-semibold sp-uppercase sp-tracking-wider sp-text-ink-3">Example Output</th>
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
          <h2 className="sp-text-base sp-font-semibold sp-text-ink-0">⚙️ Settings</h2>
        </div>
        <div className="sp-divide-y sp-divide-surface-2">
          <SettingToggle
            label="Suppress conflicting schema output"
            desc="Removes JSON-LD emitted by other plugins — or by WooCommerce's own Product/Review/BreadcrumbList/WebSite markup — wherever it would duplicate what t1 Schema outputs. Leave this off unless you actually see duplicate structured data on your pages."
            checked={!!settings?.suppress_conflicts}
            disabled={!settings || updateSettings.isPending}
            onChange={(value) => updateSettings.mutate({ suppress_conflicts: value })}
          />
          <SettingToggle
            label="Delete all data on uninstall"
            desc="When the plugin is deleted, drop its database tables, options, and per-page schemas. Off means your schemas survive a reinstall."
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
    post_title: 'My Blog Post Title',
    post_excerpt: 'A short summary of the post…',
    post_content: 'Full post content as plain text',
    post_date: '2026-05-04T12:00:00+02:00',
    post_modified: '2026-05-04T14:30:00+02:00',
    post_url: 'https://example.com/my-post/',
    post_id: '42',
    post_slug: 'my-post',
    post_type: 'post',
    featured_image_url: 'https://example.com/wp-content/uploads/hero.jpg',
    featured_image_alt: 'Hero image description',
    author_name: 'Max Mustermann',
    author_url: 'https://example.com/author/max/',
    author_description: 'Senior WordPress Developer',
    author_avatar_url: 'https://secure.gravatar.com/avatar/…',
    site_name: 'My Website',
    site_url: 'https://example.com/',
    site_description: 'Your site tagline from Settings → General',
    site_logo: 'https://example.com/wp-content/uploads/logo.svg',
    site_language: 'en-US',
    primary_category: 'Marketing',
    primary_category_url: 'https://example.com/category/marketing/',
    categories: 'Marketing, SEO, Growth',
    tags: 'schema, json-ld, structured-data',
    'meta:{key}': '(any custom post meta value)',
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
