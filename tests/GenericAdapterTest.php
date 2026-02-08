<?php

declare(strict_types=1);

namespace Italix\Forms\Tests;

use PHPUnit\Framework\TestCase;
use Italix\Forms\Adapters\GenericColumnAdapter;
use Italix\Forms\Adapters\GenericTableAdapter;
use Italix\Forms\Contracts\ColumnMeta;
use Italix\Forms\Contracts\TableMeta;

class GenericAdapterTest extends TestCase
{
    // =========================================================================
    // GenericColumnAdapter - Constructor
    // =========================================================================

    public function test_column_constructor_all_params(): void
    {
        $col = new GenericColumnAdapter('email', 'VARCHAR', false, false, 255, 'default@example.com', true);

        $this->assertSame('email', $col->get_name());
        $this->assertSame('VARCHAR', $col->get_type());
        $this->assertFalse($col->is_nullable());
        $this->assertFalse($col->is_primary_key());
        $this->assertSame(255, $col->get_length());
        $this->assertSame('default@example.com', $col->get_default());
        $this->assertTrue($col->has_default());
    }

    public function test_column_constructor_defaults(): void
    {
        $col = new GenericColumnAdapter('name');

        $this->assertSame('name', $col->get_name());
        $this->assertSame('VARCHAR', $col->get_type());
        $this->assertTrue($col->is_nullable());
        $this->assertFalse($col->is_primary_key());
        $this->assertNull($col->get_length());
        $this->assertNull($col->get_default());
        $this->assertFalse($col->has_default());
    }

    public function test_column_implements_column_meta(): void
    {
        $col = new GenericColumnAdapter('id');

        $this->assertInstanceOf(ColumnMeta::class, $col);
    }

    public function test_column_primary_key(): void
    {
        $col = new GenericColumnAdapter('id', 'INTEGER', false, true);

        $this->assertTrue($col->is_primary_key());
    }

    public function test_column_nullable(): void
    {
        $col = new GenericColumnAdapter('bio', 'TEXT', true);

        $this->assertTrue($col->is_nullable());
    }

    public function test_column_not_nullable(): void
    {
        $col = new GenericColumnAdapter('name', 'VARCHAR', false);

        $this->assertFalse($col->is_nullable());
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

        $this->assertSame('email', $col->get_name());
        $this->assertSame('VARCHAR', $col->get_type());
        $this->assertFalse($col->is_nullable());
        $this->assertFalse($col->is_primary_key());
        $this->assertSame(255, $col->get_length());
        $this->assertSame('test@example.com', $col->get_default());
        $this->assertTrue($col->has_default());
    }

    public function test_from_array_empty_definition(): void
    {
        $col = GenericColumnAdapter::from_array('field', []);

        $this->assertSame('field', $col->get_name());
        $this->assertSame('VARCHAR', $col->get_type());
        $this->assertTrue($col->is_nullable());
        $this->assertFalse($col->is_primary_key());
        $this->assertNull($col->get_length());
        $this->assertNull($col->get_default());
        $this->assertFalse($col->has_default());
    }

    public function test_from_array_partial_definition(): void
    {
        $col = GenericColumnAdapter::from_array('age', [
            'type' => 'INTEGER',
            'nullable' => true,
        ]);

        $this->assertSame('age', $col->get_name());
        $this->assertSame('INTEGER', $col->get_type());
        $this->assertTrue($col->is_nullable());
        $this->assertFalse($col->is_primary_key());
        $this->assertNull($col->get_length());
    }

    public function test_from_array_primary_key(): void
    {
        $col = GenericColumnAdapter::from_array('id', [
            'type' => 'INTEGER',
            'primary_key' => true,
            'nullable' => false,
        ]);

        $this->assertTrue($col->is_primary_key());
        $this->assertFalse($col->is_nullable());
    }

    public function test_from_array_with_type_only(): void
    {
        $col = GenericColumnAdapter::from_array('bio', [
            'type' => 'TEXT',
        ]);

        $this->assertSame('TEXT', $col->get_type());
        $this->assertTrue($col->is_nullable());
    }

    public function test_from_array_boolean_type(): void
    {
        $col = GenericColumnAdapter::from_array('active', [
            'type' => 'BOOLEAN',
            'nullable' => false,
            'default' => false,
        ]);

        $this->assertSame('BOOLEAN', $col->get_type());
        $this->assertFalse($col->get_default());
        $this->assertTrue($col->has_default());
    }

    // =========================================================================
    // GenericColumnAdapter - has_default() distinguishes set vs unset
    // =========================================================================

    public function test_has_default_false_when_no_default_in_definition(): void
    {
        $col = GenericColumnAdapter::from_array('name', [
            'type' => 'VARCHAR',
        ]);

        $this->assertFalse($col->has_default());
        $this->assertNull($col->get_default());
    }

    public function test_has_default_true_when_default_is_null(): void
    {
        $col = GenericColumnAdapter::from_array('name', [
            'type' => 'VARCHAR',
            'default' => null,
        ]);

        $this->assertTrue($col->has_default());
        $this->assertNull($col->get_default());
    }

    public function test_has_default_true_when_default_is_empty_string(): void
    {
        $col = GenericColumnAdapter::from_array('name', [
            'type' => 'VARCHAR',
            'default' => '',
        ]);

        $this->assertTrue($col->has_default());
        $this->assertSame('', $col->get_default());
    }

    public function test_has_default_true_when_default_is_zero(): void
    {
        $col = GenericColumnAdapter::from_array('count', [
            'type' => 'INTEGER',
            'default' => 0,
        ]);

        $this->assertTrue($col->has_default());
        $this->assertSame(0, $col->get_default());
    }

    public function test_has_default_true_when_default_is_false(): void
    {
        $col = GenericColumnAdapter::from_array('active', [
            'type' => 'BOOLEAN',
            'default' => false,
        ]);

        $this->assertTrue($col->has_default());
        $this->assertFalse($col->get_default());
    }

    public function test_has_default_false_via_constructor(): void
    {
        $col = new GenericColumnAdapter('name', 'VARCHAR', true, false, null, null, false);

        $this->assertFalse($col->has_default());
    }

    public function test_has_default_true_via_constructor(): void
    {
        $col = new GenericColumnAdapter('name', 'VARCHAR', true, false, null, 'default_val', true);

        $this->assertTrue($col->has_default());
        $this->assertSame('default_val', $col->get_default());
    }

    // =========================================================================
    // GenericTableAdapter - Construction
    // =========================================================================

    public function test_table_adapter_implements_table_meta(): void
    {
        $table = new GenericTableAdapter([]);

        $this->assertInstanceOf(TableMeta::class, $table);
    }

    public function test_table_adapter_wraps_arrays_correctly(): void
    {
        $table = new GenericTableAdapter([
            'name' => ['type' => 'VARCHAR', 'length' => 100, 'nullable' => false],
            'email' => ['type' => 'VARCHAR', 'length' => 255, 'nullable' => false],
            'age' => ['type' => 'INTEGER', 'nullable' => true],
        ]);

        $columns = $table->describe_columns();

        $this->assertCount(3, $columns);
        $this->assertArrayHasKey('name', $columns);
        $this->assertArrayHasKey('email', $columns);
        $this->assertArrayHasKey('age', $columns);

        $this->assertInstanceOf(ColumnMeta::class, $columns['name']);
        $this->assertSame('VARCHAR', $columns['name']->get_type());
        $this->assertSame(100, $columns['name']->get_length());
        $this->assertFalse($columns['name']->is_nullable());
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

        $this->assertSame($col1, $columns['id']);
        $this->assertSame($col2, $columns['name']);
    }

    public function test_table_adapter_mixed_arrays_and_column_meta(): void
    {
        $col = new GenericColumnAdapter('id', 'INTEGER', false, true);

        $table = new GenericTableAdapter([
            'id' => $col,
            'name' => ['type' => 'VARCHAR', 'length' => 100],
        ]);

        $columns = $table->describe_columns();

        $this->assertSame($col, $columns['id']);
        $this->assertInstanceOf(ColumnMeta::class, $columns['name']);
        $this->assertSame('VARCHAR', $columns['name']->get_type());
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

        $this->assertCount(3, $cols);
        $this->assertSame('VARCHAR', $cols['a']->get_type());
        $this->assertSame('INTEGER', $cols['b']->get_type());
        $this->assertSame('TEXT', $cols['c']->get_type());
    }

    public function test_describe_column_existing(): void
    {
        $table = new GenericTableAdapter([
            'email' => ['type' => 'VARCHAR', 'length' => 255],
        ]);

        $col = $table->describe_column('email');

        $this->assertInstanceOf(ColumnMeta::class, $col);
        $this->assertSame('email', $col->get_name());
        $this->assertSame(255, $col->get_length());
    }

    public function test_describe_column_nonexistent_returns_null(): void
    {
        $table = new GenericTableAdapter([
            'email' => ['type' => 'VARCHAR'],
        ]);

        $result = $table->describe_column('nonexistent');

        $this->assertNull($result);
    }

    public function test_describe_columns_empty_table(): void
    {
        $table = new GenericTableAdapter([]);

        $cols = $table->describe_columns();

        $this->assertCount(0, $cols);
    }

    public function test_describe_columns_preserves_column_names(): void
    {
        $table = new GenericTableAdapter([
            'first_name' => ['type' => 'VARCHAR'],
            'last_name' => ['type' => 'VARCHAR'],
        ]);

        $cols = $table->describe_columns();
        $names = array_keys($cols);

        $this->assertSame(['first_name', 'last_name'], $names);

        $this->assertSame('first_name', $cols['first_name']->get_name());
        $this->assertSame('last_name', $cols['last_name']->get_name());
    }
}
