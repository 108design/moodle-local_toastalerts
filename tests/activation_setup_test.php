<?php
/**
 * This file is part of a 108design source-available software product.
 *
 * Use and modification are permitted only under the Software License included
 * with this distribution. See LICENSE.md for the full terms.
 *
 * File documentation for tests/activation_setup_test.php.
 *
 * @package   local_toastalerts
 * @copyright 2026 Andreas Giesen
 * @license   108design source-available software license; see LICENSE.md
 */
// Copyright (C) 2026 Andreas Giesen. See LICENSE.md.
namespace local_toastalerts;

use local_toastalerts\local\activation_setup;

/** Neutral Free setup never classifies the installation or writes licensing state during rendering. */
final class activation_setup_test extends \advanced_testcase {
    public function test_free_setup_and_pending_link_lifecycle(): void {
        $this->resetAfterTest();
        $url = new \moodle_url('/local/toastalerts/activation.php');
        $before = get_config('local_toastalerts');
        $html = activation_setup::render($url, false, false);
        $this->assertStringContainsString('value="start"', $html);
        $this->assertStringNotContainsString('name="environment"', $html);
        $this->assertStringNotContainsString('step-marker-complete', $html);
        $this->assertEquals($before, get_config('local_toastalerts'));
        set_config('sitelinkcode', 'TESTCODE', 'local_toastalerts');
        set_config('sitelinkurl', 'https://example.invalid/approval', 'local_toastalerts');
        set_config('sitelinkexpires', time() + 600, 'local_toastalerts');
        $html = activation_setup::render($url, false, false);
        $this->assertTrue(activation_setup::pending());
        $this->assertStringContainsString('value="poll"', $html);
        $this->assertStringNotContainsString('step-marker-complete', $html);
        $this->assertStringNotContainsString('TESTCODE', $html);
        set_config('sitelinkexpires', time() - 1, 'local_toastalerts');
        $html = activation_setup::render($url, false, false);
        $this->assertFalse(activation_setup::pending());
        $this->assertStringContainsString('value="start"', $html);
        $this->assertStringNotContainsString('value="poll"', $html);
        $html = activation_setup::render($url, true, true);
        $this->assertSame(2, substr_count($html, 'step-marker-complete'));
        $this->assertStringNotContainsString('value="start"', $html);
        $this->assertStringNotContainsString('value="poll"', $html);
    }
}
