<?php
/**
 * Example 06: File Upload Form
 *
 * Demonstrates file input fields and automatic multipart/form-data enctype
 * detection. When any field has type "file", the FormHtml renderer
 * automatically sets the form enctype to "multipart/form-data".
 *
 * You can also set the enctype explicitly with the enctype() method.
 */

require __DIR__ . '/../vendor/autoload.php';

use Italix\Forms\Rendering\FormHtml;
use Italix\Rules\Rule;
use function Italix\Forms\form_meta;

// 1. Define columns -- file columns are just VARCHAR (they store the file path).
$form = form_meta([
    'title'    => ['type' => 'VARCHAR', 'length' => 200, 'nullable' => false],
    'avatar'   => ['type' => 'VARCHAR', 'length' => 500, 'nullable' => true],
    'resume'   => ['type' => 'VARCHAR', 'length' => 500, 'nullable' => true],
    'cover'    => ['type' => 'TEXT', 'nullable' => true],
]);

// 2. Configure fields -- override the type to "file" for upload fields.
$form->fields([
    'title' => [
        'label'       => 'Document Title',
        'placeholder' => 'My Application',
        'order'       => 1,
    ],
    'avatar' => [
        'label' => 'Profile Photo',
        'type'  => 'file',
        'help'  => 'Accepted formats: JPG, PNG, GIF. Max 2MB.',
        'order' => 2,
    ],
    'resume' => [
        'label' => 'Resume / CV',
        'type'  => 'file',
        'help'  => 'PDF only, max 5MB.',
        'order' => 3,
    ],
    'cover' => [
        'label'       => 'Cover Letter',
        'placeholder' => 'Explain why you are a good fit...',
        'order'       => 4,
    ],
]);

// File-specific validation rules.
$form->field('avatar')->rules(
    Rule::file(),
    Rule::image(),
    Rule::mimes(['jpg', 'png', 'gif']),
    Rule::max_file_size(2048)
);
$form->field('resume')->rules(
    Rule::file(),
    Rule::mimes(['pdf']),
    Rule::max_file_size(5120)
);

// 3. Render -- the enctype is automatically set to multipart/form-data
// because file-type fields are present.
$html = new FormHtml($form);
$html->action('/applications/upload')
     ->method('POST')
     ->buttons(['submit' => 'Upload & Submit', 'cancel' => '/applications']);

echo $html->render();

// Note: You can also force the enctype explicitly if needed:
// $html->enctype('multipart/form-data');
