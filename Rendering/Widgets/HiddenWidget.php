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
 * Widget for hidden inputs.
 *
 * Renders a bare <input type="hidden"> without wrapper, label, or help text.
 */
class HiddenWidget extends AbstractWidget
{
    public function render_edit(FieldMeta $field, $value, array $attrs = []): string
    {
        $input_attrs = [
            'type' => 'hidden',
            'id' => 'field-' . $field->get_name(),
            'name' => $field->get_name(),
            'value' => (string)$value,
        ];

        foreach ($attrs as $k => $v) {
            $input_attrs[$k] = $v;
        }

        return '<input' . $this->build_attrs($input_attrs) . '>';
    }

    public function render_view(FieldMeta $field, $value, array $attrs = []): string
    {
        // Hidden fields are not displayed in view mode
        return '';
    }
}
