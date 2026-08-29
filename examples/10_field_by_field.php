<?php
/**
 * Example 10: Individual Field Rendering
 *
 * Demonstrates rendering fields one at a time for full layout control.
 * Instead of calling $html->render() (which outputs the entire form),
 * you use open(), field($name), render_buttons(), and close() individually.
 *
 * This approach is useful when you need a custom HTML layout -- for example,
 * placing fields in a CSS grid, Bootstrap columns, or a wizard-style UI.
 */

require __DIR__ . '/../vendor/autoload.php';

use Italix\Forms\Rendering\FormHtml;
use Italix\Rules\Rule;
use function Italix\Forms\form_meta;

// 1. Define columns.
$form = form_meta([
    'first_name' => ['type' => 'VARCHAR', 'length' => 50, 'nullable' => false],
    'last_name'  => ['type' => 'VARCHAR', 'length' => 50, 'nullable' => false],
    'email'      => ['type' => 'VARCHAR', 'length' => 255, 'nullable' => false],
    'phone'      => ['type' => 'VARCHAR', 'length' => 20, 'nullable' => true],
    'notes'      => ['type' => 'TEXT', 'nullable' => true],
]);

$form->fields([
    'first_name' => ['label' => 'First Name', 'placeholder' => 'Jane'],
    'last_name'  => ['label' => 'Last Name',  'placeholder' => 'Doe'],
    'email'      => ['label' => 'Email',       'type' => 'email', 'placeholder' => 'jane@example.com'],
    'phone'      => ['label' => 'Phone',       'placeholder' => '+1 555-0123'],
    'notes'      => ['label' => 'Notes',       'placeholder' => 'Any additional notes...'],
]);

$form->field('first_name')->rules(Rule::required());
$form->field('last_name')->rules(Rule::required());
$form->field('email')->rules(Rule::required(), Rule::email());

// 2. Create the renderer with values and errors.
$html = new FormHtml($form);
$html->action('/contacts/store')
     ->method('POST')
     ->values([
         'first_name' => 'Jane',
         'last_name'  => '',
         'email'      => 'jane@example.com',
     ])
     ->errors([
         'last_name' => 'Last name is required.',
     ])
     ->buttons(['submit' => 'Save Contact', 'cancel' => '/contacts']);

// 3. Render fields individually inside a custom layout.
echo $html->open();

echo '<div class="custom-row" style="display:flex;gap:1rem;">';
echo '  <div style="flex:1;">';
echo      $html->field('first_name');
echo '  </div>';
echo '  <div style="flex:1;">';
echo      $html->field('last_name');
echo '  </div>';
echo '</div>';

echo '<div class="custom-row" style="display:flex;gap:1rem;">';
echo '  <div style="flex:2;">';
echo      $html->field('email');
echo '  </div>';
echo '  <div style="flex:1;">';
echo      $html->field('phone');
echo '  </div>';
echo '</div>';

echo '<div class="custom-row">';
echo    $html->field('notes');
echo '</div>';

echo '<hr>';
echo $html->render_buttons();

echo $html->close();
