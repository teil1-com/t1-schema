import React, { useState, useEffect, useMemo } from 'react';
import { useCreateRule, useUpdateRule, useContexts, useSchemaTypes, useCustomVariables } from '../../hooks/useSchema';
import ObjectEditor from '../Editor/ObjectEditor';
import VariablePicker from '../Editor/VariablePicker';
import JsonImporter from '../Editor/JsonImporter';
import HealthDetail from '../Dashboard/HealthDetail';

/**
 * Resolve {{custom.*}} variable tags in a schema object for preview display.
 * Only replaces custom.* tags — post/site/archive variables remain as-is
 * since they are context-dependent and can't be resolved without a specific post.
 */
function resolveCustomVariables(schema, customVars) {
  if (!customVars || typeof customVars !== 'object') return JSON.stringify(schema, null, 2);
  const json = JSON.stringify(schema, null, 2);
  return json.replace(/\{\{custom\.([a-z0-9_]+)\}\}/g, (match, key) => {
    return key in customVars ? customVars[key] : match;
  });
}

/**
 * RuleBuilder — full-featured rule editor with structured property editing.
 *
 * Reuses PropertyCard, VariablePicker, HealthDetail and JsonImporter
 * from the global SchemaBuilder for a consistent editing experience.
 */
export default function RuleBuilder({ rule, onBack }) {
  const isNew = !rule || rule.isNew;
  const { data: contexts = [] } = useContexts();
  const { data: typeDefs = {} } = useSchemaTypes();
  const { data: customVars = {} } = useCustomVariables();
  const createMutation = useCreateRule();
  const updateMutation = useUpdateRule();

  const [ruleName, setRuleName] = useState(isNew ? '' : (rule?.rule_name || ''));
  const [schemaType, setSchemaType] = useState(isNew ? '' : (rule?.schema_type || ''));
  const [conditions, setConditions] = useState(isNew ? [{ type: '', value: '' }] : (rule?.conditions || []));
  const [priority, setPriority] = useState(isNew ? 10 : (rule?.priority || 10));
  const [saving, setSaving] = useState(false);
  const [saveSuccess, setSaveSuccess] = useState(false);
  const [showImporter, setShowImporter] = useState(false);
  const [activeVariableField, setActiveVariableField] = useState(null);

  // Initialize schema data — strip @context/@type so we only edit properties.
  const initData = useMemo(() => {
    if (isNew) return {};
    const d = { ...(rule?.schema_data || {}) };
    delete d['@context'];
    delete d['@type'];
    return d;
  }, []);
  const [schemaData, setSchemaData] = useState(initData);

  // Group contexts for dropdown.
  const grouped = contexts.reduce((acc, ctx) => {
    const g = ctx.group || wp.i18n._x('Other', 'context group label', 'teil1-schema-manager');
    if (!acc[g]) acc[g] = [];
    acc[g].push(ctx);
    return acc;
  }, {});

  const typeNames = Object.keys(typeDefs).sort();
  const typeDef = typeDefs[schemaType] || null;
  const registryProperties = typeDef?.properties || {};

  // Merge: show registry properties + any extra keys already in the saved data.
  const allPropertyKeys = useMemo(() => {
    const keys = new Set(Object.keys(registryProperties));
    for (const k of Object.keys(schemaData)) {
      if (!k.startsWith('@') && !k.startsWith('_')) keys.add(k);
    }
    return [...keys];
  }, [registryProperties, schemaData]);

  // ── Conditions logic ──────────────────────────────────────────────────

  const addCondition = () => setConditions([...conditions, { type: '', value: '' }]);
  const removeCondition = (i) => setConditions(conditions.filter((_, idx) => idx !== i));

  const handleDataChange = (newData) => {
    setSchemaData({ ...newData, '@context': 'https://schema.org', '@type': schemaType });
  };

  const handleImport = (imported) => {
    if (imported['@type']) {
      setSchemaType(typeof imported['@type'] === 'string' ? imported['@type'] : imported['@type'][0] || '');
    }
    const d = { ...imported };
    delete d['@context'];
    delete d['@type'];
    setSchemaData(d);
    setShowImporter(false);
  };

  const handleInsertVariable = (variable) => {
    if (activeVariableField) {
      handleDataChange({ ...schemaData, [activeVariableField]: `{{${variable}}}` });
      setActiveVariableField(null);
    }
  };

  // ── Save ───────────────────────────────────────────────────────────────

  const handleSave = async () => {
    setSaving(true);
    setSaveSuccess(false);
    try {
      const payload = {
        rule_name: ruleName,
        schema_type: schemaType,
        schema_data: { '@context': 'https://schema.org', '@type': schemaType, ...schemaData },
        conditions: conditions.filter(c => c.type),
        priority,
        status: 'active',
      };

      if (isNew) {
        await createMutation.mutateAsync(payload);
      } else {
        await updateMutation.mutateAsync({ id: rule.id, ...payload });
      }
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

  // ── Live health validation ─────────────────────────────────────────────

  const fullSchema = { '@context': 'https://schema.org', '@type': schemaType, ...schemaData };

  const liveHealth = useMemo(() => {
    if (!schemaType || !typeDef) return null;
    const errors = [];
    const warnings = [];
    for (const [key, def] of Object.entries(registryProperties)) {
      const val = schemaData[key];
      const empty = val === undefined || val === '' || val === null;
      if (def.required && empty) {
        errors.push(
          wp.i18n.sprintf(
            /* translators: %1$s: Schema.org property identifier, %2$s: Schema.org type identifier. */
            wp.i18n.__("Missing required: '%1$s' for %2$s.", 'teil1-schema-manager'),
            key,
            schemaType
          )
        );
      } else if (def.recommended && empty) {
        warnings.push(
          wp.i18n.sprintf(
            /* translators: %1$s: Schema.org property identifier, %2$s: Schema.org type identifier. */
            wp.i18n.__("Missing recommended: '%1$s' for %2$s.", 'teil1-schema-manager'),
            key,
            schemaType
          )
        );
      }
    }
    return { valid: errors.length === 0, errors, warnings };
  }, [schemaData, schemaType, typeDef, registryProperties]);

  return (
    <div className="sp-space-y-4">
      {/* Back button */}
      <button
        onClick={onBack}
        className="sp-flex sp-items-center sp-gap-1 sp-text-sm sp-text-ink-3 sp-transition-colors hover:sp-text-ink-0"
      >
        {wp.i18n.__('← Back to Rules', 'teil1-schema-manager')}
      </button>

      <div className="sp-grid sp-grid-cols-1 sp-gap-6 lg:sp-grid-cols-3">
        {/* ════════════════════════════════════════════════════════════════
            Main Editor (2/3 width)
            ════════════════════════════════════════════════════════════ */}
        <div className="lg:sp-col-span-2">
          <div className="sp-rounded-xl sp-border sp-border-surface-3 sp-bg-white sp-shadow-bento">
            {/* Header */}
            <div className="sp-flex sp-items-center sp-justify-between sp-border-b sp-border-surface-2 sp-px-6 sp-py-4">
              <h2 className="sp-text-base sp-font-semibold sp-text-ink-0">
                {isNew
                  ? wp.i18n.__('New Schema Rule', 'teil1-schema-manager')
                  : wp.i18n.sprintf(
                    /* translators: %1$s: Rule name. */
                    wp.i18n.__('Edit Rule: %1$s', 'teil1-schema-manager'),
                    rule.rule_name
                  )}
              </h2>
              <button
                onClick={() => setShowImporter(!showImporter)}
                className="sp-rounded-lg sp-border sp-border-surface-3 sp-px-3 sp-py-1.5 sp-text-xs sp-font-medium sp-text-ink-2 sp-transition-colors hover:sp-bg-surface-1"
              >
                {showImporter
                  ? wp.i18n.__('Visual Editor', 'teil1-schema-manager')
                  : wp.i18n.__('{ } Import JSON', 'teil1-schema-manager')}
              </button>
            </div>

            {showImporter ? (
              <div className="sp-p-6">
                <JsonImporter onImport={handleImport} />
              </div>
            ) : (
              <div className="sp-p-6 sp-space-y-6">
                {/* ── Rule Metadata ─────────────────────────────────── */}
                <div className="sp-rounded-lg sp-border sp-border-surface-2 sp-bg-surface-1/50 sp-p-4 sp-space-y-4">
                  <h3 className="sp-text-xs sp-font-semibold sp-text-ink-2 sp-uppercase sp-tracking-wider">
                    {wp.i18n.__('Rule Configuration', 'teil1-schema-manager')}
                  </h3>

                  {/* Rule Name */}
                  <div>
                    <label className="sp-block sp-text-xs sp-font-medium sp-text-ink-3 sp-mb-1">
                      {wp.i18n.__('Rule Name', 'teil1-schema-manager')}{' '}
                      <span className="sp-text-ink-4">
                        {wp.i18n.__('(optional)', 'teil1-schema-manager')}
                      </span>
                    </label>
                    <input
                      type="text"
                      value={ruleName}
                      onChange={(e) => setRuleName(e.target.value)}
                      placeholder={wp.i18n.__('Auto-generated from conditions…', 'teil1-schema-manager')}
                      className="sp-w-full sp-rounded-lg sp-border sp-border-surface-3 sp-bg-white sp-px-3 sp-py-2 sp-text-sm sp-outline-none focus:sp-border-brand-400 focus:sp-ring-1 focus:sp-ring-brand-200"
                    />
                  </div>

                  {/* Conditions */}
                  <div>
                    <div className="sp-flex sp-items-center sp-justify-between sp-mb-2">
                      <label className="sp-text-xs sp-font-medium sp-text-ink-3">
                        {wp.i18n.__('Conditions — "Apply this schema when…"', 'teil1-schema-manager')}
                      </label>
                      <button
                        onClick={addCondition}
                        className="sp-rounded-lg sp-border sp-border-dashed sp-border-brand-300 sp-px-2.5 sp-py-1 sp-text-xs sp-font-medium sp-text-brand-600 sp-transition-colors hover:sp-bg-brand-50"
                      >
                        {wp.i18n.__('+ Add', 'teil1-schema-manager')}
                      </button>
                    </div>
                    <div className="sp-space-y-2">
                      {conditions.map((cond, i) => (
                        <div key={i} className="sp-flex sp-items-center sp-gap-2">
                          <select
                            value={`${cond.type}${cond.value ? '::' + cond.value : ''}`}
                            onChange={(e) => {
                              const [type, value] = e.target.value.split('::');
                              const next = [...conditions];
                              next[i] = { type, value: value || '' };
                              setConditions(next);
                            }}
                            className="sp-flex-1 sp-rounded-lg sp-border sp-border-surface-3 sp-bg-white sp-px-3 sp-py-2 sp-text-sm sp-outline-none"
                          >
                            <option value="">
                              {wp.i18n.__('Select condition…', 'teil1-schema-manager')}
                            </option>
                            {Object.entries(grouped).map(([group, items]) => (
                              <optgroup key={group} label={group}>
                                {items.map((ctx, ci) => (
                                  <option key={ci} value={`${ctx.type}${ctx.value ? '::' + ctx.value : ''}`}>
                                    {ctx.label}
                                  </option>
                                ))}
                              </optgroup>
                            ))}
                          </select>
                          {conditions.length > 1 && (
                            <button
                              onClick={() => removeCondition(i)}
                              aria-label={wp.i18n.__('Remove condition', 'teil1-schema-manager')}
                              className="sp-rounded-lg sp-border sp-border-surface-3 sp-px-2 sp-py-1.5 sp-text-xs sp-text-ink-4 sp-transition-colors hover:sp-bg-red-50 hover:sp-text-red-600"
                            >
                              ×
                            </button>
                          )}
                        </div>
                      ))}
                    </div>
                    {conditions.length > 1 && (
                      <p className="sp-mt-2 sp-text-2xs sp-text-ink-4">
                        {wp.i18n.__('Multiple conditions use AND logic — all must match.', 'teil1-schema-manager')}
                      </p>
                    )}
                  </div>

                  {/* Priority */}
                  <div className="sp-flex sp-items-center sp-gap-3">
                    <label className="sp-text-xs sp-font-medium sp-text-ink-3">
                      {wp.i18n.__('Priority:', 'teil1-schema-manager')}
                    </label>
                    <input
                      type="number"
                      value={priority}
                      onChange={(e) => setPriority(parseInt(e.target.value) || 10)}
                      min="1" max="100"
                      className="sp-w-16 sp-rounded sp-border sp-border-surface-3 sp-bg-white sp-px-2 sp-py-1 sp-text-xs sp-text-center sp-outline-none"
                    />
                    <span className="sp-text-2xs sp-text-ink-4">
                      {wp.i18n.__('Lower = higher priority', 'teil1-schema-manager')}
                    </span>
                  </div>
                </div>

                {/* ── Schema Type Selector ─────────────────────────── */}
                <div>
                  <label className="sp-block sp-text-xs sp-font-semibold sp-text-ink-2 sp-mb-1.5 sp-uppercase sp-tracking-wider">
                    {wp.i18n.__('Schema Type', 'teil1-schema-manager')}
                  </label>
                  <select
                    value={schemaType}
                    onChange={(e) => setSchemaType(e.target.value)}
                    className="sp-w-full sp-rounded-lg sp-border sp-border-surface-3 sp-bg-white sp-px-3 sp-py-2 sp-text-sm sp-outline-none focus:sp-border-brand-400"
                  >
                    <option value="">{wp.i18n.__('Select type…', 'teil1-schema-manager')}</option>
                    {typeNames.map((t) => (
                      <option key={t} value={t}>{t}</option>
                    ))}
                  </select>
                </div>

                {/* ── Properties (PropertyCard) ────────────────────── */}
                {schemaType && allPropertyKeys.length > 0 && (
                  <div className="sp-space-y-3">
                    <div className="sp-flex sp-items-center sp-justify-between">
                      <h3 className="sp-text-sm sp-font-semibold sp-text-ink-1">
                        {wp.i18n.__('Properties', 'teil1-schema-manager')}
                      </h3>
                      <span className="sp-text-2xs sp-text-ink-3">
                        {wp.i18n.sprintf(
                          /* translators: %1$d: Number of properties set, %2$d: Total number of properties. */
                          wp.i18n._n(
                            '%1$d / %2$d property set',
                            '%1$d / %2$d properties set',
                            Object.keys(schemaData).filter(k => !k.startsWith('@') && !k.startsWith('_')).length,
                            'teil1-schema-manager'
                          ),
                          Object.keys(schemaData).filter(k => !k.startsWith('@') && !k.startsWith('_')).length,
                          allPropertyKeys.length
                        )}
                      </span>
                    </div>

                    <ObjectEditor
                      type={schemaType}
                      data={schemaData}
                      onChange={handleDataChange}
                      typeDefs={typeDefs}
                      activeVariableField={activeVariableField}
                      setActiveVariableField={setActiveVariableField}
                    />
                  </div>
                )}

                {!schemaType && (
                  <div className="sp-flex sp-flex-col sp-items-center sp-justify-center sp-py-12 sp-text-center">
                    <span className="sp-mb-3 sp-text-3xl">🎯</span>
                    <p className="sp-text-sm sp-text-ink-3">
                      {wp.i18n.__('Select a schema type above to configure properties', 'teil1-schema-manager')}
                    </p>
                  </div>
                )}
              </div>
            )}

            {/* Save Bar */}
            {schemaType && conditions.some(c => c.type) && (
              <div className="sp-flex sp-items-center sp-justify-between sp-border-t sp-border-surface-2 sp-bg-surface-1 sp-px-6 sp-py-3">
                <div className="sp-text-xs sp-text-ink-3">
                  {wp.i18n.sprintf(
                    /* translators: %1$d: Number of properties set. */
                    wp.i18n._n(
                      '%1$d property set',
                      '%1$d properties set',
                      Object.keys(schemaData).filter(k => !k.startsWith('@') && !k.startsWith('_')).length,
                      'teil1-schema-manager'
                    ),
                    Object.keys(schemaData).filter(k => !k.startsWith('@') && !k.startsWith('_')).length
                  )}
                </div>
                <div className="sp-flex sp-items-center sp-gap-2">
                  {saveSuccess && (
                    <span className="sp-animate-fade-in sp-text-xs sp-font-medium sp-text-green-600">
                      {wp.i18n.__('✓ Saved', 'teil1-schema-manager')}
                    </span>
                  )}
                  <button
                    onClick={handleSave}
                    disabled={saving || !schemaType}
                    className="sp-rounded-lg sp-bg-brand-600 sp-px-4 sp-py-1.5 sp-text-sm sp-font-medium sp-text-white sp-transition-all hover:sp-bg-brand-700 disabled:sp-opacity-50"
                  >
                    {saving
                      ? wp.i18n.__('Saving…', 'teil1-schema-manager')
                      : isNew
                        ? wp.i18n.__('Create Rule', 'teil1-schema-manager')
                        : wp.i18n.__('Save Changes', 'teil1-schema-manager')}
                  </button>
                </div>
              </div>
            )}
          </div>
        </div>

        {/* ════════════════════════════════════════════════════════════════
            Sidebar (1/3 width)
            ════════════════════════════════════════════════════════════ */}
        <div className="sp-space-y-4">
          {/* Variable Picker */}
          {activeVariableField && (
            <VariablePicker
              onSelect={handleInsertVariable}
              onClose={() => setActiveVariableField(null)}
              targetField={activeVariableField}
            />
          )}

          {/* Rule Summary */}
          <div className="sp-rounded-xl sp-border sp-border-surface-3 sp-bg-white sp-p-4 sp-shadow-bento">
            <h3 className="sp-text-xs sp-font-semibold sp-uppercase sp-tracking-wider sp-text-ink-3 sp-mb-3">
              {wp.i18n.__('Rule Summary', 'teil1-schema-manager')}
            </h3>
            <div className="sp-space-y-2">
              <div className="sp-flex sp-items-center sp-gap-2">
                <span className="sp-text-xs sp-text-ink-3">
                  {wp.i18n.__('Type:', 'teil1-schema-manager')}
                </span>
                <span className="sp-rounded sp-bg-brand-100 sp-px-2 sp-py-0.5 sp-text-2xs sp-font-semibold sp-text-brand-700">
                  {schemaType || '—'}
                </span>
              </div>
              <div className="sp-text-xs sp-text-ink-3">
                {wp.i18n.__('Applies to:', 'teil1-schema-manager')}
              </div>
              {conditions.filter(c => c.type).length === 0 ? (
                <p className="sp-text-xs sp-italic sp-text-ink-4">
                  {wp.i18n.__('No conditions set', 'teil1-schema-manager')}
                </p>
              ) : (
                <div className="sp-space-y-1">
                  {conditions.filter(c => c.type).map((c, i) => {
                    const ctx = contexts.find(x => x.type === c.type && (x.value || '') === (c.value || ''));
                    return (
                      <div key={i} className="sp-flex sp-items-center sp-gap-1.5">
                        <span className="sp-text-green-500">✓</span>
                        <span className="sp-text-xs sp-text-ink-1">{ctx?.label || `${c.type}:${c.value}`}</span>
                      </div>
                    );
                  })}
                </div>
              )}
              <div className="sp-mt-2 sp-flex sp-items-center sp-gap-2 sp-border-t sp-border-surface-2 sp-pt-2">
                <span className="sp-text-xs sp-text-ink-3">
                  {wp.i18n.__('Priority:', 'teil1-schema-manager')}
                </span>
                <span className="sp-text-xs sp-font-medium sp-text-ink-1">{priority}</span>
              </div>
            </div>
          </div>

          {/* Live Health */}
          {liveHealth && <HealthDetail health={liveHealth} type={schemaType} />}

          {/* JSON-LD Preview */}
          <div className="sp-rounded-xl sp-border sp-border-surface-3 sp-bg-white sp-shadow-bento">
            <div className="sp-border-b sp-border-surface-2 sp-px-4 sp-py-3">
              <h3 className="sp-text-xs sp-font-semibold sp-uppercase sp-tracking-wider sp-text-ink-3">
                {wp.i18n.__('JSON-LD Output', 'teil1-schema-manager')}
              </h3>
            </div>
            <pre className="sp-max-h-64 sp-overflow-auto sp-p-4 sp-font-mono sp-text-xs sp-text-ink-2">
              {resolveCustomVariables(fullSchema, customVars)}
            </pre>
          </div>
        </div>
      </div>
    </div>
  );
}

/**
 * Inline component for adding a custom property key.
 */
function AddCustomProperty({ existingKeys, onAdd }) {
  const [adding, setAdding] = useState(false);
  const [key, setKey] = useState('');

  const submit = () => {
    const k = key.trim();
    if (k && !existingKeys.includes(k) && !k.startsWith('@')) {
      onAdd(k);
      setKey('');
      setAdding(false);
    }
  };

  if (!adding) {
    return (
      <button
        onClick={() => setAdding(true)}
        className="sp-w-full sp-rounded-lg sp-border sp-border-dashed sp-border-surface-3 sp-py-2.5 sp-text-xs sp-font-medium sp-text-ink-3 sp-transition-colors hover:sp-border-brand-300 hover:sp-text-brand-600 hover:sp-bg-brand-50/30"
      >
        {wp.i18n.__('+ Add Custom Property', 'teil1-schema-manager')}
      </button>
    );
  }

  return (
    <div className="sp-flex sp-items-center sp-gap-2">
      <input
        type="text"
        value={key}
        onChange={(e) => setKey(e.target.value)}
        onKeyDown={(e) => e.key === 'Enter' && submit()}
        placeholder="propertyName"
        autoFocus
        className="sp-flex-1 sp-rounded-lg sp-border sp-border-brand-300 sp-bg-white sp-px-3 sp-py-2 sp-text-sm sp-font-mono sp-outline-none focus:sp-ring-1 focus:sp-ring-brand-200"
      />
      <button
        onClick={submit}
        className="sp-rounded-lg sp-bg-brand-600 sp-px-3 sp-py-2 sp-text-xs sp-font-medium sp-text-white hover:sp-bg-brand-700"
      >
        {wp.i18n.__('Add', 'teil1-schema-manager')}
      </button>
      <button
        onClick={() => { setAdding(false); setKey(''); }}
        className="sp-rounded-lg sp-border sp-border-surface-3 sp-px-3 sp-py-2 sp-text-xs sp-text-ink-3 hover:sp-bg-surface-1"
      >
        {wp.i18n.__('Cancel', 'teil1-schema-manager')}
      </button>
    </div>
  );
}
