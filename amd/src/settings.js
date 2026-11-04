/**
 * This file is part of a 108design source-available software product.
 *
 * Use and modification are permitted only under the Software License included
 * with this distribution. See LICENSE.md for the full terms.
 *
 * File documentation for amd/src/settings.js.
 *
 * @package   local_toastalerts
 * @copyright 2026 Andreas Giesen
 * @license   108design source-available software license; see LICENSE.md
 */
// Copyright (C) 2026 Andreas Giesen. See LICENSE.md.

/** @module local_toastalerts/settings */
import {success, notice} from 'local_toastalerts/manager';
import Notification from 'core/notification';

/** Bind the explicitly requested, local-only presentation test on the activated settings page. */
export const init = messages => {
    document.querySelectorAll('[data-toastalerts-test]').forEach(button => {
        if (button.dataset.toastalertsTestBound) {
            return;
        }
        button.dataset.toastalertsTestBound = '1';
        button.disabled = false;
        button.addEventListener('click', async() => {
            button.disabled = true;
            try {
                if (button.dataset.toastalertsTest === 'success') {
                    await success(messages.success, {closeButton: true});
                } else {
                    await notice(messages.notice);
                }
            } catch (error) {
                Notification.exception(error);
            } finally {
                button.disabled = false;
            }
        });
    });
};
