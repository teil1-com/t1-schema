import React, { useState } from 'react';
import ObjectEditor from './ObjectEditor';
import ArrayEditor from './ArrayEditor';

/**
 * PropertyCard — Single schema property editor.
 */
export default function PropertyCard({
  name,
  definition,
  value,
  onChange,
  onRemove,
  onRequestVariable,
  isActive,
  typeDefs = {},
}) {
  const isRequired = definition?.required;
  const isRecommended = definition?.recommended;
  const valueType = definition?.type || 'Text';
  const description = definition?.description || '';
  
  const hasValue = value !== '' && value !== undefined && value !== null;
  const isVariable = typeof value === 'string' && value.startsWith('{{') && value.endsWith('}}');
  
  const isObj = isObjectType(valueType);
  const isArr = valueType.endsWith('[]');
  const baseType = isArr ? valueType.replace('[]', '') : valueType;
  
  // Decide whether to show the Visual Builder or raw text mode for complex objects.
  // If it's an object/array, default to Builder mode if value is not a string/variable.
  const [builderMode, setBuilderMode] = useState(
    (isObj || isArr) && (!hasValue || typeof value !== 'string')
  );

  // Detect @id-only references (e.g. { "@id": "{{site_url}}#organization" })
  const isIdRef = typeof value === 'object' && value !== null && !Array.isArray(value)
    && Object.keys(value).length === 1 && value['@id'];

  return (
    <div
      className={`sp-group sp-rounded-lg sp-border sp-p-4 sp-transition-all ${
        isActive
          ? 'sp-border-brand-300 sp-bg-brand-50/30 sp-ring-1 sp-ring-brand-200'
          : 'sp-border-surface-3 sp-bg-white hover:sp-border-surface-4'
      }`}
    >
      {/* Header */}
      <div className="sp-mb-2 sp-flex sp-items-center sp-justify-between">
        <div className="sp-flex sp-items-center sp-gap-2">
          <span className="sp-font-mono sp-text-sm sp-font-medium sp-text-ink-0">{name}</span>
          {isRequired && (
            <span className={`sp-rounded sp-px-1.5 sp-py-0.5 sp-text-2xs sp-font-semibold ${
              hasValue
                ? 'sp-bg-green-100 sp-text-green-600'
                : 'sp-bg-red-100 sp-text-red-600'
            }`}>
              Required
            </span>
          )}
          {!isRequired && isRecommended && (
            <span className="sp-rounded sp-bg-blue-100 sp-px-1.5 sp-py-0.5 sp-text-2xs sp-font-semibold sp-text-blue-600">
              Recommended
            </span>
          )}
          <span className="sp-text-2xs sp-text-ink-4">{valueType}</span>
        </div>
        
        <div className="sp-flex sp-items-center sp-gap-2">
          {/* Builder Mode Toggle for Objects/Arrays */}
          {(isObj || isArr) && !isIdRef && (
            <button
              onClick={() => {
                if (builderMode) {
                  onChange(typeof value === 'object' ? '' : value);
                } else {
                  onChange(isArr ? [] : { '@type': baseType });
                }
                setBuilderMode(!builderMode);
              }}
              className="sp-rounded sp-border sp-border-surface-3 sp-bg-surface-1 sp-px-2 sp-py-0.5 sp-text-2xs sp-font-medium sp-text-ink-2 sp-transition-colors hover:sp-bg-surface-2"
              title="Toggle input mode"
            >
              {builderMode ? 'Use Text/Var' : 'Build Object'}
            </button>
          )}

          <div className="sp-flex sp-items-center sp-gap-1 sp-opacity-0 sp-transition-opacity group-hover:sp-opacity-100">
            <button
              onClick={onRequestVariable}
              className="sp-rounded sp-p-1 sp-text-ink-4 sp-transition-colors hover:sp-bg-brand-50 hover:sp-text-brand-600"
              title="Insert dynamic variable"
            >
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                <path d="M4 7c0-1.1.9-2 2-2h3a2 2 0 0 1 2 2v1a2 2 0 0 0 2 2" />
                <path d="M20 17c0 1.1-.9 2-2 2h-3a2 2 0 0 1-2-2v-1a2 2 0 0 0-2-2" />
              </svg>
            </button>
            {hasValue && (
              <button
                onClick={onRemove}
                className="sp-rounded sp-p-1 sp-text-ink-4 sp-transition-colors hover:sp-bg-red-50 hover:sp-text-red-500"
                title="Clear value"
              >
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                  <path d="M18 6 6 18" /><path d="m6 6 12 12" />
                </svg>
              </button>
            )}
          </div>
        </div>
      </div>

      {/* Description */}
      {description && (
        <p className="sp-mb-2 sp-text-xs sp-text-ink-3">{description}</p>
      )}

      {/* @id Reference Badge */}
      {isIdRef ? (
        <div className="sp-flex sp-items-center sp-justify-between sp-rounded-lg sp-border sp-border-teal-200 sp-bg-teal-50/60 sp-px-3 sp-py-2.5">
          <div className="sp-flex sp-items-center sp-gap-2">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className="sp-text-teal-600 sp-flex-shrink-0">
              <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71" />
              <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71" />
            </svg>
            <span className="sp-text-xs sp-font-medium sp-text-teal-700">Linked:</span>
            <code className="sp-rounded sp-bg-teal-100/80 sp-px-1.5 sp-py-0.5 sp-text-xs sp-font-mono sp-text-teal-800">
              {value['@id']}
            </code>
          </div>
          <button
            onClick={() => {
              onChange({ '@type': baseType, '@id': value['@id'] });
              setBuilderMode(true);
            }}
            className="sp-rounded sp-border sp-border-teal-300 sp-bg-white sp-px-2 sp-py-0.5 sp-text-2xs sp-font-medium sp-text-teal-700 sp-transition-colors hover:sp-bg-teal-100"
            title="Expand to full object editor"
          >
            Expand
          </button>
        </div>
      ) : (isObj || isArr) && builderMode ? (
        <div className="sp-mt-3 sp-border-l-2 sp-border-brand-200 sp-pl-4">
          {isArr ? (
            <ArrayEditor
              itemType={baseType}
              items={Array.isArray(value) ? value : []}
              onChange={onChange}
              typeDefs={typeDefs}
              activeVariableField={null}
              setActiveVariableField={null}
            />
          ) : (
            <ObjectEditor
              type={baseType}
              data={typeof value === 'object' && value !== null ? value : { '@type': baseType }}
              onChange={onChange}
              typeDefs={typeDefs}
              activeVariableField={null}
              setActiveVariableField={null}
            />
          )}
        </div>
      ) : (
        <input
          type="text"
          value={typeof value === 'string' ? value : typeof value === 'object' ? JSON.stringify(value) : ''}
          onChange={(e) => onChange(e.target.value)}
          placeholder={getPlaceholder(name, valueType)}
          className={`sp-w-full sp-rounded-lg sp-border sp-px-3 sp-py-2 sp-text-sm sp-outline-none sp-transition-colors ${
            isVariable
              ? 'sp-border-brand-200 sp-bg-brand-50 sp-font-mono sp-text-brand-700'
              : 'sp-border-surface-3 sp-bg-white sp-text-ink-0 focus:sp-border-brand-400 focus:sp-ring-1 focus:sp-ring-brand-200'
          }`}
        />
      )}
    </div>
  );
}

const PRIMITIVE_TYPES = ['Text', 'URL', 'Date', 'DateTime', 'Time', 'Number', 'Integer', 'Float', 'Boolean'];

function isObjectType(type) {
  const base = type.replace('[]', '');
  if (PRIMITIVE_TYPES.includes(base)) return false;
  if (type.endsWith('[]')) return true;
  const objects = ['Person', 'Organization', 'PostalAddress', 'GeoCoordinates', 'Offer', 'AggregateOffer',
    'Review', 'AggregateRating', 'Rating', 'Brand', 'Place', 'SearchAction',
    'MonetaryAmount', 'Answer', 'Question', 'HowToStep', 'ListItem', 'DefinedTermSet'];
  return objects.includes(type);
}

function getPlaceholder(name, type) {
  if (type === 'URL') return 'https://…';
  if (type === 'Date') return 'YYYY-MM-DD or {{post_date}}';
  if (type === 'Number') return '0';
  if (name === 'name') return '{{site_name}} or enter text';
  return `Enter ${name} or use {{variable}}`;
}
