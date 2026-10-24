<?php
/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at https://mozilla.org/MPL/2.0/.
 */
/**
 * Italix Forms — generic column adapter
 *
 * Written as an xUnit class; runs on the shared runner (`Italix\Testing\Runner`) through XunitCase.
 *
 * Run: php src/Libs/Italix/Forms/tests/GenericAdapterTest.php
 */

declare(strict_types=1);

namespace Italix\Forms\Tests;

use Italix\Forms\Adapters\GenericColumnAdapter;
use Italix\Forms\Adapters\GenericTableAdapter;
use Italix\Contracts\ColumnMeta;
use Italix\Contracts\TableMeta;


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

class GenericAdapterTest extends XunitCase
{
    // =========================================================================
    // GenericColumnAdapter - Constructor
    // =========================================================================

    public function test_column_constructor_all_params(): void
    {
        $col = new GenericColumnAdapter('email', 'VARCHAR', false, false, 255, 'default@example.com', true);

        $this->assert_same('email', $col->get_name());
        $this->assert_same('VARCHAR', $col->get_type());
        $this->assert_false($col->is_nullable());
        $this->assert_false($col->is_primary_key());
        $this->assert_same(255, $col->get_length());
        $this->assert_same('default@example.com', $col->get_default());
        $this->assert_true($col->has_default());
    }

    public function test_column_constructor_defaults(): void
    {
        $col = new GenericColumnAdapter('name');

        $this->assert_same('name', $col->get_name());
        $this->assert_same('VARCHAR', $col->get_type());
        $this->assert_true($col->is_nullable());
        $this->assert_false($col->is_primary_key());
        $this->assert_null($col->get_length());
        $this->assert_null($col->get_default());
        $this->assert_false($col->has_default());
    }

    public function test_column_implements_column_meta(): void
    {
        $col = new GenericColumnAdapter('id');

        $this->assert_instance_of(ColumnMeta::class, $col);
    }

    public function test_column_primary_key(): void
    {
        $col = new GenericColumnAdapter('id', 'INTEGER', false, true);

        $this->assert_true($col->is_primary_key());
    }

    public function test_column_nullable(): void
    {
        $col = new GenericColumnAdapter('bio', 'TEXT', true);

        $this->assert_true($col->is_nullable());
    }

    public function test_column_not_nullable(): void
    {
        $col = new GenericColumnAdapter('name', 'VARCHAR', false);

        $this->assert_false($col->is_nullable());
    }

    // =========================================================================
    // GenericColumnAdapter - from_array()
    // =========================================================================

    public function test_from_array_full_definition(): void
    {
        $col = GenericColumnAdapter::from_array('email', [
            'type' => 'VARCHAR',
            'nullable' => false,
            'primary_key' => false,
            'length' => 255,
            'default' => 'test@example.com',
        ]);

        $this->assert_same('email', $col->get_name());
        $this->assert_same('VARCHAR', $col->get_type());
        $this->assert_false($col->is_nullable());
        $this->assert_false($col->is_primary_key());
        $this->assert_same(255, $col->get_length());
        $this->assert_same('test@example.com', $col->get_default());
        $this->assert_true($col->has_default());
    }

    public function test_from_array_empty_definition(): void
    {
        $col = GenericColumnAdapter::from_array('field', []);

        $this->assert_same('field', $col->get_name());
        $this->assert_same('VARCHAR', $col->get_type());
        $this->assert_true($col->is_nullable());
        $this->assert_false($col->is_primary_key());
        $this->assert_null($col->get_length());
        $this->assert_null($col->get_default());
        $this->assert_false($col->has_default());
    }

    public function test_from_array_partial_definition(): void
    {
        $col = GenericColumnAdapter::from_array('age', [
            'type' => 'INTEGER',
            'nullable' => true,
        ]);

        $this->assert_same('age', $col->get_name());
        $this->assert_same('INTEGER', $col->get_type());
        $this->assert_true($col->is_nullable());
        $this->assert_false($col->is_primary_key());
        $this->assert_null($col->get_length());
    }

    public function test_from_array_primary_key(): void
    {
        $col = GenericColumnAdapter::from_array('id', [
            'type' => 'INTEGER',
            'primary_key' => true,
            'nullable' => false,
        ]);

        $this->assert_true($col->is_primary_key());
        $this->assert_false($col->is_nullable());
    }

    public function test_from_array_with_type_only(): void
    {
        $col = GenericColumnAdapter::from_array('bio', [
            'type' => 'TEXT',
        ]);

        $this->assert_same('TEXT', $col->get_type());
        $this->assert_true($col->is_nullable());
    }

    public function test_from_array_boolean_type(): void
    {
        $col = GenericColumnAdapter::from_array('active', [
            'type' => 'BOOLEAN',
            'nullable' => false,
            'default' => false,
        ]);

        $this->assert_same('BOOLEAN', $col->get_type());
        $this->assert_false($col->get_default());
        $this->assert_true($col->has_default());
    }

    // =========================================================================
    // GenericColumnAdapter - has_default() distinguishes set vs unset
    // =========================================================================

    public function test_has_default_false_when_no_default_in_definition(): void
    {
        $col = GenericColumnAdapter::from_array('name', [
            'type' => 'VARCHAR',
        ]);

        $this->assert_false($col->has_default());
        $this->assert_null($col->get_default());
    }

    public function test_has_default_true_when_default_is_null(): void
    {
        $col = GenericColumnAdapter::from_array('name', [
            'type' => 'VARCHAR',
            'default' => null,
        ]);

        $this->assert_true($col->has_default());
        $this->assert_null($col->get_default());
    }

    public function test_has_default_true_when_default_is_empty_string(): void
    {
        $col = GenericColumnAdapter::from_array('name', [
            'type' => 'VARCHAR',
            'default' => '',
        ]);

        $this->assert_true($col->has_default());
        $this->assert_same('', $col->get_default());
    }

    public function test_has_default_true_when_default_is_zero(): void
    {
        $col = GenericColumnAdapter::from_array('count', [
            'type' => 'INTEGER',
            'default' => 0,
        ]);

        $this->assert_true($col->has_default());
        $this->assert_same(0, $col->get_default());
    }

    public function test_has_default_true_when_default_is_false(): void
    {
        $col = GenericColumnAdapter::from_array('active', [
            'type' => 'BOOLEAN',
            'default' => false,
        ]);

        $this->assert_true($col->has_default());
        $this->assert_false($col->get_default());
    }

    public function test_has_default_false_via_constructor(): void
    {
        $col = new GenericColumnAdapter('name', 'VARCHAR', true, false, null, null, false);

        $this->assert_false($col->has_default());
    }

    public function test_has_default_true_via_constructor(): void
    {
        $col = new GenericColumnAdapter('name', 'VARCHAR', true, false, null, 'default_val', true);

        $this->assert_true($col->has_default());
        $this->assert_same('default_val', $col->get_default());
    }

    // =========================================================================
    // GenericTableAdapter - Construction
    // =========================================================================

    public function test_table_adapter_implements_table_meta(): void
    {
        $table = new GenericTableAdapter([]);

        $this->assert_instance_of(TableMeta::class, $table);
    }

    public function test_table_adapter_wraps_arrays_correctly(): void
    {
        $table = new GenericTableAdapter([
            'name' => ['type' => 'VARCHAR', 'length' => 100, 'nullable' => false],
            'email' => ['type' => 'VARCHAR', 'length' => 255, 'nullable' => false],
            'age' => ['type' => 'INTEGER', 'nullable' => true],
        ]);

        $columns = $table->describe_columns();

        $this->assert_count(3, $columns);
        $this->assert_has_key('name', $columns);
        $this->assert_has_key('email', $columns);
        $this->assert_has_key('age', $columns);

        $this->assert_instance_of(ColumnMeta::class, $columns['name']);
        $this->assert_same('VARCHAR', $columns['name']->get_type());
        $this->assert_same(100, $columns['name']->get_length());
        $this->assert_false($columns['name']->is_nullable());
    }

    public function test_table_adapter_accepts_column_meta_instances(): void
    {
        $col1 = new GenericColumnAdapter('id', 'INTEGER', false, true);
        $col2 = new GenericColumnAdapter('name', 'VARCHAR', false, false, 100);

        $table = new GenericTableAdapter([
            'id' => $col1,
            'name' => $col2,
        ]);

        $columns = $table->describe_columns();

        $this->assert_same($col1, $columns['id']);
        $this->assert_same($col2, $columns['name']);
    }

    public function test_table_adapter_mixed_arrays_and_column_meta(): void
    {
        $col = new GenericColumnAdapter('id', 'INTEGER', false, true);

        $table = new GenericTableAdapter([
            'id' => $col,
            'name' => ['type' => 'VARCHAR', 'length' => 100],
        ]);

        $columns = $table->describe_columns();

        $this->assert_same($col, $columns['id']);
        $this->assert_instance_of(ColumnMeta::class, $columns['name']);
        $this->assert_same('VARCHAR', $columns['name']->get_type());
    }

    // =========================================================================
    // GenericTableAdapter - describe_columns() / describe_column()
    // =========================================================================

    public function test_describe_columns_returns_all(): void
    {
        $table = new GenericTableAdapter([
            'a' => ['type' => 'VARCHAR'],
            'b' => ['type' => 'INTEGER'],
            'c' => ['type' => 'TEXT'],
        ]);

        $cols = $table->describe_columns();

        $this->assert_count(3, $cols);
        $this->assert_same('VARCHAR', $cols['a']->get_type());
        $this->assert_same('INTEGER', $cols['b']->get_type());
        $this->assert_same('TEXT', $cols['c']->get_type());
    }

    public function test_describe_column_existing(): void
    {
        $table = new GenericTableAdapter([
            'email' => ['type' => 'VARCHAR', 'length' => 255],
        ]);

        $col = $table->describe_column('email');

        $this->assert_instance_of(ColumnMeta::class, $col);
        $this->assert_same('email', $col->get_name());
        $this->assert_same(255, $col->get_length());
    }

    public function test_describe_column_nonexistent_returns_null(): void
    {
        $table = new GenericTableAdapter([
            'email' => ['type' => 'VARCHAR'],
        ]);

        $result = $table->describe_column('nonexistent');

        $this->assert_null($result);
    }

    public function test_describe_columns_empty_table(): void
    {
        $table = new GenericTableAdapter([]);

        $cols = $table->describe_columns();

        $this->assert_count(0, $cols);
    }

    public function test_describe_columns_preserves_column_names(): void
    {
        $table = new GenericTableAdapter([
            'first_name' => ['type' => 'VARCHAR'],
            'last_name' => ['type' => 'VARCHAR'],
        ]);

        $cols = $table->describe_columns();
        $names = array_keys($cols);

        $this->assert_same(['first_name', 'last_name'], $names);

        $this->assert_same('first_name', $cols['first_name']->get_name());
        $this->assert_same('last_name', $cols['last_name']->get_name());
    }
}

exit(GenericAdapterTest::run_all('Italix Forms — generic column adapter'));
