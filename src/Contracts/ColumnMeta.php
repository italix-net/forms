<?php

declare(strict_types=1);

namespace Italix\Forms\Contracts;

/**
 * Interface for column/field metadata.
 *
 * Any class that provides column information (from an ORM, database schema,
 * or custom definition) can implement this interface to be compatible with
 * the Italix Forms library.
 *
 * This is the minimal contract. For FK-aware columns, implement
 * RelationalColumnMeta. For polymorphic FK columns, implement
 * PolymorphicColumnMeta.
 */
interface ColumnMeta
{
    /**
     * Get the column/field name.
     *
     * @return string
     */
    public function get_name(): string;

    /**
     * Get the column data type (e.g., VARCHAR, INTEGER, TEXT, BOOLEAN).
     *
     * @return string
     */
    public function get_type(): string;

    /**
     * Check if the column allows NULL values.
     *
     * @return bool
     */
    public function is_nullable(): bool;

    /**
     * Check if the column is a primary key.
     *
     * @return bool
     */
    public function is_primary_key(): bool;

    /**
     * Get the column length (for VARCHAR, CHAR, etc.).
     * Returns null if not applicable.
     *
     * @return int|null
     */
    public function get_length(): ?int;

    /**
     * Get the default value for the column.
     *
     * @return mixed
     */
    public function get_default();

    /**
     * Check if the column has an explicit default value.
     *
     * @return bool
     */
    public function has_default(): bool;
}
