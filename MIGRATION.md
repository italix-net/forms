# Italix Forms — migration notes

## 2026-08 — the rule vocabulary moved to `italix/rules`

### What changed

| Before | After |
|---|---|
| `Italix\Forms\Validation\Rule` | `Italix\Rules\Rule` |
| `FieldMeta::rules()` accepted `Rule` objects **and** shorthand strings | accepts anything implementing `Italix\Contracts\RuleMeta` |
| `FieldMeta::parse_rule_string()` (private) | `Italix\Rules\Rule::parse()` (public, static) |
| nothing executed rules | `Italix\Rules\Checker` executes them |

`Italix\Forms\Validation\` no longer exists.

### Why

Forms owned the vocabulary of validation (`Rule::email()`, `Rule::iban()`, …)
but had no way to execute it — nothing in the library ever read
`FieldMeta::get_rules()`. A rule attached to a field was therefore inert: it
rendered nothing, enforced nothing, and looked exactly like a rule that worked.
That is a bad place for a vocabulary to live.

The rules now live with the engine that runs them. Forms keeps its actual job —
describing how a field looks and behaves — and merely carries rule objects it
does not interpret, which is why it types against a `Contracts` interface
rather than against any concrete rule class. Same arrangement that lets
`italix/forms` accept a `TableMeta` without depending on `italix/orm`.

The practical gain: a rule can now be checked with or without a form in sight —
over a CSV row, an API payload, a value in a script — because the engine takes
a plain map of field names to rules, not a `FormMeta`.

### How to adapt a project

**1. Add the dependency.** In `composer.json`:

```json
"Italix\\Rules\\": "src/Libs/Italix/Rules/"
```

in `autoload.psr-4`, and `"src/Libs/Italix/Rules/functions.php"` in
`autoload.files`. Then `composer dump-autoload`.

**2. Update the imports.** One line per file, mechanical:

```bash
grep -rl 'Italix\\Forms\\Validation\\Rule' src/ \
  | xargs sed -i 's|use Italix\\Forms\\Validation\\Rule;|use Italix\\Rules\\Rule;|'
```

Nothing else about the call sites changes: `Rule::required()`,
`Rule::max_length(240)` and friends keep their exact signatures.

**3. Replace shorthand strings, if you used any.** `FieldMeta::rules()` no
longer parses them — it throws an `InvalidArgumentException` naming the
replacement, so you will not miss one:

```php
$field->rules('max_length:255');              // before
$field->rules(Rule::parse('max_length:255')); // after
```

**4. Decide what to do about rules that never ran.** This is the part worth
actual attention. Every `Rule::` call already in the codebase was decoration:
it is now executable, so switching on the engine may start rejecting data that
has been saved happily for years. Run it in report-only mode first
(`Checker::check_all()` and log the outcome without blocking the save), see
what it says about the existing rows, and only then enforce.

### Nothing else moved

`FormMeta`, `FieldMeta`, `FormSection`, the widgets and the adapters are
unchanged. If a project attaches no rules at all, the only thing it needs is
step 1 — and only if something else in it references `Rule`.
