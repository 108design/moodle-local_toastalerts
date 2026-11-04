<?php
// This file is part of a 108design source-available software product.
//
// Copyright (C) 2026 Andreas Giesen <andreas@108design.com>
//
// Use and modification are permitted only under the Software License included
// with this distribution. Redistribution and circumvention of licensing
// restrictions are prohibited. See LICENSE.md for the full terms.

namespace local_toastalerts;

/**
 * Static regression checks for the global Moodle 4.5-5.2 notification contract.
 *
 * @package    local_toastalerts
 * @copyright  2026 Andreas Giesen <andreas@108design.com>
 * @license    See LICENSE.md for the full terms.
 */
final class compatibility_test extends \PHPUnit\Framework\TestCase {
    /** The plugin must initialise before body attributes are rendered. */
    public function test_output_hook_enables_global_manager_before_headers(): void {
        $hooks = file_get_contents(__DIR__ . '/../db/hooks.php');
        $callback = file_get_contents(__DIR__ . '/../classes/local/hook_callbacks.php');

        $this->assertStringContainsString('before_http_headers::class', $hooks);
        $this->assertStringContainsString("add_body_class('local-toastalerts-enabled')", $callback);
        $this->assertStringContainsString("requires->css(new \\moodle_url('/local/toastalerts/styles.css'", $callback);
        $this->assertStringContainsString("'local_toastalerts/manager', 'init'", $callback);
    }

    /** Floating lanes are plugin-owned while only explicit contextual notices remain inline. */
    public function test_styles_define_semantic_notification_lanes(): void {
        $styles = file_get_contents(__DIR__ . '/../styles.css');

        $this->assertStringContainsString('.local-toastalerts-transient-region', $styles);
        $this->assertStringContainsString('.local-toastalerts-persistent-region', $styles);
        $this->assertStringContainsString('#user-notifications .local-toastalerts-inline-error', $styles);
        $this->assertStringContainsString('.local-toastalerts-adopt.local-toastalerts-managed', $styles);
        $this->assertStringContainsString('position: fixed', $styles);
        $this->assertStringContainsString('scrollbar-width: none', $styles);
        $this->assertStringContainsString('.local-toastalerts-transient-region::-webkit-scrollbar', $styles);
        $this->assertStringContainsString('.toast-success .toast-body::before', $styles);
        $this->assertStringContainsString('--local-toastalerts-site-background', $styles);
        $this->assertStringContainsString('background-color: #fff !important', $styles);
        $this->assertStringNotContainsString('scrollbar-gutter', $styles);
        $this->assertStringNotContainsString("\n.alert {", $styles);
    }

    /** Severity defaults, Core toast adoption and explicit overrides must remain visible in source. */
    public function test_semantic_policy_and_public_api_contract(): void {
        $manager = file_get_contents(__DIR__ . '/../amd/src/manager.js');
        $build = file_get_contents(__DIR__ . '/../amd/build/manager.min.js');

        $this->assertStringContainsString("const REGION_SELECTOR = '#user-notifications'", $manager);
        $this->assertStringContainsString("const TRANSIENT_REGION_CLASS = 'local-toastalerts-transient-region'", $manager);
        $this->assertStringContainsString("const PERSISTENT_REGION_CLASS = 'local-toastalerts-persistent-region'", $manager);
        $this->assertStringContainsString("const SUCCESS_CLASS = 'alert-success'", $manager);
        $this->assertStringContainsString("const ERROR_CLASS = 'alert-danger'", $manager);
        $this->assertStringContainsString("const PERSISTENT_CLASS = 'local-toastalerts-persistent'", $manager);
        $this->assertStringContainsString("const AUTOHIDE_CLASS = 'local-toastalerts-autohide'", $manager);
        $this->assertStringContainsString("const ADOPT_CLASS = 'local-toastalerts-adopt'", $manager);
        $this->assertStringContainsString("getRequestedMode(alert) === 'inline'", $manager);
        $this->assertStringContainsString("toast.dataset.autohide === 'false'", $manager);
        $this->assertStringContainsString('consolidateBodyToastRegions', $manager);
        $this->assertStringContainsString('storeFloatingSurfaceColour(alert)', $manager);
        $this->assertStringContainsString('storeFloatingSurfaceColour(toast)', $manager);
        $this->assertStringContainsString("import {add as addCoreToast} from 'core/toast'", $manager);
        $this->assertStringContainsString('export const success =', $manager);
        $this->assertStringContainsString('export const notice =', $manager);
        $this->assertStringContainsString('export const warning =', $manager);
        $this->assertStringContainsString('export const error =', $manager);
        $this->assertStringContainsString("type === 'error' && mode === 'transient'", $manager);
        $this->assertStringContainsString("mode: options.mode || 'persistent'", $manager);
        $this->assertStringContainsString('new MutationObserver', $manager);
        $this->assertStringContainsString("removeAttribute('data-dismiss')", $manager);
        $this->assertStringContainsString("removeAttribute('data-bs-dismiss')", $manager);
        $this->assertStringContainsString('define("local_toastalerts/manager"', $build);
        $this->assertStringContainsString('local-toastalerts-transient-region', $build);
        // The export object identifier may be shortened by the canonical minifier.
        $this->assertMatchesRegularExpression('/[\w$]+\.success\s*=/', $build);
        $this->assertMatchesRegularExpression('/[\w$]+\.error\s*=/', $build);
    }

}
