<?php
/**
 * File documentation for index.php.
 *
 * @package   local_toastalerts
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @license   108design source-available software license; see LICENSE.md
 */
// This file is part of a 108design source-available software product.
// Copyright (C) 2026 Andreas Giesen <andreas@108design.com>

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

require_login();
require_capability('moodle/site:config', context_system::instance());
admin_externalpage_setup('local_toastalerts');

$url = new moodle_url('/local/toastalerts/index.php');
$PAGE->set_url($url);
$PAGE->set_title(get_string('pluginname', 'local_toastalerts'));
$PAGE->set_heading(get_string('pluginname', 'local_toastalerts'));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_sesskey();
}
$form = new \local_toastalerts\form\settings_form();
$form->set_data([
    'successtimeout' => (int) (get_config('local_toastalerts', 'successtimeout') ?: 5000),
]);

if ($data = $form->get_data()) {
    set_config('successtimeout', (int) $data->successtimeout, 'local_toastalerts');
    redirect($url, get_string('changessaved'), null, \core\output\notification::NOTIFY_SUCCESS);
}
$PAGE->requires->js_call_amd('local_toastalerts/settings', 'init', [[
    'success' => get_string('testsuccess', 'local_toastalerts'),
    'notice' => get_string('testnotice', 'local_toastalerts'),
]]);

echo $OUTPUT->header();
echo \local_toastalerts\local\admin_navigation::render('settings');
echo $OUTPUT->heading(get_string('pluginname', 'local_toastalerts'), 2);
$form->display();
echo html_writer::start_div('mt-4');
foreach (['success' => 'local-toastalerts-test', 'notice' => 'local-toastalerts-test-info'] as $type => $id) {
    echo html_writer::tag('button', get_string('testbutton' . $type, 'local_toastalerts'), [
        'type' => 'button', 'class' => 'btn btn-secondary me-2 mr-2 mb-2', 'id' => $id,
        'disabled' => 'disabled', 'data-toastalerts-test' => $type,
    ]);
}
echo ' ' . \local_toastalerts\local\admin_help::icon('test');
echo html_writer::tag('noscript', get_string('testrequiresjs', 'local_toastalerts'), ['class' => 'd-block mt-2']);
echo html_writer::end_div();
echo $OUTPUT->footer();
