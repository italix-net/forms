<?php
/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at https://mozilla.org/MPL/2.0/.
 */
/**
 * Italix Forms — widget registry
 *
 * Written as an xUnit class; runs on the shared runner (`Italix\Testing\Runner`) through XunitCase.
 *
 * Run: php src/Libs/Italix/Forms/tests/WidgetRegistryTest.php
 */

declare(strict_types=1);

namespace Italix\Forms\Tests;

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

class WidgetRegistryTest extends XunitCase
{
    // =========================================================================
    // Default Widgets Registered
    // =========================================================================

    public function test_default_text_widget_registered(): void
    {
        $registry = new WidgetRegistry();

        $this->assert_true($registry->has('text'));
        $this->assert_instance_of(TextWidget::class, $registry->get('text'));
    }

    public function test_default_email_widget_registered(): void
    {
        $registry = new WidgetRegistry();

        $this->assert_true($registry->has('email'));
        $this->assert_instance_of(TextWidget::class, $registry->get('email'));
    }

    public function test_default_url_widget_registered(): void
    {
        $registry = new WidgetRegistry();

        $this->assert_true($registry->has('url'));
        $this->assert_instance_of(TextWidget::class, $registry->get('url'));
    }

    public function test_default_tel_widget_registered(): void
    {
        $registry = new WidgetRegistry();

        $this->assert_true($registry->has('tel'));
        $this->assert_instance_of(TextWidget::class, $registry->get('tel'));
    }

    public function test_default_search_widget_registered(): void
    {
        $registry = new WidgetRegistry();

        $this->assert_true($registry->has('search'));
        $this->assert_instance_of(TextWidget::class, $registry->get('search'));
    }

    public function test_default_color_widget_registered(): void
    {
        $registry = new WidgetRegistry();

        $this->assert_true($registry->has('color'));
        $this->assert_instance_of(TextWidget::class, $registry->get('color'));
    }

    public function test_default_textarea_widget_registered(): void
    {
        $registry = new WidgetRegistry();

        $this->assert_true($registry->has('textarea'));
        $this->assert_instance_of(TextareaWidget::class, $registry->get('textarea'));
    }

    public function test_default_select_widget_registered(): void
    {
        $registry = new WidgetRegistry();

        $this->assert_true($registry->has('select'));
        $this->assert_instance_of(SelectWidget::class, $registry->get('select'));
    }

    public function test_default_checkbox_widget_registered(): void
    {
        $registry = new WidgetRegistry();

        $this->assert_true($registry->has('checkbox'));
        $this->assert_instance_of(CheckboxWidget::class, $registry->get('checkbox'));
    }

    public function test_default_radio_widget_registered(): void
    {
        $registry = new WidgetRegistry();

        $this->assert_true($registry->has('radio'));
        $this->assert_instance_of(RadioWidget::class, $registry->get('radio'));
    }

    public function test_default_password_widget_registered(): void
    {
        $registry = new WidgetRegistry();

        $this->assert_true($registry->has('password'));
        $this->assert_instance_of(PasswordWidget::class, $registry->get('password'));
    }

    public function test_default_number_widget_registered(): void
    {
        $registry = new WidgetRegistry();

        $this->assert_true($registry->has('number'));
        $this->assert_instance_of(NumberWidget::class, $registry->get('number'));
    }

    public function test_default_range_widget_registered(): void
    {
        $registry = new WidgetRegistry();

        $this->assert_true($registry->has('range'));
        $this->assert_instance_of(NumberWidget::class, $registry->get('range'));
    }

    public function test_default_date_widget_registered(): void
    {
        $registry = new WidgetRegistry();

        $this->assert_true($registry->has('date'));
        $this->assert_instance_of(DateWidget::class, $registry->get('date'));
    }

    public function test_default_datetime_local_widget_registered(): void
    {
        $registry = new WidgetRegistry();

        $this->assert_true($registry->has('datetime-local'));
        $this->assert_instance_of(DateWidget::class, $registry->get('datetime-local'));
    }

    public function test_default_time_widget_registered(): void
    {
        $registry = new WidgetRegistry();

        $this->assert_true($registry->has('time'));
        $this->assert_instance_of(DateWidget::class, $registry->get('time'));
    }

    public function test_default_month_widget_registered(): void
    {
        $registry = new WidgetRegistry();

        $this->assert_true($registry->has('month'));
        $this->assert_instance_of(DateWidget::class, $registry->get('month'));
    }

    public function test_default_week_widget_registered(): void
    {
        $registry = new WidgetRegistry();

        $this->assert_true($registry->has('week'));
        $this->assert_instance_of(DateWidget::class, $registry->get('week'));
    }

    public function test_default_file_widget_registered(): void
    {
        $registry = new WidgetRegistry();

        $this->assert_true($registry->has('file'));
        $this->assert_instance_of(FileWidget::class, $registry->get('file'));
    }

    public function test_default_hidden_widget_registered(): void
    {
        $registry = new WidgetRegistry();

        $this->assert_true($registry->has('hidden'));
        $this->assert_instance_of(HiddenWidget::class, $registry->get('hidden'));
    }

    public function test_default_readonly_widget_registered(): void
    {
        $registry = new WidgetRegistry();

        $this->assert_true($registry->has('readonly'));
        $this->assert_instance_of(ReadonlyWidget::class, $registry->get('readonly'));
    }

    // =========================================================================
    // get() - Fallback to text
    // =========================================================================

    public function test_get_falls_back_to_text_for_unknown_type(): void
    {
        $registry = new WidgetRegistry();
        $widget = $registry->get('nonexistent_type');

        $this->assert_instance_of(TextWidget::class, $widget);
    }

    public function test_get_falls_back_to_text_for_empty_string(): void
    {
        $registry = new WidgetRegistry();
        $widget = $registry->get('');

        $this->assert_instance_of(TextWidget::class, $widget);
    }

    // =========================================================================
    // get() - Returns correct types
    // =========================================================================

    public function test_get_text_and_email_share_same_instance(): void
    {
        $registry = new WidgetRegistry();

        $this->assert_same($registry->get('text'), $registry->get('email'));
    }

    public function test_get_number_and_range_share_same_instance(): void
    {
        $registry = new WidgetRegistry();

        $this->assert_same($registry->get('number'), $registry->get('range'));
    }

    public function test_get_date_and_datetime_local_share_same_instance(): void
    {
        $registry = new WidgetRegistry();

        $this->assert_same($registry->get('date'), $registry->get('datetime-local'));
    }

    // =========================================================================
    // register() - Custom Widgets
    // =========================================================================

    public function test_register_custom_widget(): void
    {
        $registry = new WidgetRegistry();
        $custom = $this->stub_widget();

        $result = $registry->register('wysiwyg', $custom);

        $this->assert_same($registry, $result, 'register() should return self');
        $this->assert_true($registry->has('wysiwyg'));
        $this->assert_same($custom, $registry->get('wysiwyg'));
    }

    public function test_register_overrides_default_widget(): void
    {
        $registry = new WidgetRegistry();
        $custom = $this->stub_widget();

        $registry->register('text', $custom);

        $this->assert_same($custom, $registry->get('text'));
        $this->assert_not_instance_of(TextWidget::class, $registry->get('text'));
    }

    public function test_register_multiple_custom_widgets(): void
    {
        $registry = new WidgetRegistry();
        $widget_a = $this->stub_widget();
        $widget_b = $this->stub_widget();

        $registry->register('custom_a', $widget_a);
        $registry->register('custom_b', $widget_b);

        $this->assert_same($widget_a, $registry->get('custom_a'));
        $this->assert_same($widget_b, $registry->get('custom_b'));
    }

    // =========================================================================
    // has() Method
    // =========================================================================

    public function test_has_returns_true_for_registered_type(): void
    {
        $registry = new WidgetRegistry();

        $this->assert_true($registry->has('text'));
    }

    public function test_has_returns_false_for_unregistered_type(): void
    {
        $registry = new WidgetRegistry();

        $this->assert_false($registry->has('nonexistent'));
    }

    public function test_has_returns_true_after_custom_register(): void
    {
        $registry = new WidgetRegistry();
        $registry->register('custom', $this->stub_widget());

        $this->assert_true($registry->has('custom'));
    }

    // =========================================================================
    // all() Method
    // =========================================================================

    public function test_all_returns_all_registered_widgets(): void
    {
        $registry = new WidgetRegistry();
        $all = $registry->all();

        $this->assert_is_array($all);
        $this->assert_has_key('text', $all);
        $this->assert_has_key('textarea', $all);
        $this->assert_has_key('select', $all);
        $this->assert_has_key('checkbox', $all);
        $this->assert_has_key('radio', $all);
        $this->assert_has_key('password', $all);
        $this->assert_has_key('number', $all);
        $this->assert_has_key('date', $all);
        $this->assert_has_key('file', $all);
        $this->assert_has_key('hidden', $all);
        $this->assert_has_key('readonly', $all);
    }

    public function test_all_includes_custom_widgets(): void
    {
        $registry = new WidgetRegistry();
        $custom = $this->stub_widget();
        $registry->register('my_widget', $custom);

        $all = $registry->all();

        $this->assert_has_key('my_widget', $all);
        $this->assert_same($custom, $all['my_widget']);
    }

    public function test_all_widgets_implement_widget_interface(): void
    {
        $registry = new WidgetRegistry();

        foreach ($registry->all() as $type => $widget) {
            $this->assert_instance_of(
                WidgetInterface::class,
                $widget,
                "Widget for type '{$type}' must implement WidgetInterface"
            );
        }
    }
}

exit(WidgetRegistryTest::run_all('Italix Forms — widget registry'));
