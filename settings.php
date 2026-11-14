<?php
// This file is part of Moodle - https://moodle.org/
/**
 * File documentation for settings.php.
 *
 * @package   local_toastalerts
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @license   108design source-available software license; see LICENSE.md
 */
// This file is part of a 108design source-available software product.
//
// Copyright (C) 2026 Andreas Giesen <andreas@108design.com>
//
// Use and modification are permitted only under the Software License included
// with this distribution. Redistribution and circumvention of licensing
// restrictions are prohibited. See LICENSE.md for the full terms.

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $ADMIN->add('localplugins', new admin_externalpage(
        'local_toastalerts',
        get_string('pluginname', 'local_toastalerts'),
        new moodle_url('/local/toastalerts/index.php')
    ));
}
