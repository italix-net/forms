<?php
/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at https://mozilla.org/MPL/2.0/.
 */

declare(strict_types=1);

namespace Italix\Forms\Rendering\Widgets;

use Italix\Forms\FieldMeta;

/**
 * Widget that renders a complete form field from a PHP template file.
 *
 * The template is responsible for the full field markup:
 * wrapper, label, input element, help text, and error message.
 *
 * Variables available inside the template:
 *   $field  — FieldMeta instance
 *   $value  — current field value (mixed)
 *   $error  — validation error string (empty string if none)
 *   $esc    — fn(mixed): string — HTML-escapes a value
 *
 * Usage in a theme's theme.php:
 *
 *   $dir = __DIR__ . '/widgets/';
 *   $registry->register('text',     new TemplateWidget($dir . 'text.php'));
 *   $registry->register('email',    new TemplateWidget($dir . 'text.php'));
 *   $registry->register('textarea', new TemplateWidget($dir . 'textarea.php'));
 *   $registry->register('select',   new TemplateWidget($dir . 'select.php'));
 */
class TemplateWidget extends AbstractWidget
{
    private string $template_path;
    /** @var mixed */
    private $pending_value = null;

    public function __construct(string $template_path)
    {
        $this->template_path = $template_path;
    }

    /**
     * Stores the value; actual rendering is deferred to wrap() so the
     * template receives both the value and the validation error together.
     */
    public function render_edit(FieldMeta $field, $value, array $attrs = []): string
    {
        $this->pending_value = $value;
        return '';
    }

    /**
     * Executes the template with $field, $value, $error, and $esc in scope.
     * The $input_html parameter (always '') is intentionally ignored — the
     * template produces the complete field HTML itself.
     */
    public function wrap(FieldMeta $field, string $input_html, string $error = ''): string
    {
        $value = $this->pending_value;
        $esc   = static fn($v): string => htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        ob_start();
        include $this->template_path;
        return ob_get_clean();
    }

    public function render_view(FieldMeta $field, $value, array $attrs = []): string
    {
        $esc = static fn($v): string => htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        return '<span class="form-view-value">' . $esc($value) . '</span>';
    }
}
