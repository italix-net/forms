<?php
/**
 * Example 09: Custom Widget Registration
 *
 * Demonstrates how to create a custom widget by implementing WidgetInterface,
 * then register it in a WidgetRegistry so it is used whenever a field has the
 * corresponding type.
 *
 * This example creates two custom widgets:
 *   1. ColorPickerWidget -- renders an <input type="color"> with a text preview.
 *   2. RatingWidget      -- renders a 1-5 star rating using radio buttons.
 */

require __DIR__ . '/../vendor/autoload.php';

use Italix\Forms\FieldMeta;
use Italix\Forms\Rendering\FormHtml;
use Italix\Forms\Rendering\WidgetInterface;
use Italix\Forms\Rendering\WidgetRegistry;
use function Italix\Forms\form_meta;

// ---------------------------------------------------------------------------
// Custom Widget 1: Color Picker
// ---------------------------------------------------------------------------
class ColorPickerWidget implements WidgetInterface
{
    /**
     * Render an HTML5 color input with a text fallback.
     *
     * @param FieldMeta $field
     * @param mixed $value
     * @param array $attrs
     * @return string
     */
    public function render_edit(FieldMeta $field, $value, array $attrs = []): string
    {
        $name = htmlspecialchars($field->get_name(), ENT_QUOTES, 'UTF-8');
        $id   = 'field-' . $name;
        $val  = htmlspecialchars((string)($value ?: '#000000'), ENT_QUOTES, 'UTF-8');

        $html  = '<div class="color-picker-widget">';
        $html .= '<input type="color" id="' . $id . '" name="' . $name . '" value="' . $val . '"';
        if ($field->is_required()) {
            $html .= ' required';
        }
        $html .= '>';
        $html .= ' <span class="color-preview" style="display:inline-block;width:2em;height:2em;'
               . 'vertical-align:middle;background:' . $val . ';border:1px solid #ccc;"></span>';
        $html .= ' <code>' . $val . '</code>';
        $html .= '</div>';

        return $html;
    }

    /**
     * Render a color swatch in view mode.
     *
     * @param FieldMeta $field
     * @param mixed $value
     * @param array $attrs
     * @return string
     */
    public function render_view(FieldMeta $field, $value, array $attrs = []): string
    {
        $val = htmlspecialchars((string)($value ?: '#000000'), ENT_QUOTES, 'UTF-8');

        return '<span class="color-swatch" style="display:inline-block;width:1.5em;height:1.5em;'
             . 'background:' . $val . ';border:1px solid #ccc;vertical-align:middle;"></span>'
             . ' <code>' . $val . '</code>';
    }
}

// ---------------------------------------------------------------------------
// Custom Widget 2: Star Rating
// ---------------------------------------------------------------------------
class RatingWidget implements WidgetInterface
{
    /**
     * Render a 1-5 radio button group styled as a rating.
     *
     * @param FieldMeta $field
     * @param mixed $value
     * @param array $attrs
     * @return string
     */
    public function render_edit(FieldMeta $field, $value, array $attrs = []): string
    {
        $name = htmlspecialchars($field->get_name(), ENT_QUOTES, 'UTF-8');
        $current = (int)$value;

        $html = '<div class="rating-widget">';
        for ($i = 1; $i <= 5; $i++) {
            $checked = ($i === $current) ? ' checked' : '';
            $id = 'field-' . $name . '-' . $i;
            $html .= '<label for="' . $id . '" style="cursor:pointer;font-size:1.5em;">';
            $html .= '<input type="radio" name="' . $name . '" id="' . $id . '" value="' . $i . '"'
                   . $checked . ' style="display:none;">';
            $star = ($i <= $current) ? '&#9733;' : '&#9734;';
            $html .= $star;
            $html .= '</label>';
        }
        $html .= '</div>';

        return $html;
    }

    /**
     * Render filled/empty stars in view mode.
     *
     * @param FieldMeta $field
     * @param mixed $value
     * @param array $attrs
     * @return string
     */
    public function render_view(FieldMeta $field, $value, array $attrs = []): string
    {
        $current = (int)$value;
        $html = '<span class="rating-view" style="font-size:1.5em;">';
        for ($i = 1; $i <= 5; $i++) {
            $html .= ($i <= $current) ? '&#9733;' : '&#9734;';
        }
        $html .= '</span>';
        return $html;
    }
}

// ---------------------------------------------------------------------------
// Build the form using the custom widgets
// ---------------------------------------------------------------------------

// 1. Create a custom widget registry and register our widgets.
$registry = new WidgetRegistry();
$registry->register('color', new ColorPickerWidget());
$registry->register('rating', new RatingWidget());

// 2. Define the form.
$form = form_meta([
    'product_name'  => ['type' => 'VARCHAR', 'length' => 200, 'nullable' => false],
    'brand_color'   => ['type' => 'VARCHAR', 'length' => 7, 'nullable' => false],
    'accent_color'  => ['type' => 'VARCHAR', 'length' => 7, 'nullable' => true],
    'quality_rating'=> ['type' => 'INTEGER', 'nullable' => false],
]);

$form->fields([
    'product_name' => [
        'label'       => 'Product Name',
        'placeholder' => 'My Awesome Product',
        'order'       => 1,
    ],
    'brand_color' => [
        'label' => 'Brand Color',
        'type'  => 'color',
        'help'  => 'Pick your primary brand color.',
        'order' => 2,
    ],
    'accent_color' => [
        'label' => 'Accent Color',
        'type'  => 'color',
        'help'  => 'Optional secondary color.',
        'order' => 3,
    ],
    'quality_rating' => [
        'label' => 'Quality Rating',
        'type'  => 'rating',
        'help'  => 'Rate from 1 to 5 stars.',
        'order' => 4,
    ],
]);

// 3. Render, passing the custom registry to FormHtml.
$html = new FormHtml($form, $registry);
$html->action('/products/store')
     ->method('POST')
     ->values([
         'product_name'   => 'Widget Pro',
         'brand_color'    => '#3498db',
         'accent_color'   => '#e74c3c',
         'quality_rating' => 4,
     ])
     ->buttons(['submit' => 'Save Product', 'cancel' => '/products']);

echo $html->render();
