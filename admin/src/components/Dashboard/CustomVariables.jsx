import React, { useState, useEffect } from 'react';
import { useCustomVariables, useUpdateCustomVariables } from '../../hooks/useSchema';

/**
 * Custom Variables Editor — key-value pairs for site-wide constants.
 *
 * Users define variables like "phone" → "+43 1 234 5678" and
 * use {{custom.phone}} in any schema property.
 */
export default function CustomVariables() {
  const { data: saved = {}, isLoading } = useCustomVariables();
  const updateMutation = useUpdateCustomVariables();
  const variableExample = '{{custom.key}}';
  const helpText = wp.i18n.sprintf(
    /* translators: %1$s: Custom variable syntax example. */
    wp.i18n.__(
      'Define site-wide constants. Use %1$s in any schema.',
      'teil1-schema-manager'
    ),
    variableExample
  );
  const [helpBeforeExample, helpAfterExample] = helpText.split(variableExample);

  const [entries, setEntries] = useState([]);
  const [hasChanges, setHasChanges] = useState(false);

  // Sync from server.
  useEffect(() => {
    if (!isLoading) {
      const items = Object.entries(saved).map(([key, value]) => ({ key, value }));
      setEntries(items.length > 0 ? items : [{ key: '', value: '' }]);
      setHasChanges(false);
    }
  }, [saved, isLoading]);

  const updateEntry = (index, field, val) => {
    setEntries(prev => {
      const next = [...prev];
      next[index] = { ...next[index], [field]: val };
      return next;
    });
    setHasChanges(true);
  };

  const addEntry = () => {
    setEntries(prev => [...prev, { key: '', value: '' }]);
    setHasChanges(true);
  };

  const removeEntry = (index) => {
    setEntries(prev => prev.filter((_, i) => i !== index));
    setHasChanges(true);
  };

  const handleSave = () => {
    const obj = {};
    entries.forEach(({ key, value }) => {
      const k = key.trim().toLowerCase().replace(/[^a-z0-9_]/g, '_');
      if (k && value.trim()) {
        obj[k] = value.trim();
      }
    });
    updateMutation.mutate(obj);
    setHasChanges(false);
  };

  if (isLoading) {
    return (
      <div
        className="sp-rounded-xl sp-border sp-border-surface-2 sp-bg-surface-1 sp-p-6 sp-animate-pulse"
        role="status"
        aria-label={wp.i18n.__( 'Loading custom variables…', 'teil1-schema-manager' )}
      >
        <div className="sp-h-6 sp-w-40 sp-rounded sp-bg-surface-2" />
      </div>
    );
  }

  return (
    <div className="sp-rounded-xl sp-border sp-border-surface-2 sp-bg-surface-1 sp-p-6">
      {/* Header */}
      <div className="sp-flex sp-items-center sp-justify-between sp-mb-4">
        <div>
          <h3 className="sp-text-sm sp-font-semibold sp-text-ink-0 sp-flex sp-items-center sp-gap-2">
            <span>⚡</span> {wp.i18n.__( 'Custom Variables', 'teil1-schema-manager' )}
          </h3>
          <p className="sp-text-xs sp-text-ink-3 sp-mt-0.5">
            {helpBeforeExample}
            <code className="sp-px-1 sp-py-0.5 sp-rounded sp-bg-surface-2 sp-text-xs sp-font-mono sp-text-brand-700">
              {variableExample}
            </code>
            {helpAfterExample}
          </p>
        </div>
        <button
          onClick={handleSave}
          disabled={!hasChanges || updateMutation.isPending}
          className="sp-rounded-md sp-bg-brand-600 sp-px-3 sp-py-1.5 sp-text-xs sp-font-medium sp-text-white sp-transition hover:sp-bg-brand-700 disabled:sp-opacity-40 disabled:sp-cursor-not-allowed"
        >
          {updateMutation.isPending
            ? wp.i18n.__( 'Saving…', 'teil1-schema-manager' )
            : hasChanges
              ? wp.i18n.__( 'Save Changes', 'teil1-schema-manager' )
              : wp.i18n.__( 'Saved ✓', 'teil1-schema-manager' )}
        </button>
      </div>

      {/* Entries */}
      <div className="sp-space-y-2">
        {entries.map((entry, i) => (
          <div key={i} className="sp-flex sp-items-center sp-gap-2">
            {/* Key input */}
            <div className="sp-relative sp-flex-shrink-0" style={{ width: '180px' }}>
              <span className="sp-absolute sp-left-2 sp-top-1/2 sp--translate-y-1/2 sp-text-xs sp-text-ink-4 sp-font-mono sp-pointer-events-none sp-select-none">
                {'{{custom.'}
              </span>
              <input
                type="text"
                value={entry.key}
                onChange={(e) => updateEntry(i, 'key', e.target.value)}
                placeholder={wp.i18n._x( 'key', 'custom variable key placeholder', 'teil1-schema-manager' )}
                className="sp-w-full sp-rounded-md sp-border sp-border-surface-3 sp-bg-white sp-py-1.5 sp-pr-2 sp-text-xs sp-font-mono sp-text-ink-0 sp-transition focus:sp-border-brand-400 focus:sp-ring-2 focus:sp-ring-brand-100 sp-outline-none"
                style={{ paddingLeft: '80px' }}
              />
              <span className="sp-absolute sp-right-2 sp-top-1/2 sp--translate-y-1/2 sp-text-xs sp-text-ink-4 sp-font-mono sp-pointer-events-none sp-select-none">
                {'}}'}
              </span>
            </div>

            {/* Arrow */}
            <span className="sp-text-ink-4 sp-text-xs sp-flex-shrink-0">→</span>

            {/* Value input */}
            <input
              type="text"
              value={entry.value}
              onChange={(e) => updateEntry(i, 'value', e.target.value)}
              placeholder={wp.i18n.__( 'Value (e.g. +43 1 234 5678)', 'teil1-schema-manager' )}
              className="sp-flex-1 sp-rounded-md sp-border sp-border-surface-3 sp-bg-white sp-px-3 sp-py-1.5 sp-text-xs sp-text-ink-0 sp-transition focus:sp-border-brand-400 focus:sp-ring-2 focus:sp-ring-brand-100 sp-outline-none"
            />

            {/* Remove */}
            <button
              onClick={() => removeEntry(i)}
              className="sp-flex-shrink-0 sp-rounded sp-p-1 sp-text-ink-4 sp-transition hover:sp-bg-red-50 hover:sp-text-red-500"
              title={wp.i18n.__( 'Remove variable', 'teil1-schema-manager' )}
            >
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                <path d="M18 6 6 18" /><path d="m6 6 12 12" />
              </svg>
            </button>
          </div>
        ))}
      </div>

      {/* Add button */}
      <button
        onClick={addEntry}
        className="sp-mt-3 sp-flex sp-items-center sp-gap-1 sp-rounded-md sp-border sp-border-dashed sp-border-surface-3 sp-px-3 sp-py-1.5 sp-text-xs sp-font-medium sp-text-ink-3 sp-transition hover:sp-border-brand-400 hover:sp-text-brand-600 hover:sp-bg-brand-50"
      >
        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
          <path d="M12 5v14" /><path d="M5 12h14" />
        </svg>
        {wp.i18n.__( 'Add Variable', 'teil1-schema-manager' )}
      </button>

      {/* Usage hint */}
      {entries.some(e => e.key.trim()) && (
        <div className="sp-mt-4 sp-rounded-lg sp-bg-brand-50 sp-px-3 sp-py-2 sp-border sp-border-brand-100">
          <p className="sp-text-xs sp-text-brand-700 sp-font-medium sp-mb-1">
            {wp.i18n.__( 'Usage', 'teil1-schema-manager' )}
          </p>
          <div className="sp-flex sp-flex-wrap sp-gap-1.5">
            {entries.filter(e => e.key.trim()).map((e, i) => (
              <code key={i} className="sp-rounded sp-bg-white sp-px-1.5 sp-py-0.5 sp-text-xs sp-font-mono sp-text-brand-800 sp-border sp-border-brand-200">
                {'{{custom.' + e.key.trim().toLowerCase().replace(/[^a-z0-9_]/g, '_') + '}}'}
              </code>
            ))}
          </div>
        </div>
      )}
    </div>
  );
}
