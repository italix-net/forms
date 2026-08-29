<?php
/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at https://mozilla.org/MPL/2.0/.
 */
/**
 * Italix Forms — the renderer, tested as an attacker would
 *
 * Every string this library emits ends up inside an HTML attribute or a text
 * node, and there are eleven places it can come from: the value, the label, the
 * help, the placeholder, an option, an error message, a section title, a custom
 * attribute, a hidden field, the form action, and the button captions.
 *
 * A single missing `esc()` in any one of them is a stored XSS, and it is
 * invisible in review because the surrounding lines all look identical. So the
 * corpus below is a list of payloads, and each one is pushed through **every**
 * entry point rather than through the one that seemed likeliest.
 *
 * The assertion is never "the output contains `&lt;`". It is that the raw
 * payload — the thing that would execute — is **not** in the output. Those are
 * different tests, and only the second one fails when somebody adds a twelfth
 * entry point.
 *
 * Run: php src/Libs/Italix/Forms/tests/FormHtmlTest.php
 */

declare(strict_types=1);

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

    fwrite(STDERR, "Could not find an autoloader. Run composer install.\n");
    exit(2);
})();

use Italix\Forms\Rendering\FormHtml;

use function Italix\Forms\form_meta;
use function Italix\Testing\{suite, section, test, summary};

suite('Italix Forms — rendering, and the escaping underneath it');

$columns = static fn (): array => [
    'title'   => ['type' => 'VARCHAR', 'length' => 200, 'nullable' => false],
    'summary' => ['type' => 'TEXT',    'nullable' => true],
    'status'  => ['type' => 'VARCHAR', 'length' => 20,  'nullable' => true],
];

// -----------------------------------------------------------------------------
section('it renders a form at all');

$html = (new FormHtml(form_meta($columns())))
    ->action('/documents/save')
    ->values(['title' => 'Quarterly report'])
    ->render();

test('there is a form element', strpos($html, '<form') !== false && strpos($html, '</form>') !== false);
test('the action is on it', strpos($html, 'action="/documents/save"') !== false);
test('the value is in the input', strpos($html, 'value="Quarterly report"') !== false);
test('a NOT NULL column renders as required', strpos($html, 'required') !== false);
test('the column length becomes a maxlength', strpos($html, 'maxlength="200"') !== false,
    'the schema is the single declaration; the browser limit should not be typed again by hand');

// -----------------------------------------------------------------------------
section('every entry point is escaped — pushed through one at a time');

/**
 * Payloads chosen to break out of the three contexts this library writes into:
 * a double-quoted attribute, a single-quoted one, and a text node.
 */
$payloads = [
    'script tag'      => '<script>alert(1)</script>',
    'attribute break' => '" onmouseover="alert(1)',
    'single quote'    => "' onfocus='alert(1)",
    'tag close'       => '</textarea><img src=x onerror=alert(1)>',
    'entity-ish'      => '&lt;script&gt;<script>',
];

$escaped_everywhere_flag = true;
$leaks = [];

foreach ($payloads as $label_c => $payload_c) {
    // Each closure renders a form with the payload in exactly one position.
    $renderings = [
        'value' => static function () use ($columns, $payload_c): string {
            return (new FormHtml(form_meta($columns())))->values(['title' => $payload_c])->render();
        },
        'label' => static function () use ($columns, $payload_c): string {
            $form = form_meta($columns());
            $form->field('title')->label($payload_c);

            return (new FormHtml($form))->render();
        },
        'help' => static function () use ($columns, $payload_c): string {
            $form = form_meta($columns());
            $form->field('title')->help($payload_c);

            return (new FormHtml($form))->render();
        },
        'placeholder' => static function () use ($columns, $payload_c): string {
            $form = form_meta($columns());
            $form->field('title')->placeholder($payload_c);

            return (new FormHtml($form))->render();
        },
        'select option' => static function () use ($columns, $payload_c): string {
            $form = form_meta($columns());
            $form->field('status')->options(['draft' => $payload_c]);

            return (new FormHtml($form))->render();
        },
        'option key' => static function () use ($columns, $payload_c): string {
            $form = form_meta($columns());
            $form->field('status')->options([$payload_c => 'Draft']);

            return (new FormHtml($form))->render();
        },
        'custom attribute' => static function () use ($columns, $payload_c): string {
            $form = form_meta($columns());
            $form->field('title')->attr('data-note', $payload_c);

            return (new FormHtml($form))->render();
        },
        'error message' => static function () use ($columns, $payload_c): string {
            return (new FormHtml(form_meta($columns())))->errors(['title' => $payload_c])->render();
        },
        'hidden field' => static function () use ($columns, $payload_c): string {
            return (new FormHtml(form_meta($columns())))->hidden('token', $payload_c)->render();
        },
        'form action' => static function () use ($columns, $payload_c): string {
            return (new FormHtml(form_meta($columns())))->action($payload_c)->render();
        },
        'button caption' => static function () use ($columns, $payload_c): string {
            return (new FormHtml(form_meta($columns())))->buttons(['submit' => $payload_c])->render();
        },
    ];

    foreach ($renderings as $where_c => $render) {
        $out = $render();

        // The raw payload must not survive anywhere in the document.
        if (strpos($out, $payload_c) !== false) {
            $escaped_everywhere_flag = false;
            $leaks[] = "{$label_c} survives in {$where_c}";
        }
    }
}

test('NO PAYLOAD SURVIVES ANY OF THE ELEVEN ENTRY POINTS',
    $escaped_everywhere_flag,
    $leaks === [] ? '' : implode('; ', $leaks));

test('…which is ' . (count($payloads) * 11) . ' renderings', true);

// -----------------------------------------------------------------------------
section('escaped, not merely stripped');

// The other half of the property: escaping must not destroy the text. A
// renderer that deletes `<` passes every test above and quietly mangles every
// label with an ampersand in it.
$form = form_meta($columns());
$form->field('title')->label('Profit & loss <2026>');

$html = (new FormHtml($form))->render();

test('an ampersand is encoded rather than dropped', strpos($html, '&amp;') !== false);
test('…and the angle brackets too', strpos($html, '&lt;2026&gt;') !== false);
test('…and the word itself is still readable', strpos($html, 'Profit') !== false
    && strpos($html, 'loss') !== false,
    'stripping instead of encoding is a data-loss bug that hides behind a passing XSS test');

$html = (new FormHtml(form_meta($columns())))->values(['title' => "O'Brien"])->render();

test('a single quote in a value does not break the attribute',
    strpos($html, "O'Brien") === false && strpos($html, '&#039;') !== false,
    'ENT_QUOTES is what makes a single-quoted attribute safe as well');

// -----------------------------------------------------------------------------
section('the pieces can be rendered separately, and stay escaped');

// open()/fields()/close() exist so a template can interleave its own markup.
// It is a separate code path and gets the same treatment.
$form = form_meta($columns());
$form->field('title')->label('<script>alert(1)</script>');

$renderer = (new FormHtml($form))->action('"><script>alert(1)</script>');

$pieces = $renderer->open() . $renderer->fields() . $renderer->close();

test('the split rendering produces the same document',
    strpos($pieces, '<form') !== false && strpos($pieces, '</form>') !== false);
test('…and escapes exactly as render() does',
    strpos($pieces, '<script>alert(1)</script>') === false,
    'a second path to the same output is a second place for an escape to be missing');

test('a single field can be rendered on its own', strpos($renderer->field('summary'), 'name="summary"') !== false);

// -----------------------------------------------------------------------------
section('TemplateWidget — the fourth escaper, which the corpus above never reached');

// Found by mutation rather than by reading: breaking the escaping in
// `FormHtml` or in `AbstractWidget` fails the suite above, and breaking it in
// `TemplateWidget` did not — nothing routed through it. An escaping
// implementation with no test is the one that will be wrong.
//
// There are four `htmlspecialchars` calls in this library across three files.
// They are duplicated rather than shared because house rule 13 makes a library
// a leaf — `Italix\\Forms` may not depend on `Italix\\Encode` — so the
// guarantee cannot be centralised and has to be *covered* instead.
$template_c = tempnam(sys_get_temp_dir(), 'ix_widget_') . '.php';

// The template puts the value in an **attribute**, which is where a theme
// actually puts it, and the payload below breaks out of one using nothing but
// a double quote. A payload containing `<` would be neutralised by
// htmlspecialchars even with ENT_QUOTES removed, so it cannot tell a correct
// escaper from a half-broken one — which is exactly the mistake the first
// version of this test made.
file_put_contents($template_c, '<div class="tpl"><input value="<?= $esc($value) ?>"></div>');

$registry = new \Italix\Forms\Rendering\WidgetRegistry();
$registry->register('text', new \Italix\Forms\Rendering\Widgets\TemplateWidget($template_c));

$attack_c = '" onmouseover="alert(1)';

$html = (new FormHtml(form_meta($columns()), $registry))
    ->values(['title' => $attack_c])
    ->render();

test('the template actually ran', strpos($html, 'class="tpl"') !== false,
    'if it silently fell back to the default widget this section would prove nothing');
test('THE $esc HANDED TO A TEMPLATE REALLY ESCAPES',
    strpos($html, $attack_c) === false,
    'every theme template in every project calls this closure; if it is wrong they are all wrong');

$view_html = (new FormHtml(form_meta($columns()), $registry))
    ->mode('view')
    ->values(['title' => $attack_c])
    ->render();

test('…and so does the one in view mode', strpos($view_html, $attack_c) === false,
    'render_view() builds its own closure, separately, and is a fifth place to get it wrong');

unlink($template_c);

exit(summary());
