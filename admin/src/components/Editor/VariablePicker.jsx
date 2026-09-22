import React from 'react';
import { useVariables } from '../../hooks/useSchema';

/**
 * VariablePicker — dropdown to insert dynamic {{variable}} tags.
 */
export default function VariablePicker({ onSelect, onClose, targetField }) {
  const { data: variables = {} } = useVariables();

  const categoryLabels = {
    post: wp.i18n._x('📄 Post', 'variable category', 'teil1-schema-manager'),
    author: wp.i18n._x('👤 Author', 'variable category', 'teil1-schema-manager'),
    site: wp.i18n._x('🌐 Site', 'variable category', 'teil1-schema-manager'),
    taxonomy: wp.i18n._x('🏷️ Taxonomy', 'variable category', 'teil1-schema-manager'),
    meta: wp.i18n._x('🔧 Custom Meta', 'variable category', 'teil1-schema-manager'),
  };

  return (
    <div className="sp-animate-slide-up sp-rounded-xl sp-border sp-border-brand-200 sp-bg-white sp-shadow-bento">
      <div className="sp-flex sp-items-center sp-justify-between sp-border-b sp-border-surface-2 sp-px-4 sp-py-3">
        <div>
          <h3 className="sp-text-xs sp-font-semibold sp-uppercase sp-tracking-wider sp-text-ink-3">
            {wp.i18n.__('Insert Variable', 'teil1-schema-manager')}
          </h3>
          <p className="sp-text-2xs sp-text-ink-4">
            {wp.i18n._x('for', 'target field prefix', 'teil1-schema-manager')}{' '}
            <span className="sp-font-mono sp-text-brand-600">{targetField}</span>
          </p>
        </div>
        <button
          onClick={onClose}
          className="sp-rounded sp-p-1 sp-text-ink-4 sp-transition-colors hover:sp-bg-surface-2 hover:sp-text-ink-1"
        >
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
            <path d="M18 6 6 18" /><path d="m6 6 12 12" />
          </svg>
        </button>
      </div>

      <div className="sp-max-h-72 sp-overflow-y-auto sp-p-2">
        {Object.entries(variables).map(([category, vars]) => (
          <div key={category} className="sp-mb-2">
            <div className="sp-px-2 sp-py-1 sp-text-2xs sp-font-semibold sp-uppercase sp-tracking-wider sp-text-ink-3">
              {categoryLabels[category] || category}
            </div>
            {Object.entries(vars).map(([tag, desc]) => (
              <button
                key={tag}
                onClick={() => onSelect(tag)}
                className="sp-flex sp-w-full sp-items-center sp-justify-between sp-rounded-lg sp-px-2 sp-py-1.5 sp-text-left sp-transition-colors hover:sp-bg-brand-50"
              >
                <span className="sp-font-mono sp-text-xs sp-text-brand-600">
                  {`{{${tag}}}`}
                </span>
                <span className="sp-text-2xs sp-text-ink-4">{desc}</span>
              </button>
            ))}
          </div>
        ))}
      </div>
    </div>
  );
}
