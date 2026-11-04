<?php
/**
 * File documentation for classes/form/settings_form.php.
 *
 * @package   local_toastalerts
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @license   108design source-available software license; see LICENSE.md
 */
// This file is part of a 108design source-available software product.
// Copyright (C) 2026 Andreas Giesen <andreas@108design.com>

namespace local_toastalerts\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/** Settings form for Toast Alerts. */
final class settings_form extends \moodleform {
    /** Define the form. */
    protected function definition(): void {
        $mform = $this->_form;
        $mform->addElement('text', 'successtimeout', get_string('successtimeout', 'local_toastalerts'));
        $mform->setType('successtimeout', PARAM_INT);
        $mform->addRule('successtimeout', null, 'required', null, 'client');
        $mform->addRule('successtimeout', null, 'numeric', null, 'client');
        $mform->addHelpButton('successtimeout', 'successtimeout', 'local_toastalerts');
        $this->add_action_buttons(false);
    }

    /** Validate the supported timeout range. */
    public function validation($data, $files): array {
        $errors = parent::validation($data, $files);
        $timeout = (int) ($data['successtimeout'] ?? 0);
        if ($timeout < 2000 || $timeout > 30000) {
            $errors['successtimeout'] = get_string('successtimeoutinvalid', 'local_toastalerts');
        }
        return $errors;
    }
}
