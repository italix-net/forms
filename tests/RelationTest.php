<?php
/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at https://mozilla.org/MPL/2.0/.
 */
/**
 * Italix Forms — relations
 *
 * Written as an xUnit class; runs on the shared runner (`Italix\Testing\Runner`) through XunitCase.
 *
 * Run: php src/Libs/Italix/Forms/tests/RelationTest.php
 */

declare(strict_types=1);

namespace Italix\Forms\Tests;

use Italix\Forms\Adapters\GenericColumnAdapter;
use Italix\Forms\Adapters\GenericRelationAdapter;
use Italix\Forms\Adapters\GenericTableAdapter;
use Italix\Contracts\RelationMeta;
use Italix\Forms\FieldMeta;
use Italix\Forms\FormMeta;


(static function (): void {
    foreach ([
        __DIR__ . '/../../../../../vendor/autoload.php',
        __DIR__ . '/../../../../vendor/autoload.php',
        __DIR__ . '/../../../autoload.php',
        __DIR__ . '/../vendor/autoload.php',
    ] as $autoload) {
        if (is_file($autoload)) {
            require_once $autoload;

            return;
        }
    }

    fwrite(STDERR, "Could not find an autoloader. Run composer install.
");
    exit(2);
})();

require_once __DIR__ . '/XunitCase.php';

class RelationTest extends XunitCase
{
    // =========================================================================
    // GenericRelationAdapter - Construction
    // =========================================================================

    public function test_relation_adapter_implements_relation_meta(): void
    {
        $relation = new GenericRelationAdapter('users');

        $this->assert_instance_of(RelationMeta::class, $relation);
    }

    public function test_get_foreign_table(): void
    {
        $relation = new GenericRelationAdapter('countries');

        $this->assert_same('countries', $relation->get_foreign_table());
    }

    public function test_get_foreign_key_default(): void
    {
        $relation = new GenericRelationAdapter('countries');

        $this->assert_same('id', $relation->get_foreign_key());
    }

    public function test_get_foreign_key_custom(): void
    {
        $relation = new GenericRelationAdapter('countries', 'country_code');

        $this->assert_same('country_code', $relation->get_foreign_key());
    }

    public function test_get_foreign_label_default(): void
    {
        $relation = new GenericRelationAdapter('countries');

        $this->assert_same('name', $relation->get_foreign_label());
    }

    public function test_get_foreign_label_custom(): void
    {
        $relation = new GenericRelationAdapter('countries', 'id', 'title');

        $this->assert_same('title', $relation->get_foreign_label());
    }

    // =========================================================================
    // GenericRelationAdapter - fetch_options with fetcher
    // =========================================================================

    public function test_fetch_options_with_fetcher(): void
    {
        $relation = new GenericRelationAdapter('countries', 'id', 'name',
            function ($table, $key, $label, $limit) {
                $this->assert_same('countries', $table);
                $this->assert_same('id', $key);
                $this->assert_same('name', $label);
                return [1 => 'USA', 2 => 'Canada', 3 => 'Mexico'];
            }
        );

        $options = $relation->fetch_options();

        $this->assert_is_array($options);
        $this->assert_same([1 => 'USA', 2 => 'Canada', 3 => 'Mexico'], $options);
    }

    public function test_fetch_options_passes_limit_plus_one(): void
    {
        $received_limit = null;
        $relation = new GenericRelationAdapter('countries', 'id', 'name',
            function ($table, $key, $label, $limit) use (&$received_limit) {
                $received_limit = $limit;
                return [1 => 'A'];
            }
        );

        $relation->fetch_options(50);

        $this->assert_same(51, $received_limit);
    }

    public function test_fetch_options_default_max_options_is_100(): void
    {
        $received_limit = null;
        $relation = new GenericRelationAdapter('countries', 'id', 'name',
            function ($table, $key, $label, $limit) use (&$received_limit) {
                $received_limit = $limit;
                return [];
            }
        );

        $relation->fetch_options();

        $this->assert_same(101, $received_limit);
    }

    // =========================================================================
    // GenericRelationAdapter - fetch_options returns null
    // =========================================================================

    public function test_fetch_options_returns_null_when_no_fetcher(): void
    {
        $relation = new GenericRelationAdapter('countries');

        $this->assert_null($relation->fetch_options());
    }

    public function test_fetch_options_returns_null_when_too_many_rows(): void
    {
        $relation = new GenericRelationAdapter('countries', 'id', 'name',
            function ($table, $key, $label, $limit) {
                // Return exactly $limit items (max_options + 1) which means "too many"
                $options = [];
                for ($i = 1; $i <= $limit; $i++) {
                    $options[$i] = "Country {$i}";
                }
                return $options;
            }
        );

        // max_options = 5, so fetcher receives 6, returns 6 items => null
        $this->assert_null($relation->fetch_options(5));
    }

    public function test_fetch_options_returns_results_at_exact_limit(): void
    {
        $relation = new GenericRelationAdapter('countries', 'id', 'name',
            function ($table, $key, $label, $limit) {
                // Return exactly max_options items (less than $limit which is max+1)
                $options = [];
                for ($i = 1; $i <= $limit - 1; $i++) {
                    $options[$i] = "Country {$i}";
                }
                return $options;
            }
        );

        // max_options = 5, fetcher receives 6, returns 5 items => should return them
        $result = $relation->fetch_options(5);
        $this->assert_is_array($result);
        $this->assert_count(5, $result);
    }

    public function test_fetch_options_returns_null_when_fetcher_returns_non_array(): void
    {
        $relation = new GenericRelationAdapter('countries', 'id', 'name',
            function ($table, $key, $label, $limit) {
                return null;
            }
        );

        $this->assert_null($relation->fetch_options());
    }

    public function test_fetch_options_returns_empty_array_when_fetcher_returns_empty(): void
    {
        $relation = new GenericRelationAdapter('countries', 'id', 'name',
            function ($table, $key, $label, $limit) {
                return [];
            }
        );

        $result = $relation->fetch_options();
        $this->assert_is_array($result);
        $this->assert_empty($result);
    }

    // =========================================================================
    // GenericRelationAdapter - set_fetcher
    // =========================================================================

    public function test_set_fetcher_enables_fetching(): void
    {
        $relation = new GenericRelationAdapter('countries');

        $this->assert_null($relation->fetch_options());

        $relation->set_fetcher(function ($table, $key, $label, $limit) {
            return [1 => 'USA'];
        });

        $this->assert_same([1 => 'USA'], $relation->fetch_options());
    }

    public function test_set_fetcher_returns_self(): void
    {
        $relation = new GenericRelationAdapter('countries');
        $result = $relation->set_fetcher(function () {
            return [];
        });

        $this->assert_same($relation, $result);
    }

    // =========================================================================
    // GenericColumnAdapter - with relation from array definition
    // =========================================================================

    public function test_column_from_array_with_relation(): void
    {
        $column = GenericColumnAdapter::from_array('country_id', [
            'type' => 'INTEGER',
            'relation' => [
                'table' => 'countries',
                'key' => 'id',
                'label' => 'name',
            ],
        ]);

        $relation = $column->get_relation();

        $this->assert_not_null($relation);
        $this->assert_instance_of(RelationMeta::class, $relation);
        $this->assert_same('countries', $relation->get_foreign_table());
        $this->assert_same('id', $relation->get_foreign_key());
        $this->assert_same('name', $relation->get_foreign_label());
    }

    public function test_column_from_array_relation_defaults(): void
    {
        $column = GenericColumnAdapter::from_array('category_id', [
            'type' => 'INTEGER',
            'relation' => [
                'table' => 'categories',
            ],
        ]);

        $relation = $column->get_relation();

        $this->assert_not_null($relation);
        $this->assert_same('categories', $relation->get_foreign_table());
        $this->assert_same('id', $relation->get_foreign_key());
        $this->assert_same('name', $relation->get_foreign_label());
    }

    public function test_column_from_array_relation_with_fetcher(): void
    {
        $fetcher = function ($table, $key, $label, $limit) {
            return [1 => 'Category A'];
        };

        $column = GenericColumnAdapter::from_array('category_id', [
            'type' => 'INTEGER',
            'relation' => [
                'table' => 'categories',
                'fetcher' => $fetcher,
            ],
        ]);

        $relation = $column->get_relation();
        $options = $relation->fetch_options();

        $this->assert_same([1 => 'Category A'], $options);
    }

    // =========================================================================
    // GenericColumnAdapter - without relation returns null
    // =========================================================================

    public function test_column_without_relation_returns_null(): void
    {
        $column = GenericColumnAdapter::from_array('name', [
            'type' => 'VARCHAR',
        ]);

        $this->assert_null($column->get_relation());
    }

    public function test_column_constructor_without_relation(): void
    {
        $column = new GenericColumnAdapter('name', 'VARCHAR');

        $this->assert_null($column->get_relation());
    }

    public function test_column_constructor_with_relation(): void
    {
        $relation = new GenericRelationAdapter('roles');
        $column = new GenericColumnAdapter('role_id', 'INTEGER', true, false, null, null, false, $relation);

        $this->assert_same($relation, $column->get_relation());
    }

    // =========================================================================
    // GenericTableAdapter with relations
    // =========================================================================

    public function test_table_adapter_columns_with_relations(): void
    {
        $table = new GenericTableAdapter([
            'name' => ['type' => 'VARCHAR', 'length' => 100],
            'country_id' => [
                'type' => 'INTEGER',
                'relation' => ['table' => 'countries', 'key' => 'id', 'label' => 'name'],
            ],
        ]);

        $country_col = $table->describe_column('country_id');
        $relation = $country_col->get_relation();

        $this->assert_not_null($relation);
        $this->assert_same('countries', $relation->get_foreign_table());
    }

    public function test_table_adapter_column_without_relation(): void
    {
        $table = new GenericTableAdapter([
            'name' => ['type' => 'VARCHAR'],
        ]);

        $this->assert_null($table->describe_column('name')->get_relation());
    }

    // =========================================================================
    // Integration: Relation + FormMeta + FieldMeta
    // =========================================================================

    public function test_field_meta_column_has_relation(): void
    {
        $table = new GenericTableAdapter([
            'country_id' => [
                'type' => 'INTEGER',
                'relation' => ['table' => 'countries'],
            ],
        ]);
        $form = new FormMeta($table);
        $field = $form->field('country_id');

        $relation = $field->column()->get_relation();

        $this->assert_not_null($relation);
        $this->assert_same('countries', $relation->get_foreign_table());
    }

    public function test_field_meta_without_relation(): void
    {
        $table = new GenericTableAdapter([
            'name' => ['type' => 'VARCHAR'],
        ]);
        $form = new FormMeta($table);
        $field = $form->field('name');

        $this->assert_null($field->column()->get_relation());
    }
}

exit(RelationTest::run_all('Italix Forms — relations'));
