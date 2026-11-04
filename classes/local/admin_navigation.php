<?php
/**
 * File documentation for classes/local/admin_navigation.php.
 *
 * @package   local_toastalerts
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @license   108design source-available software license; see LICENSE.md
 */
// This file is part of a 108design source-available software product.
// Copyright (C) 2026 Andreas Giesen <andreas@108design.com>

namespace local_toastalerts\local;

defined('MOODLE_INTERNAL') || die();

/** Native settings navigation. */
final class admin_navigation {
    public static function render(string $active): string {
        $settingslabel = get_string('settings');
        $tabs = [
            new \tabobject('settings', new \moodle_url('/local/toastalerts/index.php'),
                self::tab_label('fa-gear', $settingslabel), $settingslabel),
        ];
        return \html_writer::div(print_tabs([$tabs], $active, null, null, true));
    }

    private static function tab_label(string $icon, string $label): string {
        return \html_writer::tag('i', '', ['class' => 'fa ' . $icon . ' icon', 'aria-hidden' => 'true'])
            . ' ' . s($label);
    }
}
