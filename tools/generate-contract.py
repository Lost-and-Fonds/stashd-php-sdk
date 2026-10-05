#!/usr/bin/env python3
"""Generate typed immutable contract facts from the frozen language-neutral schema."""

import argparse
import json
import subprocess
import tempfile
from pathlib import Path

ROOT = Path(__file__).resolve().parent.parent
SCHEMA = ROOT / 'resources/contract/wit-schema.json'
PREFIX = 'Stashd\\PluginSdk\\Contract'
HELPER_DOCS = {
    'helper-output': 'One nonempty arbitrary byte chunk from the live stdout or stderr channel; bytes are not lines.',
    'helper-output.channel': 'The original stdout or stderr channel of these bytes.',
    'helper-output.bytes': 'Nonempty unmodified output bytes, including carriage returns and invalid text encodings.',
    'helper-exit': 'Normal child exit, including non-zero codes, optionally returning the still-valid owned staged writer.',
    'helper-exit.code': 'Signed 32-bit normal child exit code; non-zero does not mean host runtime failure.',
    'helper-exit.output': 'Owned staged stdout writer returned only after normal exit so the plugin can finish it.',
    'helper-event': 'One live output chunk, cumulative staged stdout activity count, or the sole terminal event.',
    'helper-event.output': 'Live stdout or stderr bytes; staged stdout is never duplicated here.',
    'helper-event.stdout-activity': 'Monotonic cumulative bytes accepted by the staged stdout writer.',
    'helper-event.terminal': 'Sole process outcome after all accepted output and final staged activity.',
    'helper-terminal': 'Exactly one normal exit, cancellation, timeout or host/runtime failure before EOF.',
    'helper-terminal.exited': 'Normal exit returns the valid owned staged writer when stdout was staged.',
    'helper-terminal.cancelled': 'The process was cancelled before another outcome; no writer is returned.',
    'helper-terminal.timed-out': 'Host timeout ended the process; its staged writer is discarded.',
    'helper-terminal.failed': 'Host/runtime failure ended the process; its staged writer is discarded.',
    'helper-process': 'A running helper, available during this call; closing it stops and reaps the child.',
    'helper-process.next-event': 'Wait for accepted output or activity, then one terminal event, then sticky EOF.',
    'helper-process.cancel': 'Request cancellation; repeated requests do nothing, and an earlier outcome takes priority.',
    'helper-output-stream': 'Identifies which child output pipe delivered diagnostic bytes.',
    'helper-output-stream.stdout': 'Unstaged stdout byte channel; never emitted with staged stdout.',
    'helper-output-stream.stderr': 'Live stderr byte channel, including when stdout is staged.',
    'input-host.discovered-item': 'An item the plugin can later retrieve using its stable ID and opaque reference.',
    'input-host.discovered-item.id': 'Stable item identity used when the host requests acquisition.',
    'input-host.discovered-item.reference': 'Opaque value the plugin uses to retrieve the item; preserved exactly.',
    'input-host.discovered-item.delegation': 'Optional opaque handoff reference for another Input plugin.',
    'input-host.discovered-item.size-bytes': 'Optional known or estimated total item size in bytes.',
    'input-host.discovered-item.size-estimated': 'Whether the supplied item size is an estimate.',
    'input-host.discovered-item.metadata': 'Plugin-owned metadata facets associated with this item.',
    'input-plugin.resolved-input.size-bytes': 'Optional known or estimated total input size in bytes.',
    'io-host.preserved-asset.size-bytes': 'Total bytes in the saved file.',
    'io-host.staged-artifact.size-bytes': 'Total bytes written to this staged output.',
    'io-host.helper-event.stdout-activity.value': 'Cumulative bytes accepted by the staged stdout writer.',
}
RECORD_DOCS: dict[str, str] = {
    'deficiency': 'A known gap in the saved result and whether retrying may fill it.',
    'discovery-batch': 'Items to save together with a discovery checkpoint.',
    'discovery-continuation': 'A restart point for an unfinished discovery run, with no credentials.',
    'discovery-refresh-state': 'A baseline shared between successfully completed discovery runs.',
    'input-delegation': 'An opaque reference for another Input plugin to resolve.',
    'outcome-diagnostic': 'An explanation of a result, with supporting metadata.',
    'acquisition-options': 'Settings and credentials for saving an item.',
    'acquisition-result': 'Saved outputs and a description of any missing work.',
    'discovery-request': 'Settings for a discovery run; these stay fixed when the run resumes.',
    'input-option': 'A named setting for finding or saving items.',
    'resolved-input': 'An identified source that can be searched in a later call.',
    'source-value': 'A named value used to identify an input source.',
    'item': 'An item and its saved assets and metadata.',
    'published-file': 'A file published for a saved asset.',
    'choice': 'A selectable value and its display label.',
    'destination-configuration': 'Settings for a publication destination.',
    'operation-request': 'A named operation and the values it needs.',
    'operation-result': 'Choices and values returned by an operation.',
    'publication': 'The output of publishing a collection.',
    'publish-request': 'A collection to publish and a reporter for the resulting files.',
    'setting': 'A named configuration value.',
    'item-context': 'An item and the saved assets available for enrichment.',
    'capability': 'An enrichment task and the options it accepts.',
    'configuration-choice': 'A selectable configuration value and its display label.',
    'configuration-option': 'An option the caller can configure.',
    'configuration-value': 'A selected configuration value.',
    'derived-asset': 'A new asset and the source assets and activity used to create it.',
    'enrichment-result': 'Metadata and assets produced by enrichment.',
    'collection': 'A titled list of entries to export.',
    'collection-entry': 'A reference and optional title to include in an export.',
    'exported-artifact': 'An exported file returned as bytes.',
    'http-header': 'One HTTP header name and value.',
    'http-request': 'An HTTP request; sending it transfers ownership of its body stream.',
    'http-response': 'An HTTP response with a readable body stream.',
    'credential-binding': 'A named credential available during the current call.',
    'credential-reference': 'An opaque credential selector; knowing its ID does not grant access.',
    'plugin-metadata': 'Plugin-defined JSON metadata identified by a schema.',
    'preserved-asset': 'A saved asset that a plugin may read during the current call.',
    'staged-artifact': 'A receipt for completed output; its fields must match the saved receipt.',
    'plugin-error-detail': 'An error explanation and whether retrying may help.',
    'progress': 'A work stage and optional completion fraction between zero and one.',
}
FIELD_DOCS = {
    'id': 'Stable identifier used in later calls.',
    'reference': 'Opaque reference interpreted by the service that issued it.',
    'media-type': 'Media type when known; null when unspecified.',
    'metadata': 'Metadata facets supplied by the plugin.',
    'size-bytes': 'Known or estimated size in bytes; null when unknown.',
    'size-estimated': 'Whether the supplied size is an estimate.',
    'key': 'Name of the setting supplied by the plugin.',
    'value': 'Value associated with this entry.',
    'label': 'Text shown to the caller for this choice.',
    'message': 'Human-readable explanation of what happened.',
    'retryable': 'Whether retrying the failed work may succeed.',
    'items': 'Items included in this batch, in order.',
    'assets': 'Assets associated with this result.',
    'artifacts': 'Completed outputs returned for the host to save.',
    'options': 'Settings selected for this work.',
    'credentials': 'Named credentials available during this call.',
    'settings': 'Configuration values for this operation.',
    'choices': 'Values the caller may select.',
    'headers': 'HTTP headers in order, including repeated names.',
    'method': 'Case-sensitive HTTP method token.',
    'url': 'Address to request.',
    'status': 'HTTP response status code.',
    'body': 'Stream containing the body bytes.',
    'credential': 'Credential to use; authorization and availability are checked for each request.',
    'schema': 'Non-empty schema identifier chosen by the metadata producer.',
    'json': 'JSON object text with no duplicate member names; must not contain secrets.',
    'fraction': 'Completion fraction from zero to one; null for unknown progress.',
    'stage': 'Description of the work currently in progress.',
    'maximum-items-per-batch': 'Positive upper limit on items per batch; frame size may require fewer.',
    'maximum-report-records-per-batch': 'Upper limit on file reports per batch.',
    'continuation': 'Restart point for unfinished work; null starts a new run.',
    'refresh-state': 'Baseline from a completed run; null when none is available.',
    'estimated-item-count': 'Estimated number of items; null when unknown.',
    'canonical-reference': 'Opaque source reference that remains usable across calls; null when absent.',
    'disposition': 'Whether later work may fill this gap.',
    'diagnostic': 'Explanation of the missing work.',
    'evidence': 'Plugin-owned metadata supporting the explanation.',
    'progress': 'Checkpoint or completion result saved together with these items.',
    'outcome': 'Whether all intended work completed, including any known gaps.',
    'intent': 'Whether to refresh known work or enumerate the full input.',
    'input-id': 'Input identifier returned when the source was resolved.',
    'item-id': 'Identifier of the item this result belongs to.',
    'asset-id': 'Identifier of the saved asset this file represents.',
    'relative-path': 'Published path relative to the destination.',
    'required': 'Whether the caller must select a value.',
    'derived-from': 'Source asset IDs used to produce this asset.',
    'activity': 'Plugin-defined name of the work that produced the asset.',
    'activity-version': 'Version of the activity that produced the asset.',
    'entries': 'Collection entries in export order.',
    'title': 'Display title when supplied.',
    'filename': 'Suggested name for the exported file.',
    'contents': 'Exported file bytes.',
    'collection': 'Collection available to read for publication.',
    'reporter': 'Reporter used to record published files.',
    'artifact': 'Completed output produced by this operation.',
    'files': 'Whether the publication supplied a complete file report.',
    'payload': 'Additional data for the selected operation.',
    'values': 'Named values returned by the operation.',
    'name': 'Name used to select this entry.',
    'revision': 'Revision of the capability definition.',
    'delegation': 'Reference for another Input plugin; null when no handoff is needed.',
}
RECORD_FIELD_DOCS: dict[str, str] = {
    'discovery-continuation.value': 'Plugin-owned restart data that works across processes and contains no credentials.',
    'discovery-refresh-state.value': 'Plugin-owned baseline from a successfully completed run.',
    'preserved-asset.reference': 'Opaque read reference, not a path or URL; valid only for this call and not an access grant by itself.',
    'preserved-asset.id': 'Stable asset ID.',
    'credential-reference.id': 'Opaque credential selector; the host checks permission each time it is used.',
    'credential-binding.name': 'Plugin-defined slot name; used as the environment variable name for helpers.',
    'credential-binding.reference': 'Authorized credential available for this call.',
    'http-request.body': 'Request body stream transferred to the host; null when there is no body.',
}
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
    if schema['package'] != 'stashd:plugin@0.18.0':
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
                      'u64': 'string', 's64': 'int'}.get(name, 'int')
            return native, native
        if kind == 'named':
            target = named(interface, specification['name'])
            return target, target
        if kind == 'list':
            _, element = types(interface, specification['value'])
            return 'array', 'list<' + element + '>'
        if kind == 'option':
            native, documented = types(interface, specification['value'])
            return '?' + native, documented + '|null'
        if kind == 'borrow':
            return types(interface, specification['value'])
        if kind == 'result':
            return types(interface, specification['ok']) if specification['ok'] else ('void', 'void')
        raise ValueError(specification)

    outputs = {}
    for interface, definition in interfaces.items():
        namespace = PREFIX + '\\' + pascal(interface)
        def prose(name: str, fallback: str) -> str:
            return HELPER_DOCS.get(interface + '.' + name, HELPER_DOCS.get(name, fallback))

        for name, record in definition['records'].items():
            classname = pascal(name)
            fields = record['fields']
            description = prose(name, RECORD_DOCS[name] if name in RECORD_DOCS else 'Values describing ' + name.replace('-', ' ') + '.')
            lines = ['<?php', '', 'declare(strict_types=1);', '', 'namespace ' + namespace + ';', '',
                     '/**', ' * ' + description, ' */',
                     'final readonly class ' + classname, '{', '    /**',
                     '     * Create ' + ('a saved asset reference' if name == 'preserved-asset' else 'the ' + name.replace('-', ' ')) + '.', '     *']
            for field in fields:
                _, documented = types(interface, field['type'])
                field_key = name + '.' + field['name']
                fallback = RECORD_FIELD_DOCS[field_key] if field_key in RECORD_FIELD_DOCS else FIELD_DOCS[field['name']] if field['name'] in FIELD_DOCS else 'The supplied ' + field['name'].replace('-', ' ') + '.'
                description = prose(field_key, fallback)
                lines.append('     * @param ' + documented + ' $' + camel(field['name']) + ' ' + description)
            lines.extend(['     */', '    public function __construct('])
            for field in fields:
                native, _ = types(interface, field['type'])
                lines.append('        public ' + native + ' $' + camel(field['name']) + ',')
            lines.extend(['    ) {}', '}', ''])
            outputs[ROOT / 'src/Contract' / pascal(interface) / (classname + '.php')] = '\n'.join(lines)
        for name, enum in definition['enums'].items():
            lines = ['<?php', '', 'declare(strict_types=1);', '', 'namespace ' + namespace + ';', '',
                     '/**', ' * ' + prose(name, 'Available ' + name.replace('-', ' ') + ' values.'), ' */',
                     'enum ' + pascal(name) + ': string', '{']
            for value in enum['values']:
                description = prose(name + '.' + value, ENUM_CASE_DOCS[(interface, name, value)] if (interface, name, value) in ENUM_CASE_DOCS else 'Selects ' + value.replace('-', ' ') + '.')
                lines.extend(['    /**', '     * ' + description, '     */', '    case ' + pascal(value) + " = '" + value + "';"])
            lines.extend(['}', ''])
            outputs[ROOT / 'src/Contract' / pascal(interface) / (pascal(name) + '.php')] = '\n'.join(lines)
        for name, variant in definition['variants'].items():
            base = pascal(name)
            lines = ['<?php', '', 'declare(strict_types=1);', '', 'namespace ' + namespace + ';', '',
                     '/**', ' * ' + prose(name, 'Possible ' + name.replace('-', ' ') + ' values.'), ' */',
                     'interface ' + base, '{', '}', '']
            outputs[ROOT / 'src/Contract' / pascal(interface) / (base + '.php')] = '\n'.join(lines)
            for case in variant['values']:
                classname = base + pascal(case['name'])
                lines = ['<?php', '', 'declare(strict_types=1);', '', 'namespace ' + namespace + ';', '',
                         '/**', ' * ' + prose(name + '.' + case['name'], 'The ' + case['name'].replace('-', ' ') + ' form of ' + name.replace('-', ' ') + '.'), ' */',
                         'final readonly class ' + classname + ' implements ' + base, '{']
                if case['type'] is not None:
                    native, documented = types(interface, case['type'])
                    lines.extend(['    /**', '     * ' + prose(name + '.' + case['name'], 'Data carried by this result.'),
                                  '     * @var ' + documented, '     */', '    public ' + native + ' $value;', '',
                                  '    /**', '     * Create this result with its associated data.',
                                  '     * @param ' + documented + ' $value', '     */',
                                  '    public function __construct(' + native + ' $value)', '    {',
                                  '        $this->value = $value;', '    }'])
                lines.extend(['}', ''])
                outputs[ROOT / 'src/Contract' / pascal(interface) / (classname + '.php')] = '\n'.join(lines)

        if interface.endswith('-plugin'):
            lines = ['<?php', '', 'declare(strict_types=1);', '', 'namespace ' + namespace + ';', '',
                     '/**', ' * Internal ' + interface + ' methods used by the runtime.', ' */',
                     'interface Plugin', '{']
            for function in definition['functions']:
                result = function['result']
                native, documented = types(interface, result)
                if result and result['kind'] == 'result':
                    error_native, error_doc = types(interface, result['error'])
                    native = (native if native != 'void' else 'null') + '|' + error_native
                    documented = (documented if documented != 'void' else 'null') + '|' + error_doc
                lines.extend(['    /**', '     * Run ' + function['name'].replace('-', ' ') + ' with the supplied values.'])
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
                     '/**', ' * ' + prose(resource['name'], 'A ' + resource['name'].replace('-', ' ') + ' available during the current call.'),
                     ' * Close it when finished; it cannot be used after the call ends.', ' */',
                     'interface ' + classname, '{', '    /**',
                     '     * Release this resource; closing it again or using it afterwards is an error.',
                     '     */', '    public function close(): void;', '']
            for function in resource['functions']:
                native, documented = types(interface, function['result'])
                lines.extend(['    /**', '     * ' + prose(resource['name'] + '.' + function['name'], 'Run ' + function['name'].replace('-', ' ') + ' on this ' + resource['name'].replace('-', ' ') + '.'),
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
                    return value
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
        if kind == 'result':
            return has_resource(interface, specification['ok'], visited) or has_resource(interface, specification['error'], visited)
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
            return any(has_resource(owner, field['type'], visited.copy()) for field in definition['records'][name]['fields'])
        if name in definition['variants']:
            return any(has_resource(owner, case['type'], visited.copy()) for case in definition['variants'][name]['values'])
        return False

    for interface, definition in interfaces.items():
        lines = ['<?php', '', 'declare(strict_types=1);', '', 'namespace Stashd\\PluginSdk\\Runtime\\Codec\\Generated;', '',
                 'use Stashd\\PluginSdk\\Runtime\\Codec\\Values;', 'use Stashd\\PluginSdk\\Runtime\\ProtocolViolation;', '',
                 '/**', ' * Converts JSON values to and from ' + interface + ' declarations.', ' */',
                 'final class ' + pascal(interface) + 'Codec', '{']
        for name, record in definition['records'].items():
            if has_resource(interface, {'kind': 'named', 'name': name}):
                continue
            classname = named(interface, name)
            fields = record['fields']
            lines.extend(['    /**', '     * Read the expected fields, rejecting missing or extra fields.',
                          '     */', '    public static function decode' + pascal(name) + '(mixed $value): ' + classname, '    {',
                          '        $record = Values::record($value, [' + ', '.join("'" + field['name'] + "'" for field in fields) + ']);'])
            bounded_size = interface == 'io-host' and name in ('preserved-asset', 'staged-artifact')
            if bounded_size:
                lines.extend(["        $size = Values::unsigned($record->{'size-bytes'});", '',
                              '        if (strlen($size) > strlen((string) PHP_INT_MAX) || (strlen($size) === strlen((string) PHP_INT_MAX) && strcmp($size, (string) PHP_INT_MAX) > 0)) {',
                              "            throw new ProtocolViolation('Byte size exceeds the PHP integer limit');", '        }'])
            lines.extend(['', '        return new ' + classname + '('])
            for field in fields:
                value = '(string) (int) $size' if bounded_size and field['name'] == 'size-bytes' else expression(interface, field['type'], "$record->{'" + field['name'] + "'}")
                lines.append('            ' + value + ',')
            lines.extend(['        );', '    }', '', '    /**', '     * Write the field names expected by the host.', '     */',
                          '    public static function encode' + pascal(name) + '(' + classname + ' $value): \\stdClass', '    {',
                          '        return (object) ['])
            for field in fields:
                lines.append("            '" + field['name'] + "' => " + expression(interface, field['type'], '$value->' + camel(field['name']), True) + ',')
            lines.extend(['        ];', '    }', ''])
        for name, enum in definition['enums'].items():
            classname = named(interface, name)
            lines.extend(['    /**', '     * Read a supported enum value.', '     */',
                          '    public static function decode' + pascal(name) + '(mixed $value): ' + classname, '    {',
                          '        return ' + classname + '::tryFrom(Values::text($value)) ?? throw new ProtocolViolation(\'Unknown enum case\');',
                          '    }', '', '    /**', '     * Write the enum value expected by the host.', '     */',
                          '    public static function encode' + pascal(name) + '(' + classname + ' $value): string', '    {',
                          '        return $value->value;', '    }', ''])
        for name, variant in definition['variants'].items():
            if has_resource(interface, {'kind': 'named', 'name': name}):
                continue
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
                          '    /**', '     * Write a supported result type, rejecting unrecognized implementations.', '     */',
                          '    public static function encode' + pascal(name) + '(' + classname + ' $value): ' + '|'.join(kind for kind, present in [('string', any(case['type'] is None for case in variant['values'])), ('\\stdClass', any(case['type'] is not None for case in variant['values']))] if present), '    {',
                          '        return match (true) {'])
            for case in variant['values']:
                output = "'" + case['name'] + "'" if case['type'] is None else "(object) ['tag' => '" + case['name'] + "', 'value' => " + expression(interface, case['type'], '$value->value', True) + ']'
                lines.append('            $value instanceof ' + classname + pascal(case['name']) + ' => ' + output + ',')
            lines.extend(["            default => throw new ProtocolViolation('Foreign variant implementation'),", '        };', '    }', ''])
        lines.extend(['}', ''])
        outputs[ROOT / 'src/Runtime/Codec/Generated' / (pascal(interface) + 'Codec.php')] = '\n'.join(lines)

    generated_roots = [ROOT / 'src/Contract', ROOT / 'src/Runtime/Codec/Generated']
    obsolete = sorted(path for directory in generated_roots for path in directory.rglob('*.php') if path not in outputs)
    if arguments.check and obsolete:
        raise SystemExit('Obsolete generated contract files: ' + ', '.join(str(path.relative_to(ROOT)) for path in obsolete))
    if not arguments.check:
        for path in obsolete:
            path.unlink()

    failures = []
    fixer = [str(ROOT / 'vendor/bin/php-cs-fixer'), 'fix', '--allow-risky=yes',
             '--config=.php-cs-fixer.dist.php', '--path-mode=override', '--using-cache=no']
    for path, content in outputs.items():
        if not arguments.check:
            path.parent.mkdir(parents=True, exist_ok=True)
            path.write_text(content)
    if not arguments.check:
        subprocess.run(fixer + [str(path) for path in outputs], cwd=ROOT, check=True,
                       stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL)
    for path, content in outputs.items():
        if arguments.check:
            if not path.exists():
                failures.append(str(path.relative_to(ROOT)))
                continue
            with tempfile.TemporaryDirectory() as directory:
                candidate = Path(directory) / path.name
                candidate.write_text(content)
                result = subprocess.run(fixer + [str(candidate)], cwd=ROOT,
                                        stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL)
                if result.returncode != 0 or path.read_text() != candidate.read_text():
                    failures.append(str(path.relative_to(ROOT)))
    if failures:
        raise SystemExit('Stale generated contract files: ' + ', '.join(failures))
    print(f'{len(outputs)} frozen contract declarations verified' if arguments.check else f'{len(outputs)} contract declarations generated')


if __name__ == '__main__':
    main()
