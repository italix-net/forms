<?php
/**
 * Example 12: Delegated Types
 *
 * Demonstrates how FormMeta works with delegated types - tables that share
 * a common base table with type-specific sub-tables.
 *
 * This pattern is common in ORMs like Italix ORM where a "things" table
 * has delegates like "books", "movies", etc.
 */

require __DIR__ . '/../vendor/autoload.php';

use Italix\Contracts\ColumnMeta;
use Italix\Contracts\DelegatedTableMeta;
use Italix\Contracts\TableMeta;
use Italix\Forms\FormMeta;
use Italix\Forms\Rendering\FormHtml;
use Italix\Forms\Validation\Rule;

// ============================================================================
// Stub Classes
//
// In a real application, your ORM would provide these implementations.
// Here we create minimal stubs to demonstrate the delegation API.
// ============================================================================

/**
 * Simple column descriptor that implements ColumnMeta.
 */
class SimpleColumn implements ColumnMeta
{
    /** @var string */
    private $name;

    /** @var string */
    private $type;

    /** @var bool */
    private $nullable;

    /** @var bool */
    private $is_pk;

    /** @var int|null */
    private $length;

    public function __construct(
        string $name,
        string $type = 'VARCHAR',
        bool $nullable = true,
        bool $is_pk = false,
        ?int $length = null
    ) {
        $this->name = $name;
        $this->type = $type;
        $this->nullable = $nullable;
        $this->is_pk = $is_pk;
        $this->length = $length;
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
        return $this->length;
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
 * Simple table descriptor that implements TableMeta.
 *
 * Used for delegate tables that do NOT themselves have sub-delegates
 * (i.e., leaf nodes in the delegation tree).
 */
class SimpleTable implements TableMeta
{
    /** @var string */
    private $name;

    /** @var array<string, ColumnMeta> */
    private $columns;

    /**
     * @param string $name Table name (for display/debugging)
     * @param array<string, ColumnMeta> $columns Column name => ColumnMeta pairs
     */
    public function __construct(string $name, array $columns)
    {
        $this->name = $name;
        $this->columns = $columns;
    }

    public function describe_columns(): iterable
    {
        return $this->columns;
    }

    public function describe_column(string $name): ?ColumnMeta
    {
        return $this->columns[$name] ?? null;
    }

    public function get_name(): string
    {
        return $this->name;
    }
}

/**
 * Delegated table descriptor that implements DelegatedTableMeta.
 *
 * This represents a table that has a type discriminator column and
 * one or more delegate sub-tables. Each delegate table may itself
 * be a DelegatedTable (enabling N-level chains).
 */
class DelegatedTable extends SimpleTable implements DelegatedTableMeta
{
    /** @var string|null */
    private $type_column;

    /** @var string|null */
    private $type_path_column;

    /** @var string */
    private $delegate_fk;

    /** @var array<string, TableMeta> */
    private $delegate_tables;

    /**
     * @param string $name Table name
     * @param array<string, ColumnMeta> $columns Column descriptors
     * @param string|null $type_column Name of the type discriminator column
     * @param string|null $type_path_column Name of the type path column (nullable)
     * @param string $delegate_fk FK column name in delegate tables (e.g., 'thing_id')
     * @param array<string, TableMeta> $delegate_tables Map of type name => TableMeta
     */
    public function __construct(
        string $name,
        array $columns,
        ?string $type_column,
        ?string $type_path_column,
        string $delegate_fk,
        array $delegate_tables
    ) {
        parent::__construct($name, $columns);
        $this->type_column = $type_column;
        $this->type_path_column = $type_path_column;
        $this->delegate_fk = $delegate_fk;
        $this->delegate_tables = $delegate_tables;
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

// ============================================================================
// Build the table schema
//
// Schema overview:
//
//   things (base table)
//     - id (PK)
//     - name
//     - type          (discriminator: 'Book', 'Movie', etc.)
//     - type_path     (full path: 'Thing/Book/ComicsBook')
//     - created_at
//
//   books (delegate of Thing)
//     - id (PK)
//     - thing_id (FK -> things.id)
//     - isbn
//     - page_count
//     - author
//     -- also delegates to: ComicsBook
//
//   comics_books (delegate of Book)
//     - id (PK)
//     - book_id (FK -> books.id)
//     - illustrator
//     - is_color
//
//   movies (delegate of Thing)
//     - id (PK)
//     - thing_id (FK -> things.id)
//     - director
//     - duration_minutes
// ============================================================================

// -- comics_books: leaf delegate of Book
$comics_books_table = new SimpleTable('comics_books', [
    'id'           => new SimpleColumn('id', 'INTEGER', false, true),
    'book_id'      => new SimpleColumn('book_id', 'INTEGER', false),
    'illustrator'  => new SimpleColumn('illustrator', 'VARCHAR', false, false, 100),
    'is_color'     => new SimpleColumn('is_color', 'BOOLEAN', false),
]);

// -- books: delegate of Thing, also delegates to ComicsBook
$books_table = new DelegatedTable(
    'books',
    [
        'id'         => new SimpleColumn('id', 'INTEGER', false, true),
        'thing_id'   => new SimpleColumn('thing_id', 'INTEGER', false),
        'isbn'       => new SimpleColumn('isbn', 'VARCHAR', false, false, 13),
        'page_count' => new SimpleColumn('page_count', 'INTEGER', true),
        'author'     => new SimpleColumn('author', 'VARCHAR', false, false, 100),
    ],
    'type',           // type_column (on the Book level, for sub-delegation)
    'type_path',      // type_path_column
    'book_id',        // FK used in comics_books to reference books
    ['ComicsBook' => $comics_books_table]  // sub-delegates
);

// -- movies: leaf delegate of Thing
$movies_table = new SimpleTable('movies', [
    'id'               => new SimpleColumn('id', 'INTEGER', false, true),
    'thing_id'         => new SimpleColumn('thing_id', 'INTEGER', false),
    'director'         => new SimpleColumn('director', 'VARCHAR', false, false, 100),
    'duration_minutes' => new SimpleColumn('duration_minutes', 'INTEGER', true),
]);

// -- things: root table with delegation
$things_table = new DelegatedTable(
    'things',
    [
        'id'         => new SimpleColumn('id', 'INTEGER', false, true),
        'name'       => new SimpleColumn('name', 'VARCHAR', false, false, 200),
        'type'       => new SimpleColumn('type', 'VARCHAR', false, false, 50),
        'type_path'  => new SimpleColumn('type_path', 'VARCHAR', true, false, 255),
        'created_at' => new SimpleColumn('created_at', 'DATETIME', false),
    ],
    'type',          // type discriminator column
    'type_path',     // type path column
    'thing_id',      // FK used in delegate tables to reference things
    [
        'Book'  => $books_table,
        'Movie' => $movies_table,
    ]
);


// ============================================================================
// Scenario 1: Single delegation level (Thing + Book)
//
// Creates a form for a "Book". The delegate('Book') call:
//   - Resolves the chain: things -> books
//   - Merges book columns (isbn, page_count, author) into the form
//   - Hides glue columns (type, type_path, thing_id, book PK)
// ============================================================================

echo "=== Scenario 1: Thing + Book (single delegation) ===\n";

$form = new FormMeta($things_table);
$form->delegate('Book');

// Verify the chain was resolved
echo "Delegate chain length: " . count($form->get_delegate_chain()) . "\n";
echo "Delegate type: " . $form->get_delegate_type() . "\n\n";

// Configure labels for the merged fields
$form->field('name')->label('Title')->placeholder('Enter the book title');
$form->field('isbn')->label('ISBN')->placeholder('978-0-123456-78-9');
$form->field('page_count')->label('Pages');
$form->field('author')->label('Author')->placeholder('Author name');
$form->field('created_at')->label('Added On');

// Exclude the auto-increment PK from the form
$form->exclude('id');

// Render
$html = new FormHtml($form);
$html->action('/things/books/store')
     ->method('POST')
     ->buttons(['submit' => 'Create Book', 'cancel' => '/things']);

echo $html->render();
echo "\n\n";


// ============================================================================
// Scenario 2: Two-level chain (Thing + Book + ComicsBook)
//
// Creates a form for a "ComicsBook". You only call delegate('ComicsBook')
// and FormMeta automatically resolves the full chain:
//   things -> books -> comics_books
//
// All columns from all three tables are merged. Glue columns at every
// level (type, type_path, thing_id, book_id, PKs) are hidden.
// ============================================================================

echo "=== Scenario 2: Thing + Book + ComicsBook (two-level chain) ===\n";

$form = new FormMeta($things_table);
$form->delegate('ComicsBook');

echo "Delegate chain length: " . count($form->get_delegate_chain()) . "\n";
echo "Delegate type: " . $form->get_delegate_type() . "\n\n";

// Configure all visible fields
$form->field('name')->label('Comic Title')->placeholder('Enter the comic title');
$form->field('isbn')->label('ISBN');
$form->field('page_count')->label('Pages');
$form->field('author')->label('Writer');
$form->field('illustrator')->label('Illustrator')->placeholder('Artist name');
$form->field('is_color')->label('Full Color');
$form->field('created_at')->label('Added On');

$form->exclude('id');

// Add validation rules to delegate fields
$form->field('illustrator')->rules(Rule::required(), Rule::max_length(100));
$form->field('isbn')->rules(Rule::required(), Rule::length(13));

$html = new FormHtml($form);
$html->action('/things/books/comics/store')
     ->method('POST')
     ->values([
         'name'        => 'Watchmen',
         'author'      => 'Alan Moore',
         'illustrator' => 'Dave Gibbons',
         'is_color'    => true,
         'page_count'  => 416,
     ])
     ->buttons(['submit' => 'Create Comic', 'cancel' => '/things']);

echo $html->render();
echo "\n\n";


// ============================================================================
// Scenario 3: Wildcard mode with delegate('*')
//
// Shows ALL delegate types at once with data-delegate-type attributes
// on each field. The type column becomes a <select> so the user can
// choose the delegate type. JavaScript can then show/hide the relevant
// fields based on the selected type.
// ============================================================================

echo "=== Scenario 3: Wildcard delegation with delegate('*') ===\n";

$form = new FormMeta($things_table);
$form->delegate('*');

echo "Delegate type: " . $form->get_delegate_type() . "\n\n";

// The type column is automatically turned into a select with all types.
// Configure labels for the common fields.
$form->field('name')->label('Title')->placeholder('Enter title');
$form->field('type')->label('Thing Type');
$form->field('created_at')->label('Added On');

// Configure delegate-specific fields
$form->field('isbn')->label('ISBN');
$form->field('author')->label('Author');
$form->field('director')->label('Director');
$form->field('duration_minutes')->label('Duration (minutes)');

$form->exclude('id');

$html = new FormHtml($form);
$html->action('/things/store')
     ->method('POST')
     ->buttons(['submit' => 'Create Thing', 'cancel' => '/things']);

echo $html->render();
echo "\n\n";


// ============================================================================
// Scenario 4: Configuring delegate fields with labels, help, and rules
//
// Shows that delegate fields can be fully configured just like regular
// fields -- labels, placeholders, help text, rules, ordering, etc.
// ============================================================================

echo "=== Scenario 4: Configured delegate fields ===\n";

$form = new FormMeta($things_table);
$form->delegate('Movie');

$form->exclude('id');

// Configure with sections
$form->section('basic')
    ->title('Movie Details')
    ->columns(2)
    ->order(1);

$form->section('meta')
    ->title('Metadata')
    ->order(2);

$form->fields([
    'name' => [
        'label'       => 'Movie Title',
        'placeholder' => 'Enter the movie title',
        'group'       => 'basic',
        'order'       => 1,
    ],
    'director' => [
        'label'       => 'Director',
        'placeholder' => 'Director name',
        'help'        => 'Full name of the primary director.',
        'group'       => 'basic',
        'order'       => 2,
    ],
    'duration_minutes' => [
        'label'       => 'Runtime',
        'placeholder' => '120',
        'help'        => 'Duration in minutes.',
        'group'       => 'basic',
        'order'       => 3,
    ],
    'created_at' => [
        'label' => 'Date Added',
        'group' => 'meta',
        'order' => 1,
    ],
]);

$form->field('name')->rules(Rule::required(), Rule::max_length(200));
$form->field('director')->rules(Rule::required(), Rule::max_length(100));
$form->field('duration_minutes')->rules(Rule::integer(), Rule::between(1, 600));

$html = new FormHtml($form);
$html->action('/things/movies/store')
     ->method('POST')
     ->values([
         'name'             => 'Blade Runner 2049',
         'director'         => 'Denis Villeneuve',
         'duration_minutes' => 164,
     ])
     ->buttons(['submit' => 'Create Movie', 'cancel' => '/things']);

echo $html->render();
echo "\n";
