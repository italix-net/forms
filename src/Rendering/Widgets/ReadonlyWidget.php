<?php

declare(strict_types=1);

namespace Italix\Forms\Rendering\Widgets;

use Italix\Forms\FieldMeta;

/**
 * Widget for read-only display fields.
 *
 * Always renders as a span, even in edit mode. Includes a hidden input
 * to preserve the value on form submission.
 */
class ReadonlyWidget extends AbstractWidget
{
    public function render_edit(FieldMeta $field, $value, array $attrs = []): string
    {
        $html = '<input type="hidden" name="' . $this->esc($field->get_name()) . '" value="' . $this->esc($value) . '">';
        $html .= '<span class="form-readonly-value">' . $this->esc($value) . '</span>';

        return $html;
    }

    public function render_view(FieldMeta $field, $value, array $attrs = []): string
    {
        return '<span class="form-view-value">' . $this->esc($value) . '</span>';
    }
}
