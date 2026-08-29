<?php
/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at https://mozilla.org/MPL/2.0/.
 */

declare(strict_types=1);

namespace Italix\Forms\Rendering;

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

/**
 * Registry that maps field types to widget instances.
 *
 * Comes pre-loaded with default widgets for all common input types.
 * Register custom widgets to override defaults or add new types.
 *
 * Example:
 *
 *     $registry = new WidgetRegistry();
 *     $registry->register('date', new MyDatePickerWidget());
 *     $registry->register('wysiwyg', new WysiwygWidget());
 */
class WidgetRegistry
{
    /** @var array<string, WidgetInterface> */
    private $widgets = [];

    public function __construct()
    {
        $this->register_defaults();
    }

    /**
     * Register a widget for a field type.
     *
     * @param string $type  The input type name (e.g., 'text', 'select', 'date')
     * @param WidgetInterface $widget  The widget instance
     * @return self
     */
    public function register(string $type, WidgetInterface $widget): self
    {
        $this->widgets[$type] = $widget;
        return $this;
    }

    /**
     * Get the widget for a field type.
     *
     * Falls back to the text widget if no widget is registered for the type.
     *
     * @param string $type
     * @return WidgetInterface
     */
    public function get(string $type): WidgetInterface
    {
        return $this->widgets[$type] ?? $this->widgets['text'];
    }

    /**
     * Check if a widget is registered for a type.
     *
     * @param string $type
     * @return bool
     */
    public function has(string $type): bool
    {
        return isset($this->widgets[$type]);
    }

    /**
     * Get all registered type => widget mappings.
     *
     * @return array<string, WidgetInterface>
     */
    public function all(): array
    {
        return $this->widgets;
    }

    /**
     * Register the default widgets for common input types.
     */
    private function register_defaults(): void
    {
        $text = new TextWidget();
        $this->widgets['text'] = $text;
        $this->widgets['email'] = $text;
        $this->widgets['url'] = $text;
        $this->widgets['tel'] = $text;
        $this->widgets['search'] = $text;
        $this->widgets['color'] = $text;

        $this->widgets['textarea'] = new TextareaWidget();
        $this->widgets['select'] = new SelectWidget();
        $this->widgets['checkbox'] = new CheckboxWidget();
        $this->widgets['radio'] = new RadioWidget();
        $this->widgets['password'] = new PasswordWidget();

        $number = new NumberWidget();
        $this->widgets['number'] = $number;
        $this->widgets['range'] = $number;

        $date = new DateWidget();
        $this->widgets['date'] = $date;
        $this->widgets['datetime-local'] = $date;
        $this->widgets['time'] = $date;
        $this->widgets['month'] = $date;
        $this->widgets['week'] = $date;

        $this->widgets['file'] = new FileWidget();
        $this->widgets['hidden'] = new HiddenWidget();
        $this->widgets['readonly'] = new ReadonlyWidget();
    }
}
