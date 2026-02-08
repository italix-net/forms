<?php

declare(strict_types=1);

namespace Italix\Forms;

/**
 * Represents a section/group of fields in a form.
 *
 * Sections allow you to organize form fields into logical groups with
 * titles, descriptions, and layout options.
 *
 * Example:
 *
 *     $form->section('personal')
 *         ->title('Personal Information')
 *         ->description('Enter your basic details')
 *         ->icon('user')
 *         ->columns(2);
 *
 *     $form->section('settings')
 *         ->title('Account Settings')
 *         ->collapsible(collapsed: true);
 */
class FormSection
{
    // Display properties
    private ?string $title = null;
    private ?string $description = null;
    private ?string $icon = null;

    // Layout properties
    private int $order = 0;
    private int $columns = 1;
    private ?string $class = null;

    // Behavior properties
    private bool $collapsible = false;
    private bool $collapsed = false;

    public function __construct(
        private string $name
    ) {}

    // =========================================================================
    // Display Setters (Fluent)
    // =========================================================================

    /**
     * Set the section title.
     */
    public function title(string $title): self
    {
        $this->title = $title;
        return $this;
    }

    /**
     * Set the section description.
     */
    public function description(string $description): self
    {
        $this->description = $description;
        return $this;
    }

    /**
     * Set the section icon (icon name or CSS class).
     */
    public function icon(string $icon): self
    {
        $this->icon = $icon;
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
     * Set the number of columns in the section grid.
     */
    public function columns(int $columns): self
    {
        $this->columns = $columns;
        return $this;
    }

    /**
     * Set the section wrapper CSS class.
     */
    public function class(string $class): self
    {
        $this->class = $class;
        return $this;
    }

    // =========================================================================
    // Behavior Setters (Fluent)
    // =========================================================================

    /**
     * Make the section collapsible.
     *
     * @param bool $collapsed Whether the section should start collapsed
     */
    public function collapsible(bool $collapsed = false): self
    {
        $this->collapsible = true;
        $this->collapsed = $collapsed;
        return $this;
    }

    /**
     * Start the section in collapsed state.
     */
    public function collapsed(): self
    {
        $this->collapsible = true;
        $this->collapsed = true;
        return $this;
    }

    /**
     * Start the section in expanded state (default).
     */
    public function expanded(): self
    {
        $this->collapsed = false;
        return $this;
    }

    // =========================================================================
    // Getters
    // =========================================================================

    /**
     * Get the section identifier name.
     */
    public function get_name(): string
    {
        return $this->name;
    }

    /**
     * Get the section title (auto-generated from name if not set).
     */
    public function get_title(): string
    {
        if ($this->title !== null) {
            return $this->title;
        }

        // Convert snake_case to Title Case
        return ucwords(str_replace('_', ' ', $this->name));
    }

    /**
     * Get the section description.
     */
    public function get_description(): ?string
    {
        return $this->description;
    }

    /**
     * Get the section icon.
     */
    public function get_icon(): ?string
    {
        return $this->icon;
    }

    /**
     * Get the display order.
     */
    public function get_order(): int
    {
        return $this->order;
    }

    /**
     * Get the number of grid columns.
     */
    public function get_columns(): int
    {
        return $this->columns;
    }

    /**
     * Get the CSS class.
     */
    public function get_class(): ?string
    {
        return $this->class;
    }

    /**
     * Check if the section is collapsible.
     */
    public function is_collapsible(): bool
    {
        return $this->collapsible;
    }

    /**
     * Check if the section starts collapsed.
     */
    public function is_collapsed(): bool
    {
        return $this->collapsed;
    }

    // =========================================================================
    // Export
    // =========================================================================

    /**
     * Export all section metadata as an array.
     */
    public function to_array(): array
    {
        return [
            'name' => $this->name,
            'title' => $this->get_title(),
            'description' => $this->description,
            'icon' => $this->icon,
            'order' => $this->order,
            'columns' => $this->columns,
            'class' => $this->class,
            'collapsible' => $this->collapsible,
            'collapsed' => $this->collapsed,
        ];
    }
}
