import fs from 'node:fs';
import path from 'node:path';
import { parse } from '@babel/parser';
import { describe, expect, it } from 'vitest';

const SOURCE_ROOT = path.resolve(import.meta.dirname);
const DOMAIN = 'teil1-schema-manager';

function sourceFiles(directory) {
  return fs.readdirSync(directory, { withFileTypes: true }).flatMap((entry) => {
    const target = path.join(directory, entry.name);
    if (entry.isDirectory()) {
      return sourceFiles(target);
    }
    if (!/\.(?:js|jsx)$/.test(entry.name) || entry.name.endsWith('.test.js')) {
      return [];
    }
    return [target];
  });
}

const files = sourceFiles(SOURCE_ROOT);

function walk(node, visit) {
  if (!node || typeof node !== 'object') {
    return;
  }

  visit(node);
  for (const [key, value] of Object.entries(node)) {
    if (key === 'loc' || key === 'start' || key === 'end') {
      continue;
    }
    if (Array.isArray(value)) {
      value.forEach((child) => walk(child, visit));
    } else {
      walk(value, visit);
    }
  }
}

describe('WordPress JavaScript i18n contract', () => {
  it('does not leave visible JSX text or accessibility attributes hardcoded', () => {
    const violations = [];
    const allowedLiteralText = new Set([
      'Teil1 Schema Manager',
      '🏢 Organization',
      '🌐 WebSite',
      '@type: ["',
      'Organization ·',
    ]);
    const allowedAttributes = new Set(['propertyName']);

    for (const file of files) {
      const relative = path.relative(SOURCE_ROOT, file);
      const source = fs.readFileSync(file, 'utf8');
      const ast = parse(source, { sourceType: 'module', plugins: ['jsx'] });

      walk(ast, (node) => {
        if (node.type === 'JSXText') {
          const value = node.value.replace(/\s+/g, ' ').trim();
          if (/[A-Za-z]/.test(value) && !allowedLiteralText.has(value)) {
            violations.push(`${relative}:${node.loc.start.line} JSX text "${value}"`);
          }
        }

        if (
          node.type === 'JSXAttribute' &&
          ['title', 'placeholder', 'aria-label'].includes(node.name?.name) &&
          node.value?.type === 'StringLiteral' &&
          /[A-Za-z]/.test(node.value.value) &&
          !allowedAttributes.has(node.value.value)
        ) {
          violations.push(`${relative}:${node.loc.start.line} attribute "${node.value.value}"`);
        }

        if (
          node.type === 'CallExpression' &&
          node.callee?.type === 'Identifier' &&
          ['alert', 'confirm'].includes(node.callee.name) &&
          ['StringLiteral', 'TemplateLiteral'].includes(node.arguments[0]?.type)
        ) {
          violations.push(`${relative}:${node.loc.start.line} hardcoded dialog`);
        }
      });
    }

    expect(violations).toEqual([]);
  });

  it('uses the plugin text domain for every gettext call', () => {
    const violations = [];
    let callCount = 0;

    for (const file of files) {
      const relative = path.relative(SOURCE_ROOT, file);
      const source = fs.readFileSync(file, 'utf8');
      const ast = parse(source, { sourceType: 'module', plugins: ['jsx'] });

      walk(ast, (node) => {
        if (
          node.type === 'CallExpression' &&
          node.callee?.type === 'MemberExpression' &&
          node.callee.object?.type === 'MemberExpression' &&
          node.callee.object.object?.name === 'wp' &&
          node.callee.object.property?.name === 'i18n' &&
          ['__', '_x', '_n'].includes(node.callee.property?.name)
        ) {
          callCount += 1;
          const domain = node.arguments.at(-1);
          if (domain?.type !== 'StringLiteral' || domain.value !== DOMAIN) {
            violations.push(`${relative}:${node.loc.start.line}`);
          }
        }
      });
    }

    expect(callCount).toBeGreaterThan(300);
    expect(violations).toEqual([]);
  });
});
