import React, { useState, useEffect } from 'react';
import { useRecommendedRules, useActivateRecommendedRule } from '../../hooks/useSchema';

const STORAGE_KEY = 't1schema_rules_dismissed';

export default function RuleTemplates() {
  const { data: templates = [], isLoading } = useRecommendedRules();
  const activateMutation = useActivateRecommendedRule();
  const [dismissed, setDismissed] = useState(() => {
    try { return JSON.parse(localStorage.getItem(STORAGE_KEY) || 'false'); } catch { return false; }
  });
  const [minimized, setMinimized] = useState(false);

  // Persist dismiss state.
  useEffect(() => {
    localStorage.setItem(STORAGE_KEY, JSON.stringify(dismissed));
  }, [dismissed]);

  if (isLoading || dismissed) return null;

  const inactive = templates.filter(t => !t.active);
  if (inactive.length === 0) return null;

  return (
    <div className="sp-rounded-xl sp-border sp-border-amber-200 sp-bg-amber-50/50 sp-p-5 sp-transition-all">
      <div className="sp-flex sp-items-center sp-gap-2 sp-mb-1">
        <span className="sp-text-lg">💡</span>
        <h3 className="sp-text-sm sp-font-semibold sp-text-ink-0 sp-flex-1">
          {wp.i18n.__( 'Recommended Rules', 'teil1-schema-manager' )}
          {minimized && (
            <span className="sp-ml-2 sp-text-xs sp-font-normal sp-text-ink-3">
              {wp.i18n.sprintf(
                /* translators: %1$d: Number of recommended rule suggestions. */
                wp.i18n._n(
                  '%1$d suggestion',
                  '%1$d suggestions',
                  inactive.length,
                  'teil1-schema-manager'
                ),
                inactive.length
              )}
            </span>
          )}
        </h3>

        {/* Minimize / Expand */}
        <button
          type="button"
          onClick={() => setMinimized(!minimized)}
          title={
            minimized
              ? wp.i18n.__( 'Expand', 'teil1-schema-manager' )
              : wp.i18n.__( 'Minimize', 'teil1-schema-manager' )
          }
          className="sp-flex sp-h-6 sp-w-6 sp-items-center sp-justify-center sp-rounded sp-text-ink-3 sp-transition-colors hover:sp-bg-amber-100 hover:sp-text-ink-1"
        >
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
            {minimized
              ? <polyline points="6 9 12 15 18 9" />
              : <polyline points="18 15 12 9 6 15" />
            }
          </svg>
        </button>

        {/* Dismiss */}
        <button
          type="button"
          onClick={() => setDismissed(true)}
          title={wp.i18n.__( 'Dismiss recommendations', 'teil1-schema-manager' )}
          className="sp-flex sp-h-6 sp-w-6 sp-items-center sp-justify-center sp-rounded sp-text-ink-3 sp-transition-colors hover:sp-bg-amber-100 hover:sp-text-ink-1"
        >
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
            <path d="M18 6 6 18" /><path d="m6 6 12 12" />
          </svg>
        </button>
      </div>

      {!minimized && (
        <>
          <p className="sp-text-xs sp-text-ink-3 sp-mb-3 sp-ml-7">
            {wp.i18n.__( 'Opt-in templates to improve your coverage', 'teil1-schema-manager' )}
          </p>

          <div className="sp-space-y-2">
            {inactive.map(template => (
                <div
                  key={template.key}
                  className="sp-flex sp-items-center sp-justify-between sp-rounded-lg sp-bg-white sp-border sp-border-surface-2 sp-px-4 sp-py-3 sp-transition hover:sp-border-brand-300"
                >
                  <div className="sp-flex-1 sp-min-w-0">
                    <div className="sp-flex sp-items-center sp-gap-2">
                      <span className="sp-text-sm sp-font-medium sp-text-ink-0">{template.name}</span>
                      <span className="sp-rounded sp-bg-brand-100 sp-px-1.5 sp-py-0.5 sp-text-xs sp-text-brand-700">
                        {template.schema_type}
                      </span>
                    </div>
                    <p className="sp-text-xs sp-text-ink-3 sp-mt-0.5">{template.description}</p>
                  </div>
                  <button
                    onClick={() => activateMutation.mutate(template.key)}
                    disabled={activateMutation.isPending}
                    className="sp-ml-4 sp-flex-shrink-0 sp-rounded-md sp-bg-brand-600 sp-px-3 sp-py-1.5 sp-text-xs sp-font-medium sp-text-white hover:sp-bg-brand-700 sp-transition disabled:sp-opacity-50"
                  >
                    {activateMutation.isPending
                      ? wp.i18n._x( '...', 'activating a recommended rule loading indicator', 'teil1-schema-manager' )
                      : wp.i18n.__( 'Activate', 'teil1-schema-manager' )}
                  </button>
                </div>
            ))}
          </div>
        </>
      )}
    </div>
  );
}
