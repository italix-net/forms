<?php

declare(strict_types=1);

namespace Italix\Forms\Tests;

use PHPUnit\Framework\TestCase;
use Italix\Forms\Rendering\WidgetInterface;
use Italix\Forms\Rendering\WidgetRegistry;
use Italix\Forms\Rendering\Widgets\TextWidget;
use Italix\Forms\Rendering\Widgets\TextareaWidget;
use Italix\Forms\Rendering\Widgets\SelectWidget;
use Italix\Forms\Rendering\Widgets\CheckboxWidget;
use Italix\Forms\Rendering\Widgets\RadioWidget;
use Italix\Forms\Rendering\Widgets\PasswordWidget;
use Italix\Forms\Rendering\Widgets\NumberWidget;
use Italix\Forms\Rendering\Widgets\DateWidget;
use Italix\Forms\Rendering\Widgets\FileWidget;
use Italix\Forms\Rendering\Widgets\HiddenWidget;
use Italix\Forms\Rendering\Widgets\ReadonlyWidget;
use Italix\Forms\FieldMeta;

class WidgetRegistryTest extends TestCase
{
    // =========================================================================
    // Default Widgets Registered
    // =========================================================================

    public function test_default_text_widget_registered(): void
    {
        $registry = new WidgetRegistry();

        $this->assertTrue($registry->has('text'));
        $this->assertInstanceOf(TextWidget::class, $registry->get('text'));
    }

    public function test_default_email_widget_registered(): void
    {
        $registry = new WidgetRegistry();

        $this->assertTrue($registry->has('email'));
        $this->assertInstanceOf(TextWidget::class, $registry->get('email'));
    }

    public function test_default_url_widget_registered(): void
    {
        $registry = new WidgetRegistry();

        $this->assertTrue($registry->has('url'));
        $this->assertInstanceOf(TextWidget::class, $registry->get('url'));
    }

    public function test_default_tel_widget_registered(): void
    {
        $registry = new WidgetRegistry();

        $this->assertTrue($registry->has('tel'));
        $this->assertInstanceOf(TextWidget::class, $registry->get('tel'));
    }

    public function test_default_search_widget_registered(): void
    {
        $registry = new WidgetRegistry();

        $this->assertTrue($registry->has('search'));
        $this->assertInstanceOf(TextWidget::class, $registry->get('search'));
    }

    public function test_default_color_widget_registered(): void
    {
        $registry = new WidgetRegistry();

        $this->assertTrue($registry->has('color'));
        $this->assertInstanceOf(TextWidget::class, $registry->get('color'));
    }

    public function test_default_textarea_widget_registered(): void
    {
        $registry = new WidgetRegistry();

        $this->assertTrue($registry->has('textarea'));
        $this->assertInstanceOf(TextareaWidget::class, $registry->get('textarea'));
    }

    public function test_default_select_widget_registered(): void
    {
        $registry = new WidgetRegistry();

        $this->assertTrue($registry->has('select'));
        $this->assertInstanceOf(SelectWidget::class, $registry->get('select'));
    }

    public function test_default_checkbox_widget_registered(): void
    {
        $registry = new WidgetRegistry();

        $this->assertTrue($registry->has('checkbox'));
        $this->assertInstanceOf(CheckboxWidget::class, $registry->get('checkbox'));
    }

    public function test_default_radio_widget_registered(): void
    {
        $registry = new WidgetRegistry();

        $this->assertTrue($registry->has('radio'));
        $this->assertInstanceOf(RadioWidget::class, $registry->get('radio'));
    }

    public function test_default_password_widget_registered(): void
    {
        $registry = new WidgetRegistry();

        $this->assertTrue($registry->has('password'));
        $this->assertInstanceOf(PasswordWidget::class, $registry->get('password'));
    }

    public function test_default_number_widget_registered(): void
    {
        $registry = new WidgetRegistry();

        $this->assertTrue($registry->has('number'));
        $this->assertInstanceOf(NumberWidget::class, $registry->get('number'));
    }

    public function test_default_range_widget_registered(): void
    {
        $registry = new WidgetRegistry();

        $this->assertTrue($registry->has('range'));
        $this->assertInstanceOf(NumberWidget::class, $registry->get('range'));
    }

    public function test_default_date_widget_registered(): void
    {
        $registry = new WidgetRegistry();

        $this->assertTrue($registry->has('date'));
        $this->assertInstanceOf(DateWidget::class, $registry->get('date'));
    }

    public function test_default_datetime_local_widget_registered(): void
    {
        $registry = new WidgetRegistry();

        $this->assertTrue($registry->has('datetime-local'));
        $this->assertInstanceOf(DateWidget::class, $registry->get('datetime-local'));
    }

    public function test_default_time_widget_registered(): void
    {
        $registry = new WidgetRegistry();

        $this->assertTrue($registry->has('time'));
        $this->assertInstanceOf(DateWidget::class, $registry->get('time'));
    }

    public function test_default_month_widget_registered(): void
    {
        $registry = new WidgetRegistry();

        $this->assertTrue($registry->has('month'));
        $this->assertInstanceOf(DateWidget::class, $registry->get('month'));
    }

    public function test_default_week_widget_registered(): void
    {
        $registry = new WidgetRegistry();

        $this->assertTrue($registry->has('week'));
        $this->assertInstanceOf(DateWidget::class, $registry->get('week'));
    }

    public function test_default_file_widget_registered(): void
    {
        $registry = new WidgetRegistry();

        $this->assertTrue($registry->has('file'));
        $this->assertInstanceOf(FileWidget::class, $registry->get('file'));
    }

    public function test_default_hidden_widget_registered(): void
    {
        $registry = new WidgetRegistry();

        $this->assertTrue($registry->has('hidden'));
        $this->assertInstanceOf(HiddenWidget::class, $registry->get('hidden'));
    }

    public function test_default_readonly_widget_registered(): void
    {
        $registry = new WidgetRegistry();

        $this->assertTrue($registry->has('readonly'));
        $this->assertInstanceOf(ReadonlyWidget::class, $registry->get('readonly'));
    }

    // =========================================================================
    // get() - Fallback to text
    // =========================================================================

    public function test_get_falls_back_to_text_for_unknown_type(): void
    {
        $registry = new WidgetRegistry();
        $widget = $registry->get('nonexistent_type');

        $this->assertInstanceOf(TextWidget::class, $widget);
    }

    public function test_get_falls_back_to_text_for_empty_string(): void
    {
        $registry = new WidgetRegistry();
        $widget = $registry->get('');

        $this->assertInstanceOf(TextWidget::class, $widget);
    }

    // =========================================================================
    // get() - Returns correct types
    // =========================================================================

    public function test_get_text_and_email_share_same_instance(): void
    {
        $registry = new WidgetRegistry();

        $this->assertSame($registry->get('text'), $registry->get('email'));
    }

    public function test_get_number_and_range_share_same_instance(): void
    {
        $registry = new WidgetRegistry();

        $this->assertSame($registry->get('number'), $registry->get('range'));
    }

    public function test_get_date_and_datetime_local_share_same_instance(): void
    {
        $registry = new WidgetRegistry();

        $this->assertSame($registry->get('date'), $registry->get('datetime-local'));
    }

    // =========================================================================
    // register() - Custom Widgets
    // =========================================================================

    public function test_register_custom_widget(): void
    {
        $registry = new WidgetRegistry();
        $custom = $this->createMock(WidgetInterface::class);

        $result = $registry->register('wysiwyg', $custom);

        $this->assertSame($registry, $result, 'register() should return self');
        $this->assertTrue($registry->has('wysiwyg'));
        $this->assertSame($custom, $registry->get('wysiwyg'));
    }

    public function test_register_overrides_default_widget(): void
    {
        $registry = new WidgetRegistry();
        $custom = $this->createMock(WidgetInterface::class);

        $registry->register('text', $custom);

        $this->assertSame($custom, $registry->get('text'));
        $this->assertNotInstanceOf(TextWidget::class, $registry->get('text'));
    }

    public function test_register_multiple_custom_widgets(): void
    {
        $registry = new WidgetRegistry();
        $widget_a = $this->createMock(WidgetInterface::class);
        $widget_b = $this->createMock(WidgetInterface::class);

        $registry->register('custom_a', $widget_a);
        $registry->register('custom_b', $widget_b);

        $this->assertSame($widget_a, $registry->get('custom_a'));
        $this->assertSame($widget_b, $registry->get('custom_b'));
    }

    // =========================================================================
    // has() Method
    // =========================================================================

    public function test_has_returns_true_for_registered_type(): void
    {
        $registry = new WidgetRegistry();

        $this->assertTrue($registry->has('text'));
    }

    public function test_has_returns_false_for_unregistered_type(): void
    {
        $registry = new WidgetRegistry();

        $this->assertFalse($registry->has('nonexistent'));
    }

    public function test_has_returns_true_after_custom_register(): void
    {
        $registry = new WidgetRegistry();
        $registry->register('custom', $this->createMock(WidgetInterface::class));

        $this->assertTrue($registry->has('custom'));
    }

    // =========================================================================
    // all() Method
    // =========================================================================

    public function test_all_returns_all_registered_widgets(): void
    {
        $registry = new WidgetRegistry();
        $all = $registry->all();

        $this->assertIsArray($all);
        $this->assertArrayHasKey('text', $all);
        $this->assertArrayHasKey('textarea', $all);
        $this->assertArrayHasKey('select', $all);
        $this->assertArrayHasKey('checkbox', $all);
        $this->assertArrayHasKey('radio', $all);
        $this->assertArrayHasKey('password', $all);
        $this->assertArrayHasKey('number', $all);
        $this->assertArrayHasKey('date', $all);
        $this->assertArrayHasKey('file', $all);
        $this->assertArrayHasKey('hidden', $all);
        $this->assertArrayHasKey('readonly', $all);
    }

    public function test_all_includes_custom_widgets(): void
    {
        $registry = new WidgetRegistry();
        $custom = $this->createMock(WidgetInterface::class);
        $registry->register('my_widget', $custom);

        $all = $registry->all();

        $this->assertArrayHasKey('my_widget', $all);
        $this->assertSame($custom, $all['my_widget']);
    }

    public function test_all_widgets_implement_widget_interface(): void
    {
        $registry = new WidgetRegistry();

        foreach ($registry->all() as $type => $widget) {
            $this->assertInstanceOf(
                WidgetInterface::class,
                $widget,
                "Widget for type '{$type}' must implement WidgetInterface"
            );
        }
    }
}
