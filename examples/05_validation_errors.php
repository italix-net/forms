<?php
/**
 * Example 05: Displaying Validation Errors
 *
 * Demonstrates how to re-render a form after server-side validation fails.
 * The form is pre-filled with the user's submitted values and each field
 * with an error gets an error message displayed below it.
 *
 * The errors() method accepts an associative array of field_name => message.
 * Fields with errors automatically receive the "has-error" CSS class on
 * their wrapper div.
 */

require __DIR__ . '/../vendor/autoload.php';

use Italix\Forms\Rendering\FormHtml;
use Italix\Forms\Validation\Rule;
use function Italix\Forms\form_meta;

// 1. Define a registration form.
$form = form_meta([
    'username' => ['type' => 'VARCHAR', 'length' => 50, 'nullable' => false],
    'email'    => ['type' => 'VARCHAR', 'length' => 255, 'nullable' => false],
    'password' => ['type' => 'VARCHAR', 'length' => 255, 'nullable' => false],
    'age'      => ['type' => 'INTEGER', 'nullable' => false],
    'website'  => ['type' => 'VARCHAR', 'length' => 255, 'nullable' => true],
]);

$form->fields([
    'username' => [
        'label'       => 'Username',
        'placeholder' => 'Choose a username',
        'help'        => 'Must be 3-50 characters. Letters, numbers, dashes only.',
    ],
    'email' => [
        'label'       => 'Email',
        'type'        => 'email',
        'placeholder' => 'you@example.com',
    ],
    'password' => [
        'label'       => 'Password',
        'type'        => 'password',
        'placeholder' => 'Minimum 8 characters',
    ],
    'age' => [
        'label'       => 'Age',
        'placeholder' => '18',
    ],
    'website' => [
        'label'       => 'Website',
        'type'        => 'url',
        'placeholder' => 'https://example.com',
    ],
]);

$form->field('username')->rules(
    Rule::required(),
    Rule::alpha_dash(),
    Rule::length_between(3, 50)
);
$form->field('email')->rules(Rule::required(), Rule::email());
$form->field('password')->rules(Rule::required(), Rule::min_length(8));
$form->field('password')->sensitive();
$form->field('age')->rules(Rule::required(), Rule::integer(), Rule::between(18, 120));
$form->field('website')->rules(Rule::url());

// 2. Simulate the user's submitted values (what they typed before hitting submit).
$submitted_values = [
    'username' => 'ab',
    'email'    => 'not-an-email',
    'password' => '123',
    'age'      => 15,
    'website'  => 'not a url',
];

// 3. Simulate validation error messages returned by the server.
$validation_errors = [
    'username' => 'Username must be between 3 and 50 characters.',
    'email'    => 'Please enter a valid email address.',
    'password' => 'Password must be at least 8 characters.',
    'age'      => 'You must be at least 18 years old.',
    'website'  => 'Please enter a valid URL.',
];

// 4. Render the form with values and errors.
$html = new FormHtml($form);
$html->action('/register')
     ->method('POST')
     ->values($submitted_values)
     ->errors($validation_errors)
     ->buttons(['submit' => 'Register', 'cancel' => '/login']);

echo $html->render();
