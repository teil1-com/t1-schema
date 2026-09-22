import React from 'react';

/**
 * Health Scorecard — site-wide schema health overview.
 */
export default function HealthScorecard({ health, onEditGlobal, onNavigateToRules }) {
  if (!health) {
    return (
      <div className="sp-rounded-xl sp-border sp-border-surface-3 sp-bg-white sp-p-6 sp-shadow-bento">
        <div className="sp-flex sp-items-center sp-gap-3">
          <div className="sp-h-5 sp-w-5 sp-animate-pulse-subtle sp-rounded-full sp-bg-surface-3" />
          <span className="sp-text-sm sp-text-ink-3">
            {wp.i18n.__( 'Loading health data…', 'teil1-schema-manager' )}
          </span>
        </div>
      </div>
    );
  }

  const { summary } = health;
  const totalIssues = (summary?.errors || 0) + (summary?.warnings || 0);
  const isHealthy = summary?.errors === 0;
  let statusText;

  if (isHealthy && totalIssues === 0) {
    statusText = wp.i18n.__( 'All schemas are valid and complete', 'teil1-schema-manager' );
  } else if (isHealthy) {
    statusText = wp.i18n.sprintf(
      /* translators: %1$d: Number of schema recommendations. */
      wp.i18n._n(
        'Valid with %1$d recommendation',
        'Valid with %1$d recommendations',
        summary.warnings,
        'teil1-schema-manager'
      ),
      summary.warnings
    );
  } else {
    statusText = wp.i18n.sprintf(
      /* translators: %1$d: Number of schema errors requiring attention. */
      wp.i18n._n(
        '%1$d error needs attention',
        '%1$d errors need attention',
        summary.errors,
        'teil1-schema-manager'
      ),
      summary.errors
    );
  }

  return (
    <div className="sp-rounded-xl sp-border sp-border-surface-3 sp-bg-white sp-p-6 sp-shadow-bento">
      <div className="sp-grid sp-grid-cols-2 sp-gap-4 md:sp-grid-cols-4">
        {/* Total Schemas */}
        <MetricCard
          label={wp.i18n.__( 'Total Schemas', 'teil1-schema-manager' )}
          value={summary?.total || 0}
          icon="📊"
          color="sp-text-brand-600 sp-bg-brand-50"
        />

        {/* Valid */}
        <MetricCard
          label={wp.i18n.__( 'Valid', 'teil1-schema-manager' )}
          value={summary?.valid || 0}
          icon="✅"
          color="sp-text-green-600 sp-bg-green-50"
        />

        {/* Warnings */}
        <MetricCard
          label={wp.i18n.__( 'Warnings', 'teil1-schema-manager' )}
          value={summary?.warnings || 0}
          icon="⚠️"
          color={summary?.warnings > 0 ? 'sp-text-yellow-600 sp-bg-yellow-50' : 'sp-text-ink-3 sp-bg-surface-1'}
        />

        {/* Errors */}
        <MetricCard
          label={wp.i18n.__( 'Errors', 'teil1-schema-manager' )}
          value={summary?.errors || 0}
          icon="🚨"
          color={summary?.errors > 0 ? 'sp-text-red-600 sp-bg-red-50' : 'sp-text-ink-3 sp-bg-surface-1'}
        />
      </div>

      {/* Status bar */}
      <div className="sp-mt-4 sp-flex sp-items-center sp-gap-2 sp-border-t sp-border-surface-2 sp-pt-4">
        <div className={`sp-h-2 sp-w-2 sp-rounded-full ${isHealthy ? 'sp-bg-green-400' : 'sp-bg-red-400'}`} />
        <span className="sp-text-xs sp-font-medium sp-text-ink-2">
          {statusText}
        </span>
      </div>

      {/* Action Items */}
      {totalIssues > 0 && (
        <div className="sp-mt-6 sp-border-t sp-border-surface-2 sp-pt-6">
          <h3 className="sp-mb-4 sp-text-sm sp-font-semibold sp-text-ink-0">
            {wp.i18n.__( 'Action Items', 'teil1-schema-manager' )}
          </h3>
          <div className="sp-space-y-3">
            {health.globals?.filter((g) => g.health && (!g.health.valid || g.health.warnings.length > 0)).map((g) => (
              <IssueItem
                key={`global-${g.id}`}
                item={g}
                onClick={() => onEditGlobal(g.id)}
                actionLabel={wp.i18n.__( 'Fix Global Schema', 'teil1-schema-manager' )}
              />
            ))}
            {health.rules?.filter((r) => r.health && (!r.health.valid || r.health.warnings.length > 0)).map((r) => (
              <IssueItem
                key={`rule-${r.id}`}
                item={r}
                onClick={onNavigateToRules}
                actionLabel={wp.i18n.__( 'Go to Rules', 'teil1-schema-manager' )}
              />
            ))}
          </div>
        </div>
      )}
    </div>
  );
}

function IssueItem({ item, onClick, actionLabel }) {
  const isGlobal = item.layer === 'global';
  const hasErrors = !item.health.valid;
  const issuesCount = hasErrors ? item.health.errors.length : item.health.warnings.length;
  const firstIssue = hasErrors ? item.health.errors[0] : item.health.warnings[0];

  return (
    <div className="sp-flex sp-items-center sp-justify-between sp-rounded-lg sp-border sp-border-surface-2 sp-bg-surface-1 sp-p-4 sp-transition-colors hover:sp-bg-surface-2">
      <div className="sp-flex sp-items-start sp-gap-3">
        <div className={`sp-mt-0.5 sp-flex sp-h-6 sp-w-6 sp-shrink-0 sp-items-center sp-justify-center sp-rounded-full sp-text-xs ${
          hasErrors ? 'sp-bg-red-100 sp-text-red-600' : 'sp-bg-yellow-100 sp-text-yellow-600'
        }`}>
          {hasErrors ? '🚨' : '⚠️'}
        </div>
        <div>
          <div className="sp-flex sp-items-center sp-gap-2">
            <span className="sp-text-2xs sp-font-bold sp-uppercase sp-tracking-wider sp-text-ink-3">
              [
              {isGlobal
                ? wp.i18n._x( 'Global', 'schema layer label', 'teil1-schema-manager' )
                : wp.i18n._x( 'Rule', 'schema layer label', 'teil1-schema-manager' )}
              ]
            </span>
            <span className="sp-text-sm sp-font-semibold sp-text-ink-0">
              {item.name || item.type}
            </span>
            <span className={`sp-rounded-full sp-px-2 sp-py-0.5 sp-text-2xs sp-font-semibold ${
              hasErrors ? 'sp-bg-red-50 sp-text-red-700' : 'sp-bg-yellow-50 sp-text-yellow-700'
            }`}>
              {wp.i18n.sprintf(
                /* translators: %1$d: Number of schema issues. */
                wp.i18n._n(
                  '%1$d issue',
                  '%1$d issues',
                  issuesCount,
                  'teil1-schema-manager'
                ),
                issuesCount
              )}
            </span>
          </div>
          <p className="sp-mt-1 sp-text-xs sp-text-ink-2 sp-truncate sp-max-w-md">
            {firstIssue}
          </p>
        </div>
      </div>
      <button
        onClick={onClick}
        className="sp-shrink-0 sp-rounded-lg sp-border sp-border-surface-3 sp-bg-white sp-px-3 sp-py-1.5 sp-text-xs sp-font-medium sp-text-ink-1 sp-shadow-sm sp-transition-colors hover:sp-bg-surface-1 hover:sp-text-ink-0"
      >
        {actionLabel}
      </button>
    </div>
  );
}

function MetricCard({ label, value, icon, color }) {
  return (
    <div className={`sp-flex sp-items-center sp-gap-3 sp-rounded-lg sp-p-3 ${color}`}>
      <span className="sp-text-xl">{icon}</span>
      <div>
        <div className="sp-text-xl sp-font-bold">{value}</div>
        <div className="sp-text-2xs sp-font-medium sp-uppercase sp-tracking-wider sp-opacity-75">{label}</div>
      </div>
    </div>
  );
}
