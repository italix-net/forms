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
 * Widget for text-like inputs (text, email, url, tel, search, color).
 */
class TextWidget extends AbstractWidget
{
    public function render_edit(FieldMeta $field, $value, array $attrs = []): string
    {
        $input_attrs = $this->common_attrs($field, $value, $attrs);
        $input_attrs['type'] = $field->get_type();
        $input_attrs['value'] = (string)$value;

        $length = $field->column()->get_length();
        if ($length !== null && !isset($input_attrs['maxlength'])) {
            $input_attrs['maxlength'] = (string)$length;
        }

        return '<input' . $this->build_attrs($input_attrs) . '>';
    }
}
