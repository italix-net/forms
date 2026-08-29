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
 * Widget for checkbox inputs.
 *
 * Supports single boolean checkboxes and multiple-checkbox groups
 * (when the field has options).
 */
class CheckboxWidget extends AbstractWidget
{
    public function render_edit(FieldMeta $field, $value, array $attrs = []): string
    {
        $options = $field->get_options();

        // Multiple checkboxes from options
        if (!empty($options)) {
            return $this->render_multiple($field, $value, $options, $attrs);
        }

        // Single boolean checkbox
        return $this->render_single($field, $value, $attrs);
    }

    private function render_single(FieldMeta $field, $value, array $attrs): string
    {
        $input_attrs = $this->common_attrs($field, $value, $attrs);
        $input_attrs['type'] = 'checkbox';
        $input_attrs['value'] = '1';
        unset($input_attrs['placeholder']);

        if ($value) {
            $input_attrs['checked'] = true;
        }

        // Hidden field to ensure the key is present when unchecked
        $html = '<input type="hidden" name="' . $this->esc($field->get_name()) . '" value="0">';
        $html .= '<input' . $this->build_attrs($input_attrs) . '>';

        return $html;
    }

    private function render_multiple(FieldMeta $field, $value, array $options, array $attrs): string
    {
        $selected = is_array($value) ? $value : [];
        $name = $field->get_name() . '[]';
        $html = '<div class="checkbox-group">';

        foreach ($options as $opt_value => $opt_label) {
            $id = 'field-' . $field->get_name() . '-' . $opt_value;
            $checked = in_array((string)$opt_value, array_map('strval', $selected), true) ? ' checked' : '';
            $html .= '<label class="checkbox-label" for="' . $this->esc($id) . '">';
            $html .= '<input type="checkbox" id="' . $this->esc($id) . '" name="' . $this->esc($name) . '" value="' . $this->esc($opt_value) . '"' . $checked . '>';
            $html .= ' ' . $this->esc($opt_label);
            $html .= '</label>';
        }

        $html .= '</div>';
        return $html;
    }

    public function render_view(FieldMeta $field, $value, array $attrs = []): string
    {
        $options = $field->get_options();

        if (!empty($options)) {
            $selected = is_array($value) ? $value : [];
            $labels = [];
            foreach ($selected as $v) {
                if (isset($options[$v])) {
                    $labels[] = $options[$v];
                }
            }
            $display = implode(', ', $labels);
        } else {
            $display = $value ? 'Yes' : 'No';
        }

        return '<span class="form-view-value">' . $this->esc($display) . '</span>';
    }
}
