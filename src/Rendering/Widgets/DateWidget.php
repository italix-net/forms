<?php

declare(strict_types=1);

namespace Italix\Forms\Rendering\Widgets;

use Italix\Forms\FieldMeta;

/**
 * Widget for date/time inputs (date, datetime-local, time, month, week).
 */
class DateWidget extends AbstractWidget
{
    public function render_edit(FieldMeta $field, $value, array $attrs = []): string
    {
        $input_attrs = $this->common_attrs($field, $value, $attrs);
        $input_attrs['type'] = $field->get_type();
        $input_attrs['value'] = $value !== null ? (string)$value : '';

        return '<input' . $this->build_attrs($input_attrs) . '>';
    }
}
