# Italix Forms

A PHP library for form metadata, validation, and layout management. Works with any ORM or standalone.

## Installation

```bash
composer require italix/forms
```

## Requirements

- PHP 8.1 or higher

## Quick Start

```php
<?php

use Italix\Forms\Validation\Rule;
use function Italix\Forms\form_meta;

// Create form metadata from an array (no ORM needed)
$form = form_meta([
    'name'  => ['type' => 'VARCHAR', 'length' => 100, 'nullable' => false],
    'email' => ['type' => 'VARCHAR', 'length' => 255, 'nullable' => false],
    'bio'   => ['type' => 'TEXT', 'nullable' => true],
]);

// Configure fields
$form->field('name')
    ->label('Full Name')
    ->placeholder('John Doe')
    ->rules(Rule::required(), Rule::max_length(100));

$form->field('email')
    ->label('Email Address')
    ->placeholder('you@example.com')
    ->help('We will never share your email')
    ->rules(Rule::required(), Rule::email());

$form->field('bio')
    ->label('About You')
    ->type('textarea')
    ->attr('rows', 5);

// Render in a template
foreach ($form->each() as $name => $field) {
    echo "<div class='form-group'>";
    echo "  <label>{$field->get_label()}</label>";
    echo "  <input type='{$field->get_type()}' name='{$name}'";
    echo "         placeholder='{$field->get_placeholder()}'";
    if ($field->is_required()) echo " required";
    echo "  />";
    if ($help = $field->get_help()) {
        echo "  <small>{$help}</small>";
    }
    echo "</div>";
}
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

    private array $columns = [];

    protected function get_columns_for_description(): array
    {
        return $this->columns;
    }
}
```

Your Column class should implement `ColumnMeta`:

```php
<?php

use Italix\Forms\Contracts\ColumnMeta;

class MyColumn implements ColumnMeta
{
    public function get_name(): string { /* ... */ }
    public function get_type(): string { /* ... */ }
    public function is_nullable(): bool { /* ... */ }
    public function is_primary_key(): bool { /* ... */ }
    public function get_length(): ?int { /* ... */ }
    public function get_default(): mixed { /* ... */ }
    public function has_default(): bool { /* ... */ }
}
```

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
    ->collapsible(collapsed: true)
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

| Rule | Description |
|------|-------------|
| `required()` | Field cannot be empty |
| `required_if($field, $value)` | Required if another field has value |
| `email()` | Valid email address |
| `url()` | Valid URL |
| `numeric()` | Numeric value |
| `integer()` | Integer value |
| `alpha()` | Letters only |
| `alpha_num()` | Letters and numbers |
| `alpha_dash()` | Letters, numbers, dashes, underscores |
| `date()` | Valid date |
| `date_format($format)` | Matches date format |
| `min($value)` | Minimum value |
| `max($value)` | Maximum value |
| `min_length($length)` | Minimum string length |
| `max_length($length)` | Maximum string length |
| `length($length)` | Exact string length |
| `between($min, $max)` | Value in range |
| `length_between($min, $max)` | String length in range |
| `in($values)` | Must be one of values |
| `not_in($values)` | Must not be one of values |
| `confirmed($field)` | Must match another field |
| `same($field)` | Must equal another field |
| `different($field)` | Must differ from another field |
| `pattern($regex)` | Must match regex |
| `before($date)` | Date before |
| `after($date)` | Date after |
| `unique($table, $column)` | Unique in database |
| `exists($table, $column)` | Exists in database |
| `file()` | Must be uploaded file |
| `image()` | Must be image |
| `mimes($types)` | File type restriction |
| `max_file_size($kb)` | Max file size in KB |
| `custom($name, $params)` | Custom rule |

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

## Export to JSON

For JavaScript form builders (React, Vue, etc.):

```php
<?php

$json = $form->to_json(JSON_PRETTY_PRINT);
echo "<script>const formConfig = {$json};</script>";
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

## License

MIT License. See [LICENSE](LICENSE) for details.
