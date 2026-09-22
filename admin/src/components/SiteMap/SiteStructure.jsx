import React, { useState } from 'react';
import { useSiteStructure } from '../../hooks/useSchema';

/**
 * SiteStructure — hierarchical view of all WordPress URL contexts
 * with schema coverage indicators.
 */
export default function SiteStructure({ onCreateRule }) {
  const { data: structure = [], isLoading } = useSiteStructure();

  if (isLoading) {
    return (
      <div className="sp-flex sp-items-center sp-justify-center sp-py-16">
        <div
          role="status"
          aria-label={wp.i18n.__('Loading site structure…', 'teil1-schema-manager')}
          className="sp-h-6 sp-w-6 sp-animate-spin sp-rounded-full sp-border-2 sp-border-brand-200 sp-border-t-brand-600"
        />
      </div>
    );
  }

  // Count coverage.
  const totalContexts = structure.length;
  const coveredContexts = structure.filter(s => s.rules?.length > 0).length;

  return (
    <div className="sp-space-y-4">
      {/* Header */}
      <div className="sp-flex sp-items-center sp-justify-between">
        <div>
          <h2 className="sp-text-base sp-font-semibold sp-text-ink-0">
            {wp.i18n.__('Site Structure', 'teil1-schema-manager')}
          </h2>
          <p className="sp-text-sm sp-text-ink-3">
            {wp.i18n.__('Overview of all page contexts and their schema coverage', 'teil1-schema-manager')}
          </p>
        </div>
        <div className="sp-flex sp-items-center sp-gap-3">
          <div className="sp-rounded-full sp-bg-surface-2 sp-px-3 sp-py-1 sp-text-xs sp-font-medium sp-text-ink-2">
            {wp.i18n.sprintf(
              /* translators: %1$d: Number of covered contexts, %2$d: Total number of contexts. */
              wp.i18n._n(
                '%1$d/%2$d context covered',
                '%1$d/%2$d contexts covered',
                totalContexts,
                'teil1-schema-manager'
              ),
              coveredContexts,
              totalContexts
            )}
          </div>
        </div>
      </div>

      {/* Progress bar */}
      <div className="sp-rounded-xl sp-border sp-border-surface-3 sp-bg-white sp-p-4 sp-shadow-bento">
        <div className="sp-flex sp-items-center sp-justify-between sp-mb-2">
          <span className="sp-text-xs sp-font-semibold sp-text-ink-2">
            {wp.i18n.__('Schema Coverage', 'teil1-schema-manager')}
          </span>
          <span className="sp-text-xs sp-font-semibold sp-text-brand-600">
            {totalContexts > 0 ? Math.round((coveredContexts / totalContexts) * 100) : 0}%
          </span>
        </div>
        <div className="sp-h-2 sp-rounded-full sp-bg-surface-2 sp-overflow-hidden">
          <div
            className="sp-h-full sp-rounded-full sp-bg-gradient-to-r sp-from-brand-400 sp-to-brand-600 sp-transition-all"
            style={{ width: `${totalContexts > 0 ? (coveredContexts / totalContexts) * 100 : 0}%` }}
          />
        </div>
      </div>

      {/* Structure tree */}
      <div className="sp-space-y-1">
        {structure.map((node, i) => (
          <StructureNode key={i} node={node} onCreateRule={onCreateRule} />
        ))}
      </div>
    </div>
  );
}

function StructureNode({ node, onCreateRule, depth = 0 }) {
  const [expanded, setExpanded] = useState(false);
  const hasChildren = node.children && node.children.length > 0;
  const hasArchive = node.archive;
  const hasCoverage = node.rules?.length > 0;
  const archiveCovered = hasArchive && node.archive.rules?.length > 0;

  return (
    <div>
      <div
        className={`sp-group sp-flex sp-items-center sp-justify-between sp-rounded-lg sp-border sp-px-4 sp-py-3 sp-transition-all ${
          hasCoverage
            ? 'sp-border-green-200 sp-bg-green-50/50 hover:sp-bg-green-50'
            : 'sp-border-surface-3 sp-bg-white hover:sp-bg-surface-1'
        }`}
        style={{ marginLeft: depth * 20 }}
      >
        <div className="sp-flex sp-items-center sp-gap-3">
          {/* Expand toggle */}
          {(hasChildren || hasArchive) ? (
            <button
              onClick={() => setExpanded(!expanded)}
              className="sp-text-ink-3 sp-transition-transform sp-text-xs"
              style={{ transform: expanded ? 'rotate(90deg)' : 'rotate(0deg)' }}
            >
              ▶
            </button>
          ) : (
            <span className="sp-w-3" />
          )}

          {/* Icon + Label */}
          <span className="sp-text-base">{node.icon || '📄'}</span>
          <div>
            <span className="sp-text-sm sp-font-medium sp-text-ink-0">{node.label}</span>
            {node.count !== undefined && (
              <span className="sp-ml-2 sp-text-2xs sp-text-ink-4">
                {wp.i18n.sprintf(
                  /* translators: %1$d: Number of items in this site-structure context. */
                  wp.i18n._n('(%1$d item)', '(%1$d items)', node.count, 'teil1-schema-manager'),
                  node.count
                )}
              </span>
            )}
            {node.url && (
              <p className="sp-text-xs sp-text-ink-4 sp-truncate sp-max-w-xs">{node.url}</p>
            )}
          </div>
        </div>

        <div className="sp-flex sp-items-center sp-gap-2">
          {/* Rule badges */}
          {node.rules?.map((r, i) => (
            <span key={i} className="sp-rounded sp-bg-brand-100 sp-px-1.5 sp-py-0.5 sp-text-2xs sp-font-semibold sp-text-brand-700">
              {r.schema_type}
            </span>
          ))}

          {/* Coverage indicator */}
          {hasCoverage ? (
            <span className="sp-rounded-full sp-bg-green-100 sp-px-2 sp-py-0.5 sp-text-2xs sp-font-semibold sp-text-green-700">
              {wp.i18n.__('✓ Covered', 'teil1-schema-manager')}
            </span>
          ) : (
            <button
              onClick={() => onCreateRule && onCreateRule(node.context)}
              className="sp-rounded-lg sp-border sp-border-dashed sp-border-surface-3 sp-px-2.5 sp-py-1 sp-text-xs sp-text-ink-4 sp-opacity-0 sp-transition-all group-hover:sp-opacity-100 hover:sp-border-brand-300 hover:sp-text-brand-600"
            >
              {wp.i18n.__('+ Add Rule', 'teil1-schema-manager')}
            </button>
          )}
        </div>
      </div>

      {/* Archive sub-row */}
      {expanded && hasArchive && (
        <div
          className={`sp-group sp-flex sp-items-center sp-justify-between sp-rounded-lg sp-border sp-px-4 sp-py-2.5 sp-mt-1 sp-transition-all ${
            archiveCovered
              ? 'sp-border-green-200 sp-bg-green-50/50'
              : 'sp-border-surface-3 sp-bg-white'
          }`}
          style={{ marginLeft: (depth + 1) * 20 }}
        >
          <div className="sp-flex sp-items-center sp-gap-3">
            <span className="sp-w-3" />
            <span className="sp-text-sm">📋</span>
            <div>
              <span className="sp-text-xs sp-font-medium sp-text-ink-1">{node.archive.label}</span>
              {node.archive.url && (
                <p className="sp-text-2xs sp-text-ink-4">{node.archive.url}</p>
              )}
            </div>
          </div>
          <div className="sp-flex sp-items-center sp-gap-2">
            {node.archive.rules?.map((r, i) => (
              <span key={i} className="sp-rounded sp-bg-brand-100 sp-px-1.5 sp-py-0.5 sp-text-2xs sp-font-semibold sp-text-brand-700">
                {r.schema_type}
              </span>
            ))}
            {archiveCovered ? (
              <span className="sp-rounded-full sp-bg-green-100 sp-px-2 sp-py-0.5 sp-text-2xs sp-font-semibold sp-text-green-700">
                ✓
              </span>
            ) : (
              <button
                onClick={() => onCreateRule && onCreateRule(node.archive.context)}
                className="sp-rounded-lg sp-border sp-border-dashed sp-border-surface-3 sp-px-2.5 sp-py-1 sp-text-xs sp-text-ink-4 sp-opacity-0 sp-transition-all group-hover:sp-opacity-100 hover:sp-border-brand-300 hover:sp-text-brand-600"
              >
                {wp.i18n.__('+ Add Rule', 'teil1-schema-manager')}
              </button>
            )}
          </div>
        </div>
      )}

      {/* Children */}
      {expanded && hasChildren && (
        <div className="sp-mt-1 sp-space-y-1">
          {node.children.map((child, i) => (
            <StructureNode key={i} node={child} onCreateRule={onCreateRule} depth={depth + 1} />
          ))}
        </div>
      )}
    </div>
  );
}
