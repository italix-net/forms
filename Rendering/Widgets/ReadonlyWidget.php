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
