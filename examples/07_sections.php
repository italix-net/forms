<?php
/**
 * Example 07: Form Sections / Fieldsets
 *
 * Demonstrates organizing fields into named sections. Each section renders
 * as an HTML <fieldset> with a <legend>, optional description, configurable
 * grid columns, and collapsible behavior.
 *
 * Fields are assigned to sections using the group() method on FieldMeta.
 * Sections are configured using the section() method on FormMeta.
 */

require __DIR__ . '/../vendor/autoload.php';

use Italix\Forms\Rendering\FormHtml;
use Italix\Rules\Rule;
use function Italix\Forms\form_meta;

// 1. Define all columns for a comprehensive user registration form.
$form = form_meta([
    // Personal info
    'first_name' => ['type' => 'VARCHAR', 'length' => 50, 'nullable' => false],
    'last_name'  => ['type' => 'VARCHAR', 'length' => 50, 'nullable' => false],
    'email'      => ['type' => 'VARCHAR', 'length' => 255, 'nullable' => false],
    'phone'      => ['type' => 'VARCHAR', 'length' => 20, 'nullable' => true],
    // Address
    'street'     => ['type' => 'VARCHAR', 'length' => 200, 'nullable' => true],
    'city'       => ['type' => 'VARCHAR', 'length' => 100, 'nullable' => true],
    'state'      => ['type' => 'VARCHAR', 'length' => 50, 'nullable' => true],
    'zip'        => ['type' => 'VARCHAR', 'length' => 10, 'nullable' => true],
    // Account settings
    'username'   => ['type' => 'VARCHAR', 'length' => 50, 'nullable' => false],
    'password'   => ['type' => 'VARCHAR', 'length' => 255, 'nullable' => false],
    'role'       => ['type' => 'VARCHAR', 'length' => 20, 'nullable' => false],
    // Preferences
    'theme'      => ['type' => 'VARCHAR', 'length' => 10, 'nullable' => true],
    'newsletter' => ['type' => 'BOOLEAN', 'nullable' => false, 'default' => true],
]);

// 2. Define sections with titles, descriptions, columns, and collapsibility.
$form->section('personal')
    ->title('Personal Information')
    ->description('Your basic contact details.')
    ->icon('user')
    ->columns(2)
    ->order(1);

$form->section('address')
    ->title('Mailing Address')
    ->description('Used for shipping and billing.')
    ->icon('map-pin')
    ->columns(2)
    ->order(2)
    ->collapsible();

$form->section('account')
    ->title('Account Settings')
    ->description('Choose your login credentials and role.')
    ->icon('lock')
    ->columns(2)
    ->order(3);

$form->section('preferences')
    ->title('Preferences')
    ->description('Optional settings you can change later.')
    ->icon('settings')
    ->order(4)
    ->collapsible(true);  // starts collapsed

// 3. Assign fields to sections via group() and configure them.
$form->fields([
    'first_name' => ['label' => 'First Name', 'group' => 'personal', 'order' => 1],
    'last_name'  => ['label' => 'Last Name',  'group' => 'personal', 'order' => 2],
    'email'      => ['label' => 'Email',       'group' => 'personal', 'order' => 3, 'type' => 'email'],
    'phone'      => ['label' => 'Phone',       'group' => 'personal', 'order' => 4],

    'street' => ['label' => 'Street Address', 'group' => 'address', 'order' => 1],
    'city'   => ['label' => 'City',           'group' => 'address', 'order' => 2],
    'state'  => ['label' => 'State',          'group' => 'address', 'order' => 3],
    'zip'    => ['label' => 'ZIP Code',       'group' => 'address', 'order' => 4],

    'username' => ['label' => 'Username', 'group' => 'account', 'order' => 1],
    'password' => ['label' => 'Password', 'group' => 'account', 'order' => 2, 'type' => 'password'],
    'role'     => [
        'label'   => 'Role',
        'group'   => 'account',
        'order'   => 3,
        'type'    => 'select',
        'options' => ['viewer' => 'Viewer', 'editor' => 'Editor', 'admin' => 'Administrator'],
    ],

    'theme' => [
        'label'   => 'Theme',
        'group'   => 'preferences',
        'type'    => 'radio',
        'options' => ['light' => 'Light', 'dark' => 'Dark', 'auto' => 'System Default'],
        'order'   => 1,
    ],
    'newsletter' => [
        'label' => 'Subscribe to Newsletter',
        'group' => 'preferences',
        'order' => 2,
    ],
]);

$form->field('first_name')->rules(Rule::required());
$form->field('last_name')->rules(Rule::required());
$form->field('email')->rules(Rule::required(), Rule::email());
$form->field('username')->rules(Rule::required(), Rule::alpha_dash());
$form->field('password')->rules(Rule::required(), Rule::min_length(8));
$form->field('password')->sensitive();

// 4. Render the sectioned form.
$html = new FormHtml($form);
$html->action('/users/store')
     ->method('POST')
     ->attrs(['id' => 'registration-form'])
     ->buttons(['submit' => 'Create Account', 'cancel' => '/']);

echo $html->render();
