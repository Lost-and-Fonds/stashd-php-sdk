#!/usr/bin/env python3
"""Generate typed immutable contract facts from the frozen language-neutral schema."""

import argparse
import json
from pathlib import Path

ROOT = Path(__file__).resolve().parent.parent
SCHEMA = ROOT / 'resources/contract/wit-schema.json'
PREFIX = 'Stashd\\PluginSdk\\Contract'
ENUM_CASE_DOCS = {
    ('input-host', 'deficiency-disposition', 'retryable'): 'The known gap may be filled by later preservation work.',
    ('input-host', 'deficiency-disposition', 'terminal'): 'The known gap cannot be filled by retrying the same work.',
    ('input-host', 'deficiency-disposition', 'unknown'): 'The producer cannot establish whether the gap can be filled.',
    ('input-plugin', 'discovery-intent', 'refresh'): 'Discover changes using an optional completed refresh baseline.',
    ('input-plugin', 'discovery-intent', 'complete'): 'Enumerate the logical Input without requiring a refresh baseline.',
    ('broadcast-plugin', 'file-report-status', 'not-applicable'): 'The publication reports no filesystem-relative paths.',
    ('broadcast-plugin', 'file-report-status', 'complete'): 'Accepted file reports exhaust the publication filesystem result.',
}


def pascal(name):
    return ''.join(part.capitalize() for part in name.split('-'))


def camel(name):
    value = pascal(name)
    return value[0].lower() + value[1:]


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--check', action='store_true')
    arguments = parser.parse_args()
    schema = json.loads(SCHEMA.read_text())
    if schema['package'] != 'stashd:plugin@0.17.0':
        raise SystemExit('Unexpected frozen contract identity')
    interfaces = {}
    for contract in schema['contracts']:
        interfaces.update(contract['interfaces'])

    def named(interface, name):
        owner = interfaces[interface].get('uses', {}).get(name, interface)
        return '\\' + PREFIX + '\\' + pascal(owner) + '\\' + pascal(name)

    def types(interface, specification):
        if specification is None:
            return 'null', 'null'
        kind = specification['kind']
        if kind == 'scalar':
            name = specification['name']
            native = {'bool': 'bool', 'string': 'string', 'f32': 'float', 'f64': 'float',
                      'u64': '\\Stashd\\PluginSdk\\Shared\\Unsigned64', 's64': 'int'}.get(name, 'int')
            return native, native
        if kind == 'named':
            target = named(interface, specification['name'])
            return target, target
        if kind == 'list':
            _, element = types(interface, specification['value'])
            return 'array', 'list<' + element + '>'
        if kind == 'option':
            native, documented = types(interface, specification['value'])
            return native + '|null', documented + '|null'
        if kind == 'borrow':
            return types(interface, specification['value'])
        if kind == 'result':
            return types(interface, specification['ok']) if specification['ok'] else ('void', 'void')
        raise ValueError(specification)

    outputs = {}
    for interface, definition in interfaces.items():
        namespace = PREFIX + '\\' + pascal(interface)
        for name, record in definition['records'].items():
            classname = pascal(name)
            fields = record['fields']
            lines = ['<?php', '', 'declare(strict_types=1);', '', 'namespace ' + namespace + ';', '',
                     '/**', ' * Immutable ' + interface + '.' + name + ' contract fact.',
                     ' * Field order and opaque values follow stashd:plugin@0.17.0 without normalization.', ' */',
                     'final readonly class ' + classname, '{']
            for field in fields:
                native, documented = types(interface, field['type'])
                lines.extend(['    /**', '     * Canonical ' + field['name'] + ' value; retained in contract order without normalization.',
                              '     * @var ' + documented, '     */', '    public ' + native + ' $' + camel(field['name']) + ';', ''])
            lines.extend(['    /**', '     * Assemble the complete contract fact; wire and lifecycle validators enforce boundary invariants.'])
            for field in fields:
                _, documented = types(interface, field['type'])
                lines.append('     * @param ' + documented + ' $' + camel(field['name']))
            lines.extend(['     */', '    public function __construct('])
            for field in fields:
                native, _ = types(interface, field['type'])
                lines.append('        ' + native + ' $' + camel(field['name']) + ',')
            lines.extend(['    ) {'])
            for field in fields:
                prop = camel(field['name'])
                lines.append('        $this->' + prop + ' = $' + prop + ';')
            lines.extend(['    }', '}', ''])
            outputs[ROOT / 'src/Contract' / pascal(interface) / (classname + '.php')] = '\n'.join(lines)
        for name, enum in definition['enums'].items():
            lines = ['<?php', '', 'declare(strict_types=1);', '', 'namespace ' + namespace + ';', '',
                     '/**', ' * Closed canonical cases for ' + interface + '.' + name + '; spelling is protocol identity.', ' */',
                     'enum ' + pascal(name) + ': string', '{']
            for value in enum['values']:
                description = ENUM_CASE_DOCS[(interface, name, value)] if (interface, name, value) in ENUM_CASE_DOCS else 'Canonical ' + value + ' case of ' + name + '.'
                lines.extend(['    /**', '     * ' + description, '     */', '    case ' + pascal(value) + " = '" + value + "';"])
            lines.extend(['}', ''])
            outputs[ROOT / 'src/Contract' / pascal(interface) / (pascal(name) + '.php')] = '\n'.join(lines)
        for name, variant in definition['variants'].items():
            base = pascal(name)
            lines = ['<?php', '', 'declare(strict_types=1);', '', 'namespace ' + namespace + ';', '',
                     '/**', ' * Typed union of the canonical ' + interface + '.' + name + ' cases.', ' */',
                     'interface ' + base, '{', '}', '']
            outputs[ROOT / 'src/Contract' / pascal(interface) / (base + '.php')] = '\n'.join(lines)
            for case in variant['values']:
                classname = base + pascal(case['name'])
                lines = ['<?php', '', 'declare(strict_types=1);', '', 'namespace ' + namespace + ';', '',
                         '/**', ' * Canonical ' + case['name'] + ' branch of ' + interface + '.' + name + '.', ' */',
                         'final readonly class ' + classname + ' implements ' + base, '{']
                if case['type'] is not None:
                    native, documented = types(interface, case['type'])
                    lines.extend(['    /**', '     * Payload belonging only to this variant case, preserving optional absence and list order.',
                                  '     * @var ' + documented, '     */', '    public ' + native + ' $value;', '',
                                  '    /**', '     * Construct this specific branch without string tags or raw wire objects.',
                                  '     * @param ' + documented + ' $value', '     */',
                                  '    public function __construct(' + native + ' $value)', '    {',
                                  '        $this->value = $value;', '    }'])
                lines.extend(['}', ''])
                outputs[ROOT / 'src/Contract' / pascal(interface) / (classname + '.php')] = '\n'.join(lines)

        if interface.endswith('-plugin'):
            lines = ['<?php', '', 'declare(strict_types=1);', '', 'namespace ' + namespace + ';', '',
                     '/**', ' * Typed author lifecycle surface for ' + interface + '.',
                     ' * Values are independent of JSON framing and host process reuse.', ' */',
                     'interface Plugin', '{']
            for function in definition['functions']:
                result = function['result']
                native, documented = types(interface, result)
                if result and result['kind'] == 'result':
                    error_native, error_doc = types(interface, result['error'])
                    native = (native if native != 'void' else 'null') + '|' + error_native
                    documented = (documented if documented != 'void' else 'null') + '|' + error_doc
                lines.extend(['    /**', '     * Execute canonical ' + function['name'] + ' using current invocation values only.'])
                parameters = []
                for argument in function['arguments']:
                    argtype, argdoc = types(interface, argument['type'])
                    argname = camel(argument['name'])
                    lines.append('     * @param ' + argdoc + ' $' + argname)
                    parameters.append(argtype + ' $' + argname)
                lines.extend(['     * @return ' + documented, '     */',
                              '    public function ' + camel(function['name']) + '(' + ', '.join(parameters) + '): ' + native + ';', ''])
            lines.extend(['}', ''])
            outputs[ROOT / 'src/Contract' / pascal(interface) / 'Plugin.php'] = '\n'.join(lines)
        for resource in definition['resources']:
            classname = pascal(resource['name'])
            lines = ['<?php', '', 'declare(strict_types=1);', '', 'namespace ' + namespace + ';', '',
                     '/**', ' * Invocation-scoped ' + interface + '.' + resource['name'] + ' capability.',
                     ' * Explicit release ends ownership; retained objects cannot extend invocation authority.', ' */',
                     'interface ' + classname, '{', '    /**',
                     '     * Explicitly release ownership; duplicate release and later use violate resource lifetime.',
                     '     */', '    public function close(): void;', '']
            for function in resource['functions']:
                native, documented = types(interface, function['result'])
                lines.extend(['    /**', '     * Invoke canonical ' + resource['name'] + '.' + function['name'] + ' on this live resource.',
                              '     * Ordinary host failures are distinct from protocol violations.'])
                parameters = []
                for argument in function['arguments']:
                    argtype, argdoc = types(interface, argument['type'])
                    argname = camel(argument['name'])
                    lines.append('     * @param ' + argdoc + ' $' + argname)
                    parameters.append(argtype + ' $' + argname)
                if documented != 'void':
                    lines.append('     * @return ' + documented)
                lines.extend(['     */', '    public function ' + camel(function['name']) + '(' + ', '.join(parameters) + '): ' + native + ';', ''])
            lines.extend(['}', ''])
            outputs[ROOT / 'src/Contract' / pascal(interface) / (classname + '.php')] = '\n'.join(lines)

    def codec_class(interface):
        return '\\Stashd\\PluginSdk\\Runtime\\Codec\\Generated\\' + pascal(interface) + 'Codec'

    def expression(interface, specification, value, encode=False):
        kind = specification['kind']
        if kind == 'scalar':
            scalar = specification['name']
            if encode:
                if scalar == 'u64':
                    return value + '->decimal'
                if scalar == 's64':
                    return '(string) ' + value
                return value
            if scalar == 'string':
                return 'Values::text(' + value + ')'
            if scalar == 'bool':
                return 'Values::boolean(' + value + ')'
            if scalar == 'u64':
                return 'Values::unsigned(' + value + ')'
            if scalar == 's64':
                return 'Values::signed(' + value + ')'
            method = 'floating' if scalar in ('f32', 'f64') else 'integer'
            return "Values::" + method + "('" + scalar + "', " + value + ')'
        if kind == 'option':
            return '(' + value + ' === null ? null : ' + expression(interface, specification['value'], value, encode) + ')'
        if kind == 'list':
            native, documented = types(interface, specification['value'])
            output = expression(interface, specification['value'], '$element', encode)
            return 'array_map(static fn (' + (native if encode else 'mixed') + ' $element) => ' + output + ', ' + (value if encode else 'Values::list(' + value + ')') + ')'
        if kind == 'named':
            name = specification['name']
            owner = interfaces[interface].get('uses', {}).get(name, interface)
            return codec_class(owner) + '::' + ('encode' if encode else 'decode') + pascal(name) + '(' + value + ')'
        raise ValueError(specification)

    resource_names = {(interface, resource['name']) for interface, definition in interfaces.items() for resource in definition['resources']}

    def has_resource(interface, specification, visited=None):
        if specification is None:
            return False
        visited = set() if visited is None else visited
        kind = specification['kind']
        if kind in ('list', 'option', 'borrow'):
            return has_resource(interface, specification['value'], visited)
        if kind != 'named':
            return False
        name = specification['name']
        owner = interfaces[interface].get('uses', {}).get(name, interface)
        if (owner, name) in resource_names:
            return True
        if (owner, name) in visited:
            return False
        visited.add((owner, name))
        definition = interfaces[owner]
        if name in definition['records']:
            return any(has_resource(owner, field['type'], visited) for field in definition['records'][name]['fields'])
        return False

    for interface, definition in interfaces.items():
        lines = ['<?php', '', 'declare(strict_types=1);', '', 'namespace Stashd\\PluginSdk\\Runtime\\Codec\\Generated;', '',
                 'use Stashd\\PluginSdk\\Runtime\\Codec\\Values;', 'use Stashd\\PluginSdk\\Runtime\\ProtocolViolation;', '',
                 '/**', ' * Exact focused value codec for frozen ' + interface + ' declarations.', ' */',
                 'final class ' + pascal(interface) + 'Codec', '{']
        for name, record in definition['records'].items():
            if has_resource(interface, {'kind': 'named', 'name': name}):
                continue
            classname = named(interface, name)
            fields = record['fields']
            lines.extend(['    /**', '     * Decode all and only the declared fields before constructing an immutable value.',
                          '     */', '    public static function decode' + pascal(name) + '(mixed $value): ' + classname, '    {',
                          '        $record = Values::record($value, [' + ', '.join("'" + field['name'] + "'" for field in fields) + ']);', '',
                          '        return new ' + classname + '('])
            for field in fields:
                lines.append('            ' + expression(interface, field['type'], "$record->{'" + field['name'] + "'}") + ',')
            lines.extend(['        );', '    }', '', '    /**', '     * Encode canonical field spellings without leaking PHP names into the wire.', '     */',
                          '    public static function encode' + pascal(name) + '(' + classname + ' $value): \\stdClass', '    {',
                          '        return (object) ['])
            for field in fields:
                lines.append("            '" + field['name'] + "' => " + expression(interface, field['type'], '$value->' + camel(field['name']), True) + ',')
            lines.extend(['        ];', '    }', ''])
        for name, enum in definition['enums'].items():
            classname = named(interface, name)
            lines.extend(['    /**', '     * Decode only a declared canonical enum spelling.', '     */',
                          '    public static function decode' + pascal(name) + '(mixed $value): ' + classname, '    {',
                          '        return ' + classname + '::tryFrom(Values::text($value)) ?? throw new ProtocolViolation(\'Unknown enum case\');',
                          '    }', '', '    /**', '     * Encode the exact protocol identity of this enum case.', '     */',
                          '    public static function encode' + pascal(name) + '(' + classname + ' $value): string', '    {',
                          '        return $value->value;', '    }', ''])
        for name, variant in definition['variants'].items():
            classname = named(interface, name)
            lines.extend(['    /**', '     * Decode a payloadless string or exact tagged payload, never accepting extra fields.', '     */',
                          '    public static function decode' + pascal(name) + '(mixed $value): ' + classname, '    {',
                          '        if (is_string($value)) {', '            return match ($value) {'])
            for case in variant['values']:
                if case['type'] is None:
                    lines.append("                '" + case['name'] + "' => new " + classname + pascal(case['name']) + '(),')
            lines.extend(["                default => throw new ProtocolViolation('Unknown payloadless variant case'),", '            };', '        }', '',
                          "        $record = Values::record($value, ['tag', 'value']);", '', '        return match (Values::text($record->tag)) {'])
            for case in variant['values']:
                if case['type'] is not None:
                    lines.append("            '" + case['name'] + "' => new " + classname + pascal(case['name']) + '(' + expression(interface, case['type'], '$record->value') + '),')
            lines.extend(["            default => throw new ProtocolViolation('Unknown payload-bearing variant case'),", '        };', '    }', '',
                          '    /**', '     * Encode only canonical concrete branches, rejecting foreign implementations of the union.', '     */',
                          '    public static function encode' + pascal(name) + '(' + classname + ' $value): ' + '|'.join(kind for kind, present in [('string', any(case['type'] is None for case in variant['values'])), ('\\stdClass', any(case['type'] is not None for case in variant['values']))] if present), '    {',
                          '        return match (true) {'])
            for case in variant['values']:
                output = "'" + case['name'] + "'" if case['type'] is None else "(object) ['tag' => '" + case['name'] + "', 'value' => " + expression(interface, case['type'], '$value->value', True) + ']'
                lines.append('            $value instanceof ' + classname + pascal(case['name']) + ' => ' + output + ',')
            lines.extend(["            default => throw new ProtocolViolation('Foreign variant implementation'),", '        };', '    }', ''])
        lines.extend(['}', ''])
        outputs[ROOT / 'src/Runtime/Codec/Generated' / (pascal(interface) + 'Codec.php')] = '\n'.join(lines)

    failures = []
    for path, content in outputs.items():
        if arguments.check:
            if not path.exists() or path.read_text() != content:
                failures.append(str(path.relative_to(ROOT)))
        else:
            path.parent.mkdir(parents=True, exist_ok=True)
            path.write_text(content)
    if failures:
        raise SystemExit('Stale generated contract files: ' + ', '.join(failures))
    print(f'{len(outputs)} frozen contract declarations verified' if arguments.check else f'{len(outputs)} contract declarations generated')


if __name__ == '__main__':
    main()
