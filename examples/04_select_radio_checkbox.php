<?php
/**
 * Example 04: Select, Radio, and Checkbox Fields
 *
 * Demonstrates the three choice-based field types:
 *   - select:   A dropdown menu with key => label options.
 *   - radio:    A group of radio buttons for single selection.
 *   - checkbox: A single boolean toggle (derived from BOOLEAN column type).
 *
 * Options are provided as associative arrays of value => display label.
 */

require __DIR__ . '/../vendor/autoload.php';

use Italix\Forms\Rendering\FormHtml;
use Italix\Forms\Validation\Rule;
use function Italix\Forms\form_meta;

// 1. Define columns with appropriate types.
$form = form_meta([
    'country'       => ['type' => 'VARCHAR', 'length' => 2, 'nullable' => false],
    'priority'      => ['type' => 'VARCHAR', 'length' => 10, 'nullable' => false],
    'newsletter'    => ['type' => 'BOOLEAN', 'nullable' => false, 'default' => false],
    'contact_method'=> ['type' => 'VARCHAR', 'length' => 20, 'nullable' => false],
]);

// 2. Configure each field with its type and options.

// Select dropdown -- the library auto-detects type as "select" when options are set,
// but setting type explicitly makes the intent clearer.
$form->field('country')
    ->label('Country')
    ->type('select')
    ->options([
        ''   => '-- Select a country --',
        'us' => 'United States',
        'gb' => 'United Kingdom',
        'ca' => 'Canada',
        'de' => 'Germany',
        'fr' => 'France',
        'jp' => 'Japan',
        'au' => 'Australia',
    ])
    ->help('Where are you located?')
    ->rules(Rule::required());

// Radio buttons -- explicitly set type to "radio".
$form->field('priority')
    ->label('Priority Level')
    ->type('radio')
    ->options([
        'low'    => 'Low',
        'medium' => 'Medium',
        'high'   => 'High',
        'urgent' => 'Urgent',
    ])
    ->rules(Rule::required(), Rule::in(['low', 'medium', 'high', 'urgent']));

// Checkbox -- auto-detected from BOOLEAN column type.
// No need to set type or options for a simple on/off toggle.
$form->field('newsletter')
    ->label('Subscribe to Newsletter')
    ->help('We send at most one email per week.');

// Radio for contact method preference.
$form->field('contact_method')
    ->label('Preferred Contact Method')
    ->type('radio')
    ->options([
        'email' => 'Email',
        'phone' => 'Phone',
        'sms'   => 'SMS',
    ])
    ->rules(Rule::required());

// 3. Render with some pre-selected values.
$html = new FormHtml($form);
$html->action('/preferences/save')
     ->method('POST')
     ->values([
         'country'        => 'us',
         'priority'       => 'medium',
         'newsletter'     => true,
         'contact_method' => 'email',
     ])
     ->buttons(['submit' => 'Save Preferences', 'cancel' => '/']);

echo $html->render();
