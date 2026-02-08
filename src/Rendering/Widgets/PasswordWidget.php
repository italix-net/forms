<?php

declare(strict_types=1);

namespace Italix\Forms\Rendering\Widgets;

use Italix\Forms\FieldMeta;

/**
 * Widget for password inputs.
 *
 * Never pre-fills password values for security.
 */
class PasswordWidget extends AbstractWidget
{
    public function render_edit(FieldMeta $field, $value, array $attrs = []): string
    {
        $input_attrs = $this->common_attrs($field, null, $attrs);
        $input_attrs['type'] = 'password';
        // Never pre-fill passwords
        $input_attrs['value'] = '';
        $input_attrs['autocomplete'] = $input_attrs['autocomplete'] ?? 'new-password';

        return '<input' . $this->build_attrs($input_attrs) . '>';
    }

    public function render_view(FieldMeta $field, $value, array $attrs = []): string
    {
        return '<span class="form-view-value">********</span>';
    }
}
