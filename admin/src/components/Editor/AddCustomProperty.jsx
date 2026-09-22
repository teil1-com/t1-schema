import React, { useState } from 'react';

/**
 * Inline component for adding a custom property key.
 */
export default function AddCustomProperty({ existingKeys, onAdd }) {
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
        placeholder={wp.i18n.__('propertyName', 'teil1-schema-manager')}
        autoFocus
        className="sp-flex-1 sp-rounded-lg sp-border sp-border-brand-300 sp-bg-white sp-px-3 sp-py-2 sp-text-sm sp-font-mono sp-outline-none focus:sp-ring-1 focus:sp-ring-brand-200"
      />
      <button
        onClick={submit}
        className="sp-rounded-lg sp-bg-brand-600 sp-px-3 sp-py-2 sp-text-xs sp-font-medium sp-text-white hover:sp-bg-brand-700"
      >
        {wp.i18n._x('Add', 'custom property button', 'teil1-schema-manager')}
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
