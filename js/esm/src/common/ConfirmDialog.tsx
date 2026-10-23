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
 * Accessible confirmation dialogue built on the native <dialog> element
 * (focus trap, Escape and backdrop handled by the browser).
 *
 * @module     mod_hiveforum/common/ConfirmDialog
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {useEffect, useId, useRef} from 'react';
import {useStrings} from '@moodle/lms/mod_hiveforum/common/i18n';

interface ConfirmDialogProps {
    open: boolean;
    title: string;
    message: string;
    confirmLabel: string;
    danger?: boolean;
    onConfirm: () => void;
    onClose: () => void;
}

export default function ConfirmDialog({open, title, message, confirmLabel, danger = false, onConfirm, onClose}: ConfirmDialogProps) {
    const t = useStrings();
    const ref = useRef<HTMLDialogElement>(null);
    const titleId = useId();
    const messageId = useId();

    useEffect(() => {
        const dialog = ref.current;
        if (!dialog) {
            return;
        }
        if (open && !dialog.open) {
            dialog.showModal();
        } else if (!open && dialog.open) {
            dialog.close();
        }
    }, [open]);

    return (
        <dialog
            ref={ref}
            className="hf-dialog"
            aria-labelledby={titleId}
            aria-describedby={messageId}
            onClose={onClose}
            onClick={(e) => {
                // Click on the backdrop closes the dialogue.
                if (e.target === ref.current) {
                    onClose();
                }
            }}
        >
            <div className="hf-dialog__body">
                <h2 id={titleId} className="hf-dialog__title">{title}</h2>
                <p id={messageId} className="hf-dialog__message">{message}</p>
                <div className="hf-dialog__actions">
                    <button type="button" className="btn btn-secondary" onClick={onClose}>{t('cancel')}</button>
                    <button
                        type="button"
                        className={`btn ${danger ? 'btn-danger' : 'btn-primary'}`}
                        onClick={() => {
                            onConfirm();
                            onClose();
                        }}
                        autoFocus
                    >
                        {confirmLabel}
                    </button>
                </div>
            </div>
        </dialog>
    );
}
