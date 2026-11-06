<?php
/**
 * This file is part of a 108design source-available software product.
 *
 * Use and modification are permitted only under the Software License included
 * with this distribution. See LICENSE.md for the full terms.
 *
 * File documentation for lang/en/local_toastalerts.php.
 *
 * @package   local_toastalerts
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @license   108design source-available software license; see LICENSE.md
 */

$string['pluginname'] = 'Toast alerts';
$string['privacy:metadata'] = 'The Toast alerts plugin does not store personal data.';
$string['successtimeout'] = 'Success notification duration';
$string['successtimeout_desc'] = 'Time in milliseconds before a global success notification is dismissed automatically. Values are limited to between 2000 and 30000 milliseconds.';

$string['successtimeoutinvalid'] = 'Enter a value from 2000 to 30000 milliseconds.';

$string['adminhelp'] = 'Administration help';
$string['adminhelpmore'] = 'More in the help panel';
$string['adminhelptopics'] = 'Help Topics';
$string['adminhelp_overview'] = 'Displaying notices';
$string['adminhelp_overview_article'] = '<p>Choose how Moodle displays notifications. Brief success notices may disappear automatically; warnings and errors must remain readable.</p><p>These settings control presentation, not whether the reported action succeeds. Notices embedded in forms and pages retain their context.</p>';

$string['testsuccess'] = 'Toast Alerts test: this success notification disappears automatically.';
$string['testnotice'] = 'Toast Alerts test: this information stays visible until you close it.';
$string['testrequiresjs'] = 'JavaScript is required to show test notifications.';
$string['successtimeout_help'] = 'Duration in milliseconds for automatically dismissed success notifications, from 2000 to 30000. Save changes before testing a different duration. Hovering or focusing a notification pauses its timer.';
$string['test'] = 'Test Notifications';
$string['test_help'] = 'Test a temporary success notification or a persistent information notice in your browser. No other users receive a message and no settings are changed.';
$string['adminhelp_test'] = 'Testing notifications';
$string['adminhelp_test_article'] = '<p>Use <strong>Test Success Notification</strong> or <strong>Test Information Notice</strong> in <strong>Settings</strong>. Test one example at a time. The success example appears at the top centre and disappears after the saved duration. The information example stays in the right-hand stack until you close it.</p><p>You can pause the success timer by hovering over or focusing its close button. Save a changed duration before testing again. These examples are local to your current browser page; no email, Moodle message or notification to another user is sent.</p>';
$string['testbuttonsuccess'] = 'Test Success Notification';
$string['testbuttonnotice'] = 'Test Information Notice';
