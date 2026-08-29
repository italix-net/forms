# Italix Forms

[![PHP Version](https://img.shields.io/badge/php-%3E%3D7.4-8892BF.svg)](https://php.net/)
[![License](https://img.shields.io/badge/license-MPL%202.0-blue.svg)](LICENSE)

Form metadata over a table schema, and HTML rendering for it. You describe the *form* — labels,
sections, widgets, rules — and the *fields* come from whatever already knows your columns.

```php
use function Italix\Forms\form_meta;
use Italix\Rules\Rule;

$form = form_meta($users_table);          // anything implementing Italix\Contracts\TableMeta

$form->section('identity')->title($t->get('form.identity'))->order(1)->columns(2);

$form->field('email')
     ->label($t->get('form.email'))
     ->placeholder('you@example.com')
     ->rules(Rule::required(), Rule::email())
     ->group('identity');

$form->field('subject_type')
     ->label($t->get('form.type'))
     ->type('select')
     ->options(['PER' => $t->get('form.person'), 'ORG' => $t->get('form.company')])
     ->attrs(['x-model' => 'subject_type'])
     ->group('identity');

$form->field('password')->sensitive();    // never leaves the process in any serialisation
```

Rendering:

```php
use Italix\Forms\Rendering\FormHtml;

$html = new FormHtml($form, $widget_registry);
echo $html->action('/it/admin/users/1/edit.html')->method('POST')->render();
```

## Why the fields are not declared here

Because they are already declared somewhere. A form built from a hand-written field list drifts from
the table the moment a column is added, and the drift is silent — the form keeps rendering, just
without the new field. `FormMeta` wraps a `TableMeta` and describes only what the *form* adds on top:
what a column should be called on screen, which section it belongs to, which widget renders it, what
must be true before it saves.

The adapters in `Adapters/` build a `TableMeta` from a schema object, an array, or a relation, so the
source can be an ORM table, a config array, or something you wrote this morning.

## `sensitive()` is tested adversarially, not asserted

A field marked `sensitive()` is redacted everywhere the object can be serialised. The suite does not
ask the object whether it redacted anything — it plants a recognisable secret in a placeholder, a
help string and a custom attribute, then **searches the serialised output for the secret**. That is
the difference between testing the flag and testing the guarantee.

Exclusion is total and separate: `excluded` fields disappear from `each()`, `all()`, `by_section()`,
`count()` and `to_array()`. Hidden and excluded are different things and the suite pins both.

## Escaping: four implementations, on purpose

`FormHtml::esc()`, `AbstractWidget::esc()` and two closures in `TemplateWidget` each call
`htmlspecialchars` separately. That is duplication and it is deliberate: the house rule that makes a
library a leaf means `Italix\Forms` may not depend on `Italix\Encode`. The guarantee therefore cannot
be centralised, so it is **covered** instead — the suite breaks when any of the three files loses
`ENT_QUOTES`.

If you are reading this because the duplication looked like a defect: it is the trade, written down.
The cost of the alternative is a dependency edge between two packages that are otherwise independent.

`TemplateWidget` was found uncovered **by mutation, not by reading** — the first version of the XSS
corpus never routed through it, and breaking it changed nothing.

The corpus itself is five payloads pushed through **eleven** entry points each: 55 renderings,
asserting that the raw payload is *absent* rather than that some entity is present. Plus one
assertion that escaping does not **strip** — that is a data-loss bug a passing XSS test hides.

## Rules live elsewhere

Since 2.0.0 the rule vocabulary is `italix/rules`: `Italix\Forms\Validation\Rule` →
`Italix\Rules\Rule`. `MIGRATION.md`, next to this file, has the one-line `sed` that performs the move
and advice on rolling it out in report-only mode first.

## Widgets

`Rendering/Widgets` ships the usual set; `WidgetRegistry` is how you replace one or add your own.
`WidgetInterface` is the whole contract — a widget receives a `FieldMeta` and returns markup.

## Requirements

`php >= 7.4`, `italix/contracts`. `italix/rules` is suggested: without it, `rules()` still stores
whatever you hand it, but there is no vocabulary to hand it.
