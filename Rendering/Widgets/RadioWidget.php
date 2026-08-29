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
 * Widget for radio button groups.
 */
class RadioWidget extends AbstractWidget
{
    public function render_edit(FieldMeta $field, $value, array $attrs = []): string
    {
        $html = '<div class="radio-group">';

        foreach ($field->get_options() as $opt_value => $opt_label) {
            $id = 'field-' . $field->get_name() . '-' . $opt_value;
            $checked = ((string)$opt_value === (string)$value) ? ' checked' : '';
            $disabled = $field->is_disabled() ? ' disabled' : '';

            $html .= '<label class="radio-label" for="' . $this->esc($id) . '">';
            $html .= '<input type="radio" id="' . $this->esc($id) . '" name="' . $this->esc($field->get_name()) . '" value="' . $this->esc($opt_value) . '"' . $checked . $disabled . '>';
            $html .= ' ' . $this->esc($opt_label);
            $html .= '</label>';
        }

        $html .= '</div>';
        return $html;
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
