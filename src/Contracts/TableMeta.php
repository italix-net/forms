<?php

declare(strict_types=1);

namespace Italix\Forms\Contracts;

/**
 * Interface for table/entity metadata.
 *
 * Any class that can describe its columns (ORM Table, Entity, Model, or custom
 * definition) can implement this interface to be compatible with FormMeta.
 *
 * This allows the Italix Forms library to work with any ORM or framework,
 * or even with plain arrays via the GenericTableAdapter.
 */
interface TableMeta
{
    /**
     * Return an iterable of column descriptors.
     *
     * @return iterable<string, ColumnMeta> Column name => ColumnMeta pairs
     */
    public function describe_columns(): iterable;

    /**
     * Get a specific column descriptor by name.
     *
     * @param string $name The column name
     * @return ColumnMeta|null The column descriptor, or null if not found
     */
    public function describe_column(string $name): ?ColumnMeta;
}
