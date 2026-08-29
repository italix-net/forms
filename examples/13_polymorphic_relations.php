<?php
/**
 * Example 13: Polymorphic Relations
 *
 * Demonstrates how polymorphic FK fields render with the Italix Forms library.
 *
 * A polymorphic FK uses two columns: a type discriminator (e.g., 'commentable_type')
 * and an ID column (e.g., 'commentable_id'). The PolymorphicColumnMeta interface
 * on the ID column tells the renderer which types are available, and the rendering
 * layer automatically adds data-depends-on and data-polymorphic attributes for
 * JavaScript-driven dependent field behavior.
 *
 * Schema overview:
 *
 *   comments
 *     - id (PK)
 *     - body (TEXT)
 *     - commentable_type (VARCHAR) -- 'post' or 'video'
 *     - commentable_id   (INTEGER) -- FK to posts.id or videos.id
 *     - author_name (VARCHAR)
 *     - created_at (DATETIME)
 */

require __DIR__ . '/../vendor/autoload.php';

use Italix\Contracts\ColumnMeta;
use Italix\Contracts\PolymorphicColumnMeta;
use Italix\Contracts\TableMeta;
use Italix\Forms\FormMeta;
use Italix\Forms\Rendering\FormHtml;
use Italix\Rules\Rule;

// ============================================================================
// Stub Classes
//
// In a real application, your ORM would provide these implementations.
// Here we create minimal stubs to demonstrate the polymorphic field API.
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
 */
class SimpleTable implements TableMeta
{
    /** @var string */
    private $name;

    /** @var array<string, ColumnMeta> */
    private $columns;

    /**
     * @param string $name Table name
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
 * Polymorphic column descriptor that implements PolymorphicColumnMeta.
 *
 * This represents the ID column in a polymorphic FK pair. It knows which
 * column holds the type discriminator and which target tables are available.
 *
 * The rendering layer inspects this interface to:
 *   - Add data-depends-on pointing to the type column
 *   - Add data-polymorphic="true"
 *   - Add data-polymorphic-targets with JSON array of available types
 */
class PolymorphicColumn implements PolymorphicColumnMeta
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

    /** @var string */
    private $type_column;

    /** @var array<string, TableMeta> */
    private $targets;

    /**
     * @param string $name Column name (e.g., 'commentable_id')
     * @param string $type Column data type
     * @param bool $nullable Whether the column allows NULL
     * @param string $type_column Name of the type discriminator column
     * @param array<string, TableMeta> $targets Map of type value => target TableMeta
     */
    public function __construct(
        string $name,
        string $type,
        bool $nullable,
        string $type_column,
        array $targets
    ) {
        $this->name = $name;
        $this->type = $type;
        $this->nullable = $nullable;
        $this->is_pk = false;
        $this->length = null;
        $this->type_column = $type_column;
        $this->targets = $targets;
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

    public function get_polymorphic_type_column(): string
    {
        return $this->type_column;
    }

    public function get_polymorphic_targets(): array
    {
        return $this->targets;
    }
}


// ============================================================================
// Build the table schema
//
// Target tables (posts, videos) are defined so the polymorphic column
// knows what types it can reference.
// ============================================================================

$posts_table = new SimpleTable('posts', [
    'id'    => new SimpleColumn('id', 'INTEGER', false, true),
    'title' => new SimpleColumn('title', 'VARCHAR', false, false, 200),
    'body'  => new SimpleColumn('body', 'TEXT', false),
]);

$videos_table = new SimpleTable('videos', [
    'id'       => new SimpleColumn('id', 'INTEGER', false, true),
    'title'    => new SimpleColumn('title', 'VARCHAR', false, false, 200),
    'url'      => new SimpleColumn('url', 'VARCHAR', false, false, 500),
    'duration' => new SimpleColumn('duration', 'INTEGER', true),
]);

// The comments table uses a polymorphic FK: commentable_type + commentable_id.
// The commentable_id column implements PolymorphicColumnMeta to describe the
// relationship to the renderer.
$comments_table = new SimpleTable('comments', [
    'id'               => new SimpleColumn('id', 'INTEGER', false, true),
    'body'             => new SimpleColumn('body', 'TEXT', false),
    'commentable_type' => new SimpleColumn('commentable_type', 'VARCHAR', false, false, 50),
    'commentable_id'   => new PolymorphicColumn(
        'commentable_id',
        'INTEGER',
        false,
        'commentable_type',  // The type discriminator column
        [
            'post'  => $posts_table,
            'video' => $videos_table,
        ]
    ),
    'author_name'      => new SimpleColumn('author_name', 'VARCHAR', false, false, 100),
    'created_at'       => new SimpleColumn('created_at', 'DATETIME', false),
]);


// ============================================================================
// Scenario 1: Basic polymorphic form
//
// The commentable_type column is configured as a select so the user picks
// the target type. The commentable_id field automatically receives
// data-depends-on="commentable_type" and data-polymorphic="true" attributes,
// enabling JavaScript to swap ID options based on the selected type.
// ============================================================================

echo "=== Scenario 1: Comments form with polymorphic FK ===\n";

$form = new FormMeta($comments_table);

// Exclude the auto-increment PK
$form->exclude('id');

// Configure the type column as a select with human-readable labels.
// The values ('post', 'video') match the keys in get_polymorphic_targets().
$form->field('commentable_type')
    ->label('Comment On')
    ->type('select')
    ->options([
        ''      => '-- Select type --',
        'post'  => 'Blog Post',
        'video' => 'Video',
    ])
    ->help('Choose what this comment is attached to.');

// Configure the polymorphic ID column
$form->field('commentable_id')
    ->label('Target')
    ->help('Select the specific post or video. Options depend on the type above.');

// Configure other fields
$form->field('body')
    ->label('Comment')
    ->placeholder('Write your comment...');

$form->field('author_name')
    ->label('Your Name')
    ->placeholder('John Doe');

$form->field('created_at')
    ->label('Date')
    ->readonly();

// Validation
$form->field('commentable_type')->rules(Rule::required());
$form->field('commentable_id')->rules(Rule::required(), Rule::integer());
$form->field('body')->rules(Rule::required(), Rule::min_length(3));
$form->field('author_name')->rules(Rule::required(), Rule::max_length(100));

$html = new FormHtml($form);
$html->action('/comments/store')
     ->method('POST')
     ->buttons(['submit' => 'Post Comment', 'cancel' => '/comments']);

echo $html->render();
echo "\n\n";


// ============================================================================
// Scenario 2: Pre-filled edit form
//
// Shows a comment being edited with existing values. The polymorphic
// type and ID are pre-filled, and the rendered HTML includes the data
// attributes needed for JavaScript to update the ID field options.
// ============================================================================

echo "=== Scenario 2: Editing an existing comment ===\n";

$form = new FormMeta($comments_table);
$form->exclude('id');

$form->field('commentable_type')
    ->label('Comment On')
    ->type('select')
    ->options([
        'post'  => 'Blog Post',
        'video' => 'Video',
    ]);

$form->field('commentable_id')->label('Target');
$form->field('body')->label('Comment');
$form->field('author_name')->label('Author')->readonly();
$form->field('created_at')->label('Created')->readonly();

$html = new FormHtml($form);
$html->action('/comments/42')
     ->method('PUT')
     ->values([
         'commentable_type' => 'post',
         'commentable_id'   => 7,
         'body'             => 'Great article! Very informative.',
         'author_name'      => 'Jane Smith',
         'created_at'       => '2025-03-15 14:30:00',
     ])
     ->buttons(['submit' => 'Update Comment', 'cancel' => '/comments']);

echo $html->render();
echo "\n\n";


// ============================================================================
// Scenario 3: Inspecting the polymorphic metadata
//
// Shows how to programmatically inspect the polymorphic column to discover
// available targets and the dependent field relationship.
// ============================================================================

echo "=== Scenario 3: Inspecting polymorphic metadata ===\n";

$form = new FormMeta($comments_table);

// Access the underlying column through the field
$field = $form->field('commentable_id');
$column = $field->column();

if ($column instanceof PolymorphicColumnMeta) {
    echo "Column: " . $column->get_name() . "\n";
    echo "Type column: " . $column->get_polymorphic_type_column() . "\n";
    echo "Available targets:\n";
    foreach ($column->get_polymorphic_targets() as $type_name => $target_table) {
        // List columns in each target table
        $col_names = [];
        foreach ($target_table->describe_columns() as $col_name => $col) {
            $col_names[] = $col_name;
        }
        echo "  - {$type_name}: columns = [" . implode(', ', $col_names) . "]\n";
    }
} else {
    echo "commentable_id is not a polymorphic column.\n";
}

echo "\n";


// ============================================================================
// Scenario 4: JSON export of polymorphic form
//
// The polymorphic data attributes are included in the JSON export, making
// it easy for SPA frontends to detect dependent field relationships.
// ============================================================================

echo "=== Scenario 4: JSON export with polymorphic attributes ===\n";

$form = new FormMeta($comments_table);
$form->exclude('id', 'created_at');

$form->field('commentable_type')
    ->label('Type')
    ->type('select')
    ->options(['post' => 'Post', 'video' => 'Video']);

$form->field('commentable_id')->label('Target ID');
$form->field('body')->label('Comment');
$form->field('author_name')->label('Author');

// Trigger polymorphic resolution by rendering (the resolver runs on render).
// For JSON export, we can use to_json() directly -- but the polymorphic
// attributes are set during FormHtml rendering. To include them in the JSON,
// render a dummy FormHtml first to trigger attribute resolution.
$dummy = new FormHtml($form);
$dummy->auto_resolve_relations(true);

// Render one field to trigger the polymorphic resolver
$dummy->field('commentable_id');

// Now export the form metadata as JSON
echo $form->to_json(JSON_PRETTY_PRINT);
echo "\n";
