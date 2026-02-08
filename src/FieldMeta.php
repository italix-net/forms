<?php

declare(strict_types=1);

namespace Italix\Forms;

use Italix\Forms\Contracts\ColumnMeta;
use Italix\Forms\Validation\Rule;

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
 *         ->rules(Rule::required(), Rule::email());
 */
class FieldMeta
{
    // Display properties
    private ?string $label = null;
    private ?string $placeholder = null;
    private ?string $help = null;

    // Input properties
    private ?string $input_type = null;
    private array $options = [];
    private array $attributes = [];

    // State properties
    private bool $hidden = false;
    private bool $readonly = false;
    private bool $disabled = false;

    // Layout properties
    private ?int $order = null;
    private ?string $group = null;
    private ?int $colspan = null;
    private ?string $width = null;

    // CSS classes
    private ?string $wrapper_class = null;
    private ?string $input_class = null;
    private ?string $label_class = null;
    private ?string $help_class = null;

    // Validation
    /** @var Rule[] */
    private array $rules = [];

    public function __construct(
        private ColumnMeta $column
    ) {}

    /**
     * Get the underlying column metadata.
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
     */
    public function label(string $label): self
    {
        $this->label = $label;
        return $this;
    }

    /**
     * Set the placeholder text.
     */
    public function placeholder(string $placeholder): self
    {
        $this->placeholder = $placeholder;
        return $this;
    }

    /**
     * Set the help text displayed below the field.
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
     */
    public function options(array $options): self
    {
        $this->options = $options;
        return $this;
    }

    /**
     * Set a single HTML attribute.
     */
    public function attr(string $name, mixed $value): self
    {
        $this->attributes[$name] = $value;
        return $this;
    }

    /**
     * Set multiple HTML attributes at once.
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
     */
    public function hidden(bool $hidden = true): self
    {
        $this->hidden = $hidden;
        return $this;
    }

    /**
     * Mark the field as readonly.
     */
    public function readonly(bool $readonly = true): self
    {
        $this->readonly = $readonly;
        return $this;
    }

    /**
     * Mark the field as disabled.
     */
    public function disabled(bool $disabled = true): self
    {
        $this->disabled = $disabled;
        return $this;
    }

    // =========================================================================
    // Layout Setters (Fluent)
    // =========================================================================

    /**
     * Set the display order (lower numbers first).
     */
    public function order(int $order): self
    {
        $this->order = $order;
        return $this;
    }

    /**
     * Assign the field to a section/group.
     */
    public function group(string $group): self
    {
        $this->group = $group;
        return $this;
    }

    /**
     * Set the column span in a grid layout.
     */
    public function colspan(int $span): self
    {
        $this->colspan = $span;
        return $this;
    }

    /**
     * Set a custom width (CSS value).
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
     */
    public function wrapper_class(string $class): self
    {
        $this->wrapper_class = $class;
        return $this;
    }

    /**
     * Set the input element CSS class.
     */
    public function input_class(string $class): self
    {
        $this->input_class = $class;
        return $this;
    }

    /**
     * Set the label element CSS class.
     */
    public function label_class(string $class): self
    {
        $this->label_class = $class;
        return $this;
    }

    /**
     * Set the help text CSS class.
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
     * Add validation rules to the field.
     *
     * Accepts Rule objects or shorthand strings like 'required', 'email',
     * 'max_length:255', etc.
     *
     * Example:
     *
     *     $field->rules(Rule::required(), Rule::email());
     *     $field->rules('required', 'email', 'max_length:255');
     */
    public function rules(Rule|string ...$rules): self
    {
        foreach ($rules as $rule) {
            $this->rules[] = $rule instanceof Rule
                ? $rule
                : $this->parse_rule_string($rule);
        }
        return $this;
    }

    /**
     * Parse a rule string like 'max_length:255' into a Rule object.
     */
    private function parse_rule_string(string $rule): Rule
    {
        // Parse "rule_name:param1,param2" format
        if (str_contains($rule, ':')) {
            [$name, $paramStr] = explode(':', $rule, 2);
            $params = explode(',', $paramStr);
        } else {
            $name = $rule;
            $params = [];
        }

        return match ($name) {
            'required' => Rule::required(),
            'email' => Rule::email(),
            'url' => Rule::url(),
            'numeric' => Rule::numeric(),
            'integer' => Rule::integer(),
            'alpha' => Rule::alpha(),
            'alpha_num' => Rule::alpha_num(),
            'alpha_dash' => Rule::alpha_dash(),
            'date' => Rule::date(),
            'file' => Rule::file(),
            'image' => Rule::image(),
            'min' => Rule::min((float)($params[0] ?? 0)),
            'max' => Rule::max((float)($params[0] ?? 0)),
            'min_length' => Rule::min_length((int)($params[0] ?? 0)),
            'max_length' => Rule::max_length((int)($params[0] ?? 0)),
            'length' => Rule::length((int)($params[0] ?? 0)),
            'between' => Rule::between((float)($params[0] ?? 0), (float)($params[1] ?? 0)),
            'length_between' => Rule::length_between((int)($params[0] ?? 0), (int)($params[1] ?? 0)),
            'pattern' => Rule::pattern($params[0] ?? ''),
            'in' => Rule::in($params),
            'not_in' => Rule::not_in($params),
            'confirmed' => Rule::confirmed($params[0] ?? 'confirmation'),
            'same' => Rule::same($params[0] ?? ''),
            'different' => Rule::different($params[0] ?? ''),
            'date_format' => Rule::date_format($params[0] ?? ''),
            'before' => Rule::before($params[0] ?? ''),
            'after' => Rule::after($params[0] ?? ''),
            'mimes' => Rule::mimes($params),
            'max_file_size' => Rule::max_file_size((int)($params[0] ?? 0)),
            default => Rule::custom($name, $params),
        };
    }

    // =========================================================================
    // Getters
    // =========================================================================

    /**
     * Get the field name.
     */
    public function get_name(): string
    {
        return $this->column->get_name();
    }

    /**
     * Get the field label (auto-generated from name if not set).
     */
    public function get_label(): string
    {
        if ($this->label !== null) {
            return $this->label;
        }

        // Convert snake_case to Title Case
        return ucwords(str_replace('_', ' ', $this->column->get_name()));
    }

    /**
     * Get the placeholder text.
     */
    public function get_placeholder(): ?string
    {
        return $this->placeholder;
    }

    /**
     * Get the help text.
     */
    public function get_help(): ?string
    {
        return $this->help;
    }

    /**
     * Get the input type (auto-inferred from column type if not set).
     */
    public function get_type(): string
    {
        if ($this->input_type !== null) {
            return $this->input_type;
        }

        // If we have options, it's a select
        if (!empty($this->options)) {
            return 'select';
        }

        return $this->infer_input_type();
    }

    /**
     * Get the options for select/radio/checkbox inputs.
     */
    public function get_options(): array
    {
        return $this->options;
    }

    /**
     * Get HTML attributes.
     */
    public function get_attributes(): array
    {
        return $this->attributes;
    }

    /**
     * Get the validation rules.
     *
     * @return Rule[]
     */
    public function get_rules(): array
    {
        return $this->rules;
    }

    /**
     * Get the display order.
     */
    public function get_order(): ?int
    {
        return $this->order;
    }

    /**
     * Get the section/group name.
     */
    public function get_group(): ?string
    {
        return $this->group;
    }

    /**
     * Get the column span.
     */
    public function get_colspan(): ?int
    {
        return $this->colspan;
    }

    /**
     * Get the custom width.
     */
    public function get_width(): ?string
    {
        return $this->width;
    }

    /**
     * Get the wrapper CSS class.
     */
    public function get_wrapper_class(): ?string
    {
        return $this->wrapper_class;
    }

    /**
     * Get the input CSS class.
     */
    public function get_input_class(): ?string
    {
        return $this->input_class;
    }

    /**
     * Get the label CSS class.
     */
    public function get_label_class(): ?string
    {
        return $this->label_class;
    }

    /**
     * Get the help text CSS class.
     */
    public function get_help_class(): ?string
    {
        return $this->help_class;
    }

    /**
     * Check if the field is hidden.
     */
    public function is_hidden(): bool
    {
        return $this->hidden;
    }

    /**
     * Check if the field is readonly.
     */
    public function is_readonly(): bool
    {
        return $this->readonly;
    }

    /**
     * Check if the field is disabled.
     */
    public function is_disabled(): bool
    {
        return $this->disabled;
    }

    /**
     * Check if the field is required.
     *
     * A field is required if:
     * - The underlying column is not nullable, OR
     * - There's an explicit 'required' validation rule
     */
    public function is_required(): bool
    {
        // Check if column is not nullable
        if (!$this->column->is_nullable()) {
            return true;
        }

        // Check for explicit required rule
        foreach ($this->rules as $rule) {
            if ($rule->name === 'required') {
                return true;
            }
        }

        return false;
    }

    /**
     * Infer the HTML input type from the column data type.
     */
    private function infer_input_type(): string
    {
        $colType = strtoupper($this->column->get_type());

        return match (true) {
            $colType === 'BOOLEAN' => 'checkbox',
            $colType === 'TEXT' => 'textarea',
            $colType === 'DATE' => 'date',
            in_array($colType, ['DATETIME', 'TIMESTAMP']) => 'datetime-local',
            $colType === 'TIME' => 'time',
            in_array($colType, ['INTEGER', 'BIGINT', 'SMALLINT', 'SERIAL', 'BIGSERIAL']) => 'number',
            in_array($colType, ['DECIMAL', 'NUMERIC', 'REAL', 'DOUBLE PRECISION', 'FLOAT']) => 'number',
            in_array($colType, ['JSON', 'JSONB']) => 'textarea',
            $colType === 'UUID' => 'text',
            default => 'text',
        };
    }

    // =========================================================================
    // Export
    // =========================================================================

    /**
     * Export all metadata as an array.
     *
     * Useful for JSON serialization, template engines, or JavaScript form builders.
     */
    public function to_array(): array
    {
        return [
            'name' => $this->get_name(),
            'label' => $this->get_label(),
            'type' => $this->get_type(),
            'placeholder' => $this->get_placeholder(),
            'help' => $this->get_help(),
            'options' => $this->get_options(),
            'rules' => array_map(fn(Rule $r) => $r->to_array(), $this->rules),
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
