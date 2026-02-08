<?php

declare(strict_types=1);

namespace Italix\Forms\Contracts;

/**
 * Interface for foreign key / relation metadata.
 *
 * When a column is a foreign key, this interface describes the relationship
 * to the foreign table. The rendering layer uses this to automatically
 * populate select options or configure autocomplete fields.
 */
interface RelationMeta
{
    /**
     * Get the foreign table name (e.g., "countries").
     *
     * @return string
     */
    public function get_foreign_table(): string;

    /**
     * Get the foreign key column (e.g., "id").
     *
     * @return string
     */
    public function get_foreign_key(): string;

    /**
     * Get the column used as display label (e.g., "name").
     *
     * @return string
     */
    public function get_foreign_label(): string;

    /**
     * Fetch option pairs from the related table.
     *
     * Returns an associative array of [key => label] pairs for use in
     * select widgets, or null if there are too many rows (suggesting
     * an autocomplete widget should be used instead).
     *
     * @param int $max_options Return null if more rows than this exist
     * @return array|null Array of [key => label] or null if too many rows
     */
    public function fetch_options(int $max_options = 100): ?array;
}
