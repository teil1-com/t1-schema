import React, { useState, useMemo } from 'react';
import PropertyCard from './PropertyCard';
import AddCustomProperty from './AddCustomProperty';

/**
 * ObjectEditor — Renders a list of properties for a specific Schema.org type.
 * Used recursively for nested objects.
 */
export default function ObjectEditor({
  type,
  data = {},
  onChange,
  typeDefs,
  activeVariableField,
  setActiveVariableField,
}) {
  const typeDef = typeDefs[type];
  if (!typeDef) {
    return (
      <div className="sp-rounded sp-bg-yellow-50 sp-p-3 sp-text-xs sp-text-yellow-700">
        {
          wp.i18n.sprintf(
            /* translators: %1$s: Schema.org type name. */
            wp.i18n.__("Definition for type '%1$s' not found in registry.", 'teil1-schema-manager'),
            type
          )
        }
      </div>
    );
  }

  const registryProperties = typeDef.properties || {};

  const allPropertyKeys = useMemo(() => {
    const keys = new Set(Object.keys(registryProperties));
    for (const k of Object.keys(data)) {
      if (!k.startsWith('@') && !k.startsWith('_')) keys.add(k);
    }
    return [...keys];
  }, [registryProperties, data]);

  const handlePropertyChange = (key, value) => {
    onChange({ ...data, [key]: value });
  };

  const handleRemoveProperty = (key) => {
    const next = { ...data };
    delete next[key];
    onChange(next);
  };

  return (
    <div className="sp-space-y-3">
      {allPropertyKeys.map((key) => {
        const def = registryProperties[key] || {
          type: 'Text',
          description: wp.i18n.__('Custom property', 'teil1-schema-manager'),
        };
        return (
          <PropertyCard
            key={key}
            name={key}
            definition={def}
            value={data[key] ?? ''}
            onChange={(val) => handlePropertyChange(key, val)}
            onRemove={() => handleRemoveProperty(key)}
            onRequestVariable={setActiveVariableField ? () => setActiveVariableField(key) : undefined}
            isActive={activeVariableField === key}
            typeDefs={typeDefs} // pass down for recursive lookups
          />
        );
      })}

      <AddCustomProperty
        existingKeys={allPropertyKeys}
        onAdd={(key) => handlePropertyChange(key, '')}
      />
    </div>
  );
}
