#!/usr/bin/env python3
"""Inspect a local official OpenAPI contract. No network or financial calls."""
import argparse
import json
import sys
from pathlib import Path
import yaml

METHODS = {'get', 'post', 'put', 'patch', 'delete', 'head', 'options'}

def resolve(doc, node, seen=()):
    if not isinstance(node, dict):
        raise ValueError('Expected an object')
    ref = node.get('$ref')
    if ref is None:
        return node
    if not isinstance(ref, str) or not ref.startswith('#/'):
        raise ValueError('Only local references are supported')
    if ref in seen:
        raise ValueError('Cyclic reference: ' + ref)
    value = doc
    for part in ref[2:].split('/'):
        value = value[part.replace('~1', '/').replace('~0', '~')]
    return resolve(doc, value, seen + (ref,))

def shape(doc, node, seen=()):
    ref = node.get('$ref') if isinstance(node, dict) else None
    if ref and ref in seen:
        raise ValueError('Cyclic schema composition: ' + ref)
    seen = seen + ((ref,) if ref else ())
    node = resolve(doc, node)
    required = set(node.get('required', []))
    properties = set(node.get('properties', {}))
    for parent in node.get('allOf', []):
        r, p = shape(doc, parent, seen)
        required.update(r)
        properties.update(p)
    return sorted(required), sorted(properties)

def inspect(doc):
    if not isinstance(doc, dict) or not str(doc.get('openapi', '')).startswith('3.'):
        raise ValueError('Expected OpenAPI 3.x')
    result = {'operations': [], 'states': {}, 'warnings': []}
    for path, item in doc.get('paths', {}).items():
        item = resolve(doc, item)
        for method, operation in item.items():
            if method not in METHODS:
                continue
            op = resolve(doc, operation)
            row = {'method': method.upper(), 'path': path}
            if 'requestBody' in op:
                body = resolve(doc, op['requestBody'])
                schema = body.get('content', {}).get('application/json', {}).get('schema', {})
                node = resolve(doc, schema)
                row['body_type'] = node.get('type', 'composed')
                if node.get('type') == 'array':
                    row['item_required'], row['item_properties'] = shape(doc, node.get('items', {}))
                    row['maxItems'] = node.get('maxItems')
                else:
                    row['required'], row['properties'] = shape(doc, schema)
                if any(k in node for k in ('oneOf', 'anyOf')):
                    row['alternatives_need_review'] = True
            row['parameters'] = []
            for param in item.get('parameters', []) + op.get('parameters', []):
                p = resolve(doc, param)
                row['parameters'].append({'name': p.get('name'), 'in': p.get('in'), 'required': p.get('required', False)})
                if not p.get('name') or not p.get('in'):
                    result['warnings'].append('Incomplete parameter at ' + method.upper() + ' ' + path)
            result['operations'].append(row)
    for name, schema in doc.get('components', {}).get('schemas', {}).items():
        if name.endswith('Status'):
            node = resolve(doc, schema)
            if 'enum' in node:
                result['states'][name] = node['enum']
    result['warnings'].append('Structural extraction only: reconcile with guides and sandbox; do not infer runtime acceptance.')
    return result

def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--file', required=True, type=Path)
    args = parser.parse_args()
    try:
        doc = yaml.safe_load(args.file.read_text(encoding='utf-8'))
        print(json.dumps(inspect(doc), ensure_ascii=False, indent=2))
    except (OSError, ValueError, KeyError, TypeError, yaml.YAMLError) as exc:
        print('Contract inspection failed: ' + str(exc), file=sys.stderr)
        return 1
    return 0

if __name__ == '__main__':
    sys.exit(main())
