<?php
// This file is part of a 108design source-available software product.
//
// Copyright (C) 2026 Andreas Giesen <andreas@108design.com>
//
// Use and modification are permitted only under the Software License included
// with this distribution. Redistribution and circumvention of licensing
// restrictions are prohibited. See LICENSE.md for the full terms.

namespace local_toastalerts\privacy;

/**
 * Privacy provider for a plugin which stores no user data.
 *
 * @package    local_toastalerts
 * @copyright  2026 Andreas Giesen <andreas@108design.com>
 * @license    See LICENSE.md for the full terms.
 */
final class provider implements \core_privacy\local\metadata\null_provider {
    /**
     * Explain why no personal data is stored.
     *
     * @return string
     */
    public static function get_reason(): string {
        return 'privacy:metadata';
    }
}
