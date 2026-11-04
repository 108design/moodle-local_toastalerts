<?php
// This file is part of a 108design source-available software product.
//
// Copyright (C) 2026 Andreas Giesen <andreas@108design.com>
//
// Use and modification are permitted only under the Software License included
// with this distribution. Redistribution and circumvention of licensing
// restrictions are prohibited. See LICENSE.md for the full terms.

namespace local_toastalerts\local;

use core\hook\output\before_http_headers;

/**
 * Output hook callbacks for the global notification stack.
 *
 * @package    local_toastalerts
 * @copyright  2026 Andreas Giesen <andreas@108design.com>
 * @license    See LICENSE.md for the full terms.
 */
final class hook_callbacks {
    /** Current plugin version, used to invalidate the explicitly linked static stylesheet. */
    private const PLUGIN_VERSION = 2026010100;

    /** Minimum supported success timeout in milliseconds. */
    private const MIN_SUCCESS_TIMEOUT = 2000;

    /** Maximum supported success timeout in milliseconds. */
    private const MAX_SUCCESS_TIMEOUT = 30000;

    /** Default success timeout in milliseconds. */
    private const DEFAULT_SUCCESS_TIMEOUT = 5000;

    /**
     * Enable the fixed stack before body attributes and page requirements are finalised.
     *
     * @param before_http_headers $hook The Moodle output hook.
     */
    public static function before_http_headers(before_http_headers $hook): void {
        global $PAGE;

        if (during_initial_install() || (defined('CLI_SCRIPT') && CLI_SCRIPT)
                || !get_config('local_toastalerts', 'version')) {
            return;
        }
        $available = \local_toastalerts\local\licensing\gate::allows();
        if (!$available) {
            return;
        }

        $timeout = (int) get_config('local_toastalerts', 'successtimeout');
        if ($timeout <= 0) {
            $timeout = self::DEFAULT_SUCCESS_TIMEOUT;
        }
        $timeout = max(self::MIN_SUCCESS_TIMEOUT, min(self::MAX_SUCCESS_TIMEOUT, $timeout));

        $PAGE->add_body_class('local-toastalerts-enabled');
        $PAGE->requires->css(new \moodle_url('/local/toastalerts/styles.css', [
            'v' => self::PLUGIN_VERSION,
        ]));
        $PAGE->requires->js_call_amd('local_toastalerts/manager', 'init', [[
            'successDelay' => $timeout,
            'dismissLabel' => get_string('dismissnotification', 'core'),
        ]]);
    }
}
