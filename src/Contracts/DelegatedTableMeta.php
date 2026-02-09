<?php

declare(strict_types=1);

namespace Italix\Forms\Contracts;

/**
 * Extended table metadata for tables that use delegated types.
 *
 * Implement this interface when a table has a type discriminator column
 * and delegate sub-tables (e.g., a 'things' table with 'books', 'movies'
 * sub-types).
 *
 * FormMeta auto-detects this interface and enables the delegate() method
 * for merging columns from the full delegation chain.
 *
 * Each delegate table may itself implement DelegatedTableMeta for
 * N-level chains (e.g., Thing -> Book -> ComicsBook).
 *
 * Example:
 *
 *     $form = new FormMeta($things_table); // DelegatedTableMeta
 *     $form->delegate('ComicsBook');
 *     // Resolves chain: Thing -> Book -> ComicsBook
 *     // Merges all columns, hides glue columns
 */
interface DelegatedTableMeta extends TableMeta
{
    /**
     * Get the type discriminator column name (e.g., 'type').
     *
     * @return string|null Null if delegation is not active on this table
     */
    public function get_type_column(): ?string;

    /**
     * Get the type path column name (e.g., 'type_path').
     *
     * The type path stores the full hierarchy (e.g., 'Thing/Book/ComicsBook').
     * Return null if this table doesn't use a type path column.
     *
     * @return string|null
     */
    public function get_type_path_column(): ?string;

    /**
     * Get the foreign key column name used in delegate tables
     * to reference this table (e.g., 'thing_id').
     *
     * @return string
     */
    public function get_delegate_foreign_key(): string;

    /**
     * Get the direct delegate sub-tables.
     *
     * Returns a map of type name => TableMeta. Each delegate may itself
     * implement DelegatedTableMeta for deeper chains.
     *
     * @return array<string, TableMeta> e.g., ['Book' => $books_table, 'Movie' => $movies_table]
     */
    public function get_delegate_tables(): array;
}
