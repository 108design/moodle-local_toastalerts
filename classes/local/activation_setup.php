<?php
/**
 * This file is part of a 108design source-available software product.
 *
 * Use and modification are permitted only under the Software License included
 * with this distribution. See LICENSE.md for the full terms.
 *
 * File documentation for classes/local/activation_setup.php.
 *
 * @package   local_toastalerts
 * @copyright 2026 Andreas Giesen
 * @license   108design source-available software license; see LICENSE.md
 */
// Copyright (C) 2026 Andreas Giesen. See LICENSE.md.
namespace local_toastalerts\local;

defined('MOODLE_INTERNAL') || die();

/** Standalone setup presentation; signed activation remains owned by license_state. */
final class activation_setup {
    public static function pending(): bool {
        return (string) get_config('local_toastalerts', 'sitelinkcode') !== ''
            && (string) get_config('local_toastalerts', 'sitelinkurl') !== ''
            && (int) get_config('local_toastalerts', 'sitelinkexpires') >= time();
    }

    /** Render the same step marker, cells and controls as Plugin Manager, without a dependency. */
    public static function render(\moodle_url $url, bool $active, bool $connected): string {
        $pending = self::pending();
        $table = new \html_table();
        $table->attributes['class'] = 'generaltable table table-bordered w-auto mb-4 local-toastalerts-setup-table';
        $table->head = [get_string('setupprogress', 'local_toastalerts'), get_string('actions')];
        $connectionaction = '';
        $details = '';
        if (!$active && !$connected) {
            if ($pending) {
                $connectionaction = self::action($url, 'poll',
                    get_string('setupfinish', 'local_toastalerts'), 'btn btn-primary btn-sm');
                $details = \html_writer::link((string) get_config('local_toastalerts', 'sitelinkurl'),
                    get_string('setupcontinueconnection', 'local_toastalerts'));
            } else {
                $connectionaction = self::action($url, 'start',
                    get_string('connectaccount', 'local_toastalerts'), 'btn btn-primary btn-sm');
            }
        }
        $connectionkey = $connected ? 'setupaccountconnected'
            : ($active ? 'setupaccountoptional' : ($pending ? 'setupaccountpending' : 'setupaccountmissing'));
        $table->data[] = self::row($connected || $active ? 'complete'
            : 'current',
            get_string($connectionkey, 'local_toastalerts'), $connectionaction, 1, $details);
        $activationaction = !$active && $connected
            ? self::action($url, $pending ? 'poll' : 'start', get_string('setupactivate', 'local_toastalerts'),
                'btn btn-primary btn-sm') : '';
        $table->data[] = self::row($active ? 'complete' : ($activationaction === '' ? 'pending' : 'current'),
            get_string($active ? 'setuplicensecomplete' : 'setuplicensewaiting', 'local_toastalerts'),
            $activationaction, 2);
        return \html_writer::div(\html_writer::table($table), 'table-responsive local-toastalerts-setup');
    }

    /** Each transition has its own CSRF-protected POST form. */
    private static function action(\moodle_url $url, string $action, string $label,
            string $class = 'btn btn-secondary btn-sm'): string {
        $html = \html_writer::start_tag('form', ['method' => 'post', 'action' => $url->out(false), 'class' => 'm-0']);
        foreach (['sesskey' => sesskey(), 'action' => $action] as $name => $value) {
            $html .= \html_writer::empty_tag('input', ['type' => 'hidden', 'name' => $name, 'value' => $value]);
        }
        return $html . \html_writer::tag('button', $label, ['type' => 'submit', 'class' => $class])
            . \html_writer::end_tag('form');
    }

    /** Keep the Plugin Manager's exact status-cell structure and semantic classes. */
    private static function row(string $state, string $text, string $action, int $step, string $details = ''): array {
        $colour = match ($state) {
            'complete' => 'text-success',
            'current' => 'text-primary',
            default => 'text-muted',
        };
        $marker = \html_writer::span($state === 'complete' ? '&#10003;' : (string) $step,
            'local-toastalerts-step-marker local-toastalerts-step-marker-' . $state, ['aria-hidden' => 'true']);
        $body = \html_writer::span(\html_writer::span(
            get_string('stepstate' . $state, 'local_toastalerts') . ': ', 'sr-only') . s($text),
            'local-toastalerts-step-text ' . $colour . ' font-weight-bold');
        $content = \html_writer::div($marker . $body, 'local-toastalerts-step');
        if ($details !== '') {
            $content .= \html_writer::div($details, 'local-toastalerts-step-details mt-3');
        }
        $status = new \html_table_cell($content);
        $status->attributes['class'] = 'bg-white text-left';
        $actioncell = new \html_table_cell($action);
        $actioncell->attributes['class'] = 'bg-white text-center';
        return [$status, $actioncell];
    }
}
