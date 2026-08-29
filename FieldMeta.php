<?php
/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at https://mozilla.org/MPL/2.0/.
 */

declare(strict_types=1);

namespace Italix\Forms;

use Italix\Contracts\ColumnMeta;
use Italix\Contracts\RuleMeta;
use InvalidArgumentException;

/**
 * Metadata for a single form field.
 *
 * FieldMeta wraps a ColumnMeta and adds form-specific information such as
 * labels, placeholders, validation rules, and layout options.
 *
 * Example:
 *
 *     $field = new FieldMeta($column);
 *     $field
 *         ->label('Email Address')
 *         ->placeholder('you@example.com')
 *         ->help('We will never share your email')
 *         ->rules(Rule::required(), Rule::email());   // Italix\Rules\Rule
 */
class FieldMeta
{
    /** @var ColumnMeta */
    private $column;

    // Display properties
    /** @var string|null */
    private $label = null;

    /** @var string|null */
    private $placeholder = null;

    /** @var string|null */
    private $help = null;

    // Input properties
    /** @var string|null */
    private $input_type = null;

    /** @var array */
    private $options = [];

    /** @var array */
    private $attributes = [];

    // State properties
    /** @var bool */
    private $hidden = false;

    /** @var bool */
    private $readonly = false;

    /** @var bool */
    private $disabled = false;

    /** @var bool */
    private $sensitive = false;

    // Layout properties
    /** @var int|null */
    private $order = null;

    /** @var string|null */
    private $group = null;

    /** @var int|null */
    private $colspan = null;

    /** @var string|null */
    private $width = null;

    // CSS classes
    /** @var string|null */
    private $wrapper_class = null;

    /** @var string|null */
    private $input_class = null;

    /** @var string|null */
    private $label_class = null;

    /** @var string|null */
    private $help_class = null;

    // Validation
    /** @var RuleMeta[] Carried, never executed — see Italix\Rules for the engine */
    private $rules = [];

    public function __construct(ColumnMeta $column)
    {
        $this->column = $column;
    }

    /**
     * Get the underlying column metadata.
     *
     * @return ColumnMeta
     */
    public function column(): ColumnMeta
    {
        return $this->column;
    }

    // =========================================================================
    // Display Setters (Fluent)
    // =========================================================================

    /**
     * Set the field label.
     *
     * @param string $label
     * @return self
     */
    public function label(string $label): self
    {
        $this->label = $label;
        return $this;
    }

    /**
     * Set the placeholder text.
     *
     * @param string $placeholder
     * @return self
     */
    public function placeholder(string $placeholder): self
    {
        $this->placeholder = $placeholder;
        return $this;
    }

    /**
     * Set the help text displayed below the field.
     *
     * @param string $help
     * @return self
     */
    public function help(string $help): self
    {
        $this->help = $help;
        return $this;
    }

    // =========================================================================
    // Input Setters (Fluent)
    // =========================================================================

    /**
     * Set the input type (text, email, password, select, textarea, etc.).
     *
     * @param string $type
     * @return self
     */
    public function type(string $type): self
    {
        $this->input_type = $type;
        return $this;
    }

    /**
     * Set options for select, radio, or checkbox inputs.
     *
     * @param array $options Associative array of value => label pairs
     * @return self
     */
    public function options(array $options): self
    {
        $this->options = $options;
        return $this;
    }

    /**
     * Set a single HTML attribute.
     *
     * @param string $name
     * @param mixed $value
     * @return self
     */
    public function attr(string $name, $value): self
    {
        $this->attributes[$name] = $value;
        return $this;
    }

    /**
     * Set multiple HTML attributes at once.
     *
     * @param array $attributes
     * @return self
     */
    public function attrs(array $attributes): self
    {
        $this->attributes = array_merge($this->attributes, $attributes);
        return $this;
    }

    // =========================================================================
    // State Setters (Fluent)
    // =========================================================================

    /**
     * Mark the field as hidden (won't be rendered in forms).
     *
     * @param bool $hidden
     * @return self
     */
    public function hidden(bool $hidden = true): self
    {
        $this->hidden = $hidden;
        return $this;
    }

    /**
     * Mark the field as readonly.
     *
     * @param bool $readonly
     * @return self
     */
    public function readonly(bool $readonly = true): self
    {
        $this->readonly = $readonly;
        return $this;
    }

    /**
     * Mark the field as disabled.
     *
     * @param bool $disabled
     * @return self
     */
    public function disabled(bool $disabled = true): self
    {
        $this->disabled = $disabled;
        return $this;
    }

    /**
     * Mark the field as sensitive (excluded from JSON/array exports by default).
     *
     * Use this for fields like passwords, API keys, or tokens that should
     * not be serialized to the frontend.
     *
     * @param bool $sensitive
     * @return self
     */
    public function sensitive(bool $sensitive = true): self
    {
        $this->sensitive = $sensitive;
        return $this;
    }

    // =========================================================================
    // Layout Setters (Fluent)
    // =========================================================================

    /**
     * Set the display order (lower numbers first).
     *
     * @param int $order
     * @return self
     */
    public function order(int $order): self
    {
        $this->order = $order;
        return $this;
    }

    /**
     * Assign the field to a section/group.
     *
     * @param string $group
     * @return self
     */
    public function group(string $group): self
    {
        $this->group = $group;
        return $this;
    }

    /**
     * Set the column span in a grid layout.
     *
     * @param int $span
     * @return self
     */
    public function colspan(int $span): self
    {
        $this->colspan = $span;
        return $this;
    }

    /**
     * Set a custom width (CSS value).
     *
     * @param string $width
     * @return self
     */
    public function width(string $width): self
    {
        $this->width = $width;
        return $this;
    }

    // =========================================================================
    // CSS Class Setters (Fluent)
    // =========================================================================

    /**
     * Set the wrapper element CSS class.
     *
     * @param string $class
     * @return self
     */
    public function wrapper_class(string $class): self
    {
        $this->wrapper_class = $class;
        return $this;
    }

    /**
     * Set the input element CSS class.
     *
     * @param string $class
     * @return self
     */
    public function input_class(string $class): self
    {
        $this->input_class = $class;
        return $this;
    }

    /**
     * Set the label element CSS class.
     *
     * @param string $class
     * @return self
     */
    public function label_class(string $class): self
    {
        $this->label_class = $class;
        return $this;
    }

    /**
     * Set the help text CSS class.
     *
     * @param string $class
     * @return self
     */
    public function help_class(string $class): self
    {
        $this->help_class = $class;
        return $this;
    }

    // =========================================================================
    // Validation Setters (Fluent)
    // =========================================================================

    /**
     * Attach validation rules to the field.
     *
     * A rule is opaque here: FieldMeta stores it and hands it back through
     * get_rules(), and never asks what it means. Building and executing rules
     * belongs to italix/rules, which is why this takes the RuleMeta contract
     * rather than any particular rule class.
     *
     * Example:
     *
     *     $field->rules(Rule::required(), Rule::email());
     *
     * Shorthand strings ('max_length:255') were parsed here until the rule
     * vocabulary moved out; use Rule::parse('max_length:255') instead.
     *
     * @param RuleMeta ...$rules
     * @return self
     * @throws InvalidArgumentException If given something other than a RuleMeta
     */
    public function rules(...$rules): self
    {
        foreach ($rules as $rule) {
            if ($rule instanceof RuleMeta) {
                $this->rules[] = $rule;
                continue;
            }

            if (is_string($rule)) {
                throw new InvalidArgumentException(
                    "Shorthand rule strings are no longer parsed by FieldMeta. "
                    . "Use Rule::parse('{$rule}') from italix/rules instead."
                );
            }

            throw new InvalidArgumentException(
                'Rule must implement Italix\\Contracts\\RuleMeta, got ' . gettype($rule)
            );
        }

        return $this;
    }

    // =========================================================================
    // Getters
    // =========================================================================

    /**
     * Get the field name.
     *
     * @return string
     */
    public function get_name(): string
    {
        return $this->column->get_name();
    }

    /**
     * Get the field label (auto-generated from name if not set).
     *
     * @return string
     */
    public function get_label(): string
    {
        if ($this->label !== null) {
            return $this->label;
        }

        return ucwords(str_replace('_', ' ', $this->column->get_name()));
    }

    /**
     * Get the placeholder text.
     *
     * @return string|null
     */
    public function get_placeholder(): ?string
    {
        return $this->placeholder;
    }

    /**
     * Get the help text.
     *
     * @return string|null
     */
    public function get_help(): ?string
    {
        return $this->help;
    }

    /**
     * Get the input type (auto-inferred from column type if not set).
     *
     * @return string
     */
    public function get_type(): string
    {
        if ($this->input_type !== null) {
            return $this->input_type;
        }

        if (!empty($this->options)) {
            return 'select';
        }

        return $this->infer_input_type();
    }

    /**
     * Get the options for select/radio/checkbox inputs.
     *
     * @return array
     */
    public function get_options(): array
    {
        return $this->options;
    }

    /**
     * Get HTML attributes.
     *
     * @return array
     */
    public function get_attributes(): array
    {
        return $this->attributes;
    }

    /**
     * Get the validation rules, exactly as they were attached.
     *
     * @return RuleMeta[]
     */
    public function get_rules(): array
    {
        return $this->rules;
    }

    /**
     * Get the display order.
     *
     * @return int|null
     */
    public function get_order(): ?int
    {
        return $this->order;
    }

    /**
     * Get the section/group name.
     *
     * @return string|null
     */
    public function get_group(): ?string
    {
        return $this->group;
    }

    /**
     * Get the column span.
     *
     * @return int|null
     */
    public function get_colspan(): ?int
    {
        return $this->colspan;
    }

    /**
     * Get the custom width.
     *
     * @return string|null
     */
    public function get_width(): ?string
    {
        return $this->width;
    }

    /**
     * Get the wrapper CSS class.
     *
     * @return string|null
     */
    public function get_wrapper_class(): ?string
    {
        return $this->wrapper_class;
    }

    /**
     * Get the input CSS class.
     *
     * @return string|null
     */
    public function get_input_class(): ?string
    {
        return $this->input_class;
    }

    /**
     * Get the label CSS class.
     *
     * @return string|null
     */
    public function get_label_class(): ?string
    {
        return $this->label_class;
    }

    /**
     * Get the help text CSS class.
     *
     * @return string|null
     */
    public function get_help_class(): ?string
    {
        return $this->help_class;
    }

    /**
     * Check if the field is hidden.
     *
     * @return bool
     */
    public function is_hidden(): bool
    {
        return $this->hidden;
    }

    /**
     * Check if the field is readonly.
     *
     * @return bool
     */
    public function is_readonly(): bool
    {
        return $this->readonly;
    }

    /**
     * Check if the field is disabled.
     *
     * @return bool
     */
    public function is_disabled(): bool
    {
        return $this->disabled;
    }

    /**
     * Check if the field is marked as sensitive.
     *
     * @return bool
     */
    public function is_sensitive(): bool
    {
        return $this->sensitive;
    }

    /**
     * Check if the field is required.
     *
     * A field is required if:
     * - The underlying column is not nullable, OR
     * - There's an explicit 'required' validation rule
     *
     * @return bool
     */
    public function is_required(): bool
    {
        if (!$this->column->is_nullable()) {
            return true;
        }

        foreach ($this->rules as $rule) {
            if ($rule->name === 'required') {
                return true;
            }
        }

        return false;
    }

    /**
     * Infer the HTML input type from the column data type.
     *
     * @return string
     */
    private function infer_input_type(): string
    {
        $col_type = strtoupper($this->column->get_type());

        if ($col_type === 'BOOLEAN') {
            return 'checkbox';
        }
        if ($col_type === 'TEXT') {
            return 'textarea';
        }
        if ($col_type === 'DATE') {
            return 'date';
        }
        if (in_array($col_type, ['DATETIME', 'TIMESTAMP'], true)) {
            return 'datetime-local';
        }
        if ($col_type === 'TIME') {
            return 'time';
        }
        if (in_array($col_type, ['INTEGER', 'BIGINT', 'SMALLINT', 'SERIAL', 'BIGSERIAL'], true)) {
            return 'number';
        }
        if (in_array($col_type, ['DECIMAL', 'NUMERIC', 'REAL', 'DOUBLE PRECISION', 'FLOAT'], true)) {
            return 'number';
        }
        if (in_array($col_type, ['JSON', 'JSONB'], true)) {
            return 'textarea';
        }

        return 'text';
    }

    // =========================================================================
    // Export
    // =========================================================================

    /**
     * Export all metadata as an array.
     *
     * Useful for JSON serialization, template engines, or JavaScript form builders.
     * Sensitive fields are excluded from the export by default.
     *
     * @param bool $include_sensitive Whether to include sensitive fields
     * @return array
     */
    public function to_array(bool $include_sensitive = false): array
    {
        if ($this->sensitive && !$include_sensitive) {
            return [
                'name' => $this->get_name(),
                'label' => $this->get_label(),
                'type' => $this->get_type(),
                'sensitive' => true,
                'required' => $this->is_required(),
                'hidden' => $this->is_hidden(),
                'order' => $this->get_order(),
                'group' => $this->get_group(),
            ];
        }

        return [
            'name' => $this->get_name(),
            'label' => $this->get_label(),
            'type' => $this->get_type(),
            'placeholder' => $this->get_placeholder(),
            'help' => $this->get_help(),
            'options' => $this->get_options(),
            'rules' => array_map(fn(RuleMeta $r) => $r->to_array(), $this->rules),
            'required' => $this->is_required(),
            'hidden' => $this->is_hidden(),
            'readonly' => $this->is_readonly(),
            'disabled' => $this->is_disabled(),
            'order' => $this->get_order(),
            'group' => $this->get_group(),
            'colspan' => $this->get_colspan(),
            'width' => $this->get_width(),
            'attributes' => $this->get_attributes(),
            'classes' => [
                'wrapper' => $this->get_wrapper_class(),
                'input' => $this->get_input_class(),
                'label' => $this->get_label_class(),
                'help' => $this->get_help_class(),
            ],
        ];
    }
}
