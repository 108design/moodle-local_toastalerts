<?php
/**
 * This file is part of a 108design source-available software product.
 *
 * Use and modification are permitted only under the Software License included
 * with this distribution. See LICENSE.md for the full terms.
 *
 * File documentation for tests/setup_configuration_test.php.
 *
 * @package   local_toastalerts
 * @copyright 2026 Andreas Giesen
 * @license   108design source-available software license; see LICENSE.md
 */
// Copyright (C) 2026 Andreas Giesen. See LICENSE.md.
namespace local_toastalerts;

use local_toastalerts\local\setup;

/** Native local configuration, permission/CSRF and administration-page boundaries. */
final class setup_configuration_test extends \advanced_testcase {
    private array $oldpost;

    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
        $this->setAdminUser();
        $this->oldpost = $_POST;
        $_POST = ['sesskey' => sesskey()];
        set_config('version', 2026101615, 'local_toastalerts');
        global $PAGE;
        $PAGE->set_context(\context_system::instance());
        $PAGE->set_url('/admin/search.php');
    }

    protected function tearDown(): void {
        $_POST = $this->oldpost;
        parent::tearDown();
    }

    public function test_reminder_choices_preserve_settings_and_activation_state(): void {
        set_config('successtimeout', 6000, 'local_toastalerts');
        set_config('assertion', 'unchanged-activation-record', 'local_toastalerts');
        $before = time();
        setup::set_reminder('later');
        $until = \local_toastalerts\local\onboarding::preference()['until'];
        $this->assertGreaterThanOrEqual($before + 7 * DAYSECS, $until);
        $this->assertLessThanOrEqual(time() + 7 * DAYSECS, $until);
        $this->assertFalse(setup::should_remind(false));
        setup::set_reminder('hide');
        $this->assertSame(-1, \local_toastalerts\local\onboarding::preference()['until']);
        $this->assertFalse(setup::should_remind(false));
        setup::set_reminder('show');
        $this->assertTrue(setup::should_remind(false));
        $this->assertSame('6000', get_config('local_toastalerts', 'successtimeout'));
        $this->assertSame('unchanged-activation-record', get_config('local_toastalerts', 'assertion'));
    }

    public function test_active_or_uninstalled_plugins_and_unrelated_pages_do_not_prompt(): void {
        global $PAGE, $CFG;
        $this->assertTrue(setup::should_remind(false));
        $this->assertFalse(setup::should_remind(true));
        $PAGE->set_url('/course/view.php', ['id' => 2]);
        $this->assertFalse(setup::should_remind(false));
        $PAGE->set_url('/local/toastalerts/index.php');
        $this->assertFalse(setup::should_remind(false));
        $PAGE->set_url('/' . $CFG->admin . '/plugins.php');
        $this->assertTrue(setup::should_remind(false));
        unset_config('version', 'local_toastalerts');
        $this->assertFalse(setup::should_remind(false));
    }

    public function test_regular_users_and_guests_do_not_see_a_reminder(): void {
        $this->setUser($this->getDataGenerator()->create_user());
        $this->assertFalse(setup::should_remind(false));
        $this->setGuestUser();
        $this->assertFalse(setup::should_remind(false));
    }

    public function test_non_admin_cannot_change_reminder(): void {
        $this->setUser($this->getDataGenerator()->create_user());
        $this->expectException(\required_capability_exception::class);
        setup::set_reminder('hide');
    }

    public function test_invalid_csrf_does_not_change_reminder(): void {
        $_POST = ['sesskey' => 'invalid'];
        try {
            setup::set_reminder('hide');
            $this->fail('Invalid CSRF token accepted.');
        } catch (\moodle_exception $exception) {
            $this->assertSame('invalidsesskey', $exception->errorcode);
            $this->assertFalse(get_config('local_toastalerts', setup::REMINDER_SETTING));
        }
    }

    public function test_unknown_reminder_action_does_not_change_config(): void {
        try {
            setup::set_reminder('activate');
            $this->fail('Unknown reminder action accepted.');
        } catch (\invalid_parameter_exception $exception) {
            $this->assertFalse(get_config('local_toastalerts', setup::REMINDER_SETTING));
        }
    }

    public function test_suppressed_reminder_keeps_activation_and_reenable_actions_visible(): void {
        global $PAGE;
        $PAGE->set_url('/local/toastalerts/index.php');
        setup::set_reminder('hide');
        $html = setup::status(false);
        $this->assertStringContainsString(setup::activation_url()->out(), $html);
        $this->assertStringContainsString(get_string('setuprequired', 'local_toastalerts'), $html);
        $this->assertStringContainsString('value="show"', $html);
        $this->assertStringNotContainsString('value="later"', $html);
        $this->assertStringContainsString('toastalertshelp=activation', $html);
        $active = setup::status(true);
        $this->assertStringContainsString(get_string('setupready', 'local_toastalerts'), $active);
        $this->assertStringNotContainsString('setupaction', $active);
    }
}
