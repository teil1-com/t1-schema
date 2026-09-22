import React, { useState } from 'react';

/**
 * TypeSelector — Schema @type dropdown with multi-type support.
 *
 * Supports both single type ("Organization") and multi-type
 * (["Organization", "ProfessionalService"]) via an optional
 * secondary type input.
 */
export default function TypeSelector({ value, onChange, types }) {
  // value can be a string or array.
  const primaryType = Array.isArray(value) ? value[0] || '' : value || '';
  const secondaryTypes = Array.isArray(value) ? value.slice(1) : [];
  const [showMulti, setShowMulti] = useState(secondaryTypes.length > 0);
  const [newType, setNewType] = useState('');

  const groups = {
    [wp.i18n._x('Core', 'schema type group', 'teil1-schema-manager')]: ['Organization', 'LocalBusiness', 'WebSite', 'WebPage', 'Person'],
    [wp.i18n._x('Content', 'schema type group', 'teil1-schema-manager')]: ['Article', 'BlogPosting', 'CreativeWork', 'VideoObject', 'ImageObject'],
    [wp.i18n._x('Commerce', 'schema type group', 'teil1-schema-manager')]: ['Product', 'Offer', 'AggregateOffer', 'Service', 'SoftwareApplication', 'JobPosting'],
    [wp.i18n._x('Rich Results', 'schema type group', 'teil1-schema-manager')]: ['FAQPage', 'Question', 'Answer', 'HowTo', 'HowToStep', 'BreadcrumbList', 'ListItem', 'Review', 'AggregateRating'],
    [wp.i18n._x('Other', 'schema type group', 'teil1-schema-manager')]: ['Event', 'Course', 'Recipe', 'Thing'],
  };

  const emitChange = (primary, extras) => {
    if (extras.length === 0) {
      onChange(primary);
    } else {
      onChange([primary, ...extras]);
    }
  };

  const handlePrimaryChange = (e) => {
    const newPrimary = e.target.value;
    emitChange(newPrimary, secondaryTypes);
  };

  const handleAddSecondary = () => {
    const trimmed = newType.trim();
    if (trimmed && !secondaryTypes.includes(trimmed) && trimmed !== primaryType) {
      emitChange(primaryType, [...secondaryTypes, trimmed]);
      setNewType('');
    }
  };

  const handleRemoveSecondary = (typeToRemove) => {
    const updated = secondaryTypes.filter((t) => t !== typeToRemove);
    emitChange(primaryType, updated);
    if (updated.length === 0) setShowMulti(false);
  };

  // All type names for the secondary autocomplete.
  const allTypeNames = Object.keys(types || {}).sort();

  return (
    <div>
      <label className="sp-mb-1.5 sp-block sp-text-sm sp-font-medium sp-text-ink-1">
        {wp.i18n.__('Schema Type', 'teil1-schema-manager')}
      </label>
      <select
        value={primaryType}
        onChange={handlePrimaryChange}
        className="sp-w-full sp-rounded-lg sp-border sp-border-surface-3 sp-bg-white sp-px-3 sp-py-2.5 sp-text-sm sp-text-ink-0 sp-outline-none sp-transition-colors focus:sp-border-brand-400 focus:sp-ring-1 focus:sp-ring-brand-200"
      >
        <option value="">{wp.i18n.__('Select a type…', 'teil1-schema-manager')}</option>
        {Object.entries(groups).map(([group, typeNames]) => {
          const validTypes = typeNames.filter((t) => types[t]);
          if (validTypes.length === 0) return null;

          return (
            <optgroup key={group} label={group}>
              {validTypes.map((typeName) => (
                <option key={typeName} value={typeName}>
                  {types[typeName]?.label || typeName}
                </option>
              ))}
            </optgroup>
          );
        })}
      </select>

      {/* Primary type description */}
      {primaryType && types[primaryType] && (
        <p className="sp-mt-2 sp-text-xs sp-text-ink-3">
          {types[primaryType].description}
          {types[primaryType].parent && (
            <span className="sp-ml-2 sp-text-ink-4">
              {
                wp.i18n.sprintf(
                  /* translators: %1$s: Parent Schema.org type name. */
                  wp.i18n.__('Inherits from: %1$s', 'teil1-schema-manager'),
                  types[primaryType].parent
                )
              }
            </span>
          )}
        </p>
      )}

      {/* Multi-type toggle */}
      {primaryType && (
        <div className="sp-mt-3">
          {!showMulti ? (
            <button
              type="button"
              onClick={() => setShowMulti(true)}
              className="sp-text-xs sp-font-medium sp-text-brand-600 hover:sp-text-brand-700 sp-transition-colors"
            >
              {wp.i18n.__('+ Add secondary type (multi-type)', 'teil1-schema-manager')}
            </button>
          ) : (
            <div className="sp-rounded-lg sp-border sp-border-dashed sp-border-surface-3 sp-p-3 sp-bg-surface-1">
              <div className="sp-mb-2 sp-text-2xs sp-font-medium sp-uppercase sp-tracking-wider sp-text-ink-3">
                {wp.i18n.__('Multi-Type (@type array)', 'teil1-schema-manager')}
              </div>

              {/* Current types as chips */}
              <div className="sp-flex sp-flex-wrap sp-gap-1.5 sp-mb-2">
                <span className="sp-inline-flex sp-items-center sp-gap-1 sp-rounded-full sp-bg-brand-100 sp-px-2.5 sp-py-1 sp-text-xs sp-font-medium sp-text-brand-800">
                  {primaryType}
                  <span className="sp-text-2xs sp-text-brand-500">
                    {wp.i18n.__('(primary)', 'teil1-schema-manager')}
                  </span>
                </span>
                {secondaryTypes.map((st) => (
                  <span
                    key={st}
                    className="sp-inline-flex sp-items-center sp-gap-1 sp-rounded-full sp-bg-violet-100 sp-px-2.5 sp-py-1 sp-text-xs sp-font-medium sp-text-violet-800"
                  >
                    {st}
                    <button
                      type="button"
                      onClick={() => handleRemoveSecondary(st)}
                      className="sp-ml-0.5 sp-text-violet-400 hover:sp-text-red-500 sp-transition-colors"
                    >
                      ×
                    </button>
                  </span>
                ))}
              </div>

              {/* Add new secondary type */}
              <div className="sp-flex sp-gap-2">
                <input
                  type="text"
                  value={newType}
                  onChange={(e) => setNewType(e.target.value)}
                  onKeyDown={(e) => e.key === 'Enter' && (e.preventDefault(), handleAddSecondary())}
                  placeholder={
                    wp.i18n.sprintf(
                      /* translators: %1$s: Example Schema.org type name. */
                      wp.i18n.__('e.g. %1$s', 'teil1-schema-manager'),
                      'ProfessionalService'
                    )
                  }
                  list="secondary-type-suggestions"
                  className="sp-flex-1 sp-rounded-md sp-border sp-border-surface-3 sp-px-2.5 sp-py-1.5 sp-text-xs sp-text-ink-0 sp-outline-none focus:sp-border-brand-400"
                />
                <datalist id="secondary-type-suggestions">
                  {allTypeNames
                    .filter((t) => t !== primaryType && !secondaryTypes.includes(t))
                    .map((t) => (
                      <option key={t} value={t} />
                    ))}
                </datalist>
                <button
                  type="button"
                  onClick={handleAddSecondary}
                  disabled={!newType.trim()}
                  className="sp-rounded-md sp-bg-brand-600 sp-px-3 sp-py-1.5 sp-text-xs sp-font-medium sp-text-white sp-transition-colors hover:sp-bg-brand-700 disabled:sp-opacity-40"
                >
                  {wp.i18n._x('Add', 'secondary schema type button', 'teil1-schema-manager')}
                </button>
              </div>

              <p className="sp-mt-2 sp-text-2xs sp-text-ink-3">
                {wp.i18n.__('Multi-type creates', 'teil1-schema-manager')}{' '}
                <code className="sp-text-brand-600">@type: ["{primaryType}"{secondaryTypes.map((t) => `, "${t}"`).join('')}]</code>
                {' — '}
                {
                  wp.i18n.sprintf(
                    /* translators: %1$s and %2$s: Example Schema.org type names. */
                    wp.i18n.__('useful for entities like %1$s + %2$s.', 'teil1-schema-manager'),
                    'Organization',
                    'ProfessionalService'
                  )
                }
              </p>
            </div>
          )}
        </div>
      )}
    </div>
  );
}
