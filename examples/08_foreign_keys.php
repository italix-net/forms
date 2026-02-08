<?php
/**
 * Example 08: Foreign Key Auto-Resolution
 *
 * Demonstrates how foreign key relations automatically populate select
 * options via a fetcher callable. When a column has a "relation" definition
 * with a "fetcher" callback, the FormHtml renderer calls it at render time
 * to load the key => label pairs for the select dropdown.
 *
 * If no fetcher is provided (or it returns null), the field falls back to
 * a text input with data-autocomplete attributes.
 */

require __DIR__ . '/../vendor/autoload.php';

use Italix\Forms\Rendering\FormHtml;
use Italix\Forms\Validation\Rule;
use function Italix\Forms\form_meta;

// Simulate a database: in real usage, the fetcher would query your database.
$countries_db = [
    1 => 'United States',
    2 => 'United Kingdom',
    3 => 'Canada',
    4 => 'Germany',
    5 => 'France',
    6 => 'Japan',
    7 => 'Australia',
];

$departments_db = [
    10 => 'Engineering',
    20 => 'Marketing',
    30 => 'Sales',
    40 => 'Human Resources',
    50 => 'Finance',
];

// 1. Define columns with foreign key relations.
//    The "fetcher" callable receives ($table, $key, $label, $limit)
//    and must return an associative array of key => label pairs.
$form = form_meta([
    'name' => [
        'type'     => 'VARCHAR',
        'length'   => 100,
        'nullable' => false,
    ],
    'country_id' => [
        'type'     => 'INTEGER',
        'nullable' => false,
        'relation' => [
            'table'   => 'countries',
            'key'     => 'id',
            'label'   => 'name',
            'fetcher' => function ($table, $key, $label, $limit) use ($countries_db) {
                // In production, this would be:
                // $stmt = $pdo->query("SELECT {$key}, {$label} FROM {$table} LIMIT {$limit}");
                return $countries_db;
            },
        ],
    ],
    'department_id' => [
        'type'     => 'INTEGER',
        'nullable' => false,
        'relation' => [
            'table'   => 'departments',
            'key'     => 'id',
            'label'   => 'name',
            'fetcher' => function ($table, $key, $label, $limit) use ($departments_db) {
                return $departments_db;
            },
        ],
    ],
    'manager_id' => [
        'type'     => 'INTEGER',
        'nullable' => true,
        'relation' => [
            'table' => 'employees',
            'key'   => 'id',
            'label' => 'full_name',
            // No fetcher -- this simulates a table with too many rows.
            // The renderer will fall back to a text input with autocomplete attributes.
        ],
    ],
]);

// 2. Configure field labels and validation.
$form->fields([
    'name'          => ['label' => 'Employee Name', 'placeholder' => 'John Doe'],
    'country_id'    => ['label' => 'Country'],
    'department_id' => ['label' => 'Department'],
    'manager_id'    => ['label' => 'Reports To', 'help' => 'Start typing to search employees.'],
]);

$form->field('name')->rules(Rule::required());
$form->field('country_id')->rules(Rule::required());
$form->field('department_id')->rules(Rule::required());

// 3. Render -- FK relations are resolved automatically.
// country_id and department_id will render as <select> dropdowns.
// manager_id will render as <input type="text"> with data-autocomplete attributes.
$html = new FormHtml($form);
$html->action('/employees/store')
     ->method('POST')
     ->values(['country_id' => 1, 'department_id' => 10])
     ->buttons(['submit' => 'Save Employee', 'cancel' => '/employees']);

echo $html->render();
