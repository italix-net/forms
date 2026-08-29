<?php
/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at https://mozilla.org/MPL/2.0/.
 */
/**
 * Italix Forms — the properties that would break silently
 *
 * This library had no tests at all for 3,820 lines, which made it one of the
 * three the framework could not safely change: nothing objected afterwards.
 * These are not coverage; they are the assertions whose failure would not
 * otherwise be noticed until it reached a browser.
 *
 * Two of them are security properties and are written adversarially — a
 * recognisable secret is planted and the output is searched for it, rather
 * than the code being asked whether it redacted anything.
 *
 * Run: php src/Libs/Italix/Forms/tests/FormMetaTest.php
 */

declare(strict_types=1);

(static function (): void {
    foreach ([
        __DIR__ . '/../../../../../vendor/autoload.php',
        __DIR__ . '/../../../../vendor/autoload.php',
        __DIR__ . '/../../../autoload.php',
        __DIR__ . '/../vendor/autoload.php',
    ] as $autoload) {
        if (is_file($autoload)) {
            require_once $autoload;

            return;
        }
    }

    fwrite(STDERR, "Could not find an autoloader. Run composer install.\n");
    exit(2);
})();

use Italix\Forms\FormMeta;

use function Italix\Forms\form_meta;
use function Italix\Testing\{suite, section, test, summary};

suite('Italix Forms — metadata, redaction and escaping');

/** The columns every case below starts from. A neutral domain, per the boundary rule. */
$columns = static fn (): array => [
    'id'         => ['type' => 'INTEGER', 'nullable' => false, 'primary_key' => true],
    'title'      => ['type' => 'VARCHAR', 'length' => 200, 'nullable' => false],
    'summary'    => ['type' => 'TEXT',    'nullable' => true],
    'api_secret' => ['type' => 'VARCHAR', 'length' => 64,  'nullable' => true],
    'views_n'    => ['type' => 'INTEGER', 'nullable' => true],
];

// -----------------------------------------------------------------------------
section('the form reads its shape from the column definitions');

$form = form_meta($columns());

test('form_meta() accepts a plain array, with no ORM anywhere', $form instanceof FormMeta,
    'this is what makes the library usable on a CSV row or an API payload');
test('every column becomes a field', $form->count() === 5, 'count: ' . $form->count());
test('a NOT NULL column is required', $form->field('title')->is_required());
test('a nullable one is not', !$form->field('summary')->is_required());
test('the label is derived when none is given', $form->field('api_secret')->get_label() !== '');
test('an unknown column is refused rather than invented',
    (static function () use ($form): bool {
        try {
            $form->field('no_such_column');

            return false;
        } catch (\Throwable $e) {
            return true;
        }
    })(),
    'returning an empty FieldMeta would put a silent blank input on the page');

// -----------------------------------------------------------------------------
section('sensitive fields are redacted — the property, not the flag');

// The adversarial part: the secret is planted somewhere a redaction written
// against a field list would miss — inside an attribute and a placeholder —
// and the assertion searches the *output* for it rather than asking the object
// whether it redacted anything.
$secret_c = 'SUPERSECRET-a1b2c3d4';

$form = form_meta($columns());
$form->field('api_secret')
    ->sensitive()
    ->placeholder($secret_c)
    ->help('current value: ' . $secret_c)
    ->attr('data-current', $secret_c);

$json = $form->to_json();

test('THE SECRET IS NOWHERE IN THE JSON', strpos($json, $secret_c) === false,
    'a form definition is handed to a JavaScript builder; anything in it has left the server');
test('…and the field itself still appears, so the form still renders',
    strpos($json, 'api_secret') !== false);
test('…marked as sensitive, so a client can style it', strpos($json, '"sensitive":true') !== false);

$exported = $form->to_array();

test('the redacted entry carries no attributes key at all',
    !array_key_exists('attributes', $exported['fields']['api_secret']),
    'an empty array would still be a place for the next contributor to put something back');
test('nor a placeholder or help', !array_key_exists('placeholder', $exported['fields']['api_secret'])
    && !array_key_exists('help', $exported['fields']['api_secret']));

test('a non-sensitive field is not redacted',
    ($form->to_array()['fields']['title']['attributes'] ?? null) !== null);

// The flag has to be real in both directions, or the redaction is a coincidence.
test('include_sensitive: true returns it, for a server-side caller that needs it',
    strpos($form->to_json(0, true), $secret_c) !== false);

// -----------------------------------------------------------------------------
section('exclusion is total, not cosmetic');

$form = form_meta($columns())->exclude('api_secret');

$names_from = static function (iterable $fields): array {
    $names = [];

    foreach ($fields as $name_c => $field) {
        $names[] = $name_c;
    }

    return $names;
};

test('an excluded column is gone from each()', !in_array('api_secret', $names_from($form->each()), true));
test('AND FROM all(), WHICH IS THE ONE THAT INCLUDES HIDDEN FIELDS',
    !in_array('api_secret', $names_from($form->all()), true),
    'a second iterator that forgot the exclusion list is how an excluded column reaches a page');
test('…and from the count', $form->count() === 4, 'count: ' . $form->count());
test('…and from by_section()',
    !in_array('api_secret', array_merge(...array_map(
        static fn (array $group): array => array_keys($group['fields']),
        array_values($form->by_section())
    )), true));
// The definition is gone; the *name* stays, in an `excluded` list that the
// export carries on purpose so a client builder knows the column was withheld
// rather than forgotten.
//
// Pinned rather than argued with, because it is a design decision and not a
// defect — but it is worth a reader knowing: excluding a column because its
// name is itself sensitive does not achieve that, and `sensitive()` is the
// tool for the payload while exclusion is the tool for the layout.
$exported = $form->to_array();

test('the excluded field carries no definition', !array_key_exists('api_secret', $exported['fields']));
test('…and its name appears only in the excluded list, deliberately',
    $exported['excluded'] === ['api_secret']
    && substr_count($form->to_json(), 'api_secret') === 1);

// -----------------------------------------------------------------------------
section('hidden is not excluded, and the difference is load-bearing');

$form = form_meta($columns());
$form->field('id')->hidden();

test('a hidden field is skipped by each()', !in_array('id', $names_from($form->each()), true));
test('…but kept by all()', in_array('id', $names_from($form->all()), true),
    'the form still has to submit it');
test('…and to_array() goes through each(), so it is absent there too',
    !array_key_exists('id', $form->to_array()['fields']),
    'pinned because it is surprising: a hidden field is not in the exported definition at all');

// -----------------------------------------------------------------------------
section('ordering and sections');

// This block exists because these two methods were edited on 2026-08-14 — three
// variables renamed for the naming check — and nothing in the framework could
// have objected if a substitution had gone wrong inside an interpolated string.
$form = form_meta($columns());
$form->field('summary')->order(1);
$form->field('title')->order(2);

$ordered = $names_from($form->each());

test('order() decides the sequence', array_slice($ordered, 0, 2) === ['summary', 'title'],
    implode(', ', $ordered));
test('a field with no order sorts after the ones that have it',
    array_search('views_n', $ordered, true) > array_search('title', $ordered, true));

$form = form_meta($columns());
$form->field('title')->group('main');
$form->field('summary')->group('main');
$form->field('views_n')->group('stats');

$sections = $form->by_section();

test('by_section() groups by group()', isset($sections['main'], $sections['stats']));
test('…with the right fields in each',
    array_keys($sections['main']['fields']) === ['title', 'summary']
    && array_keys($sections['stats']['fields']) === ['views_n'],
    implode('|', array_keys($sections['main']['fields'])));
test('…and every group carries a section object',
    $sections['main']['section'] instanceof \Italix\Forms\FormSection);

$form = form_meta($columns())->default_section('general');
$form->field('title')->group('main');

test('ungrouped fields land in the default section',
    in_array('summary', array_keys($form->by_section()['general']['fields'] ?? []), true));

// -----------------------------------------------------------------------------
section('the fluent API returns the right object, or a chain silently drops work');

$form  = form_meta($columns());
$field = $form->field('title');

test('FieldMeta setters return the field', $field->label('T') === $field
    && $field->placeholder('p') === $field
    && $field->attr('x', '1') === $field);
test('FormMeta setters return the form',
    $form->exclude('views_n') === $form && $form->default_section('g') === $form);
test('field() hands back the same instance each time, so configuration accumulates',
    $form->field('title') === $field,
    'a fresh FieldMeta per call would discard every setting made before it');

exit(summary());
