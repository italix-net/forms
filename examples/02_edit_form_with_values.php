<?php
/**
 * Example 02: Edit Form with Pre-Filled Values
 *
 * Demonstrates how to render an edit form for an existing record. Values are
 * pre-filled from the current database row, and the form uses PUT method
 * spoofing (the library automatically adds a hidden _method field).
 */

require __DIR__ . '/../vendor/autoload.php';

use Italix\Forms\Rendering\FormHtml;
use Italix\Forms\Validation\Rule;
use function Italix\Forms\form_meta;

// 1. Define columns for a user profile form.
$form = form_meta([
    'id'       => ['type' => 'INTEGER', 'primary_key' => true, 'nullable' => false],
    'username' => ['type' => 'VARCHAR', 'length' => 50, 'nullable' => false],
    'email'    => ['type' => 'VARCHAR', 'length' => 255, 'nullable' => false],
    'bio'      => ['type' => 'TEXT', 'nullable' => true],
    'age'      => ['type' => 'INTEGER', 'nullable' => true],
]);

// 2. Exclude the primary key from the form and configure the remaining fields.
$form->exclude('id');

$form->fields([
    'username' => [
        'label'       => 'Username',
        'placeholder' => 'johndoe',
        'help'        => 'Only lowercase letters and numbers allowed.',
    ],
    'email' => [
        'label'       => 'Email Address',
        'type'        => 'email',
        'placeholder' => 'you@example.com',
    ],
    'bio' => [
        'label'       => 'Biography',
        'placeholder' => 'Tell us about yourself...',
        'help'        => 'Markdown is supported.',
    ],
    'age' => [
        'label' => 'Age',
        'help'  => 'Must be 18 or older.',
    ],
]);

$form->field('username')->rules(Rule::required(), Rule::alpha_dash(), Rule::max_length(50));
$form->field('email')->rules(Rule::required(), Rule::email());
$form->field('age')->rules(Rule::min(18), Rule::max(120));

// 3. Simulate values loaded from the database.
$existing_user = [
    'username' => 'janedoe',
    'email'    => 'jane@example.com',
    'bio'      => 'Software developer and open-source enthusiast.',
    'age'      => 29,
];

// 4. Render the form with values and PUT method spoofing.
$html = new FormHtml($form);
$html->action('/users/42')
     ->method('PUT')
     ->values($existing_user)
     ->buttons([
         'submit'       => 'Update Profile',
         'cancel'       => '/users',
         'cancel_label' => 'Back to List',
     ]);

echo $html->render();
