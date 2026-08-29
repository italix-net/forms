<?php
/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at https://mozilla.org/MPL/2.0/.
 */
/**
 * Italix Forms — what the schema decides, and what the form decides
 *
 * This package's headline is "build a form from a table schema", and that
 * derivation had no assertions. It is a lookup table with fifteen entries and a
 * pair of rules, which is precisely the shape of thing that drifts: add a column
 * type to an ORM, forget the row here, and the field silently renders as a text
 * input. Nobody files that as a bug — a date arriving as free text just looks
 * like a form somebody built carelessly.
 *
 * The rules matter more than the table. `is_required()` reads `is_nullable()`
 * from the column, so a schema change quietly changes a form's validation; that
 * is the intended behaviour and exactly why it should be impossible to change
 * by accident.
 *
 * Run: php src/Libs/Italix/Forms/tests/DerivationTest.php
 */

declare(strict_types=1);

(static function (): void {
    foreach ([
        __DIR__ . '/../vendor/autoload.php',               // checked out on its own
        __DIR__ . '/../../../../../vendor/autoload.php',   // vendored in a project
        __DIR__ . '/../../../../vendor/autoload.php',      // installed as a package
        __DIR__ . '/../../../autoload.php',                // sibling autoloader
    ] as $autoload) {
        if (is_file($autoload)) {
            require_once $autoload;

            return;
        }
    }

    fwrite(STDERR, "Could not find an autoloader. Run composer install.\n");
    exit(2);
})();

use Italix\Forms\Adapters\GenericColumnAdapter;
use Italix\Forms\Adapters\GenericTableAdapter;
use Italix\Forms\Rendering\FormHtml;
use Italix\Forms\Rendering\WidgetRegistry;

use function Italix\Forms\form_meta;
use function Italix\Testing\{suite, section, test, summary};

suite('Italix Forms — derivation from a schema');

/** @return array{0: bool, 1: string} */
$throws = static function (callable $fn): array {
    try {
        $fn();

        return [false, ''];
    } catch (\Throwable $e) {
        return [true, $e->getMessage()];
    }
};

/** A one-column form, so the derivation can be asked about in isolation. */
$field_for = static function (array $definition) {
    $table = new GenericTableAdapter(['value' => GenericColumnAdapter::from_array('value', $definition)]);

    return form_meta($table)->field('value');
};

// -----------------------------------------------------------------------------
section('the column type chooses the widget');

// The table, written out. Every row is a promise the renderer keeps, and a
// missing row is not an error — it falls through to `text`, which is why the
// absence of this suite was invisible.
$types = [
    'BOOLEAN'          => 'checkbox',
    'TEXT'             => 'textarea',
    'DATE'             => 'date',
    'DATETIME'         => 'datetime-local',
    'TIMESTAMP'        => 'datetime-local',
    'TIME'             => 'time',
    'INTEGER'          => 'number',
    'BIGINT'           => 'number',
    'SMALLINT'         => 'number',
    'SERIAL'           => 'number',
    'BIGSERIAL'        => 'number',
    'DECIMAL'          => 'number',
    'NUMERIC'          => 'number',
    'REAL'             => 'number',
    'DOUBLE PRECISION' => 'number',
    'FLOAT'            => 'number',
    'JSON'             => 'textarea',
    'JSONB'            => 'textarea',
    'VARCHAR'          => 'text',
];

foreach ($types as $column_type_c => $widget_c) {
    $field = $field_for(['type' => $column_type_c]);

    test("{$column_type_c} renders as {$widget_c}", $field->get_type() === $widget_c,
        'got ' . $field->get_type());
}

test('the type is matched case-insensitively',
    $field_for(['type' => 'boolean'])->get_type() === 'checkbox',
    $field_for(['type' => 'boolean'])->get_type());

// A type nobody wrote a row for. Falling back to text is the right behaviour —
// a form that refuses to render because a column is an ENUM is worse — but it
// is a *silent* fallback, so it is pinned here rather than discovered.
test('AN UNKNOWN COLUMN TYPE FALLS BACK TO TEXT, silently and on purpose',
    $field_for(['type' => 'GEOMETRY'])->get_type() === 'text',
    $field_for(['type' => 'GEOMETRY'])->get_type());

// -----------------------------------------------------------------------------
section('what the form is allowed to override');

test('an explicit type wins over the column',
    $field_for(['type' => 'INTEGER'])->type('range')->get_type() === 'range');

test('options turn a field into a select without anyone saying so',
    $field_for(['type' => 'VARCHAR'])->options(['a' => 'A', 'b' => 'B'])->get_type() === 'select');

test('…but an explicit type still wins over that',
    $field_for(['type' => 'VARCHAR'])->options(['a' => 'A'])->type('radio')->get_type() === 'radio');

// -----------------------------------------------------------------------------
section('required comes from the schema, not from the form');

// The load-bearing rule: a column that cannot be null produces a required
// field, with nobody writing a rule. Change the schema and the form follows,
// which is the whole point of deriving it — and the reason it must not be
// possible to break silently.
test('A NOT-NULL COLUMN IS REQUIRED WITH NO RULE WRITTEN',
    $field_for(['type' => 'VARCHAR', 'nullable' => false])->is_required(),
    'the form would accept an empty value the database will refuse');

test('a nullable column is not required',
    !$field_for(['type' => 'VARCHAR', 'nullable' => true])->is_required());

test('…but a rule can make it required anyway',
    $field_for(['type' => 'VARCHAR', 'nullable' => true])->rules(\Italix\Rules\Rule::required())->is_required());

test('the two together do not disagree',
    $field_for(['type' => 'VARCHAR', 'nullable' => false])->rules(\Italix\Rules\Rule::required())->is_required());

// -----------------------------------------------------------------------------
section('length, defaults and the primary key');

// Asserted on the rendered input rather than on a getter, because the length
// is only useful if it reaches the browser: a `varchar(40)` that lets somebody
// type 500 characters is a form that fails at the database, after the work.
$rendered_input = static function (array $definition): string {
    $table = new GenericTableAdapter(
        ['value' => GenericColumnAdapter::from_array('value', $definition)]
    );

    $html = (new FormHtml(form_meta($table)))->render();

    return preg_match('/<input[^>]*>|<textarea[^>]*>/', $html, $m) === 1 ? $m[0] : '';
};

$sized = $field_for(['type' => 'VARCHAR', 'length' => 40]);

test('the column length is on the field metadata', $sized->column()->get_length() === 40,
    var_export($sized->column()->get_length(), true));

test('AND REACHES THE BROWSER AS maxlength',
    strpos($rendered_input(['type' => 'VARCHAR', 'length' => 40]), 'maxlength="40"') !== false,
    $rendered_input(['type' => 'VARCHAR', 'length' => 40]));

test('a column with no length renders no maxlength',
    strpos($rendered_input(['type' => 'TEXT']), 'maxlength') === false,
    $rendered_input(['type' => 'TEXT']));

test('a not-null column renders the required attribute',
    strpos($rendered_input(['type' => 'VARCHAR', 'nullable' => false]), 'required') !== false,
    $rendered_input(['type' => 'VARCHAR', 'nullable' => false]));

test('…and a nullable one does not',
    strpos($rendered_input(['type' => 'VARCHAR', 'nullable' => true]), 'required') === false,
    $rendered_input(['type' => 'VARCHAR', 'nullable' => true]));

$defaulted = $field_for(['type' => 'VARCHAR', 'default' => 'draft', 'has_default' => true]);

test('a column default is readable through the column',
    $defaulted->column()->get_default() === 'draft',
    var_export($defaulted->column()->get_default(), true));

test('…and the column says it has one', $defaulted->column()->has_default());
test('a column without one says so', !$field_for(['type' => 'VARCHAR'])->column()->has_default());

// -----------------------------------------------------------------------------
section('a relation becomes a select');

$related = GenericColumnAdapter::from_array('country_id', [
    'type'     => 'INTEGER',
    'nullable' => false,
    'relation' => ['table' => 'countries', 'key' => 'id', 'label' => 'name'],
]);

test('the column carries the relation', $related->get_relation() !== null);
test('…naming the foreign table', $related->get_relation()->get_foreign_table() === 'countries');
test('…the key', $related->get_relation()->get_foreign_key() === 'id');
test('…and the column to show a person', $related->get_relation()->get_foreign_label() === 'name');

// Nothing is queried until somebody supplies a fetcher: a metadata object that
// opens a database connection on its own is one that cannot be used in a test,
// a CLI tool, or a form that is only being described.
test('WITHOUT A FETCHER, NOTHING IS QUERIED',
    $related->get_relation()->fetch_options() === null,
    'the relation went looking for a database on its own');

$asked_for = null;

$related->get_relation()->set_fetcher(static function (string $table, string $key, string $label, int $max_n)
    use (&$asked_for) {
        $asked_for = [$table, $key, $label, $max_n];

        return [1 => 'Italia', 2 => 'France'];
    });

$options = $related->get_relation()->fetch_options(50);

test('a fetcher is given the table, key and label', array_slice((array) $asked_for, 0, 3)
    === ['countries', 'id', 'name'], json_encode($asked_for));

test('…and asked for ONE MORE than the cap', ($asked_for[3] ?? null) === 51,
    'without the extra row there is no way to tell "exactly 50" from "50 and more"');

test('…and its answer is what comes back', $options === [1 => 'Italia', 2 => 'France']);

// What the extra row is for. A relation with ten thousand rows must not become
// a `<select>` with ten thousand `<option>`s — the page would be unusable and
// the query pointless. Returning null says "too many", and the caller picks a
// lookup field instead.
$many = GenericColumnAdapter::from_array('city_id', [
    'type'     => 'INTEGER',
    'relation' => ['table' => 'cities', 'key' => 'id', 'label' => 'name'],
]);

$many->get_relation()->set_fetcher(static function (string $t, string $k, string $l, int $max_n): array {
    return array_fill(1, $max_n, 'a city');
});

test('MORE OPTIONS THAN THE CAP RETURNS NULL, not a vast select',
    $many->get_relation()->fetch_options(10) === null,
    'a relation with more rows than the cap was rendered in full');

$exactly = GenericColumnAdapter::from_array('city_id', [
    'type'     => 'INTEGER',
    'relation' => ['table' => 'cities', 'key' => 'id', 'label' => 'name'],
]);

$exactly->get_relation()->set_fetcher(static function (string $t, string $k, string $l, int $max_n): array {
    return array_fill(1, $max_n - 1, 'a city');   // exactly the cap, not one over
});

test('…and exactly the cap is still returned', count((array) $exactly->get_relation()->fetch_options(10)) === 10,
    var_export($exactly->get_relation()->fetch_options(10), true));

$broken = GenericColumnAdapter::from_array('x_id', [
    'type'     => 'INTEGER',
    'relation' => ['table' => 'x', 'key' => 'id', 'label' => 'name'],
]);

$broken->get_relation()->set_fetcher(static function () {
    return 'not an array';
});

test('a fetcher that answers nonsense yields null rather than a broken select',
    $broken->get_relation()->fetch_options() === null);

// -----------------------------------------------------------------------------
section('the array source');

// `from_array()` is how a form is described without an ORM at all — a config
// file, a CSV mapping, something written by hand this morning.
$table = new GenericTableAdapter([
    'id'    => GenericColumnAdapter::from_array('id', ['type' => 'SERIAL', 'primary_key' => true]),
    'email' => GenericColumnAdapter::from_array('email', ['type' => 'VARCHAR', 'length' => 255, 'nullable' => false]),
    'bio'   => GenericColumnAdapter::from_array('bio', ['type' => 'TEXT']),
]);

$form = form_meta($table);
$names = [];

foreach ($form->each() as $name_c => $field) {
    $names[] = $name_c;
}

test('every column becomes a field', $names === ['id', 'email', 'bio'], implode(', ', $names));
test('describe_column() finds one by name', $table->describe_column('email') !== null);
test('…and returns null for one that does not exist', $table->describe_column('nope') === null);

test('the primary key is recognised', $table->describe_column('id')->is_primary_key());
test('…and an ordinary column is not', !$table->describe_column('email')->is_primary_key());

test('a minimal definition needs only a type',
    GenericColumnAdapter::from_array('x', ['type' => 'VARCHAR'])->get_type() === 'VARCHAR');

test('…and defaults to nullable, which is the safer half of the guess',
    GenericColumnAdapter::from_array('x', ['type' => 'VARCHAR'])->is_nullable());

// -----------------------------------------------------------------------------
section('the widget registry');

$registry = new WidgetRegistry();

test('the ordinary types are registered out of the box',
    $registry->has('text') && $registry->has('select') && $registry->has('textarea'),
    implode(', ', array_keys($registry->all())));

$text = $registry->get('text');

test('AN UNREGISTERED TYPE FALLS BACK TO TEXT rather than fataling',
    $registry->get('wysiwyg') === $text,
    'a form containing one unknown widget would fail to render entirely');

test('…and has() still says the truth about it', !$registry->has('wysiwyg'));

$custom = $registry->get('text');
$registry->register('wysiwyg', $custom);

test('a custom widget can be registered', $registry->has('wysiwyg')
    && $registry->get('wysiwyg') === $custom);

test('…and one can be replaced', (static function () use ($registry, $custom): bool {
    $registry->register('text', $custom);

    return $registry->get('text') === $custom;
})());

exit(summary());
