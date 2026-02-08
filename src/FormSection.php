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
 *         ->collapsible(true);
 */
class FormSection
{
    /** @var string */
    private $name;

    /** @var string|null */
    private $title = null;

    /** @var string|null */
    private $description = null;

    /** @var string|null */
    private $icon = null;

    /** @var int */
    private $order = 0;

    /** @var int */
    private $columns = 1;

    /** @var string|null */
    private $class = null;

    /** @var bool */
    private $collapsible = false;

    /** @var bool */
    private $collapsed = false;

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    // =========================================================================
    // Display Setters (Fluent)
    // =========================================================================

    /**
     * Set the section title.
     *
     * @param string $title
     * @return self
     */
    public function title(string $title): self
    {
        $this->title = $title;
        return $this;
    }

    /**
     * Set the section description.
     *
     * @param string $description
     * @return self
     */
    public function description(string $description): self
    {
        $this->description = $description;
        return $this;
    }

    /**
     * Set the section icon (icon name or CSS class).
     *
     * @param string $icon
     * @return self
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
     * Set the number of columns in the section grid.
     *
     * @param int $columns
     * @return self
     */
    public function columns(int $columns): self
    {
        $this->columns = $columns;
        return $this;
    }

    /**
     * Set the section wrapper CSS class.
     *
     * @param string $class
     * @return self
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
     * @return self
     */
    public function collapsible(bool $collapsed = false): self
    {
        $this->collapsible = true;
        $this->collapsed = $collapsed;
        return $this;
    }

    /**
     * Start the section in collapsed state.
     *
     * @return self
     */
    public function collapsed(): self
    {
        $this->collapsible = true;
        $this->collapsed = true;
        return $this;
    }

    /**
     * Start the section in expanded state (default).
     *
     * @return self
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
     *
     * @return string
     */
    public function get_name(): string
    {
        return $this->name;
    }

    /**
     * Get the section title (auto-generated from name if not set).
     *
     * @return string
     */
    public function get_title(): string
    {
        if ($this->title !== null) {
            return $this->title;
        }

        return ucwords(str_replace('_', ' ', $this->name));
    }

    /**
     * Get the section description.
     *
     * @return string|null
     */
    public function get_description(): ?string
    {
        return $this->description;
    }

    /**
     * Get the section icon.
     *
     * @return string|null
     */
    public function get_icon(): ?string
    {
        return $this->icon;
    }

    /**
     * Get the display order.
     *
     * @return int
     */
    public function get_order(): int
    {
        return $this->order;
    }

    /**
     * Get the number of grid columns.
     *
     * @return int
     */
    public function get_columns(): int
    {
        return $this->columns;
    }

    /**
     * Get the CSS class.
     *
     * @return string|null
     */
    public function get_class(): ?string
    {
        return $this->class;
    }

    /**
     * Check if the section is collapsible.
     *
     * @return bool
     */
    public function is_collapsible(): bool
    {
        return $this->collapsible;
    }

    /**
     * Check if the section starts collapsed.
     *
     * @return bool
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
     *
     * @return array
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
