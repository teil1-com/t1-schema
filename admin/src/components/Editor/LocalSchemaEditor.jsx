import React, { useState, useEffect, useMemo } from 'react';
import { useLocalSchemas, useUpdateLocal, useSchemaTypes, usePostHealth, useCustomVariables } from '../../hooks/useSchema';
import ObjectEditor from './ObjectEditor';
import TypeSelector from './TypeSelector';
import JsonImporter from './JsonImporter';
import VariablePicker from './VariablePicker';
import HealthDetail from '../Dashboard/HealthDetail';

/**
 * LocalSchemaEditor — manage schemas for an individual post/page.
 *
 * Supports PropertyCards, VariablePicker, JsonImporter, and displays
 * both registry-defined and custom properties for full schema control.
 */
export default function LocalSchemaEditor({ post, onBack }) {
  const { data: localData, isLoading } = useLocalSchemas(post?.id);
  const { data: typeDefs = {} } = useSchemaTypes();
  const { data: customVars = {} } = useCustomVariables();
  const { data: healthData } = usePostHealth(post?.id);
  const updateMutation = useUpdateLocal();

  const [schemas, setSchemas] = useState([]);
  const [activeIndex, setActiveIndex] = useState(0);
  const [activeVariableField, setActiveVariableField] = useState(null);
  const [showImporter, setShowImporter] = useState(false);
  const [saving, setSaving] = useState(false);
  const [saveSuccess, setSaveSuccess] = useState(false);

  // Load schemas from API.
  useEffect(() => {
    if (localData?.schemas) {
      setSchemas(localData.schemas);
    }
  }, [localData]);

  const activeSchema = schemas[activeIndex] || null;
  const activeType = activeSchema?.['@type'] || '';
  const primaryType = Array.isArray(activeType) ? activeType[0] || '' : activeType;
  const typeDef = typeDefs[primaryType] || null;
  const registryProperties = typeDef?.properties || {};

  // Merge: registry properties + custom properties already in the schema.
  const allPropertyKeys = useMemo(() => {
    if (!activeSchema) return [];
    const keys = new Set(Object.keys(registryProperties));
    for (const k of Object.keys(activeSchema)) {
      if (!k.startsWith('@') && !k.startsWith('_')) keys.add(k);
    }
    return [...keys];
  }, [registryProperties, activeSchema]);

  // Get health for active schema.
  const activeHealth = healthData?.schemas?.find((s) => s.index === activeIndex)?.health;

  const handlePropertyChange = (key, value) => {
    setSchemas((prev) => {
      const next = [...prev];
      next[activeIndex] = { ...next[activeIndex], [key]: value };
      return next;
    });
  };

  const handleRemoveProperty = (key) => {
    setSchemas((prev) => {
      const next = [...prev];
      const schema = { ...next[activeIndex] };
      delete schema[key];
      next[activeIndex] = schema;
      return next;
    });
  };

  const handleTypeChange = (type) => {
    setSchemas((prev) => {
      const next = [...prev];
      next[activeIndex] = {
        ...next[activeIndex],
        '@context': 'https://schema.org',
        '@type': type,
        '_t1schema_meta': next[activeIndex]['_t1schema_meta'] || { override_global: true, status: 'active' },
      };
      return next;
    });
  };

  const handleAddSchema = () => {
    setSchemas((prev) => [
      ...prev,
      {
        '@context': 'https://schema.org',
        '@type': '',
        '_t1schema_meta': { override_global: true, status: 'active' },
      },
    ]);
    setActiveIndex(schemas.length);
    setShowImporter(false);
  };

  const handleDeleteSchema = (index) => {
    if (!window.confirm(wp.i18n.__('Remove this schema from this page?', 'teil1-schema-manager'))) return;
    setSchemas((prev) => prev.filter((_, i) => i !== index));
    setActiveIndex(0);
  };

  const handleImport = (imported) => {
    if (imported['@type']) {
      const newType = imported['@type'];
      setSchemas((prev) => {
        const next = [...prev];
        next[activeIndex] = {
          ...imported,
          '_t1schema_meta': next[activeIndex]?.['_t1schema_meta'] || { override_global: true, status: 'active' },
        };
        return next;
      });
    }
    setShowImporter(false);
  };

  const handleSave = async () => {
    setSaving(true);
    setSaveSuccess(false);
    try {
      await updateMutation.mutateAsync({ postId: post.id, schemas });
      setSaveSuccess(true);
      setTimeout(() => setSaveSuccess(false), 2000);
    } catch (err) {
      alert(
        wp.i18n.sprintf(
          /* translators: %1$s: Error message returned while saving. */
          wp.i18n.__('Save failed: %1$s', 'teil1-schema-manager'),
          err.message
        )
      );
    } finally {
      setSaving(false);
    }
  };

  const handleInsertVariable = (variable) => {
    if (activeVariableField) {
      handlePropertyChange(activeVariableField, `{{${variable}}}`);
      setActiveVariableField(null);
    }
  };

  // Count user-set properties.
  const propCount = activeSchema
    ? Object.keys(activeSchema).filter(k => !k.startsWith('@') && !k.startsWith('_')).length
    : 0;

  if (isLoading) {
    return (
      <div
        className="sp-flex sp-items-center sp-justify-center sp-py-20"
        role="status"
        aria-label={wp.i18n.__('Loading schemas…', 'teil1-schema-manager')}
      >
        <div className="sp-h-6 sp-w-6 sp-animate-spin sp-rounded-full sp-border-2 sp-border-brand-200 sp-border-t-brand-600" />
      </div>
    );
  }

  return (
    <div className="sp-space-y-4">
      {/* Post Context Bar */}
      <div className="sp-flex sp-items-center sp-gap-4 sp-rounded-xl sp-border sp-border-surface-3 sp-bg-white sp-px-5 sp-py-4 sp-shadow-bento">
        <div className="sp-flex sp-items-center sp-gap-2">
          <span className={`sp-rounded sp-px-2 sp-py-0.5 sp-text-2xs sp-font-semibold sp-uppercase sp-tracking-wider ${
            post.post_type === 'page' ? 'sp-bg-purple-100 sp-text-purple-600' : 'sp-bg-blue-100 sp-text-blue-600'
          }`}>
            {post.post_type}
          </span>
          <span className="sp-rounded sp-bg-surface-2 sp-px-2 sp-py-0.5 sp-text-2xs sp-font-semibold sp-text-ink-3">
            {
              wp.i18n.sprintf(
                /* translators: %1$s: Post ID. */
                wp.i18n.__('ID: %1$s', 'teil1-schema-manager'),
                post.id
              )
            }
          </span>
        </div>
        <div className="sp-flex-1">
          <h2 className="sp-text-base sp-font-semibold sp-text-ink-0">{post.title || localData?.title || '…'}</h2>
          <p className="sp-text-xs sp-text-ink-3">{post.url}</p>
        </div>
        <a
          href={post.url}
          target="_blank"
          rel="noopener noreferrer"
          className="sp-rounded-lg sp-border sp-border-surface-3 sp-px-3 sp-py-1.5 sp-text-xs sp-font-medium sp-text-ink-2 sp-transition-colors hover:sp-bg-surface-1"
        >
          {wp.i18n.__('View page ↗', 'teil1-schema-manager')}
        </a>
      </div>

      <div className="sp-grid sp-grid-cols-1 sp-gap-4 lg:sp-grid-cols-3">
        {/* Schema Tabs + Editor */}
        <div className="lg:sp-col-span-2 sp-space-y-4">
          {/* Schema tabs */}
          <div className="sp-flex sp-items-center sp-gap-2 sp-flex-wrap">
            {schemas.map((s, i) => (
              <button
                key={i}
                onClick={() => { setActiveIndex(i); setShowImporter(false); }}
                className={`sp-group sp-flex sp-items-center sp-gap-1.5 sp-rounded-lg sp-px-3 sp-py-1.5 sp-text-sm sp-font-medium sp-transition-all ${
                  activeIndex === i
                    ? 'sp-bg-brand-600 sp-text-white sp-shadow-sm'
                    : 'sp-border sp-border-surface-3 sp-bg-white sp-text-ink-2 hover:sp-bg-surface-1'
                }`}
              >
                {(Array.isArray(s['@type']) ? s['@type'].join(' + ') : s['@type']) || (
                  wp.i18n.sprintf(
                    /* translators: %1$d: One-based schema number. */
                    wp.i18n.__('Schema %1$d', 'teil1-schema-manager'),
                    i + 1
                  )
                )}
                {schemas.length > 1 && (
                  <span
                    onClick={(e) => { e.stopPropagation(); handleDeleteSchema(i); }}
                    className={`sp-ml-1 sp-rounded sp-p-0.5 sp-transition-colors ${
                      activeIndex === i
                        ? 'sp-text-white/60 hover:sp-text-white'
                        : 'sp-text-ink-4 hover:sp-text-red-500'
                    }`}
                  >
                    ×
                  </span>
                )}
              </button>
            ))}
            <button
              onClick={handleAddSchema}
              className="sp-rounded-lg sp-border sp-border-dashed sp-border-surface-3 sp-px-3 sp-py-1.5 sp-text-sm sp-font-medium sp-text-ink-3 sp-transition-colors hover:sp-border-brand-300 hover:sp-text-brand-600"
            >
              {wp.i18n.__('+ Add Schema', 'teil1-schema-manager')}
            </button>
          </div>

          {/* Active Schema Editor */}
          {activeSchema ? (
            <div className="sp-rounded-xl sp-border sp-border-surface-3 sp-bg-white sp-shadow-bento">
              {/* Editor Header */}
              <div className="sp-flex sp-items-center sp-justify-between sp-border-b sp-border-surface-2 sp-px-6 sp-py-4">
                <h3 className="sp-text-sm sp-font-semibold sp-text-ink-0">
                  {primaryType
                    ? wp.i18n.sprintf(
                      /* translators: %1$s: Schema.org type name. */
                      wp.i18n.__('Editing: %1$s', 'teil1-schema-manager'),
                      primaryType
                    )
                    : wp.i18n.__('Configure Schema', 'teil1-schema-manager')}
                </h3>
                <button
                  onClick={() => setShowImporter(!showImporter)}
                  className="sp-rounded-lg sp-border sp-border-surface-3 sp-px-3 sp-py-1.5 sp-text-xs sp-font-medium sp-text-ink-2 sp-transition-colors hover:sp-bg-surface-1"
                >
                  {
                    showImporter
                      ? wp.i18n.__('Visual Editor', 'teil1-schema-manager')
                      : wp.i18n.__('{ } Import JSON', 'teil1-schema-manager')
                  }
                </button>
              </div>

              {showImporter ? (
                <div className="sp-p-6">
                  <JsonImporter onImport={handleImport} />
                </div>
              ) : (
                <div className="sp-p-6 sp-space-y-4">
                  {/* Type Selector */}
                  <TypeSelector value={activeType} onChange={handleTypeChange} types={typeDefs} />

                  {/* Override toggle */}
                  <label className="sp-flex sp-items-center sp-gap-2 sp-rounded-lg sp-bg-surface-1 sp-px-3 sp-py-2">
                    <input
                      type="checkbox"
                      checked={activeSchema['_t1schema_meta']?.override_global ?? true}
                      onChange={(e) => {
                        setSchemas((prev) => {
                          const next = [...prev];
                          next[activeIndex] = {
                            ...next[activeIndex],
                            '_t1schema_meta': {
                              ...(next[activeIndex]['_t1schema_meta'] || {}),
                              override_global: e.target.checked,
                            },
                          };
                          return next;
                        });
                      }}
                      className="sp-rounded sp-border-surface-3"
                    />
                    <span className="sp-text-xs sp-text-ink-2">
                      {wp.i18n.__('Override global', 'teil1-schema-manager')}{' '}
                      <strong>{primaryType}</strong>{' '}
                      {wp.i18n.__('schema on this page', 'teil1-schema-manager')}
                    </span>
                  </label>

                  {/* Properties */}
                  {primaryType && (
                    <ObjectEditor
                      type={primaryType}
                      data={activeSchema}
                      onChange={(newData) => {
                        setSchemas(prev => {
                          const next = [...prev];
                          next[activeIndex] = { ...newData, '@context': 'https://schema.org', '@type': activeType };
                          return next;
                        });
                      }}
                      typeDefs={typeDefs}
                      activeVariableField={activeVariableField}
                      setActiveVariableField={setActiveVariableField}
                    />
                  )}

                  {!primaryType && (
                    <div className="sp-py-8 sp-text-center">
                      <span className="sp-text-3xl">🎯</span>
                      <p className="sp-mt-2 sp-text-sm sp-text-ink-3">
                        {wp.i18n.__('Select a type above to start building', 'teil1-schema-manager')}
                      </p>
                    </div>
                  )}
                </div>
              )}

              {/* Save Bar */}
              <div className="sp-flex sp-items-center sp-justify-between sp-border-t sp-border-surface-2 sp-bg-surface-1 sp-px-6 sp-py-3">
                <div className="sp-text-xs sp-text-ink-3">
                  {
                    wp.i18n.sprintf(
                      /* translators: %1$d: Number of schemas on the current page. */
                      wp.i18n._n(
                        '%1$d schema on this page',
                        '%1$d schemas on this page',
                        schemas.length,
                        'teil1-schema-manager'
                      ),
                      schemas.length
                    )
                  }
                  {' · '}
                  {
                    wp.i18n.sprintf(
                      /* translators: %1$d: Number of properties set. */
                      wp.i18n._n(
                        '%1$d property set',
                        '%1$d properties set',
                        propCount,
                        'teil1-schema-manager'
                      ),
                      propCount
                    )
                  }
                </div>
                <div className="sp-flex sp-items-center sp-gap-2">
                  {saveSuccess && (
                    <span className="sp-animate-fade-in sp-text-xs sp-font-medium sp-text-green-600">
                      {wp.i18n.__('✓ Saved', 'teil1-schema-manager')}
                    </span>
                  )}
                  <button
                    onClick={handleSave}
                    disabled={saving}
                    className="sp-rounded-lg sp-bg-brand-600 sp-px-4 sp-py-1.5 sp-text-sm sp-font-medium sp-text-white sp-transition-all hover:sp-bg-brand-700 disabled:sp-opacity-50"
                  >
                    {
                      saving
                        ? wp.i18n.__('Saving…', 'teil1-schema-manager')
                        : wp.i18n.__('Save Changes', 'teil1-schema-manager')
                    }
                  </button>
                </div>
              </div>
            </div>
          ) : (
            <div className="sp-flex sp-flex-col sp-items-center sp-justify-center sp-rounded-xl sp-border sp-border-dashed sp-border-surface-3 sp-bg-white/50 sp-py-16">
              <span className="sp-mb-3 sp-text-3xl">📝</span>
              <p className="sp-mb-4 sp-text-sm sp-text-ink-3">
                {wp.i18n.__('No local schemas for this page yet', 'teil1-schema-manager')}
              </p>
              <button
                onClick={handleAddSchema}
                className="sp-rounded-lg sp-bg-brand-600 sp-px-4 sp-py-2 sp-text-sm sp-font-medium sp-text-white sp-transition-colors hover:sp-bg-brand-700"
              >
                {wp.i18n.__('+ Add First Schema', 'teil1-schema-manager')}
              </button>
            </div>
          )}
        </div>

        {/* Sidebar */}
        <div className="sp-space-y-4">
          {/* Variable Picker */}
          {activeVariableField && (
            <VariablePicker
              onSelect={handleInsertVariable}
              onClose={() => setActiveVariableField(null)}
              targetField={activeVariableField}
            />
          )}

          {/* Health Detail */}
          {activeHealth && (
            <HealthDetail health={activeHealth} type={primaryType} />
          )}

          {/* JSON Preview */}
          <JsonPreview schema={activeSchema} customVars={customVars} />
        </div>
      </div>
    </div>
  );
}

/**
 * JSON-LD preview panel with copy-to-clipboard button.
 */
function JsonPreview({ schema, customVars = {} }) {
  const [copied, setCopied] = useState(false);

  const resolveCustomVars = (obj) => {
    if (!customVars || typeof customVars !== 'object') return JSON.stringify(obj, null, 2);
    const raw = JSON.stringify(obj, null, 2);
    return raw.replace(/\{\{custom\.([a-z0-9_]+)\}\}/g, (match, key) => {
      return key in customVars ? customVars[key] : match;
    });
  };

  const json = resolveCustomVars(schema || {});

  const handleCopy = async () => {
    try {
      await navigator.clipboard.writeText(json);
      setCopied(true);
      setTimeout(() => setCopied(false), 1500);
    } catch {
      // Fallback for older browsers / non-HTTPS contexts
      const ta = document.createElement('textarea');
      ta.value = json;
      document.body.appendChild(ta);
      ta.select();
      document.execCommand('copy');
      document.body.removeChild(ta);
      setCopied(true);
      setTimeout(() => setCopied(false), 1500);
    }
  };

  return (
    <div className="sp-rounded-xl sp-border sp-border-surface-3 sp-bg-white sp-shadow-bento">
      <div className="sp-flex sp-items-center sp-justify-between sp-border-b sp-border-surface-2 sp-px-4 sp-py-3">
        <h3 className="sp-text-xs sp-font-semibold sp-uppercase sp-tracking-wider sp-text-ink-3">
          {wp.i18n.__('JSON-LD Output', 'teil1-schema-manager')}
        </h3>
        <button
          onClick={handleCopy}
          className={`sp-flex sp-items-center sp-gap-1.5 sp-rounded-lg sp-border sp-px-2.5 sp-py-1 sp-text-2xs sp-font-medium sp-transition-all ${
            copied
              ? 'sp-border-green-300 sp-bg-green-50 sp-text-green-600'
              : 'sp-border-surface-3 sp-text-ink-3 hover:sp-bg-surface-1 hover:sp-text-ink-1'
          }`}
        >
          {copied ? (
            <>
              <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round">
                <path d="M20 6 9 17l-5-5" />
              </svg>
              {wp.i18n.__('Copied', 'teil1-schema-manager')}
            </>
          ) : (
            <>
              <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                <rect x="9" y="9" width="13" height="13" rx="2" ry="2" />
                <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1" />
              </svg>
              {wp.i18n._x('Copy', 'JSON preview button', 'teil1-schema-manager')}
            </>
          )}
        </button>
      </div>
      <pre className="sp-max-h-64 sp-overflow-auto sp-p-4 sp-font-mono sp-text-xs sp-text-ink-2">
        {json}
      </pre>
    </div>
  );
}
