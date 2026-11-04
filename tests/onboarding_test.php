<?php
/**
 * This file is part of a 108design source-available software product.
 *
 * Use and modification are permitted only under the Software License included
 * with this distribution. See LICENSE.md for the full terms.
 *
 * File documentation for tests/onboarding_test.php.
 *
 * @package   local_toastalerts
 * @copyright 2026 Andreas Giesen
 * @license   108design source-available software license; see LICENSE.md
 */
// Copyright (C) 2026 Andreas Giesen. See LICENSE.md.
namespace local_toastalerts;

use local_toastalerts\local\onboarding;

/** Shared reminder boundaries are independent of database, versions and rendering. */
final class onboarding_test extends \PHPUnit\Framework\TestCase {
    public function test_completed_set_never_prompts(): void {
        $this->assertFalse(onboarding::reminder_due([], [], 0, 1000));
        $this->assertFalse(onboarding::reminder_due([], ['local_toastalerts'], -1, 1000));
    }

    public function test_site_wide_hide_covers_known_products_and_remaining_subsets(): void {
        $known = ['local_toastalerts', 'auth_magiclink'];
        $this->assertFalse(onboarding::reminder_due($known, $known, -1, 1000));
        $this->assertFalse(onboarding::reminder_due(['auth_magiclink'], $known, -1, 1000));
    }

    public function test_new_pending_component_resurfaces_once_without_a_version_dependency(): void {
        $pending = ['local_toastalerts', 'auth_magiclink'];
        $this->assertTrue(onboarding::reminder_due($pending, ['local_toastalerts'], -1, 1000));
        $this->assertFalse(onboarding::reminder_due($pending, $pending, -1, 1000));
        $this->assertFalse(onboarding::reminder_due(array_reverse($pending), $pending, -1, 1000));
    }

    public function test_snooze_boundary_and_reenable(): void {
        $pending = ['local_toastalerts'];
        $this->assertFalse(onboarding::reminder_due($pending, $pending, 1001, 1000));
        $this->assertTrue(onboarding::reminder_due($pending, $pending, 1000, 1000));
        $this->assertTrue(onboarding::reminder_due($pending, $pending, 999, 1000));
        $this->assertTrue(onboarding::reminder_due($pending, $pending, 0, 1000));
    }
}
