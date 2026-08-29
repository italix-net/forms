<?php
/**
 * Example 11: JSON Metadata Export
 *
 * Demonstrates exporting form metadata as JSON instead of rendering HTML.
 * This is useful for SPA frontends (React, Vue, Svelte) that build their
 * own form UI from a JSON schema returned by an API endpoint.
 *
 * The to_json() method serializes all field metadata, sections, validation
 * rules, and options. Sensitive fields (like passwords) are automatically
 * redacted in the export unless you explicitly opt in.
 */

require __DIR__ . '/../vendor/autoload.php';

use Italix\Rules\Rule;
use function Italix\Forms\form_meta;

// 1. Define a user form with sections and validation.
$form = form_meta([
    'username'   => ['type' => 'VARCHAR', 'length' => 50, 'nullable' => false],
    'email'      => ['type' => 'VARCHAR', 'length' => 255, 'nullable' => false],
    'password'   => ['type' => 'VARCHAR', 'length' => 255, 'nullable' => false],
    'role'       => ['type' => 'VARCHAR', 'length' => 20, 'nullable' => false],
    'bio'        => ['type' => 'TEXT', 'nullable' => true],
    'newsletter' => ['type' => 'BOOLEAN', 'nullable' => false, 'default' => true],
]);

// Sections
$form->section('account')
    ->title('Account Details')
    ->description('Login credentials and role.')
    ->columns(2)
    ->order(1);

$form->section('profile')
    ->title('Profile')
    ->description('Public information.')
    ->order(2);

// Field configuration
$form->fields([
    'username' => [
        'label'       => 'Username',
        'placeholder' => 'johndoe',
        'group'       => 'account',
        'order'       => 1,
    ],
    'email' => [
        'label'       => 'Email',
        'type'        => 'email',
        'placeholder' => 'you@example.com',
        'group'       => 'account',
        'order'       => 2,
    ],
    'password' => [
        'label'       => 'Password',
        'type'        => 'password',
        'placeholder' => 'Min 8 characters',
        'group'       => 'account',
        'order'       => 3,
    ],
    'role' => [
        'label'   => 'Role',
        'type'    => 'select',
        'options' => ['viewer' => 'Viewer', 'editor' => 'Editor', 'admin' => 'Administrator'],
        'group'   => 'account',
        'order'   => 4,
    ],
    'bio' => [
        'label'       => 'Biography',
        'placeholder' => 'Tell us about yourself...',
        'group'       => 'profile',
        'order'       => 1,
    ],
    'newsletter' => [
        'label' => 'Subscribe to Newsletter',
        'group' => 'profile',
        'order' => 2,
    ],
]);

// Validation
$form->field('username')->rules(Rule::required(), Rule::alpha_dash(), Rule::length_between(3, 50));
$form->field('email')->rules(Rule::required(), Rule::email());
$form->field('password')->rules(Rule::required(), Rule::min_length(8));
$form->field('password')->sensitive();   // Mark as sensitive -- redacted in export by default
$form->field('role')->rules(Rule::required(), Rule::in(['viewer', 'editor', 'admin']));

// 2. Export as JSON.
//    Sensitive fields (password) are redacted by default.
$json = $form->to_json(JSON_PRETTY_PRINT);

// Output with appropriate content type.
header('Content-Type: application/json; charset=utf-8');
echo $json;

// 3. Alternatively, export as a PHP array for template engines or caching.
// $array = $form->to_array();

// 4. To include sensitive fields in the export (e.g., for admin tools),
//    pass true as the second argument:
// $full_json = $form->to_json(JSON_PRETTY_PRINT, true);
