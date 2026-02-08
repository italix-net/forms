<?php
/**
 * Example 03: View Mode (Read-Only Display)
 *
 * Demonstrates rendering a form in view mode. Instead of editable inputs, the
 * library outputs read-only display elements (spans). The outer wrapper is a
 * <div> instead of a <form>, and no submit buttons are rendered.
 *
 * This is useful for detail/show pages where you want consistent layout
 * with your edit forms.
 */

require __DIR__ . '/../vendor/autoload.php';

use Italix\Forms\Rendering\FormHtml;
use function Italix\Forms\form_meta;

// 1. Define the same user profile columns.
$form = form_meta([
    'username'   => ['type' => 'VARCHAR', 'length' => 50, 'nullable' => false],
    'email'      => ['type' => 'VARCHAR', 'length' => 255, 'nullable' => false],
    'bio'        => ['type' => 'TEXT', 'nullable' => true],
    'role'       => ['type' => 'VARCHAR', 'length' => 20, 'nullable' => false],
    'created_at' => ['type' => 'DATETIME', 'nullable' => false],
]);

// 2. Configure labels and field display.
$form->fields([
    'username'   => ['label' => 'Username'],
    'email'      => ['label' => 'Email Address', 'type' => 'email'],
    'bio'        => ['label' => 'Biography'],
    'role'       => [
        'label'   => 'Role',
        'type'    => 'select',
        'options' => ['admin' => 'Administrator', 'editor' => 'Editor', 'viewer' => 'Viewer'],
    ],
    'created_at' => ['label' => 'Member Since'],
]);

// 3. The record to display.
$user = [
    'username'   => 'janedoe',
    'email'      => 'jane@example.com',
    'bio'        => 'Software developer and open-source enthusiast.',
    'role'       => 'admin',
    'created_at' => '2024-03-15 09:30:00',
];

// 4. Render in view mode -- no form tag, no buttons, read-only output.
$html = new FormHtml($form);
$html->mode('view')
     ->values($user);

echo $html->render();
