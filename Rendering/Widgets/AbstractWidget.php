<?php
/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at https://mozilla.org/MPL/2.0/.
 */

declare(strict_types=1);

namespace Italix\Forms\Rendering\Widgets;

use Italix\Forms\FieldMeta;
use Italix\Forms\Rendering\WidgetInterface;

/**
 * Base class for widgets with shared HTML rendering helpers.
 */
abstract class AbstractWidget implements WidgetInterface
{
    /**
     * Escape a string for safe HTML output.
     *
     * @param mixed $value
     * @return string
     */
    protected function esc($value): string
    {
        return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    /**
     * Build an HTML attribute string from an associative array.
     *
     * Boolean true values render the attribute name only (e.g., "required").
     * Null and false values are skipped.
     *
     * @param array $attrs
     * @return string
     */
    protected function build_attrs(array $attrs): string
    {
        $parts = [];
        foreach ($attrs as $key => $val) {
            if ($val === null || $val === false) {
                continue;
            }
            if ($val === true) {
                $parts[] = $this->esc($key);
            } else {
                $parts[] = $this->esc($key) . '="' . $this->esc($val) . '"';
            }
        }

        return $parts ? ' ' . implode(' ', $parts) : '';
    }

    /**
     * Collect common HTML attributes from a FieldMeta.
     *
     * @param FieldMeta $field
     * @param mixed     $value
     * @param array     $extra
     * @return array
     */
    protected function common_attrs(FieldMeta $field, $value = null, array $extra = []): array
    {
        $attrs = [
            'id' => 'field-' . $field->get_name(),
            'name' => $field->get_name(),
        ];

        if ($field->get_input_class()) {
            $attrs['class'] = $field->get_input_class();
        }

        if ($field->is_readonly()) {
            $attrs['readonly'] = true;
        }

        if ($field->is_disabled()) {
            $attrs['disabled'] = true;
        }

        if ($field->is_required()) {
            $attrs['required'] = true;
        }

        if ($field->get_placeholder() !== null) {
            $attrs['placeholder'] = $field->get_placeholder();
        }

        // Merge field-level attributes
        foreach ($field->get_attributes() as $k => $v) {
            $attrs[$k] = $v;
        }

        // Merge caller-provided extra attrs (highest priority)
        foreach ($extra as $k => $v) {
            $attrs[$k] = $v;
        }

        return $attrs;
    }

    /**
     * Wrap a field in a standard div with label, input, help text, and error.
     *
     * @param FieldMeta $field
     * @param string    $input_html  The rendered input element
     * @param string    $error       Error message, if any
     * @return string
     */
    public function wrap(FieldMeta $field, string $input_html, string $error = ''): string
    {
        $wrapper_class = 'form-field';
        if ($field->get_wrapper_class()) {
            $wrapper_class .= ' ' . $field->get_wrapper_class();
        }
        if ($error) {
            $wrapper_class .= ' has-error';
        }

        $html = '<div class="' . $this->esc($wrapper_class) . '">';

        // Label
        $label_class = 'form-label';
        if ($field->get_label_class()) {
            $label_class .= ' ' . $field->get_label_class();
        }
        $html .= '<label class="' . $this->esc($label_class) . '" for="field-' . $this->esc($field->get_name()) . '">';
        $html .= $this->esc($field->get_label());
        if ($field->is_required()) {
            $html .= ' <span class="required">*</span>';
        }
        $html .= '</label>';

        // Input
        $html .= $input_html;

        // Help text
        if ($field->get_help()) {
            $help_class = 'form-help';
            if ($field->get_help_class()) {
                $help_class .= ' ' . $field->get_help_class();
            }
            $html .= '<div class="' . $this->esc($help_class) . '">' . $this->esc($field->get_help()) . '</div>';
        }

        // Error message
        if ($error) {
            $html .= '<div class="field-error">' . $this->esc($error) . '</div>';
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * Render a read-only view (span with the value).
     *
     * @param FieldMeta $field
     * @param mixed     $value
     * @param array     $attrs
     * @return string
     */
    public function render_view(FieldMeta $field, $value, array $attrs = []): string
    {
        $display = $this->format_view_value($field, $value);
        return '<span class="form-view-value">' . $this->esc($display) . '</span>';
    }

    /**
     * Format a value for view mode display. Override in subclasses for
     * type-specific formatting (e.g., select shows label instead of key).
     *
     * @param FieldMeta $field
     * @param mixed     $value
     * @return string
     */
    protected function format_view_value(FieldMeta $field, $value): string
    {
        return (string)$value;
    }
}
