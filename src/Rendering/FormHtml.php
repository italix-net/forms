<?php

declare(strict_types=1);

namespace Italix\Forms\Rendering;

use Italix\Forms\Contracts\PolymorphicColumnMeta;
use Italix\Forms\Contracts\RelationalColumnMeta;
use Italix\Forms\FieldMeta;
use Italix\Forms\FormMeta;
use Italix\Forms\FormSection;
use Italix\Forms\Rendering\Widgets\AbstractWidget;

/**
 * HTML form renderer.
 *
 * Takes a FormMeta instance and renders complete HTML forms, individual
 * fields, or field groups. Supports edit and view modes, validation
 * errors, value pre-filling, and automatic FK-based option loading.
 *
 * Example:
 *
 *     $html = new FormHtml($form);
 *     $html->action('/users/store')
 *          ->method('POST')
 *          ->values($user_data)
 *          ->errors($validation_errors);
 *
 *     echo $html->render();
 */
class FormHtml
{
    /** @var FormMeta */
    private $form;

    /** @var WidgetRegistry */
    private $registry;

    /** @var string */
    private $action = '';

    /** @var string */
    private $method = 'POST';

    /** @var string */
    private $mode = 'edit';

    /** @var array */
    private $values = [];

    /** @var array */
    private $errors = [];

    /** @var array */
    private $form_attrs = [];

    /** @var array|null */
    private $button_config = null;

    /** @var string|null */
    private $enctype = null;

    /** @var bool */
    private $auto_resolve_relations = true;

    /**
     * @param FormMeta $form
     * @param WidgetRegistry|null $registry  Custom widget registry (uses defaults if null)
     */
    public function __construct(FormMeta $form, ?WidgetRegistry $registry = null)
    {
        $this->form = $form;
        $this->registry = $registry ?? new WidgetRegistry();
    }

    // =========================================================================
    // Configuration (Fluent)
    // =========================================================================

    /**
     * Set the form action URL.
     *
     * @param string $url
     * @return self
     */
    public function action(string $url): self
    {
        $this->action = $url;
        return $this;
    }

    /**
     * Set the HTTP method (GET, POST, PUT, PATCH, DELETE).
     *
     * For PUT, PATCH, and DELETE, a hidden _method field is added
     * and the actual form method is set to POST.
     *
     * @param string $method
     * @return self
     */
    public function method(string $method): self
    {
        $this->method = strtoupper($method);
        return $this;
    }

    /**
     * Set the rendering mode ('edit' or 'view').
     *
     * @param string $mode
     * @return self
     */
    public function mode(string $mode): self
    {
        $this->mode = $mode;
        return $this;
    }

    /**
     * Pre-fill field values.
     *
     * @param array $values Associative array of field_name => value
     * @return self
     */
    public function values(array $values): self
    {
        $this->values = $values;
        return $this;
    }

    /**
     * Set validation errors to display.
     *
     * @param array $errors Associative array of field_name => error message
     * @return self
     */
    public function errors(array $errors): self
    {
        $this->errors = $errors;
        return $this;
    }

    /**
     * Set extra form-level HTML attributes.
     *
     * @param array $attrs
     * @return self
     */
    public function attrs(array $attrs): self
    {
        $this->form_attrs = $attrs;
        return $this;
    }

    /**
     * Configure form buttons.
     *
     * Accepts an associative array:
     *   - 'submit' => label text for the submit button
     *   - 'cancel' => URL or false to hide
     *   - 'cancel_label' => label text for the cancel link
     *
     * @param array $config
     * @return self
     */
    public function buttons(array $config): self
    {
        $this->button_config = $config;
        return $this;
    }

    /**
     * Set the form enctype manually.
     *
     * Normally this is auto-detected from file fields. Use this to
     * override the automatic detection.
     *
     * @param string $enctype
     * @return self
     */
    public function enctype(string $enctype): self
    {
        $this->enctype = $enctype;
        return $this;
    }

    /**
     * Enable or disable automatic FK relation resolution.
     *
     * When enabled (default), fields with FK relations and no manually
     * set options will automatically fetch options from the related table.
     *
     * @param bool $enabled
     * @return self
     */
    public function auto_resolve_relations(bool $enabled = true): self
    {
        $this->auto_resolve_relations = $enabled;
        return $this;
    }

    // =========================================================================
    // Rendering
    // =========================================================================

    /**
     * Render the complete form (open + fields + buttons + close).
     *
     * @return string
     */
    public function render(): string
    {
        $html = $this->open();

        $sections = $this->form->by_section();

        if ($this->has_multiple_sections($sections)) {
            $html .= $this->render_sections($sections);
        } else {
            $html .= $this->fields();
        }

        if ($this->mode === 'edit') {
            $html .= $this->render_buttons();
        }

        $html .= $this->close();

        return $html;
    }

    /**
     * Render the opening <form> tag.
     *
     * @return string
     */
    public function open(): string
    {
        if ($this->mode === 'view') {
            return '<div class="form-view">';
        }

        $attrs = $this->form_attrs;
        $attrs['action'] = $this->action;
        $attrs['method'] = in_array($this->method, ['GET', 'POST'], true) ? $this->method : 'POST';

        if (!isset($attrs['class'])) {
            $attrs['class'] = 'form';
        }

        // Auto-detect enctype for file uploads
        $enctype = $this->enctype ?? $this->detect_enctype();
        if ($enctype) {
            $attrs['enctype'] = $enctype;
        }

        $html = '<form' . $this->build_attrs($attrs) . '>';

        // Method spoofing for PUT, PATCH, DELETE
        if (!in_array($this->method, ['GET', 'POST'], true)) {
            $html .= '<input type="hidden" name="_method" value="' . $this->esc($this->method) . '">';
        }

        return $html;
    }

    /**
     * Render the closing tag.
     *
     * @return string
     */
    public function close(): string
    {
        return $this->mode === 'view' ? '</div>' : '</form>';
    }

    /**
     * Render all visible fields.
     *
     * @return string
     */
    public function fields(): string
    {
        $html = '';
        foreach ($this->form->each() as $name => $field_meta) {
            $html .= $this->field($name);
        }
        return $html;
    }

    /**
     * Render a single field by name.
     *
     * @param string $name
     * @return string
     */
    public function field(string $name): string
    {
        $field = $this->form->field($name);
        $value = $this->values[$name] ?? null;
        $error = $this->errors[$name] ?? '';

        // Auto-resolve FK relations to options
        if ($this->auto_resolve_relations) {
            $this->resolve_relation_options($field);
        }

        $type = $field->get_type();
        $widget = $this->registry->get($type);

        if ($this->mode === 'view') {
            $input_html = $widget->render_view($field, $value);
        } else {
            $input_html = $widget->render_edit($field, $value);
        }

        // Hidden fields are rendered bare (no wrapper)
        if ($type === 'hidden') {
            return $input_html;
        }

        // Use the widget's wrap method if available, otherwise do our own wrapping
        if ($widget instanceof AbstractWidget) {
            return $widget->wrap($field, $input_html, $error);
        }

        // Fallback wrapping for custom WidgetInterface implementations
        return $this->wrap_field($field, $input_html, $error);
    }

    // =========================================================================
    // Section Rendering
    // =========================================================================

    /**
     * Render fields organized by sections.
     *
     * @param array $sections Output from FormMeta::by_section()
     * @return string
     */
    private function render_sections(array $sections): string
    {
        $html = '';

        foreach ($sections as $key => $group) {
            /** @var FormSection|null $section */
            $section = $group['section'];
            /** @var array<string, FieldMeta> $fields */
            $fields = $group['fields'];

            if ($section === null) {
                // Ungrouped fields
                foreach ($fields as $name => $field_meta) {
                    $html .= $this->field($name);
                }
                continue;
            }

            $fieldset_class = 'form-section';
            if ($section->get_class()) {
                $fieldset_class .= ' ' . $section->get_class();
            }

            $attrs = ['class' => $fieldset_class];

            if ($section->is_collapsible()) {
                $attrs['data-collapsible'] = 'true';
                if ($section->is_collapsed()) {
                    $attrs['data-collapsed'] = 'true';
                }
            }

            $html .= '<fieldset' . $this->build_attrs($attrs) . '>';
            $html .= '<legend>' . $this->esc($section->get_title()) . '</legend>';

            if ($section->get_description()) {
                $html .= '<p class="section-description">' . $this->esc($section->get_description()) . '</p>';
            }

            $columns = $section->get_columns();
            if ($columns > 1) {
                $html .= '<div class="form-grid" style="display:grid;grid-template-columns:repeat(' . $columns . ',1fr);gap:1rem;">';
            }

            foreach ($fields as $name => $field_meta) {
                $html .= $this->field($name);
            }

            if ($columns > 1) {
                $html .= '</div>';
            }

            $html .= '</fieldset>';
        }

        return $html;
    }

    // =========================================================================
    // Buttons
    // =========================================================================

    /**
     * Render form buttons (submit and optional cancel).
     *
     * @return string
     */
    public function render_buttons(): string
    {
        $config = $this->button_config ?? ['submit' => 'Save', 'cancel' => false];
        $html = '<div class="form-buttons">';

        if (isset($config['submit']) && $config['submit'] !== false) {
            $html .= '<button type="submit" class="btn btn-primary">';
            $html .= $this->esc($config['submit']);
            $html .= '</button>';
        }

        if (isset($config['cancel']) && $config['cancel'] !== false) {
            $label = $config['cancel_label'] ?? 'Cancel';
            $html .= ' <a href="' . $this->esc($config['cancel']) . '" class="btn btn-cancel">';
            $html .= $this->esc($label);
            $html .= '</a>';
        }

        $html .= '</div>';

        return $html;
    }

    // =========================================================================
    // Relation Resolution
    // =========================================================================

    /**
     * Auto-resolve FK relations to populate field options.
     *
     * Uses instanceof checks to detect column capabilities:
     * - RelationalColumnMeta: auto-populate select from FK
     * - PolymorphicColumnMeta: set up dependent type+id field pair
     *
     * @param FieldMeta $field
     */
    private function resolve_relation_options(FieldMeta $field): void
    {
        // Skip if options are already set manually
        if (!empty($field->get_options())) {
            return;
        }

        $column = $field->column();

        // Polymorphic columns: set up data attributes for dependent fields
        if ($column instanceof PolymorphicColumnMeta) {
            $this->resolve_polymorphic_field($field, $column);
            return;
        }

        // Relational columns: auto-populate select or autocomplete
        if ($column instanceof RelationalColumnMeta) {
            $relation = $column->get_relation();
            if ($relation === null) {
                return;
            }

            $options = $relation->fetch_options();

            if ($options !== null) {
                $field->options($options);
                // Use select if no explicit type was set
                if ($field->get_type() === 'number' || $field->get_type() === 'text') {
                    $field->type('select');
                }
            } else {
                // Too many options - set autocomplete data attributes
                $field->type('text');
                $field->attrs([
                    'data-autocomplete' => 'true',
                    'data-source-table' => $relation->get_foreign_table(),
                    'data-source-key' => $relation->get_foreign_key(),
                    'data-source-label' => $relation->get_foreign_label(),
                ]);
            }
        }
    }

    /**
     * Set up a polymorphic ID field with dependent-field data attributes.
     *
     * @param FieldMeta $field
     * @param PolymorphicColumnMeta $column
     */
    private function resolve_polymorphic_field(FieldMeta $field, PolymorphicColumnMeta $column): void
    {
        $targets = $column->get_polymorphic_targets();
        $type_column = $column->get_polymorphic_type_column();

        $field->attrs([
            'data-depends-on' => $type_column,
            'data-polymorphic' => 'true',
            'data-polymorphic-targets' => json_encode(array_keys($targets)),
        ]);
    }

    // =========================================================================
    // Internal Helpers
    // =========================================================================

    /**
     * Detect if multipart enctype is needed (file fields present).
     *
     * @return string|null
     */
    private function detect_enctype(): ?string
    {
        foreach ($this->form->each() as $name => $field) {
            if ($field->get_type() === 'file') {
                return 'multipart/form-data';
            }
        }
        return null;
    }

    /**
     * Check if there are multiple real sections.
     *
     * @param array $sections
     * @return bool
     */
    private function has_multiple_sections(array $sections): bool
    {
        // Filter out the _default ungrouped section
        $real = 0;
        foreach ($sections as $key => $group) {
            if ($group['section'] !== null) {
                $real++;
            }
        }
        return $real > 0;
    }

    /**
     * Fallback field wrapping for custom widget implementations.
     *
     * @param FieldMeta $field
     * @param string $input_html
     * @param string $error
     * @return string
     */
    private function wrap_field(FieldMeta $field, string $input_html, string $error): string
    {
        $wrapper_class = 'form-field';
        if ($field->get_wrapper_class()) {
            $wrapper_class .= ' ' . $field->get_wrapper_class();
        }
        if ($error) {
            $wrapper_class .= ' has-error';
        }

        $html = '<div class="' . $this->esc($wrapper_class) . '">';

        $html .= '<label class="form-label" for="field-' . $this->esc($field->get_name()) . '">';
        $html .= $this->esc($field->get_label());
        if ($field->is_required()) {
            $html .= ' <span class="required">*</span>';
        }
        $html .= '</label>';

        $html .= $input_html;

        if ($field->get_help()) {
            $html .= '<div class="form-help">' . $this->esc($field->get_help()) . '</div>';
        }

        if ($error) {
            $html .= '<div class="field-error">' . $this->esc($error) . '</div>';
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * Escape a string for safe HTML output.
     *
     * @param mixed $value
     * @return string
     */
    private function esc($value): string
    {
        return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    /**
     * Build an HTML attribute string from an associative array.
     *
     * @param array $attrs
     * @return string
     */
    private function build_attrs(array $attrs): string
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
     * Get the underlying FormMeta.
     *
     * @return FormMeta
     */
    public function get_form(): FormMeta
    {
        return $this->form;
    }

    /**
     * Get the widget registry.
     *
     * @return WidgetRegistry
     */
    public function get_registry(): WidgetRegistry
    {
        return $this->registry;
    }
}
