# Italix Forms

A PHP library for form metadata, validation, layout management, and **HTML rendering**. Works with any ORM or standalone.

## Installation

```bash
composer require italix/forms
```

## Requirements

- PHP 7.4 or higher

## Quick Start

```php
<?php

use Italix\Forms\Rendering\FormHtml;
use Italix\Forms\Validation\Rule;
use function Italix\Forms\form_meta;

// Create form metadata from an array (no ORM needed)
$form = form_meta([
    'name'  => ['type' => 'VARCHAR', 'length' => 100, 'nullable' => false],
    'email' => ['type' => 'VARCHAR', 'length' => 255, 'nullable' => false],
    'bio'   => ['type' => 'TEXT', 'nullable' => true],
]);

// Configure fields
$form->fields([
    'name'  => ['label' => 'Full Name', 'placeholder' => 'John Doe', 'rules' => [Rule::required(), Rule::max_length(100)]],
    'email' => ['label' => 'Email Address', 'placeholder' => 'you@example.com', 'type' => 'email', 'rules' => [Rule::required(), Rule::email()]],
    'bio'   => ['label' => 'About You', 'type' => 'textarea'],
]);

// Render the complete form
$html = new FormHtml($form);
$html->action('/contact/send')
     ->method('POST')
     ->buttons(['submit' => 'Send', 'cancel' => '/']);

echo $html->render();
```

## HTML Rendering

The `FormHtml` class renders complete HTML forms from metadata:

```php
<?php

use Italix\Forms\Rendering\FormHtml;

$html = new FormHtml($form);
$html->action('/users/store')
     ->method('POST');

// Full form (open + all fields + buttons + close)
echo $html->render();

// Or render parts individually for custom layout
echo $html->open();
echo '<div class="row">';
echo '<div class="col-6">' . $html->field('name') . '</div>';
echo '<div class="col-6">' . $html->field('email') . '</div>';
echo '</div>';
echo $html->field('bio');
echo $html->render_buttons();
echo $html->close();
```

### Edit Mode with Pre-filled Values

```php
$html = new FormHtml($form);
$html->action('/users/42/update')
     ->method('PUT')
     ->values(['name' => 'Mario Rossi', 'email' => 'mario@example.com', 'bio' => 'Developer']);

echo $html->render();
```

### View Mode (Read-Only)

```php
$html = new FormHtml($form);
$html->values($user_data)
     ->mode('view');

echo $html->render();
```

### Validation Errors

```php
$html = new FormHtml($form);
$html->values($_POST)
     ->errors([
         'name'  => 'Name is required.',
         'email' => 'Please enter a valid email.',
     ]);

echo $html->render();
```

### Buttons

```php
$html->buttons([
    'submit'       => 'Save User',
    'cancel'       => '/users',
    'cancel_label' => 'Back to List',
]);
```

## Metadata-Only Usage

You can also use FormMeta without `FormHtml` for manual rendering or JSON export:

```php
<?php

// Manual rendering
foreach ($form->each() as $name => $field) {
    echo "<div class='form-group'>";
    echo "  <label>{$field->get_label()}</label>";
    echo "  <input type='{$field->get_type()}' name='{$name}'";
    echo "         placeholder='{$field->get_placeholder()}'";
    if ($field->is_required()) echo " required";
    echo "  />";
    echo "</div>";
}

// JSON export for SPA frontends
$json = $form->to_json(JSON_PRETTY_PRINT);
echo "<script>const formConfig = {$json};</script>";
```

## Using with an ORM

Italix Forms works with any ORM. Just implement the `TableMeta` interface:

```php
<?php

use Italix\Forms\Contracts\TableMeta;
use Italix\Forms\Contracts\ColumnMeta;
use Italix\Forms\Concerns\TableMetaFromArray;

class MyTable implements TableMeta
{
    use TableMetaFromArray;

    /** @var array<string, ColumnMeta> */
    private $columns = [];

    protected function get_columns_for_description(): array
    {
        return $this->columns;
    }
}
```

Your Column class should implement `ColumnMeta` (minimal) or `RelationalColumnMeta` (with FK support):

```php
<?php

use Italix\Forms\Contracts\ColumnMeta;

// Minimal: just column metadata
class MyColumn implements ColumnMeta
{
    public function get_name(): string { /* ... */ }
    public function get_type(): string { /* ... */ }
    public function is_nullable(): bool { /* ... */ }
    public function is_primary_key(): bool { /* ... */ }
    public function get_length(): ?int { /* ... */ }
    /** @return mixed */
    public function get_default() { /* ... */ }
    public function has_default(): bool { /* ... */ }
}
```

```php
<?php

use Italix\Forms\Contracts\RelationalColumnMeta;
use Italix\Forms\Contracts\RelationMeta;

// With FK support: auto-populates select/autocomplete widgets
class MyOrmColumn implements RelationalColumnMeta
{
    // ... all ColumnMeta methods ...

    public function get_relation(): ?RelationMeta
    {
        // Return relation info for FK columns, null for others
    }
}
```

### Layered Interface Architecture

The contracts are designed as a layered hierarchy so implementors only need to handle what they support:

| Interface | Extends | Purpose |
|---|---|---|
| `ColumnMeta` | - | Core column info (7 methods) |
| `RelationalColumnMeta` | `ColumnMeta` | FK-aware columns with `get_relation()` |
| `PolymorphicColumnMeta` | `ColumnMeta` | Polymorphic FK columns |
| `TableMeta` | - | Core table with column enumeration |
| `DelegatedTableMeta` | `TableMeta` | Tables with delegated sub-types |

FormMeta uses `instanceof` checks at runtime to detect capabilities -- no configuration needed.

## Foreign Key Relations

Foreign key columns can automatically populate select or autocomplete widgets:

```php
<?php

$form = form_meta([
    'name'       => ['type' => 'VARCHAR', 'length' => 100, 'nullable' => false],
    'country_id' => [
        'type' => 'INTEGER',
        'relation' => [
            'table'  => 'countries',
            'key'    => 'id',
            'label'  => 'name',
            'fetcher' => function ($table, $key, $label, $limit) use ($pdo) {
                $stmt = $pdo->query("SELECT {$key}, {$label} FROM {$table} LIMIT {$limit}");
                $options = [];
                while ($row = $stmt->fetch()) {
                    $options[$row[$key]] = $row[$label];
                }
                return $options;
            },
        ],
    ],
]);

$form->field('country_id')->label('Country');

// FormHtml auto-resolves the FK: renders as <select> with all countries
$html = new FormHtml($form);
echo $html->render();
```

If the related table has more rows than `max_options` (default 100), the field automatically becomes an autocomplete-style text input with `data-autocomplete` attributes.

You can also set a relation fetcher on the table adapter to share the same database callback:

```php
$table = new GenericTableAdapter($columns);
$table->set_relation_fetcher(function ($table, $key, $label, $limit) use ($pdo) {
    $stmt = $pdo->query("SELECT {$key}, {$label} FROM {$table} LIMIT {$limit}");
    $options = [];
    while ($row = $stmt->fetch()) {
        $options[$row[$key]] = $row[$label];
    }
    return $options;
});
```

## Custom Widgets

Register custom widgets to extend or override the default rendering:

```php
<?php

use Italix\Forms\FieldMeta;
use Italix\Forms\Rendering\WidgetInterface;
use Italix\Forms\Rendering\WidgetRegistry;
use Italix\Forms\Rendering\FormHtml;

class DatePickerWidget implements WidgetInterface
{
    public function render_edit(FieldMeta $field, $value, array $attrs = []): string
    {
        $name = htmlspecialchars($field->get_name());
        $val = htmlspecialchars((string)$value);
        return '<input type="text" name="' . $name . '" value="' . $val . '" class="datepicker">';
    }

    public function render_view(FieldMeta $field, $value, array $attrs = []): string
    {
        return '<span>' . htmlspecialchars((string)$value) . '</span>';
    }
}

$registry = new WidgetRegistry();
$registry->register('date', new DatePickerWidget());

$html = new FormHtml($form, $registry);
echo $html->render();
```

### Built-in Widgets

| Input Type | Widget | Description |
|---|---|---|
| text, email, url, tel, search, color | TextWidget | Standard text input |
| textarea | TextareaWidget | Multi-line text |
| select | SelectWidget | Dropdown with options |
| checkbox | CheckboxWidget | Single or multiple checkboxes |
| radio | RadioWidget | Radio button group |
| password | PasswordWidget | Password input (never pre-fills) |
| number, range | NumberWidget | Numeric input (auto step for decimals) |
| date, datetime-local, time, month, week | DateWidget | Date/time inputs |
| file | FileWidget | File upload (auto multipart enctype) |
| hidden | HiddenWidget | Hidden input (no wrapper) |
| readonly | ReadonlyWidget | Display-only with hidden input |

## Delegated Types

When your ORM uses delegated types (a base table with type-specific sub-tables), FormMeta can automatically merge columns from the full chain:

```php
<?php

// Assuming your ORM Table implements DelegatedTableMeta:
// things (id, type, type_path, name, description)
//   ├── books (id, thing_id, isbn, pages)
//   └── movies (id, thing_id, director, duration)
//       Book also delegates to:
//       └── comics_books (id, book_id, illustrator, is_color)

$form = new FormMeta($things_table);

// Single-level: Thing + Book
$form->delegate('Book');
// Shows: name, description, isbn, pages
// Auto-hides: type, type_path, thing_id, ids

// N-level chain: Thing + Book + ComicsBook (just specify the leaf)
$form->delegate('ComicsBook');
// Resolves: Thing -> Book -> ComicsBook
// Shows: name, description, isbn, pages, illustrator, is_color
// Auto-hides: all type/path/FK/PK glue columns

// Wildcard: admin form with type selector
$form->delegate('*');
// Shows: type as <select>, plus all delegate fields with
// data-delegate-type attributes for JS conditional visibility
```

The `delegate()` method works with any depth of delegation chain. You only specify the leaf type -- the library resolves the full path automatically.

### Implementing DelegatedTableMeta

For your ORM to support delegation, implement `DelegatedTableMeta` on the base table:

```php
<?php

use Italix\Forms\Contracts\DelegatedTableMeta;

class MyOrmTable implements DelegatedTableMeta
{
    // ... TableMeta methods (describe_columns, describe_column) ...

    public function get_type_column(): ?string { return 'type'; }
    public function get_type_path_column(): ?string { return 'type_path'; }
    public function get_delegate_foreign_key(): string { return 'thing_id'; }
    public function get_delegate_tables(): array
    {
        return [
            'Book'  => $this->books_table,
            'Movie' => $this->movies_table,
        ];
    }
}
```

## Polymorphic Relations

For polymorphic FK columns (e.g., `commentable_type` + `commentable_id`), implement `PolymorphicColumnMeta` on the ID column:

```php
<?php

use Italix\Forms\Contracts\PolymorphicColumnMeta;

class MyPolymorphicColumn implements PolymorphicColumnMeta
{
    // ... ColumnMeta methods ...

    public function get_polymorphic_type_column(): string { return 'commentable_type'; }
    public function get_polymorphic_targets(): array
    {
        return [
            'post'  => $posts_table,
            'video' => $videos_table,
        ];
    }
}
```

FormHtml automatically renders dependent data attributes for JavaScript-driven type/ID switching.

## Sections and Layout

Organize fields into sections:

```php
<?php

$form = form_meta($table);

// Define sections
$form->section('personal')
    ->title('Personal Information')
    ->description('Enter your basic details')
    ->icon('user')
    ->columns(2)
    ->order(1);

$form->section('account')
    ->title('Account Settings')
    ->collapsible(true)
    ->order(2);

// Assign fields to sections
$form->field('name')->group('personal')->order(1);
$form->field('email')->group('personal')->order(2);
$form->field('password')->group('account');
$form->field('role')->group('account');

// Render by section
foreach ($form->by_section() as $sectionName => $data) {
    $section = $data['section'];
    $fields = $data['fields'];

    if ($section) {
        echo "<fieldset class='{$section->get_class()}'>";
        echo "  <legend>{$section->get_title()}</legend>";
        if ($desc = $section->get_description()) {
            echo "  <p class='description'>{$desc}</p>";
        }
        echo "  <div class='grid grid-cols-{$section->get_columns()}'>";
    }

    foreach ($fields as $name => $field) {
        // render field...
    }

    if ($section) {
        echo "  </div>";
        echo "</fieldset>";
    }
}
```

## Validation Rules

Built-in validation rules:

```php
<?php

use Italix\Forms\Validation\Rule;

$form->field('email')
    ->rules(
        Rule::required(),
        Rule::email(),
        Rule::max_length(255)
    );

$form->field('age')
    ->rules(
        Rule::integer(),
        Rule::between(18, 120)
    );

$form->field('password')
    ->rules(
        Rule::required(),
        Rule::min_length(8),
        Rule::confirmed('password_confirmation')
    );

// String shorthand also works
$form->field('username')
    ->rules('required', 'alpha_dash', 'min_length:3', 'max_length:20');
```

### Available Rules

#### Presence Rules

| Rule | Description |
|------|-------------|
| `required()` | Field cannot be empty |
| `required_if($field, $value)` | Required if another field has value |
| `required_unless($field, $value)` | Required unless another field has value |

#### Format Rules

| Rule | Description |
|------|-------------|
| `email()` | Valid email address |
| `url()` | Valid URL |
| `numeric()` | Numeric value |
| `integer()` | Integer value |
| `alpha()` | Letters only |
| `alpha_num()` | Letters and numbers |
| `alpha_dash()` | Letters, numbers, dashes, underscores |
| `date()` | Valid date |
| `date_format($format)` | Matches date format |
| `pattern($regex)` | Must match regex |

#### Size/Length Rules

| Rule | Description |
|------|-------------|
| `min($value)` | Minimum value |
| `max($value)` | Maximum value |
| `min_length($length)` | Minimum string length |
| `max_length($length)` | Maximum string length |
| `length($length)` | Exact string length |
| `between($min, $max)` | Value in range |
| `length_between($min, $max)` | String length in range |

#### Comparison Rules

| Rule | Description |
|------|-------------|
| `in($values)` | Must be one of values |
| `not_in($values)` | Must not be one of values |
| `confirmed($field)` | Must match another field |
| `same($field)` | Must equal another field |
| `different($field)` | Must differ from another field |
| `gt($value)` | Greater than a value |
| `gte($value)` | Greater than or equal to a value |
| `lt($value)` | Less than a value |
| `lte($value)` | Less than or equal to a value |

#### Date Comparison Rules

| Rule | Description |
|------|-------------|
| `before($date)` | Date before |
| `before_or_equal($date)` | Date on or before |
| `after($date)` | Date after |
| `after_or_equal($date)` | Date on or after |

#### Database Rules

| Rule | Description |
|------|-------------|
| `unique($table, $column)` | Unique in database |
| `exists($table, $column)` | Exists in database |

#### File Rules

| Rule | Description |
|------|-------------|
| `file()` | Must be uploaded file |
| `image()` | Must be image |
| `mimes($types)` | File type restriction |
| `max_file_size($kb)` | Max file size in KB |

#### Custom Rules

| Rule | Description |
|------|-------------|
| `custom($name, $params)` | Custom rule |

## Sensitive Fields

Mark fields containing sensitive data to prevent them from being included in JSON/array exports:

```php
<?php

$form->field('password')->sensitive();
$form->field('api_key')->sensitive();

// Sensitive fields are redacted by default in exports
$json = $form->to_json();

// To include sensitive fields (e.g. for server-side use)
$all_data = $form->to_array(true);
```

## Field Options

Select, radio, and checkbox options:

```php
<?php

$form->field('role')
    ->label('Role')
    ->type('select')
    ->options([
        'user' => 'Regular User',
        'admin' => 'Administrator',
        'mod' => 'Moderator',
    ])
    ->rules(Rule::in(['user', 'admin', 'mod']));

$form->field('status')
    ->type('radio')
    ->options([
        'active' => 'Active',
        'inactive' => 'Inactive',
        'pending' => 'Pending Review',
    ]);
```

## CSS Classes and Attributes

```php
<?php

$form->field('email')
    ->wrapper_class('mb-4')
    ->label_class('font-bold text-gray-700')
    ->input_class('form-input rounded-md')
    ->help_class('text-sm text-gray-500')
    ->attr('autocomplete', 'email')
    ->attrs(['data-validate' => 'true', 'aria-required' => 'true']);
```

## Layout Options

```php
<?php

$form->field('bio')
    ->colspan(2)           // Span 2 columns in grid
    ->width('100%')        // Custom width
    ->order(10);           // Display order (lower = first)

$form->field('id')
    ->hidden();            // Hide from form

$form->field('created_at')
    ->readonly();          // Read-only

$form->field('old_field')
    ->disabled();          // Disabled
```

## Excluding Fields

```php
<?php

$form->exclude('id', 'created_at', 'updated_at', 'deleted_at');
```

## Bulk Configuration

Configure multiple fields at once:

```php
<?php

$form->fields([
    'id' => ['hidden' => true],
    'email' => [
        'label' => 'Email Address',
        'placeholder' => 'you@example.com',
        'rules' => [Rule::required(), Rule::email()],
    ],
    'name' => [
        'label' => 'Full Name',
        'order' => 1,
        'group' => 'personal',
    ],
    'bio' => [
        'type' => 'textarea',
        'attr' => ['rows', 5],
    ],
]);
```

## Examples

See the `examples/` directory for complete, runnable examples:

| Example | Description |
|---|---|
| `01_basic_form.php` | Basic contact form rendering |
| `02_edit_form_with_values.php` | Edit form with pre-filled data and PUT method |
| `03_view_mode.php` | Read-only view mode |
| `04_select_radio_checkbox.php` | Choice fields with options |
| `05_validation_errors.php` | Displaying validation errors |
| `06_file_upload.php` | File uploads with auto multipart enctype |
| `07_sections.php` | Organizing fields in sections/fieldsets |
| `08_foreign_keys.php` | Auto-populating selects from FK relations |
| `09_custom_widget.php` | Registering a custom widget |
| `10_field_by_field.php` | Individual field rendering for custom layout |
| `11_json_export.php` | JSON metadata export for SPA frontends |
| `12_delegated_types.php` | Delegated type forms (single, chain, wildcard) |
| `13_polymorphic_relations.php` | Polymorphic FK with dependent fields |

## License

LGPL-3.0-or-later. See [LICENSE](LICENSE) for details.
