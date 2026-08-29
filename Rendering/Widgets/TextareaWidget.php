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
 * Widget for textarea inputs.
 */
class TextareaWidget extends AbstractWidget
{
    public function render_edit(FieldMeta $field, $value, array $attrs = []): string
    {
        $input_attrs = $this->common_attrs($field, $value, $attrs);
        unset($input_attrs['placeholder']); // we'll handle it ourselves

        if (!isset($input_attrs['rows'])) {
            $input_attrs['rows'] = '5';
        }

        $placeholder = $field->get_placeholder();
        if ($placeholder !== null) {
            $input_attrs['placeholder'] = $placeholder;
        }

        return '<textarea' . $this->build_attrs($input_attrs) . '>' . $this->esc($value) . '</textarea>';
    }
}
