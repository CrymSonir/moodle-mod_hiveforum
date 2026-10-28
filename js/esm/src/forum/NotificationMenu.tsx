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
 * Notification preferences of the current user: forum subscription and daily digest.
 *
 * @module     mod_hiveforum/forum/NotificationMenu
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {useEffect, useId, useRef, useState} from 'react';
import Icon from '@moodle/lms/mod_hiveforum/common/Icon';
import {useStrings} from '@moodle/lms/mod_hiveforum/common/i18n';
import {exception, toast} from '@moodle/lms/mod_hiveforum/common/notify';
import {setPreferences} from '@moodle/lms/mod_hiveforum/common/repository';
import type {Preferences} from '@moodle/lms/mod_hiveforum/common/types';

const FORCED = 1;
const DISABLED = 3;

export default function NotificationMenu({cmid, preferences}: {cmid: number; preferences: Preferences}) {
    const t = useStrings();
    const panelId = useId();
    const [open, setOpen] = useState(false);
    const [prefs, setPrefs] = useState(preferences);
    const [busy, setBusy] = useState(false);
    const rootRef = useRef<HTMLDivElement>(null);

    // Close on outside click and Escape.
    useEffect(() => {
        if (!open) {
            return undefined;
        }
        const onClick = (e: MouseEvent) => {
            if (!rootRef.current?.contains(e.target as Node)) {
                setOpen(false);
            }
        };
        const onKey = (e: KeyboardEvent) => {
            if (e.key === 'Escape') {
                setOpen(false);
            }
        };
        document.addEventListener('mousedown', onClick);
        document.addEventListener('keydown', onKey);
        return () => {
            document.removeEventListener('mousedown', onClick);
            document.removeEventListener('keydown', onKey);
        };
    }, [open]);

    const save = (change: {forumsubscribed?: boolean; digest?: boolean}, message: string) => {
        const previous = prefs;
        setPrefs({...prefs, ...change});
        setBusy(true);
        setPreferences(cmid, change)
            .then((result) => {
                setPrefs(result.preferences);
                toast(message);
            })
            .catch((error) => {
                setPrefs(previous);
                exception(error);
            })
            .finally(() => setBusy(false));
    };

    const disabled = prefs.subscriptionmode === DISABLED;
    const forced = prefs.subscriptionmode === FORCED;
    const label = prefs.forumsubscribed ? t('forumsubscribed') : t('notifications');

    return (
        <div className="hf-notifmenu" ref={rootRef}>
            <button
                type="button"
                className={`btn hf-notifmenu__trigger ${prefs.forumsubscribed ? 'is-on' : ''}`}
                aria-expanded={open}
                aria-controls={panelId}
                onClick={() => setOpen(!open)}
            >
                <Icon name={prefs.forumsubscribed ? 'bellcheck' : 'bell'} size={17} />
                <span>{label}</span>
                <Icon name="chevrondown" size={14} />
            </button>
            {open && (
                <div className="hf-notifmenu__panel" id={panelId} role="dialog" aria-label={t('notifications')}>
                    {!prefs.notificationsenabled && (
                        <p className="hf-notifmenu__warning">{t('notificationsoff')}</p>
                    )}
                    {disabled ? (
                        <p className="hf-notifmenu__text">{t('modeexplain_3')}</p>
                    ) : (
                        <>
                            <label className="hf-switch">
                                <input
                                    type="checkbox"
                                    role="switch"
                                    checked={prefs.forumsubscribed}
                                    disabled={!prefs.canchange || busy || forced}
                                    onChange={(e) => save(
                                        {forumsubscribed: e.target.checked},
                                        e.target.checked ? t('forumsubscribedtoast') : t('forumunsubscribedtoast'),
                                    )}
                                />
                                <span className="hf-switch__track" aria-hidden="true" />
                                <span className="hf-switch__label">
                                    <strong>{t('subscribeforum')}</strong>
                                    <small>{forced ? t('modeexplain_1') : t('subscribeforum_help')}</small>
                                </span>
                            </label>
                            {prefs.notificationsenabled && (
                                <fieldset className="hf-notifmenu__freq">
                                    <legend>{t('frequency')}</legend>
                                    {[false, true].map((digest) => (
                                        <label key={String(digest)} className="hf-radio">
                                            <input
                                                type="radio"
                                                name={`${panelId}-digest`}
                                                checked={prefs.digest === digest}
                                                disabled={busy}
                                                onChange={() => save(
                                                    {digest},
                                                    digest ? t('digestontoast') : t('digestofftoast'),
                                                )}
                                            />
                                            <Icon name={digest ? 'mail' : 'bell'} size={15} />
                                            <span>{digest ? t('frequency_digest') : t('frequency_each')}</span>
                                        </label>
                                    ))}
                                </fieldset>
                            )}
                            <p className="hf-notifmenu__text">{t('mentionsalways')}</p>
                        </>
                    )}
                </div>
            )}
        </div>
    );
}
