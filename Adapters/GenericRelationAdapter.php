<?php
/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at https://mozilla.org/MPL/2.0/.
 */

declare(strict_types=1);

namespace Italix\Forms\Adapters;

use Italix\Contracts\RelationMeta;

/**
 * Generic adapter for foreign key relations.
 *
 * Wraps relation metadata from array definitions and supports an optional
 * fetcher callable to load options from the database.
 *
 * Example:
 *
 *     $relation = new GenericRelationAdapter(
 *         'countries',
 *         'id',
 *         'name',
 *         function ($table, $key, $label, $limit) use ($pdo) {
 *             $stmt = $pdo->query("SELECT {$key}, {$label} FROM {$table} LIMIT {$limit}");
 *             $options = [];
 *             while ($row = $stmt->fetch()) {
 *                 $options[$row[$key]] = $row[$label];
 *             }
 *             return $options;
 *         }
 *     );
 */
class GenericRelationAdapter implements RelationMeta
{
    /** @var string */
    private $foreign_table;

    /** @var string */
    private $foreign_key;

    /** @var string */
    private $foreign_label;

    /** @var callable|null */
    private $fetcher;

    /**
     * @param string $foreign_table  The related table name
     * @param string $foreign_key    The key column in the foreign table
     * @param string $foreign_label  The label column in the foreign table
     * @param callable|null $fetcher Callable to fetch options: fn($table, $key, $label, $limit) => array
     */
    public function __construct(
        string $foreign_table,
        string $foreign_key = 'id',
        string $foreign_label = 'name',
        $fetcher = null
    ) {
        $this->foreign_table = $foreign_table;
        $this->foreign_key = $foreign_key;
        $this->foreign_label = $foreign_label;
        $this->fetcher = $fetcher;
    }

    public function get_foreign_table(): string
    {
        return $this->foreign_table;
    }

    public function get_foreign_key(): string
    {
        return $this->foreign_key;
    }

    public function get_foreign_label(): string
    {
        return $this->foreign_label;
    }

    public function fetch_options(int $max_options = 100): ?array
    {
        if ($this->fetcher === null) {
            return null;
        }

        $fetcher = $this->fetcher;
        $results = $fetcher($this->foreign_table, $this->foreign_key, $this->foreign_label, $max_options + 1);

        if (!is_array($results)) {
            return null;
        }

        if (count($results) > $max_options) {
            return null;
        }

        return $results;
    }

    /**
     * Set the fetcher callable.
     *
     * @param callable $fetcher fn($table, $key, $label, $limit) => array
     * @return self
     */
    public function set_fetcher($fetcher): self
    {
        $this->fetcher = $fetcher;
        return $this;
    }
}
