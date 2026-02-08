<?php

declare(strict_types=1);

namespace Italix\Forms\Contracts;

/**
 * Interface for column/field metadata.
 *
 * Any class that provides column information (from an ORM, database schema,
 * or custom definition) can implement this interface to be compatible with
 * the Italix Forms library.
 */
interface ColumnMeta
{
    /**
     * Get the column/field name.
     */
    public function get_name(): string;

    /**
     * Get the column data type (e.g., VARCHAR, INTEGER, TEXT, BOOLEAN).
     */
    public function get_type(): string;

    /**
     * Check if the column allows NULL values.
     */
    public function is_nullable(): bool;

    /**
     * Check if the column is a primary key.
     */
    public function is_primary_key(): bool;

    /**
     * Get the column length (for VARCHAR, CHAR, etc.).
     * Returns null if not applicable.
     */
    public function get_length(): ?int;

    /**
     * Get the default value for the column.
     */
    public function get_default(): mixed;

    /**
     * Check if the column has an explicit default value.
     */
    public function has_default(): bool;
}
