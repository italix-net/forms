<?php
/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at https://mozilla.org/MPL/2.0/.
 */

declare(strict_types=1);

namespace Italix\Forms\Rendering;

use Italix\Forms\FieldMeta;

/**
 * Interface for form field widgets.
 *
 * Each widget knows how to render a specific input type in both
 * edit mode (interactive input) and view mode (read-only display).
 */
interface WidgetInterface
{
    /**
     * Render the field as an editable input.
     *
     * @param FieldMeta $field  The field metadata
     * @param mixed     $value  The current field value
     * @param array     $attrs  Extra HTML attributes to merge
     * @return string HTML output
     */
    public function render_edit(FieldMeta $field, $value, array $attrs = []): string;

    /**
     * Render the field as a read-only view.
     *
     * @param FieldMeta $field  The field metadata
     * @param mixed     $value  The current field value
     * @param array     $attrs  Extra HTML attributes to merge
     * @return string HTML output
     */
    public function render_view(FieldMeta $field, $value, array $attrs = []): string;
}
