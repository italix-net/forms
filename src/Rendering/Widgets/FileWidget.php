<?php

declare(strict_types=1);

namespace Italix\Forms\Rendering\Widgets;

use Italix\Forms\FieldMeta;

/**
 * Widget for file upload inputs.
 */
class FileWidget extends AbstractWidget
{
    public function render_edit(FieldMeta $field, $value, array $attrs = []): string
    {
        $input_attrs = $this->common_attrs($field, $value, $attrs);
        $input_attrs['type'] = 'file';
        // File inputs don't have a value attribute
        unset($input_attrs['placeholder']);

        return '<input' . $this->build_attrs($input_attrs) . '>';
    }

    public function render_view(FieldMeta $field, $value, array $attrs = []): string
    {
        if ($value) {
            return '<span class="form-view-value">' . $this->esc($value) . '</span>';
        }
        return '<span class="form-view-value form-view-empty">No file</span>';
    }
}
