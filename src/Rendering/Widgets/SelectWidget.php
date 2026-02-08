<?php

declare(strict_types=1);

namespace Italix\Forms\Rendering\Widgets;

use Italix\Forms\FieldMeta;

/**
 * Widget for select dropdowns.
 */
class SelectWidget extends AbstractWidget
{
    public function render_edit(FieldMeta $field, $value, array $attrs = []): string
    {
        $input_attrs = $this->common_attrs($field, $value, $attrs);
        // Remove placeholder from attrs - we'll use it as the empty option text
        unset($input_attrs['placeholder']);

        $html = '<select' . $this->build_attrs($input_attrs) . '>';

        // Empty option
        $placeholder = $field->get_placeholder() ?? '-- Select --';
        if (!$field->is_required() || $value === null || $value === '') {
            $html .= '<option value="">' . $this->esc($placeholder) . '</option>';
        }

        foreach ($field->get_options() as $opt_value => $opt_label) {
            $selected = ((string)$opt_value === (string)$value) ? ' selected' : '';
            $html .= '<option value="' . $this->esc($opt_value) . '"' . $selected . '>';
            $html .= $this->esc($opt_label);
            $html .= '</option>';
        }

        $html .= '</select>';

        return $html;
    }

    public function render_view(FieldMeta $field, $value, array $attrs = []): string
    {
        $display = $this->format_view_value($field, $value);
        return '<span class="form-view-value">' . $this->esc($display) . '</span>';
    }

    protected function format_view_value(FieldMeta $field, $value): string
    {
        $options = $field->get_options();
        if (isset($options[$value])) {
            return (string)$options[$value];
        }
        return (string)$value;
    }
}
