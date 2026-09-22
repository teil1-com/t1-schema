import React from 'react';
import { useScore } from '../../hooks/useSchema';

const gradeColors = {
  A: { bg: 'sp-bg-emerald-100', text: 'sp-text-emerald-700', ring: '#10b981' },
  B: { bg: 'sp-bg-blue-100', text: 'sp-text-blue-700', ring: '#3b82f6' },
  C: { bg: 'sp-bg-amber-100', text: 'sp-text-amber-700', ring: '#f59e0b' },
  D: { bg: 'sp-bg-orange-100', text: 'sp-text-orange-700', ring: '#f97316' },
  F: { bg: 'sp-bg-red-100', text: 'sp-text-red-700', ring: '#ef4444' },
};

const factorIcons = {
  coverage: '🎯',
  health: '💚',
  depth: '📊',
  diversity: '🎨',
};

const factorLabels = {
  coverage: wp.i18n.__( 'Coverage', 'teil1-schema-manager' ),
  health: wp.i18n.__( 'Health', 'teil1-schema-manager' ),
  depth: wp.i18n.__( 'Depth', 'teil1-schema-manager' ),
  diversity: wp.i18n.__( 'Diversity', 'teil1-schema-manager' ),
};

export default function SchemaScore() {
  const { data: score, isLoading } = useScore();

  if (isLoading || !score) {
    return (
      <div
        className="sp-rounded-xl sp-border sp-border-surface-2 sp-bg-surface-1 sp-p-6 sp-animate-pulse"
        role="status"
        aria-label={wp.i18n.__( 'Loading schema quality score…', 'teil1-schema-manager' )}
      >
        <div className="sp-h-6 sp-w-32 sp-rounded sp-bg-surface-2" />
      </div>
    );
  }

  const colors = gradeColors[score.grade] || gradeColors.F;
  const circumference = 2 * Math.PI * 40;
  const offset = circumference - (score.score / 100) * circumference;

  return (
    <div className="sp-rounded-xl sp-border sp-border-surface-2 sp-bg-surface-1 sp-p-6">
      <div className="sp-flex sp-items-center sp-gap-6">
        {/* Score Ring */}
        <div className="sp-relative sp-flex-shrink-0">
          <svg width="100" height="100" className="sp-transform -sp-rotate-90">
            <circle cx="50" cy="50" r="40" fill="none" stroke="currentColor" strokeWidth="6" className="sp-text-surface-2" />
            <circle
              cx="50" cy="50" r="40" fill="none"
              stroke={colors.ring}
              strokeWidth="6"
              strokeLinecap="round"
              strokeDasharray={circumference}
              strokeDashoffset={offset}
              style={{ transition: 'stroke-dashoffset 1s ease-in-out' }}
            />
          </svg>
          <div className="sp-absolute sp-inset-0 sp-flex sp-flex-col sp-items-center sp-justify-center">
            <span className="sp-text-2xl sp-font-bold sp-text-ink-0">{score.score}</span>
            <span className={`sp-text-xs sp-font-semibold ${colors.text}`}>{score.grade}</span>
          </div>
        </div>

        {/* Breakdown */}
        <div className="sp-flex-1 sp-space-y-2">
          <h3 className="sp-text-sm sp-font-semibold sp-text-ink-0 sp-mb-3">
            {wp.i18n.__( 'Schema Quality Score', 'teil1-schema-manager' )}
          </h3>
          {Object.entries(score.breakdown).map(([key, factor]) => (
              <div key={key} className="sp-flex sp-items-center sp-gap-2">
                <span className="sp-text-sm">{factorIcons[key]}</span>
                <span className="sp-text-xs sp-font-medium sp-text-ink-2 sp-w-16">{factorLabels[key]}</span>
                <div className="sp-flex-1 sp-h-1.5 sp-rounded-full sp-bg-surface-2 sp-overflow-hidden">
                  <div
                    className="sp-h-full sp-rounded-full"
                    style={{
                      width: `${factor.score}%`,
                      backgroundColor: factor.score >= 75 ? '#10b981' : factor.score >= 50 ? '#f59e0b' : '#ef4444',
                      transition: 'width 0.8s ease-in-out',
                    }}
                  />
                </div>
                <span className="sp-text-xs sp-font-mono sp-text-ink-3 sp-w-8 sp-text-right">{factor.score}%</span>
                <span className="sp-text-xs sp-text-ink-4 sp-w-24 sp-truncate" title={factor.detail}>{factor.detail}</span>
              </div>
          ))}
        </div>
      </div>

      {score.types_used.length > 0 && (
        <div className="sp-mt-4 sp-pt-3 sp-border-t sp-border-surface-2 sp-flex sp-flex-wrap sp-gap-1.5">
          {score.types_used.map(type => (
            <span key={type} className="sp-rounded-full sp-bg-brand-50 sp-px-2 sp-py-0.5 sp-text-xs sp-text-brand-700">
              {type}
            </span>
          ))}
        </div>
      )}
    </div>
  );
}
