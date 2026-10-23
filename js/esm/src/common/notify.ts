// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Feedback through Moodle's own toasts and exception dialogue (AMD modules), so the
 * plugin looks and behaves like the rest of the site.
 *
 * @module     mod_hiveforum/common/notify
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {requireAsync} from '@moodle/lms/core/amd';

interface ToastModule {
    add: (message: string, options?: {type?: string; closeButton?: boolean}) => void;
}

interface NotificationModule {
    exception: (error: unknown) => void;
}

interface FilterEventsModule {
    notifyFilterContentUpdated: (nodes: Element[]) => void;
}

/** Show a toast. */
export const toast = (message: string, type: 'success' | 'info' | 'warning' | 'danger' = 'success'): void => {
    requireAsync<ToastModule>('core/toast')
        .then((module) => module.add(message, {type}))
        .catch(() => undefined);
};

/** Show a web service error with the standard Moodle dialogue. */
export const exception = (error: unknown): void => {
    requireAsync<NotificationModule>('core/notification')
        .then((module) => module.exception(error))
        .catch(() => window.console.error(error));
};

/** Let JavaScript filters (MathJax, media players...) process new content. */
export const filterContentUpdated = (node: Element | null): void => {
    if (!node) {
        return;
    }
    requireAsync<FilterEventsModule>('core_filters/events')
        .then((module) => module.notifyFilterContentUpdated([node]))
        .catch(() => undefined);
};
