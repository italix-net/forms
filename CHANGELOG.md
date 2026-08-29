# Changelog — italix/forms

## [2.3.1] — 2026-08-30

### Fixed

- README's quick-start example used `subject_type` with `PER`/`ORG` options — a field lifted
  directly from the application this library was extracted from, not a neutral example. Replaced
  with a generic `account_type` (`personal`/`business`) field carrying the same shape.

## [2.3.0] — 2026-08-29

### Added

- A PHPUnit test suite (`FunctionsTest`, `FormSectionTest`, `WidgetRegistryTest`, `RelationTest`,
  `GenericAdapterTest`, `WidgetTest`, `DelegationTest`, plus a trimmed `FieldMetaTest`) and 13
  `examples/*.php` walkthroughs, harvested from an earlier, independently-published iteration of
  this package on GitHub and reconciled against this library's real, currently-released code.
  Run via `composer test:phpunit` alongside the existing `composer test` suite.

### Fixed

- That earlier iteration had reinvented a standalone `Validation\Rule` engine, not knowing
  `italix/rules` already existed in this tree — discarded before merging, along with its
  dedicated test and every example's import, repointed at the real `Italix\Rules\Rule`.
- `FieldMetaTest.php`'s ~35 tests for shorthand rule-string parsing tested behavior this library
  no longer has (`FieldMeta::rules()` throws on a string rather than parsing it, since 2.0.0) —
  replaced with two tests against the real current exception.

## [2.2.1] — 2026-08-28

### Changed

No change to this library's own code. `require-dev`'s `italix/testing` widened to `^2.0` (was
`^1.0`) and `require`'s `italix/contracts` widened to `^2.0` (was `^1.0`), both MAJOR-bumped
elsewhere in this same round for a function-naming convention change (`_c` retired on method
names — see `src/Libs/Italix/CONVENTIONS.md`) that touches neither of the interfaces this library
actually uses.

## [2.2.0] — 2026-08-17

### Added

- **The derivation had no assertions**, which for a package whose headline is "build a form from a
  table schema" was the gap worth closing first. 59 of them.

  The type table is nineteen rows and a missing one is not an error — it falls through to `text`. Add
  a column type to an ORM, forget the row here, and a date renders as a free-text box. Nobody files
  that as a bug; it looks like a form somebody built carelessly. Every row is now written down.

  The rules matter more than the table: a not-null column produces a required field with nobody
  writing a rule, so a schema change silently changes a form's validation. That is the intended
  behaviour, and exactly why breaking it should be loud. Asserted on the *rendered input* as well as
  the metadata — a `maxlength` that never reaches the browser is a form that fails at the database,
  after the work.

  Three mutations, each failing its own assertion: dropping the `is_nullable()` branch, deleting one
  row of the type table, removing the relation option cap.

- **The relation cap is now pinned.** `fetch_options()` asks the fetcher for one *more* row than the
  cap, so it can tell "exactly fifty" from "fifty and more", and returns `null` for the second —
  which is what stops a relation with ten thousand rows becoming a `<select>` with ten thousand
  options. That `+ 1` reads like an off-by-one until you know why it is there.

  Also pinned: nothing is queried until a fetcher is supplied. A metadata object that opens a
  database connection on its own cannot be used in a test, in a CLI tool, or while a form is merely
  being described.

- `WidgetRegistry` falls back to the text widget for an unregistered type rather than fataling — so
  one unknown widget does not stop a whole form rendering. Silent, deliberate, and now written down.

## [2.1.1] — 2026-08-17

### Added

- **A README.** The package had none — including no statement of the house rule that keeps
  `Italix\Forms` a leaf and forces the four duplicated `htmlspecialchars` implementations, which is
  the first thing a reader would otherwise file as a bug.

## [2.1.0] — 2026-08-14

### Added

- **The first tests this library has had**, 48 assertions across two suites. Not coverage — the
  assertions whose failure would otherwise go unnoticed until it reached a browser.

  - `FormMetaTest` — redaction of `sensitive()` fields written adversarially: a recognisable secret
    is planted in a placeholder, a help string and a custom attribute, and the assertion searches the
    serialised output for it rather than asking the object whether it redacted anything. Plus:
    exclusion is total across `each()`, `all()`, `by_section()`, `count()` and `to_array()`; hidden
    and excluded are different things; the fluent API returns the objects a chain depends on.
  - `FormHtmlTest` — five XSS payloads pushed through **eleven** entry points each, 55 renderings,
    asserting that the raw payload is absent rather than that an entity is present. Also that
    escaping does not *strip*, which is a data-loss bug a passing XSS test hides.

### Fixed

- `$orderA`, `$orderB` and `$sectionName` renamed to `$order_a`, `$order_b` and `$section_c` — the
  library's own snake_case rule, now enforced by `ix libs:check`.

### Notes

- **Four `htmlspecialchars` implementations across three files** — `FormHtml::esc()`,
  `AbstractWidget::esc()` and two closures in `TemplateWidget`. They are duplicated rather than
  shared because house rule 13 makes a library a leaf: `Italix\Forms` may not depend on
  `Italix\Encode`. The guarantee therefore cannot be centralised and has to be *covered* instead,
  and the suite now breaks when any of the three files loses `ENT_QUOTES`.

  `TemplateWidget` was found uncovered **by mutation, not by reading** — the first version of the
  corpus never routed through it, and breaking it changed nothing.

Format: [Keep a Changelog](https://keepachangelog.com/). Versioning policy: `VERSIONING.md` at the
project root.


### Legal

- **Licensed under MPL-2.0**, applied 2026-08-13: the `license` field in `composer.json`, a `LICENSE`
  file, and the Exhibit A notice in every source file — MPL §1.4 defines "Covered Software" per file,
  so the per-file header is what makes the licence apply rather than decoration.

  This is a **first declaration, not a relicensing.** The package carried no licence at all before,
  which in most jurisdictions means all rights reserved: nothing had been granted, so nothing is
  taken away and no consumer's position gets worse. That is why it is recorded here rather than
  treated as a breaking change — unlike `italix/orm`, which went Apache-2.0 → MPL-2.0 and took a
  MAJOR because that direction does narrow what a consumer already had.

## [2.0.0] — 2026-08

### Removed

- **`Italix\Forms\Validation\` no longer exists.** The rule vocabulary moved to `italix/rules`.
  `Italix\Forms\Validation\Rule` → `Italix\Rules\Rule`.

  Find the call sites:

  ```bash
  grep -rn 'Italix\\Forms\\Validation\\Rule' src/
  ```

  Full migration guide, including the one-line `sed` that performs it and the report-only rollout
  advice: **`MIGRATION.md`**, next to this file.

- `FieldMeta::parse_rule_string()` (private) — replaced by the public static `Italix\Rules\Rule::parse()`.

### Changed

- `FieldMeta::rules()` accepts anything implementing `Italix\Contracts\RuleMeta` instead of `Rule`
  objects and shorthand strings. Shorthand strings now throw an `InvalidArgumentException` naming
  the replacement, so none can be missed silently.

### Why it was a major

Forms owned the vocabulary of validation but had no way to execute it — nothing in the library ever
read `FieldMeta::get_rules()`. Every rule attached to a field was inert: it rendered nothing,
enforced nothing, and looked exactly like a rule that worked. Moving the vocabulary to the engine
that runs it is what made those rules live, which is also why the upgrade needs a report-only phase:
data that has been saved happily for years may now be rejected.

## [1.0.0] — baseline

Versioning starts here. This entry records the state of the library at the time the policy was
adopted, not a release.

- `FormMeta`, `FieldMeta`, `FormSection` — form and field descriptors.
- `Rendering/` — `FormHtml` and the widget registry.
- `Adapters/` — build a form from a table schema without depending on `italix/orm`, by typing
  against `Italix\Contracts\TableMeta`.
- `functions.php` — the `form_meta()` factory (house rule 9).
