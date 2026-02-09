<?php

declare(strict_types=1);

namespace Italix\Forms\Tests;

use PHPUnit\Framework\TestCase;
use Italix\Forms\Contracts\ColumnMeta;
use Italix\Forms\Contracts\DelegatedTableMeta;
use Italix\Forms\Contracts\PolymorphicColumnMeta;
use Italix\Forms\Contracts\RelationalColumnMeta;
use Italix\Forms\Contracts\RelationMeta;
use Italix\Forms\Contracts\TableMeta;
use Italix\Forms\FieldMeta;
use Italix\Forms\FormMeta;
use Italix\Forms\Rendering\FormHtml;
use InvalidArgumentException;
use LogicException;

// =============================================================================
// Test Stubs
// =============================================================================

/**
 * Minimal ColumnMeta implementation for testing.
 */
class StubColumn implements ColumnMeta
{
    /** @var string */
    private $name;

    /** @var string */
    private $type;

    /** @var bool */
    private $nullable;

    /** @var bool */
    private $is_pk;

    /**
     * @param string $name
     * @param string $type
     * @param bool   $nullable
     * @param bool   $is_pk
     */
    public function __construct(string $name, string $type = 'VARCHAR', bool $nullable = true, bool $is_pk = false)
    {
        $this->name = $name;
        $this->type = $type;
        $this->nullable = $nullable;
        $this->is_pk = $is_pk;
    }

    public function get_name(): string
    {
        return $this->name;
    }

    public function get_type(): string
    {
        return $this->type;
    }

    public function is_nullable(): bool
    {
        return $this->nullable;
    }

    public function is_primary_key(): bool
    {
        return $this->is_pk;
    }

    public function get_length(): ?int
    {
        return null;
    }

    public function get_default()
    {
        return null;
    }

    public function has_default(): bool
    {
        return false;
    }
}

/**
 * Minimal TableMeta implementation for testing.
 */
class StubTable implements TableMeta
{
    /** @var array<string, ColumnMeta> */
    private $columns = [];

    /**
     * @param ColumnMeta[] $columns
     */
    public function __construct(array $columns)
    {
        foreach ($columns as $col) {
            $this->columns[$col->get_name()] = $col;
        }
    }

    public function describe_columns(): iterable
    {
        return $this->columns;
    }

    public function describe_column(string $name): ?ColumnMeta
    {
        return $this->columns[$name] ?? null;
    }
}

/**
 * DelegatedTableMeta implementation for testing.
 */
class DelegatedStubTable implements DelegatedTableMeta
{
    /** @var array<string, ColumnMeta> */
    private $columns = [];

    /** @var string|null */
    private $type_column;

    /** @var string|null */
    private $type_path_column;

    /** @var string */
    private $delegate_fk;

    /** @var array<string, TableMeta> */
    private $delegate_tables;

    /**
     * @param ColumnMeta[]             $columns
     * @param string|null              $type_column
     * @param string|null              $type_path_column
     * @param string                   $delegate_fk
     * @param array<string, TableMeta> $delegate_tables
     */
    public function __construct(
        array $columns,
        ?string $type_column,
        ?string $type_path_column,
        string $delegate_fk,
        array $delegate_tables
    ) {
        foreach ($columns as $col) {
            $this->columns[$col->get_name()] = $col;
        }
        $this->type_column = $type_column;
        $this->type_path_column = $type_path_column;
        $this->delegate_fk = $delegate_fk;
        $this->delegate_tables = $delegate_tables;
    }

    public function describe_columns(): iterable
    {
        return $this->columns;
    }

    public function describe_column(string $name): ?ColumnMeta
    {
        return $this->columns[$name] ?? null;
    }

    public function get_type_column(): ?string
    {
        return $this->type_column;
    }

    public function get_type_path_column(): ?string
    {
        return $this->type_path_column;
    }

    public function get_delegate_foreign_key(): string
    {
        return $this->delegate_fk;
    }

    public function get_delegate_tables(): array
    {
        return $this->delegate_tables;
    }
}

/**
 * PolymorphicColumnMeta implementation for testing.
 */
class StubPolymorphicColumn implements PolymorphicColumnMeta
{
    /** @var ColumnMeta */
    private $inner;

    /** @var string */
    private $type_column;

    /** @var array<string, TableMeta> */
    private $targets;

    /**
     * @param ColumnMeta               $inner
     * @param string                   $type_column
     * @param array<string, TableMeta> $targets
     */
    public function __construct(ColumnMeta $inner, string $type_column, array $targets)
    {
        $this->inner = $inner;
        $this->type_column = $type_column;
        $this->targets = $targets;
    }

    public function get_name(): string
    {
        return $this->inner->get_name();
    }

    public function get_type(): string
    {
        return $this->inner->get_type();
    }

    public function is_nullable(): bool
    {
        return $this->inner->is_nullable();
    }

    public function is_primary_key(): bool
    {
        return $this->inner->is_primary_key();
    }

    public function get_length(): ?int
    {
        return $this->inner->get_length();
    }

    public function get_default()
    {
        return $this->inner->get_default();
    }

    public function has_default(): bool
    {
        return $this->inner->has_default();
    }

    public function get_polymorphic_type_column(): string
    {
        return $this->type_column;
    }

    public function get_polymorphic_targets(): array
    {
        return $this->targets;
    }
}

/**
 * RelationalColumnMeta stub for testing.
 */
class StubRelationalColumn implements RelationalColumnMeta
{
    /** @var ColumnMeta */
    private $inner;

    /** @var RelationMeta|null */
    private $relation;

    /**
     * @param ColumnMeta       $inner
     * @param RelationMeta|null $relation
     */
    public function __construct(ColumnMeta $inner, ?RelationMeta $relation = null)
    {
        $this->inner = $inner;
        $this->relation = $relation;
    }

    public function get_name(): string
    {
        return $this->inner->get_name();
    }

    public function get_type(): string
    {
        return $this->inner->get_type();
    }

    public function is_nullable(): bool
    {
        return $this->inner->is_nullable();
    }

    public function is_primary_key(): bool
    {
        return $this->inner->is_primary_key();
    }

    public function get_length(): ?int
    {
        return $this->inner->get_length();
    }

    public function get_default()
    {
        return $this->inner->get_default();
    }

    public function has_default(): bool
    {
        return $this->inner->has_default();
    }

    public function get_relation(): ?RelationMeta
    {
        return $this->relation;
    }
}

/**
 * Minimal RelationMeta stub for testing.
 */
class StubRelation implements RelationMeta
{
    /** @var string */
    private $table;

    /** @var string */
    private $key;

    /** @var string */
    private $label;

    /** @var array|null */
    private $options;

    /**
     * @param string     $table
     * @param string     $key
     * @param string     $label
     * @param array|null $options
     */
    public function __construct(string $table, string $key = 'id', string $label = 'name', ?array $options = null)
    {
        $this->table = $table;
        $this->key = $key;
        $this->label = $label;
        $this->options = $options;
    }

    public function get_foreign_table(): string
    {
        return $this->table;
    }

    public function get_foreign_key(): string
    {
        return $this->key;
    }

    public function get_foreign_label(): string
    {
        return $this->label;
    }

    public function fetch_options(int $max_options = 100): ?array
    {
        return $this->options;
    }
}


// =============================================================================
// Test Helpers
// =============================================================================

class DelegationTest extends TestCase
{
    // =========================================================================
    // Factory helpers
    // =========================================================================

    /**
     * Create the "things" root delegated table.
     *
     * Schema: id (PK), title (VARCHAR), type (VARCHAR), type_path (VARCHAR)
     * Delegates: Book, Movie
     * FK in delegates: thing_id
     *
     * @param array<string, TableMeta> $delegates
     * @return DelegatedStubTable
     */
    private function make_things_table(array $delegates = []): DelegatedStubTable
    {
        if (empty($delegates)) {
            $delegates = [
                'Book'  => $this->make_book_table(),
                'Movie' => $this->make_movie_table(),
            ];
        }

        return new DelegatedStubTable(
            [
                new StubColumn('id', 'INTEGER', false, true),
                new StubColumn('title', 'VARCHAR', false),
                new StubColumn('type', 'VARCHAR', true),
                new StubColumn('type_path', 'VARCHAR', true),
            ],
            'type',
            'type_path',
            'thing_id',
            $delegates
        );
    }

    /**
     * Create a simple Book delegate table (leaf).
     *
     * Schema: id (PK), thing_id (FK), isbn (VARCHAR), page_count (INTEGER)
     *
     * @return StubTable
     */
    private function make_book_table(): StubTable
    {
        return new StubTable([
            new StubColumn('id', 'INTEGER', false, true),
            new StubColumn('thing_id', 'INTEGER', false),
            new StubColumn('isbn', 'VARCHAR', true),
            new StubColumn('page_count', 'INTEGER', true),
        ]);
    }

    /**
     * Create a simple Movie delegate table (leaf).
     *
     * Schema: id (PK), thing_id (FK), duration (INTEGER), director (VARCHAR)
     *
     * @return StubTable
     */
    private function make_movie_table(): StubTable
    {
        return new StubTable([
            new StubColumn('id', 'INTEGER', false, true),
            new StubColumn('thing_id', 'INTEGER', false),
            new StubColumn('duration', 'INTEGER', true),
            new StubColumn('director', 'VARCHAR', true),
        ]);
    }

    /**
     * Create a Book table that is itself delegated (intermediate in chain).
     *
     * Schema: id (PK), thing_id (FK), isbn (VARCHAR), page_count (INTEGER),
     *         book_type (VARCHAR), book_type_path (VARCHAR)
     * Delegates: ComicsBook, AudioBook
     * FK in sub-delegates: book_id
     *
     * @param array<string, TableMeta> $sub_delegates
     * @return DelegatedStubTable
     */
    private function make_delegated_book_table(array $sub_delegates = []): DelegatedStubTable
    {
        if (empty($sub_delegates)) {
            $sub_delegates = [
                'ComicsBook' => $this->make_comics_book_table(),
                'AudioBook'  => $this->make_audio_book_table(),
            ];
        }

        return new DelegatedStubTable(
            [
                new StubColumn('id', 'INTEGER', false, true),
                new StubColumn('thing_id', 'INTEGER', false),
                new StubColumn('isbn', 'VARCHAR', true),
                new StubColumn('page_count', 'INTEGER', true),
                new StubColumn('book_type', 'VARCHAR', true),
                new StubColumn('book_type_path', 'VARCHAR', true),
            ],
            'book_type',
            'book_type_path',
            'book_id',
            $sub_delegates
        );
    }

    /**
     * Create a ComicsBook leaf table.
     *
     * Schema: id (PK), book_id (FK), illustrator (VARCHAR), color (BOOLEAN)
     *
     * @return StubTable
     */
    private function make_comics_book_table(): StubTable
    {
        return new StubTable([
            new StubColumn('id', 'INTEGER', false, true),
            new StubColumn('book_id', 'INTEGER', false),
            new StubColumn('illustrator', 'VARCHAR', true),
            new StubColumn('color', 'BOOLEAN', true),
        ]);
    }

    /**
     * Create an AudioBook leaf table.
     *
     * Schema: id (PK), book_id (FK), narrator (VARCHAR), duration_minutes (INTEGER)
     *
     * @return StubTable
     */
    private function make_audio_book_table(): StubTable
    {
        return new StubTable([
            new StubColumn('id', 'INTEGER', false, true),
            new StubColumn('book_id', 'INTEGER', false),
            new StubColumn('narrator', 'VARCHAR', true),
            new StubColumn('duration_minutes', 'INTEGER', true),
        ]);
    }

    /**
     * Collect visible field names from a FormMeta by iterating each().
     *
     * @param FormMeta $form
     * @return string[]
     */
    private function visible_field_names(FormMeta $form): array
    {
        return array_keys(iterator_to_array($form->each()));
    }

    /**
     * Collect all field names (including hidden) from a FormMeta by iterating all().
     *
     * @param FormMeta $form
     * @return string[]
     */
    private function all_field_names(FormMeta $form): array
    {
        return array_keys(iterator_to_array($form->all()));
    }

    // =========================================================================
    // Single-level delegation: delegate('Book')
    // =========================================================================

    public function test_delegate_merges_child_columns(): void
    {
        $form = new FormMeta($this->make_things_table());
        $form->delegate('Book');

        $names = $this->all_field_names($form);

        // Book-specific columns should be present
        $this->assertContains('isbn', $names);
        $this->assertContains('page_count', $names);
    }

    public function test_delegate_hides_type_column(): void
    {
        $form = new FormMeta($this->make_things_table());
        $form->delegate('Book');

        $visible = $this->visible_field_names($form);

        $this->assertNotContains('type', $visible);
    }

    public function test_delegate_hides_type_path_column(): void
    {
        $form = new FormMeta($this->make_things_table());
        $form->delegate('Book');

        $visible = $this->visible_field_names($form);

        $this->assertNotContains('type_path', $visible);
    }

    public function test_delegate_hides_fk_column(): void
    {
        $form = new FormMeta($this->make_things_table());
        $form->delegate('Book');

        $visible = $this->visible_field_names($form);

        // book.thing_id should be hidden
        $this->assertNotContains('thing_id', $visible);
    }

    public function test_delegate_hides_pk_column(): void
    {
        $form = new FormMeta($this->make_things_table());
        $form->delegate('Book');

        $visible = $this->visible_field_names($form);

        // The delegate's own PK (book.id) is registered and hidden.
        // The root "id" is visible (it's the source PK, not auto-hidden by delegation).
        // Let's check that the book's "id" is hidden by confirming it's in all() but
        // that its field is hidden.
        $all = $this->all_field_names($form);
        $this->assertContains('id', $all);

        // The delegate book.id would collide with root id; let's verify the
        // delegate PK column is registered as hidden via field_meta.
        // Since both share the name 'id', the delegate's hidden registration
        // should overwrite (or the root's 'id' stays). The root id is not
        // auto-hidden by delegation, so let's verify the behavior:
        // Root 'id' is a PK but FormMeta does NOT auto-hide root PKs;
        // the delegate's 'id' (book.id) would overwrite root 'id' in field_meta as hidden.
        $this->assertNotContains('id', $visible);
    }

    public function test_delegate_preserves_source_fields(): void
    {
        $form = new FormMeta($this->make_things_table());
        $form->delegate('Book');

        $visible = $this->visible_field_names($form);

        // Root's 'title' should remain visible
        $this->assertContains('title', $visible);
    }

    public function test_delegate_fields_are_configurable(): void
    {
        $form = new FormMeta($this->make_things_table());
        $form->delegate('Book');

        $field = $form->field('isbn');
        $field->label('ISBN');

        $this->assertSame('ISBN', $field->get_label());
    }

    public function test_delegate_on_non_delegated_throws(): void
    {
        $plain_table = new StubTable([
            new StubColumn('id', 'INTEGER', false, true),
            new StubColumn('name', 'VARCHAR', false),
        ]);
        $form = new FormMeta($plain_table);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('DelegatedTableMeta');

        $form->delegate('Something');
    }

    public function test_delegate_unknown_type_throws(): void
    {
        $form = new FormMeta($this->make_things_table());

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Delegate type 'UnknownType' not found");

        $form->delegate('UnknownType');
    }

    public function test_delegate_error_lists_available(): void
    {
        $form = new FormMeta($this->make_things_table());

        try {
            $form->delegate('UnknownType');
            $this->fail('Expected InvalidArgumentException');
        } catch (InvalidArgumentException $e) {
            $message = $e->getMessage();
            $this->assertStringContainsString('Book', $message);
            $this->assertStringContainsString('Movie', $message);
        }
    }

    // =========================================================================
    // N-level chains: Thing -> Book -> ComicsBook
    // =========================================================================

    public function test_delegate_resolves_two_level_chain(): void
    {
        $delegated_book = $this->make_delegated_book_table();
        $things_table = $this->make_things_table([
            'Book'  => $delegated_book,
            'Movie' => $this->make_movie_table(),
        ]);

        $form = new FormMeta($things_table);
        $form->delegate('ComicsBook');

        // The chain should be: [Book (delegated), ComicsBook]
        $chain = $form->get_delegate_chain();
        $this->assertCount(2, $chain);
    }

    public function test_delegate_two_level_merges_all_columns(): void
    {
        $delegated_book = $this->make_delegated_book_table();
        $things_table = $this->make_things_table([
            'Book'  => $delegated_book,
            'Movie' => $this->make_movie_table(),
        ]);

        $form = new FormMeta($things_table);
        $form->delegate('ComicsBook');

        $all = $this->all_field_names($form);

        // Root columns
        $this->assertContains('title', $all);
        // Book columns
        $this->assertContains('isbn', $all);
        $this->assertContains('page_count', $all);
        // ComicsBook columns
        $this->assertContains('illustrator', $all);
        $this->assertContains('color', $all);
    }

    public function test_delegate_two_level_hides_intermediate_fk(): void
    {
        $delegated_book = $this->make_delegated_book_table();
        $things_table = $this->make_things_table([
            'Book'  => $delegated_book,
            'Movie' => $this->make_movie_table(),
        ]);

        $form = new FormMeta($things_table);
        $form->delegate('ComicsBook');

        $visible = $this->visible_field_names($form);

        // book.thing_id should be hidden
        $this->assertNotContains('thing_id', $visible);
    }

    public function test_delegate_two_level_hides_leaf_fk(): void
    {
        $delegated_book = $this->make_delegated_book_table();
        $things_table = $this->make_things_table([
            'Book'  => $delegated_book,
            'Movie' => $this->make_movie_table(),
        ]);

        $form = new FormMeta($things_table);
        $form->delegate('ComicsBook');

        $visible = $this->visible_field_names($form);

        // comics_book.book_id should be hidden
        $this->assertNotContains('book_id', $visible);
    }

    public function test_delegate_two_level_hides_intermediate_type_cols(): void
    {
        $delegated_book = $this->make_delegated_book_table();
        $things_table = $this->make_things_table([
            'Book'  => $delegated_book,
            'Movie' => $this->make_movie_table(),
        ]);

        $form = new FormMeta($things_table);
        $form->delegate('ComicsBook');

        $visible = $this->visible_field_names($form);

        // Book's own book_type and book_type_path should be hidden
        $this->assertNotContains('book_type', $visible);
        $this->assertNotContains('book_type_path', $visible);
    }

    public function test_delegate_resolves_three_level_chain(): void
    {
        // Create a 3-level chain: Thing -> Book -> ComicsBook -> MangaBook
        $manga_table = new StubTable([
            new StubColumn('id', 'INTEGER', false, true),
            new StubColumn('comics_book_id', 'INTEGER', false),
            new StubColumn('is_right_to_left', 'BOOLEAN', true),
        ]);

        $delegated_comics = new DelegatedStubTable(
            [
                new StubColumn('id', 'INTEGER', false, true),
                new StubColumn('book_id', 'INTEGER', false),
                new StubColumn('illustrator', 'VARCHAR', true),
                new StubColumn('color', 'BOOLEAN', true),
                new StubColumn('comics_type', 'VARCHAR', true),
                new StubColumn('comics_type_path', 'VARCHAR', true),
            ],
            'comics_type',
            'comics_type_path',
            'comics_book_id',
            ['MangaBook' => $manga_table]
        );

        $delegated_book = new DelegatedStubTable(
            [
                new StubColumn('id', 'INTEGER', false, true),
                new StubColumn('thing_id', 'INTEGER', false),
                new StubColumn('isbn', 'VARCHAR', true),
                new StubColumn('book_type', 'VARCHAR', true),
                new StubColumn('book_type_path', 'VARCHAR', true),
            ],
            'book_type',
            'book_type_path',
            'book_id',
            ['ComicsBook' => $delegated_comics]
        );

        $things_table = $this->make_things_table([
            'Book' => $delegated_book,
        ]);

        $form = new FormMeta($things_table);
        $form->delegate('MangaBook');

        $chain = $form->get_delegate_chain();
        $this->assertCount(3, $chain);

        $visible = $this->visible_field_names($form);
        $this->assertContains('is_right_to_left', $visible);
        $this->assertContains('title', $visible);
    }

    // =========================================================================
    // Wildcard delegation: delegate('*')
    // =========================================================================

    public function test_wildcard_turns_type_into_select(): void
    {
        $form = new FormMeta($this->make_things_table());
        $form->delegate('*');

        $type_field = $form->field('type');

        $this->assertSame('select', $type_field->get_type());

        $options = $type_field->get_options();
        $this->assertArrayHasKey('Book', $options);
        $this->assertArrayHasKey('Movie', $options);
    }

    public function test_wildcard_hides_type_path(): void
    {
        $form = new FormMeta($this->make_things_table());
        $form->delegate('*');

        $visible = $this->visible_field_names($form);

        $this->assertNotContains('type_path', $visible);
    }

    public function test_wildcard_includes_all_delegate_columns(): void
    {
        $form = new FormMeta($this->make_things_table());
        $form->delegate('*');

        $all = $this->all_field_names($form);

        // Book columns
        $this->assertContains('isbn', $all);
        $this->assertContains('page_count', $all);
        // Movie columns
        $this->assertContains('duration', $all);
        $this->assertContains('director', $all);
    }

    public function test_wildcard_columns_have_data_delegate_type_attr(): void
    {
        $form = new FormMeta($this->make_things_table());
        $form->delegate('*');

        // Book's columns should have data-delegate-type = 'Book'
        $isbn_attrs = $form->field('isbn')->get_attributes();
        $this->assertArrayHasKey('data-delegate-type', $isbn_attrs);
        $this->assertSame('Book', $isbn_attrs['data-delegate-type']);

        // Movie's columns should have data-delegate-type = 'Movie'
        $director_attrs = $form->field('director')->get_attributes();
        $this->assertArrayHasKey('data-delegate-type', $director_attrs);
        $this->assertSame('Movie', $director_attrs['data-delegate-type']);
    }

    public function test_wildcard_skips_pk_and_fk(): void
    {
        $form = new FormMeta($this->make_things_table());
        $form->delegate('*');

        $visible = $this->visible_field_names($form);

        // Delegate PKs and FKs should not appear
        $this->assertNotContains('thing_id', $visible);
        // The book/movie 'id' (PK) should be skipped in wildcard registration
        // Only the root 'id' should be present
    }

    // =========================================================================
    // FormMeta state
    // =========================================================================

    public function test_get_delegate_type_returns_null_before_delegate(): void
    {
        $form = new FormMeta($this->make_things_table());

        $this->assertNull($form->get_delegate_type());
    }

    public function test_get_delegate_type_returns_type_after_delegate(): void
    {
        $form = new FormMeta($this->make_things_table());
        $form->delegate('Book');

        $this->assertSame('Book', $form->get_delegate_type());
    }

    public function test_get_delegate_chain_empty_before_delegate(): void
    {
        $form = new FormMeta($this->make_things_table());

        $this->assertEmpty($form->get_delegate_chain());
    }

    public function test_get_delegate_chain_has_tables_after_delegate(): void
    {
        $form = new FormMeta($this->make_things_table());
        $form->delegate('Book');

        $chain = $form->get_delegate_chain();
        $this->assertCount(1, $chain);
        $this->assertInstanceOf(TableMeta::class, $chain[0]);
    }

    // =========================================================================
    // Interaction with existing features
    // =========================================================================

    public function test_delegate_with_exclude(): void
    {
        $form = new FormMeta($this->make_things_table());
        $form->delegate('Book');
        $form->exclude('isbn');

        $visible = $this->visible_field_names($form);

        $this->assertNotContains('isbn', $visible);
        $this->assertContains('page_count', $visible);
        $this->assertContains('title', $visible);
    }

    public function test_delegate_with_sections(): void
    {
        $form = new FormMeta($this->make_things_table());
        $form->delegate('Book');

        $form->field('isbn')->group('book_info');
        $form->field('page_count')->group('book_info');
        $form->section('book_info')->title('Book Information');

        $grouped = $form->by_section();

        $this->assertArrayHasKey('book_info', $grouped);
        $this->assertArrayHasKey('isbn', $grouped['book_info']['fields']);
        $this->assertArrayHasKey('page_count', $grouped['book_info']['fields']);
    }

    public function test_delegate_with_field_config(): void
    {
        $form = new FormMeta($this->make_things_table());
        $form->delegate('Book');

        $form->fields([
            'isbn'       => ['label' => 'ISBN Number', 'placeholder' => '978-...'],
            'page_count' => ['label' => 'Pages'],
        ]);

        $this->assertSame('ISBN Number', $form->field('isbn')->get_label());
        $this->assertSame('978-...', $form->field('isbn')->get_placeholder());
        $this->assertSame('Pages', $form->field('page_count')->get_label());
    }

    public function test_delegate_to_array_includes_delegate_fields(): void
    {
        $form = new FormMeta($this->make_things_table());
        $form->delegate('Book');

        $arr = $form->to_array();

        // Visible delegate fields should appear
        $this->assertArrayHasKey('isbn', $arr['fields']);
        $this->assertArrayHasKey('page_count', $arr['fields']);

        // Hidden glue columns should NOT appear (hidden fields excluded from to_array)
        $this->assertArrayNotHasKey('type', $arr['fields']);
        $this->assertArrayNotHasKey('type_path', $arr['fields']);
    }

    public function test_delegate_count_includes_delegate_fields(): void
    {
        $form = new FormMeta($this->make_things_table());
        $form->delegate('Book');

        // Visible: title + isbn + page_count = 3
        // Hidden: id (delegate PK overwrites root), type, type_path, thing_id
        $count = $form->count();

        $visible = $this->visible_field_names($form);
        $this->assertSame(count($visible), $count);
        $this->assertGreaterThanOrEqual(3, $count);
    }

    // =========================================================================
    // FormHtml rendering with delegation
    // =========================================================================

    public function test_formhtml_renders_delegate_fields(): void
    {
        $form = new FormMeta($this->make_things_table());
        $form->delegate('Book');

        $html = (new FormHtml($form))
            ->auto_resolve_relations(false)
            ->values(['title' => 'My Book', 'isbn' => '978-0-123'])
            ->fields();

        $this->assertStringContainsString('name="isbn"', $html);
        $this->assertStringContainsString('name="page_count"', $html);
        $this->assertStringContainsString('name="title"', $html);
        $this->assertStringContainsString('978-0-123', $html);

        // Hidden glue columns should not render
        $this->assertStringNotContainsString('name="type"', $html);
        $this->assertStringNotContainsString('name="type_path"', $html);
        $this->assertStringNotContainsString('name="thing_id"', $html);
    }

    public function test_formhtml_wildcard_renders_type_select(): void
    {
        $form = new FormMeta($this->make_things_table());
        $form->delegate('*');

        $html = (new FormHtml($form))
            ->auto_resolve_relations(false)
            ->fields();

        $this->assertStringContainsString('<select', $html);
        $this->assertStringContainsString('name="type"', $html);
        $this->assertStringContainsString('Book', $html);
        $this->assertStringContainsString('Movie', $html);
    }

    public function test_formhtml_wildcard_renders_data_delegate_type_attrs(): void
    {
        $form = new FormMeta($this->make_things_table());
        $form->delegate('*');

        $html = (new FormHtml($form))
            ->auto_resolve_relations(false)
            ->fields();

        $this->assertStringContainsString('data-delegate-type="Book"', $html);
        $this->assertStringContainsString('data-delegate-type="Movie"', $html);
    }

    // =========================================================================
    // PolymorphicColumnMeta in FormHtml
    // =========================================================================

    public function test_formhtml_polymorphic_sets_data_depends_on(): void
    {
        $posts_table = new StubTable([
            new StubColumn('id', 'INTEGER', false, true),
            new StubColumn('title', 'VARCHAR'),
        ]);
        $videos_table = new StubTable([
            new StubColumn('id', 'INTEGER', false, true),
            new StubColumn('name', 'VARCHAR'),
        ]);

        $poly_column = new StubPolymorphicColumn(
            new StubColumn('commentable_id', 'INTEGER', true),
            'commentable_type',
            ['Post' => $posts_table, 'Video' => $videos_table]
        );

        $table = new StubTable([
            new StubColumn('id', 'INTEGER', false, true),
            new StubColumn('commentable_type', 'VARCHAR', true),
            $poly_column,
        ]);

        $form = new FormMeta($table);
        $html = (new FormHtml($form))
            ->auto_resolve_relations(true)
            ->field('commentable_id');

        $this->assertStringContainsString('data-depends-on="commentable_type"', $html);
    }

    public function test_formhtml_polymorphic_sets_data_polymorphic_targets(): void
    {
        $posts_table = new StubTable([
            new StubColumn('id', 'INTEGER', false, true),
            new StubColumn('title', 'VARCHAR'),
        ]);
        $videos_table = new StubTable([
            new StubColumn('id', 'INTEGER', false, true),
            new StubColumn('name', 'VARCHAR'),
        ]);

        $poly_column = new StubPolymorphicColumn(
            new StubColumn('commentable_id', 'INTEGER', true),
            'commentable_type',
            ['Post' => $posts_table, 'Video' => $videos_table]
        );

        $table = new StubTable([
            new StubColumn('id', 'INTEGER', false, true),
            new StubColumn('commentable_type', 'VARCHAR', true),
            $poly_column,
        ]);

        $form = new FormMeta($table);
        $html = (new FormHtml($form))
            ->auto_resolve_relations(true)
            ->field('commentable_id');

        $this->assertStringContainsString('data-polymorphic="true"', $html);
        $this->assertStringContainsString('data-polymorphic-targets=', $html);
        // The targets JSON should contain Post and Video
        $this->assertStringContainsString('Post', $html);
        $this->assertStringContainsString('Video', $html);
    }

    // =========================================================================
    // RelationalColumnMeta vs plain ColumnMeta
    // =========================================================================

    public function test_relation_resolution_skips_plain_column_meta(): void
    {
        // A table with a plain ColumnMeta (not RelationalColumnMeta) should
        // render without errors when auto_resolve_relations is on.
        $table = new StubTable([
            new StubColumn('id', 'INTEGER', false, true),
            new StubColumn('name', 'VARCHAR', false),
            new StubColumn('category_id', 'INTEGER', true),
        ]);

        $form = new FormMeta($table);

        // This should not throw even with auto_resolve_relations enabled,
        // because plain ColumnMeta is not instanceof RelationalColumnMeta.
        $html = (new FormHtml($form))
            ->auto_resolve_relations(true)
            ->field('category_id');

        $this->assertStringContainsString('name="category_id"', $html);
        // Should render as number input (inferred from INTEGER), not select
        $this->assertStringNotContainsString('<select', $html);
    }

    public function test_relation_resolution_works_with_relational_column_meta(): void
    {
        $relation = new StubRelation('categories', 'id', 'name', [1 => 'Electronics', 2 => 'Books']);

        $relational_column = new StubRelationalColumn(
            new StubColumn('category_id', 'INTEGER', true),
            $relation
        );

        $table = new StubTable([
            new StubColumn('id', 'INTEGER', false, true),
            $relational_column,
        ]);

        $form = new FormMeta($table);
        $html = (new FormHtml($form))
            ->auto_resolve_relations(true)
            ->field('category_id');

        $this->assertStringContainsString('<select', $html);
        $this->assertStringContainsString('Electronics', $html);
        $this->assertStringContainsString('Books', $html);
    }

    // =========================================================================
    // Additional edge cases and delegation scenarios
    // =========================================================================

    public function test_delegate_returns_self_for_fluency(): void
    {
        $form = new FormMeta($this->make_things_table());
        $result = $form->delegate('Book');

        $this->assertSame($form, $result);
    }

    public function test_wildcard_delegate_type_is_star(): void
    {
        $form = new FormMeta($this->make_things_table());
        $form->delegate('*');

        $this->assertSame('*', $form->get_delegate_type());
    }

    public function test_wildcard_delegate_chain_is_empty(): void
    {
        $form = new FormMeta($this->make_things_table());
        $form->delegate('*');

        // Wildcard doesn't resolve a specific chain
        $this->assertEmpty($form->get_delegate_chain());
    }

    public function test_delegate_with_null_type_column(): void
    {
        // A DelegatedTableMeta can return null for type_column
        $table = new DelegatedStubTable(
            [
                new StubColumn('id', 'INTEGER', false, true),
                new StubColumn('title', 'VARCHAR', false),
            ],
            null,
            null,
            'thing_id',
            ['Book' => $this->make_book_table()]
        );

        $form = new FormMeta($table);
        $form->delegate('Book');

        $visible = $this->visible_field_names($form);

        // Should still work, just no type/type_path columns to hide
        $this->assertContains('isbn', $visible);
        $this->assertContains('title', $visible);
    }

    public function test_delegate_field_lookup_searches_delegate_chain(): void
    {
        $form = new FormMeta($this->make_things_table());
        $form->delegate('Book');

        // field() should find 'isbn' in the delegate chain
        $field = $form->field('isbn');
        $this->assertInstanceOf(FieldMeta::class, $field);
        $this->assertSame('isbn', $field->get_name());
    }

    public function test_delegate_error_message_lists_nested_types(): void
    {
        $delegated_book = $this->make_delegated_book_table();
        $things_table = $this->make_things_table([
            'Book'  => $delegated_book,
            'Movie' => $this->make_movie_table(),
        ]);

        $form = new FormMeta($things_table);

        try {
            $form->delegate('NonExistent');
            $this->fail('Expected InvalidArgumentException');
        } catch (InvalidArgumentException $e) {
            $message = $e->getMessage();
            // Should list both top-level and nested delegate names
            $this->assertStringContainsString('Book', $message);
            $this->assertStringContainsString('Movie', $message);
            $this->assertStringContainsString('ComicsBook', $message);
            $this->assertStringContainsString('AudioBook', $message);
        }
    }

    public function test_wildcard_with_nested_delegates_includes_nested_columns(): void
    {
        $delegated_book = $this->make_delegated_book_table();
        $things_table = $this->make_things_table([
            'Book'  => $delegated_book,
            'Movie' => $this->make_movie_table(),
        ]);

        $form = new FormMeta($things_table);
        $form->delegate('*');

        $all = $this->all_field_names($form);

        // Should include columns from nested delegates too
        $this->assertContains('illustrator', $all);
        $this->assertContains('narrator', $all);
    }

    public function test_wildcard_nested_delegate_columns_have_correct_type_attr(): void
    {
        $delegated_book = $this->make_delegated_book_table();
        $things_table = $this->make_things_table([
            'Book'  => $delegated_book,
            'Movie' => $this->make_movie_table(),
        ]);

        $form = new FormMeta($things_table);
        $form->delegate('*');

        // Book's direct columns should be tagged as 'Book'
        $isbn_attrs = $form->field('isbn')->get_attributes();
        $this->assertSame('Book', $isbn_attrs['data-delegate-type']);

        // ComicsBook's columns should be tagged as 'ComicsBook'
        $illustrator_attrs = $form->field('illustrator')->get_attributes();
        $this->assertSame('ComicsBook', $illustrator_attrs['data-delegate-type']);

        // Movie's columns should be tagged as 'Movie'
        $director_attrs = $form->field('director')->get_attributes();
        $this->assertSame('Movie', $director_attrs['data-delegate-type']);
    }

    public function test_formhtml_renders_complete_form_with_delegation(): void
    {
        $form = new FormMeta($this->make_things_table());
        $form->delegate('Book');

        $html = (new FormHtml($form))
            ->auto_resolve_relations(false)
            ->action('/things/store')
            ->method('POST')
            ->render();

        $this->assertStringContainsString('<form', $html);
        $this->assertStringContainsString('</form>', $html);
        $this->assertStringContainsString('action="/things/store"', $html);
        $this->assertStringContainsString('name="isbn"', $html);
    }

    public function test_wildcard_type_select_includes_nested_delegate_names(): void
    {
        $delegated_book = $this->make_delegated_book_table();
        $things_table = $this->make_things_table([
            'Book'  => $delegated_book,
            'Movie' => $this->make_movie_table(),
        ]);

        $form = new FormMeta($things_table);
        $form->delegate('*');

        $options = $form->field('type')->get_options();

        $this->assertArrayHasKey('Book', $options);
        $this->assertArrayHasKey('Movie', $options);
        $this->assertArrayHasKey('ComicsBook', $options);
        $this->assertArrayHasKey('AudioBook', $options);
    }

    public function test_delegate_visible_count_matches_expected(): void
    {
        $form = new FormMeta($this->make_things_table());
        $form->delegate('Movie');

        $visible = $this->visible_field_names($form);

        // Root visible: title (id overwritten by delegate hidden id)
        // Movie visible: duration, director
        // Hidden: id (delegate PK), type, type_path, thing_id (FK)
        $this->assertContains('title', $visible);
        $this->assertContains('duration', $visible);
        $this->assertContains('director', $visible);
        $this->assertCount(3, $visible);
    }
}
