<?php
/**
 * Example 01: Basic Form Rendering
 *
 * Demonstrates how to create a simple contact form with name, email, and
 * message fields, then render the complete HTML in edit mode.
 *
 * This is the simplest way to use italix/forms -- define columns, configure
 * labels/placeholders, and let FormHtml produce the HTML.
 */

require __DIR__ . '/../vendor/autoload.php';

use Italix\Forms\Rendering\FormHtml;
use Italix\Rules\Rule;
use function Italix\Forms\form_meta;

// 1. Define the form from column definitions (no ORM required).
$form = form_meta([
    'name'    => ['type' => 'VARCHAR', 'length' => 100, 'nullable' => false],
    'email'   => ['type' => 'VARCHAR', 'length' => 255, 'nullable' => false],
    'message' => ['type' => 'TEXT'],
]);

// 2. Configure field display and validation.
$form->fields([
    'name' => [
        'label'       => 'Full Name',
        'placeholder' => 'John Doe',
        'help'        => 'Enter your first and last name.',
    ],
    'email' => [
        'label'       => 'Email Address',
        'placeholder' => 'you@example.com',
        'type'        => 'email',
    ],
    'message' => [
        'label'       => 'Your Message',
        'placeholder' => 'Write your message here...',
    ],
]);

// Add validation rules individually.
$form->field('name')->rules(Rule::required(), Rule::max_length(100));
$form->field('email')->rules(Rule::required(), Rule::email());
$form->field('message')->rules(Rule::required(), Rule::min_length(10));

// 3. Render the form.
$html = new FormHtml($form);
$html->action('/contact/send')
     ->method('POST')
     ->buttons(['submit' => 'Send Message', 'cancel' => '/']);

echo $html->render();
