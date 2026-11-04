/**
 * File documentation for amd/src/manager.js.
 *
 * @package   local_toastalerts
 */
// This file is part of a 108design source-available software product.
//
// Copyright (C) 2026 Andreas Giesen <andreas@108design.com>
//
// Use and modification are permitted only under the Software License included
// with this distribution. Redistribution and circumvention of licensing
// restrictions are prohibited. See LICENSE.md for the full terms.

/**
 * Apply one semantic presentation policy to Moodle notifications and core toasts.
 *
 * @module     local_toastalerts/manager
 * @copyright  2026 Andreas Giesen <andreas@108design.com>
 * @license    See LICENSE.md for the full terms.
 */

import {add as addCoreToast} from 'core/toast';
import {addNotification as addCoreNotification} from 'core/notification';

const REGION_SELECTOR = '#user-notifications';
const ALERT_SELECTOR = '.alert';
const CORE_TOAST_SELECTOR = '.toast';
const SUCCESS_CLASS = 'alert-success';
const WARNING_CLASS = 'alert-warning';
const ERROR_CLASS = 'alert-danger';
const PERSISTENT_CLASS = 'local-toastalerts-persistent';
const AUTOHIDE_CLASS = 'local-toastalerts-autohide';
const ADOPT_CLASS = 'local-toastalerts-adopt';
const ADOPT_SELECTOR = `.${ADOPT_CLASS}`;
const PROCESSED_CLASS = 'local-toastalerts-managed';
const FLOATING_CLASS = 'local-toastalerts-toast';
const INLINE_CLASS = 'local-toastalerts-inline';
const INLINE_ERROR_CLASS = 'local-toastalerts-inline-error';
const VISIBLE_CLASS = 'local-toastalerts-visible';
const HIDING_CLASS = 'local-toastalerts-hiding';
const TRANSIENT_REGION_CLASS = 'local-toastalerts-transient-region';
const PERSISTENT_REGION_CLASS = 'local-toastalerts-persistent-region';
const CORE_TOAST_CLASS = 'local-toastalerts-core-toast';
const SURFACE_COLOUR_PROPERTY = '--local-toastalerts-site-background';
const DEFAULT_SUCCESS_DELAY = 5000;
const MIN_SUCCESS_DELAY = 2000;
const MAX_SUCCESS_DELAY = 30000;
const STACK_GAP = 16;
const TRANSITION_FALLBACK = 250;

const ICONS = {
    success: 'fa-check-circle',
    info: 'fa-info-circle',
    warning: 'fa-exclamation-circle',
    error: 'fa-times-circle',
};

let initialised = false;
let notificationRegion = null;
let transientRegion = null;
let persistentRegion = null;
let configuration = null;
let regionObserver = null;
let toastObserver = null;
let documentObserver = null;
let layoutObserver = null;
let layoutFrame = 0;
let globalEventsBound = false;
const timerStates = new WeakMap();
const observedLayoutElements = new WeakSet();

/**
 * Preserve the theme's rendered colour so the floating surface can layer it over opaque white.
 * @param {HTMLElement} element Alert or toast before plugin presentation classes are added.
 */
const storeFloatingSurfaceColour = element => {
    const colour = window.getComputedStyle(element).backgroundColor;
    element.style.setProperty(SURFACE_COLOUR_PROPERTY, colour || 'transparent');
};

/**
 * Return an integer success delay within the supported range.
 * @param {Number|String} value Candidate delay.
 */
const normaliseDelay = value => Math.max(
    MIN_SUCCESS_DELAY,
    Math.min(MAX_SUCCESS_DELAY, Number.parseInt(value, 10) || DEFAULT_SUCCESS_DELAY)
);

/**
 * Map Moodle alert and toast classes to the public semantic types.
 * @param {HTMLElement} element Alert or toast element.
 */
const getSeverity = element => {
    const requested = String(element.dataset.localToastalertsType || '').toLowerCase();
    if (requested === 'danger' || requested === 'error') {
        return 'error';
    }
    if (['success', 'info', 'warning'].includes(requested)) {
        return requested;
    }
    if (element.classList.contains(ERROR_CLASS) || element.classList.contains('toast-danger')) {
        return 'error';
    }
    if (element.classList.contains(WARNING_CLASS) || element.classList.contains('toast-warning')) {
        return 'warning';
    }
    if (element.classList.contains(SUCCESS_CLASS) || element.classList.contains('toast-success')) {
        return 'success';
    }
    return 'info';
};

/**
 * Read an explicit lane override from a managed element.
 * @param {HTMLElement} element Managed alert.
 * @returns {String}
 */
const getRequestedMode = element => {
    const mode = String(element.dataset.localToastalertsMode || '').toLowerCase();
    return ['transient', 'persistent', 'inline'].includes(mode) ? mode : '';
};

/**
 * Resolve the optional per-notification auto-hide delay.
 * @param {HTMLElement} element Managed alert.
 * @returns {Number}
 */
const getElementDelay = element => normaliseDelay(
    element.dataset.localToastalertsDelay || configuration?.successDelay
);

/**
 * Determine whether an element currently occupies visible viewport space.
 * @param {HTMLElement} element Candidate element.
 */
const isRendered = element => {
    if (!(element instanceof HTMLElement)) {
        return false;
    }
    const style = window.getComputedStyle(element);
    const rect = element.getBoundingClientRect();
    return style.display !== 'none' && style.visibility !== 'hidden'
        && rect.width > 0 && rect.height > 0;
};

/** Find the bottom edge of every visible fixed top navigation element. */
const getTopNavigationBottom = () => {
    let bottom = 0;
    document.querySelectorAll('.navbar.fixed-top').forEach(navbar => {
        if (!isRendered(navbar)) {
            return;
        }
        const rect = navbar.getBoundingClientRect();
        if (rect.bottom > 0 && rect.top < window.innerHeight) {
            bottom = Math.max(bottom, Math.min(window.innerHeight, rect.bottom));
        }
    });
    return Math.max(0, Math.round(bottom));
};

/** Register visible navigation elements with ResizeObserver. */
const observeLayoutElements = () => {
    if (!layoutObserver) {
        return;
    }
    document.querySelectorAll('.navbar.fixed-top').forEach(element => {
        if (!observedLayoutElements.has(element)) {
            observedLayoutElements.add(element);
            layoutObserver.observe(element);
        }
    });
};

/** Recalculate fixed lane offsets from actual rendered elements. */
const updateLayout = () => {
    layoutFrame = 0;
    observeLayoutElements();
    const top = `${getTopNavigationBottom()}px`;
    [transientRegion, persistentRegion].forEach(region => {
        if (region) {
            region.style.setProperty('--local-toastalerts-top', top);
        }
    });
    if (persistentRegion) {
        persistentRegion.style.setProperty('--local-toastalerts-right', `${STACK_GAP}px`);
    }
};

/** Coalesce layout changes into one animation frame. */
const scheduleLayoutUpdate = () => {
    if (!layoutFrame) {
        layoutFrame = window.requestAnimationFrame(updateLayout);
    }
};

/** Observe native Core toasts added to the shared transient lane. */
const attachToastObserver = () => {
    toastObserver?.disconnect();
    if (!transientRegion) {
        return;
    }
    transientRegion.querySelectorAll(CORE_TOAST_SELECTOR).forEach(processCoreToast);
    toastObserver = new MutationObserver(records => {
        records.forEach(record => record.addedNodes.forEach(node => {
            if (!(node instanceof Element)) {
                return;
            }
            if (node.matches(CORE_TOAST_SELECTOR)) {
                processCoreToast(node);
            }
            node.querySelectorAll(CORE_TOAST_SELECTOR).forEach(processCoreToast);
        }));
        updateRegionState();
    });
    toastObserver.observe(transientRegion, {childList: true, subtree: true});
};

/** Move every body-level Core toast into the one managed global lane. */
const consolidateBodyToastRegions = () => {
    if (!transientRegion) {
        return;
    }
    [...document.body.children].forEach(element => {
        if (!(element instanceof HTMLElement)
                || !element.matches('.toast-wrapper') || element === transientRegion) {
            return;
        }
        [...element.children].reverse().forEach(child => transientRegion.prepend(child));
        element.remove();
    });
};

/** Create or adopt the two global floating notification lanes. */
const ensureFloatingRegions = () => {
    if (!transientRegion?.isConnected) {
        transientRegion = [...document.querySelectorAll('body > .toast-wrapper')][0] || document.createElement('div');
        transientRegion.classList.add('toast-wrapper', TRANSIENT_REGION_CLASS);
        transientRegion.setAttribute('role', 'status');
        transientRegion.setAttribute('aria-live', 'polite');
        transientRegion.setAttribute('aria-relevant', 'additions text');
        if (!transientRegion.isConnected) {
            // Core prepends its global wrapper. Matching that order keeps later modal-local wrappers authoritative.
            document.body.prepend(transientRegion);
        }
        attachToastObserver();
    }
    consolidateBodyToastRegions();

    if (!persistentRegion?.isConnected) {
        persistentRegion = document.createElement('div');
        persistentRegion.className = PERSISTENT_REGION_CLASS;
        persistentRegion.setAttribute('aria-live', 'polite');
        persistentRegion.setAttribute('aria-relevant', 'additions text');
        document.body.append(persistentRegion);
    }
    scheduleLayoutUpdate();
};

/** Keep lane state classes in sync with their contents. */
const updateRegionState = () => {
    transientRegion?.classList.toggle('local-toastalerts-has-items', Boolean(transientRegion.children.length));
    persistentRegion?.classList.toggle('local-toastalerts-has-items', Boolean(persistentRegion.children.length));
};

/**
 * Remove a managed alert after its exit transition.
 * @param {HTMLElement} alert Managed alert.
 */
const dismiss = alert => {
    if (!(alert instanceof HTMLElement) || alert.classList.contains(HIDING_CLASS)) {
        return;
    }
    const timerState = timerStates.get(alert);
    if (timerState?.timeoutId) {
        window.clearTimeout(timerState.timeoutId);
    }
    alert.classList.remove(VISIBLE_CLASS);
    alert.classList.add(HIDING_CLASS);

    let removed = false;
    const remove = () => {
        if (removed) {
            return;
        }
        removed = true;
        alert.remove();
        updateRegionState();
    };
    alert.addEventListener('transitionend', remove, {once: true});
    window.setTimeout(remove, TRANSITION_FALLBACK);
};

/**
 * Start or resume an alert's remaining auto-hide interval.
 * @param {HTMLElement} alert Managed alert.
 */
const resumeTimer = alert => {
    const state = timerStates.get(alert);
    if (!state || state.timeoutId || state.remaining <= 0 || alert.classList.contains(HIDING_CLASS)) {
        return;
    }
    state.startedAt = performance.now();
    state.timeoutId = window.setTimeout(() => dismiss(alert), state.remaining);
};

/**
 * Pause an alert's remaining auto-hide interval while it is being read.
 * @param {HTMLElement} alert Managed alert.
 */
const pauseTimer = alert => {
    const state = timerStates.get(alert);
    if (!state?.timeoutId) {
        return;
    }
    window.clearTimeout(state.timeoutId);
    state.timeoutId = 0;
    state.remaining = Math.max(0, state.remaining - (performance.now() - state.startedAt));
};

/**
 * Add a version-independent dismiss button when the notification omitted one.
 * @param {HTMLElement} alert Moodle alert.
 */
const prepareDismissButton = alert => {
    let button = alert.querySelector('.btn-close, [data-local-toastalerts-dismiss-source]');
    if (!button) {
        button = document.createElement('button');
        button.type = 'button';
        button.className = 'btn-close local-toastalerts-generated-close';
        button.textContent = '\u00d7';
        button.setAttribute('aria-label', configuration.dismissLabel);
        alert.append(button);
    }
    button.removeAttribute('data-dismiss');
    button.removeAttribute('data-bs-dismiss');
    button.dataset.localToastalertsDismiss = '1';
    if (!button.getAttribute('aria-label')) {
        button.setAttribute('aria-label', configuration.dismissLabel);
    }
    alert.classList.add('alert-dismissible');
};

/**
 * Add a Core-style severity icon while keeping the original message markup intact.
 * @param {HTMLElement} alert Moodle alert.
 * @param {String} severity Normalised severity.
 */
const decorateAlert = (alert, severity) => {
    if (alert.querySelector(':scope > .local-toastalerts-icon')) {
        return;
    }
    const closeButton = [...alert.children].find(child =>
        child.matches('.btn-close, [data-local-toastalerts-dismiss]')) || null;
    const icon = document.createElement('i');
    icon.className = `fa ${ICONS[severity]} icon local-toastalerts-icon`;
    icon.setAttribute('aria-hidden', 'true');

    const message = document.createElement('div');
    message.className = 'local-toastalerts-message';
    [...alert.childNodes].forEach(node => {
        if (node !== closeButton) {
            message.append(node);
        }
    });
    alert.insertBefore(icon, closeButton);
    alert.insertBefore(message, closeButton);
};

/**
 * A success, explicit transient notice or explicit auto-hide notice is transient; errors never are.
 * @param {HTMLElement} alert Moodle alert.
 * @param {String} severity Normalised severity.
 */
const shouldAutohide = (alert, severity) => {
    const mode = getRequestedMode(alert);
    return severity !== 'error' && mode !== 'inline' && mode !== 'persistent'
        && !alert.classList.contains(PERSISTENT_CLASS)
        && (mode === 'transient' || severity === 'success' || alert.classList.contains(AUTOHIDE_CLASS));
};

/**
 * Select a presentation lane from meaning and intended lifetime.
 * @param {HTMLElement} alert Moodle alert.
 * @param {String} severity Normalised severity.
 */
const getTargetRegion = (alert, severity) => {
    if (getRequestedMode(alert) === 'inline' || alert.classList.contains(INLINE_CLASS)) {
        return notificationRegion;
    }
    return shouldAutohide(alert, severity) ? transientRegion : persistentRegion;
};

/**
 * Initialise one Moodle alert without changing its message semantics or ARIA role.
 * @param {HTMLElement} alert Moodle alert.
 */
const processAlert = alert => {
    // The shared setup reminder has persistent site-wide controls and stays in the administration page.
    if (alert?.id === 'design108-setup-reminder') return;
    if (!(alert instanceof HTMLElement) || alert.classList.contains(PROCESSED_CLASS)) {
        return;
    }
    ensureFloatingRegions();
    const severity = getSeverity(alert);
    const autohide = shouldAutohide(alert, severity);
    const target = getTargetRegion(alert, severity);

    if (!alert.classList.contains('alert')) {
        alert.classList.add('alert', `alert-${severity === 'error' ? 'danger' : severity}`);
    }

    if (target !== notificationRegion) {
        storeFloatingSurfaceColour(alert);
    }

    alert.classList.add(PROCESSED_CLASS, `local-toastalerts-${severity}`);
    alert.classList.toggle(FLOATING_CLASS, target !== notificationRegion);
    alert.classList.toggle(INLINE_ERROR_CLASS, severity === 'error' && target === notificationRegion);
    prepareDismissButton(alert);
    decorateAlert(alert, severity);
    if (target && alert.parentElement !== target) {
        target.append(alert);
    }
    if (autohide) {
        timerStates.set(alert, {
            remaining: getElementDelay(alert),
            startedAt: 0,
            timeoutId: 0,
        });
    }
    updateRegionState();
    window.requestAnimationFrame(() => {
        if (!alert.isConnected) {
            return;
        }
        alert.classList.add(VISIBLE_CLASS);
        resumeTimer(alert);
    });
};

/**
 * Process all notifications newly inserted into Moodle's global region.
 * @param {Node} node Newly added node.
 */
const processNode = node => {
    if (!(node instanceof Element)) {
        return;
    }
    if (node.matches(ALERT_SELECTOR)) {
        processAlert(node);
    }
    node.querySelectorAll(ALERT_SELECTOR).forEach(processAlert);
};

/**
 * Process explicit opt-in notifications outside Moodle's canonical region.
 * @param {Node} node Added or changed node.
 */
const processAdoptedNode = node => {
    if (!(node instanceof Element)) {
        return;
    }
    if (node.matches(ADOPT_SELECTOR)) {
        processAlert(node);
    }
    node.querySelectorAll(ADOPT_SELECTOR).forEach(processAlert);
};

/**
 * Copy the rendered content of a persistent Core toast into a managed alert.
 * @param {HTMLElement} toast Core toast.
 */
const convertCoreToast = toast => {
    if (!(toast instanceof HTMLElement) || !toast.isConnected || toast.dataset.localToastalertsConverted) {
        return;
    }
    toast.dataset.localToastalertsConverted = '1';
    const severity = getSeverity(toast);
    const alert = document.createElement('div');
    const alertSeverity = severity === 'error' ? 'danger' : severity;
    alert.className = `alert alert-${alertSeverity} alert-block ${PERSISTENT_CLASS}`;
    if (severity === 'error') {
        alert.setAttribute('role', 'alert');
    }

    const title = toast.querySelector('.toast-title');
    if (title) {
        const heading = document.createElement('strong');
        heading.className = 'local-toastalerts-converted-title';
        [...title.childNodes].forEach(node => heading.append(node));
        alert.append(heading);
    }
    const source = toast.querySelector('.toast-message') || toast.querySelector('.toast-body');
    if (source) {
        [...source.childNodes].forEach(node => alert.append(node));
    }

    // Conversion runs after Core initialises Bootstrap's toast instance. Removing an already initialised
    // node is harmless, while the managed alert retains persistent content and the correct error lane.
    toast.remove();
    processAlert(alert);
};

/**
 * Apply the policy to Core toasts sharing the central transient lane.
 * @param {HTMLElement} toast Core toast.
 */
const processCoreToast = toast => {
    if (!(toast instanceof HTMLElement) || toast.classList.contains(CORE_TOAST_CLASS)) {
        return;
    }
    storeFloatingSurfaceColour(toast);
    toast.classList.add(CORE_TOAST_CLASS);
    const persistent = toast.dataset.bsAutohide === 'false' || toast.dataset.autohide === 'false';
    if (getSeverity(toast) === 'error' || persistent) {
        toast.setAttribute('aria-hidden', 'true');
        window.requestAnimationFrame(() => convertCoreToast(toast));
    }
};

/** Bind delegated interaction once for managed alerts in every lane. */
const bindGlobalEvents = () => {
    if (globalEventsBound) {
        return;
    }
    globalEventsBound = true;
    document.addEventListener('click', event => {
        const button = event.target.closest('[data-local-toastalerts-dismiss]');
        const alert = button?.closest(`${ALERT_SELECTOR}.${PROCESSED_CLASS}`);
        if (!alert) {
            return;
        }
        event.preventDefault();
        event.stopPropagation();
        dismiss(alert);
    }, true);
    document.addEventListener('pointerover', event => {
        const alert = event.target.closest(`${ALERT_SELECTOR}.${PROCESSED_CLASS}`);
        if (alert && !alert.contains(event.relatedTarget)) {
            pauseTimer(alert);
        }
    });
    document.addEventListener('pointerout', event => {
        const alert = event.target.closest(`${ALERT_SELECTOR}.${PROCESSED_CLASS}`);
        if (alert && !alert.contains(event.relatedTarget) && !alert.contains(document.activeElement)) {
            resumeTimer(alert);
        }
    });
    document.addEventListener('focusin', event => pauseTimer(
        event.target.closest(`${ALERT_SELECTOR}.${PROCESSED_CLASS}`)
    ));
    document.addEventListener('focusout', event => {
        const alert = event.target.closest(`${ALERT_SELECTOR}.${PROCESSED_CLASS}`);
        window.requestAnimationFrame(() => {
            if (alert && !alert.contains(document.activeElement) && !alert.matches(':hover')) {
                resumeTimer(alert);
            }
        });
    });
};

/**
 * Attach behaviour once Moodle's canonical global notification region exists.
 * @param {HTMLElement} target Moodle notification region.
 */
const attachNotificationRegion = target => {
    if (!(target instanceof HTMLElement) || target === notificationRegion) {
        return;
    }
    notificationRegion = target;
    notificationRegion.setAttribute('aria-relevant', 'additions text');
    ensureFloatingRegions();
    bindGlobalEvents();
    notificationRegion.querySelectorAll(ALERT_SELECTOR).forEach(processAlert);

    regionObserver?.disconnect();
    regionObserver = new MutationObserver(records => {
        records.forEach(record => record.addedNodes.forEach(processNode));
        updateRegionState();
    });
    regionObserver.observe(notificationRegion, {childList: true, subtree: true});
    updateRegionState();
    scheduleLayoutUpdate();
};

/** Find the canonical notification region now, or report that it is not present yet. */
const discoverNotificationRegion = () => {
    const target = document.querySelector(REGION_SELECTOR);
    if (target) {
        attachNotificationRegion(target);
        return true;
    }
    return false;
};

/**
 * Normalise the public API type names to Moodle's four notification severities.
 * @param {String} type Requested type.
 */
const normaliseType = type => {
    if (type === 'danger' || type === 'error') {
        return 'error';
    }
    return ['success', 'info', 'warning'].includes(type) ? type : 'info';
};

/**
 * Add a notification by meaning rather than by Moodle output channel.
 *
 * @param {String|Promise<String>} message Notification message.
 * @param {Object} options Presentation options.
 * @param {String} [options.type=info] success, info, warning, error or danger.
 * @param {String} [options.mode] transient, persistent or inline. Errors cannot be transient.
 * @param {Number} [options.delay] Transient display duration.
 * @param {Boolean} [options.closeButton] Whether the notification has a close control.
 * @param {Boolean} [options.announce=true] Whether an in-page notification is announced.
 * @param {String} [options.title] Optional title.
 * @returns {Promise}
 */
export const add = async(message, options = {}) => {
    const type = normaliseType(options.type || 'info');
    let mode = options.mode;
    if (!mode) {
        mode = 'persistent';
        if (type === 'success') {
            mode = 'transient';
        }
    }
    if (!['transient', 'persistent', 'inline'].includes(mode)) {
        mode = 'persistent';
    }
    if (type === 'error' && mode === 'transient') {
        mode = 'persistent';
    }

    if (mode === 'transient') {
        return addCoreToast(message, {
            type,
            autohide: true,
            closeButton: options.closeButton ?? false,
            delay: normaliseDelay(options.delay || configuration?.successDelay),
            title: options.title,
        });
    }

    const extraClasses = [
        String(options.extraClasses || '').trim(),
        mode === 'persistent' ? PERSISTENT_CLASS : '',
        mode === 'inline' ? INLINE_CLASS : '',
    ].filter(Boolean).join(' ');
    return addCoreNotification({
        message: await message,
        type,
        closebutton: options.closeButton ?? true,
        announce: options.announce ?? true,
        extraclasses: extraClasses,
        title: options.title,
    });
};

/**
 * Add a transient success notification.
 * @param {String|Promise<String>} message Notification message.
 * @param {Object} options Presentation options.
 */
export const success = (message, options = {}) => add(message, {...options, type: 'success', mode: 'transient'});

/**
 * Add a persistent informational notification.
 * @param {String|Promise<String>} message Notification message.
 * @param {Object} options Presentation options.
 */
export const notice = (message, options = {}) => add(message, {...options, type: 'info', mode: 'persistent'});

/**
 * Add a persistent warning notification.
 * @param {String|Promise<String>} message Notification message.
 * @param {Object} options Presentation options.
 */
export const warning = (message, options = {}) => add(message, {...options, type: 'warning', mode: 'persistent'});

/**
 * Add a persistent error notification to the right stack unless inline is explicitly requested.
 * @param {String|Promise<String>} message Notification message.
 * @param {Object} options Presentation options.
 */
export const error = (message, options = {}) => add(message, {
    ...options,
    type: 'error',
    mode: options.mode || 'persistent',
});

/**
 * Enable the global semantic notification policy.
 * @param {Object} config Runtime configuration.
 */
export const init = config => {
    if (initialised) {
        return;
    }
    initialised = true;
    configuration = {
        successDelay: normaliseDelay(config?.successDelay),
        dismissLabel: String(config?.dismissLabel || 'Dismiss notification'),
    };

    ensureFloatingRegions();
    bindGlobalEvents();
    document.querySelectorAll(ADOPT_SELECTOR).forEach(processAlert);
    if (!discoverNotificationRegion()) {
        const discoveryObserver = new MutationObserver(() => {
            if (discoverNotificationRegion()) {
                discoveryObserver.disconnect();
            }
        });
        discoveryObserver.observe(document.body, {childList: true, subtree: true});
    }

    if (typeof ResizeObserver === 'function') {
        layoutObserver = new ResizeObserver(scheduleLayoutUpdate);
    }
    documentObserver = new MutationObserver(records => {
        records.forEach(record => {
            if (record.type === 'attributes') {
                processAdoptedNode(record.target);
            }
            record.addedNodes.forEach(processAdoptedNode);
        });
        const onlyLaneChanges = records.every(record =>
            transientRegion?.contains(record.target) || persistentRegion?.contains(record.target));
        if (!onlyLaneChanges) {
            if (!notificationRegion?.isConnected) {
                discoverNotificationRegion();
            }
            ensureFloatingRegions();
            scheduleLayoutUpdate();
        }
    });
    documentObserver.observe(document.body, {
        attributes: true,
        childList: true,
        subtree: true,
        attributeFilter: [
            'class',
            'style',
            'hidden',
            'aria-hidden',
            'data-local-toastalerts-mode',
            'data-local-toastalerts-type',
            'data-local-toastalerts-delay',
        ],
    });
    window.addEventListener('resize', scheduleLayoutUpdate, {passive: true});
    scheduleLayoutUpdate();
};
