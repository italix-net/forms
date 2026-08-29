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
 * Widget for numeric inputs (number, range).
 *
 * Automatically sets step="any" for decimal/float column types.
 */
class NumberWidget extends AbstractWidget
{
    public function render_edit(FieldMeta $field, $value, array $attrs = []): string
    {
        $input_attrs = $this->common_attrs($field, $value, $attrs);
        $input_attrs['type'] = $field->get_type();
        $input_attrs['value'] = $value !== null ? (string)$value : '';

        // Auto-set step for decimal types
        if (!isset($input_attrs['step'])) {
            $col_type = strtoupper($field->column()->get_type());
            if (in_array($col_type, ['DECIMAL', 'NUMERIC', 'REAL', 'DOUBLE PRECISION', 'FLOAT'], true)) {
                $input_attrs['step'] = 'any';
            }
        }

        return '<input' . $this->build_attrs($input_attrs) . '>';
    }
}
