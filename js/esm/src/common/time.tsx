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
 * Relative times ("il y a 5 min") that refresh themselves, in the user's language and timezone.
 *
 * @module     mod_hiveforum/common/time
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {useEffect, useState} from 'react';
import config from '@moodle/lms/core/config';

/** Moodle language code (pt_br) to BCP 47 (pt-BR). */
export const locale = (): string => {
    const [lang, region] = (config.language || 'en').split('_');
    return region ? `${lang}-${region.toUpperCase()}` : lang;
};

const timeZone = (): string | undefined => {
    const tz = config.usertimezone;
    if (!tz || tz === '99') {
        return undefined;
    }
    try {
        new Intl.DateTimeFormat('en', {timeZone: tz});
        return tz;
    } catch {
        return undefined;
    }
};

const UNITS: Array<[Intl.RelativeTimeFormatUnit, number]> = [
    ['year', 31536000],
    ['month', 2592000],
    ['week', 604800],
    ['day', 86400],
    ['hour', 3600],
    ['minute', 60],
];

/**
 * Format a unix timestamp relatively to now.
 */
export const relativeTime = (timestamp: number, now: number = Date.now() / 1000): string => {
    const diff = timestamp - now;
    const abs = Math.abs(diff);
    const rtf = new Intl.RelativeTimeFormat(locale(), {numeric: 'auto', style: 'short'});
    if (abs < 45) {
        return rtf.format(0, 'second');
    }
    for (const [unit, seconds] of UNITS) {
        if (abs >= seconds || unit === 'minute') {
            return rtf.format(Math.round(diff / seconds), unit);
        }
    }
    return rtf.format(0, 'second');
};

/**
 * Full date and time, for tooltips.
 */
export const absoluteTime = (timestamp: number): string =>
    new Intl.DateTimeFormat(locale(), {dateStyle: 'full', timeStyle: 'short', timeZone: timeZone()})
        .format(new Date(timestamp * 1000));

/**
 * Re-render every minute so relative times stay accurate.
 */
export function useNow(intervalMs = 60000): number {
    const [now, setNow] = useState(() => Date.now() / 1000);
    useEffect(() => {
        const id = window.setInterval(() => setNow(Date.now() / 1000), intervalMs);
        return () => window.clearInterval(id);
    }, [intervalMs]);
    return now;
}

/**
 * A <time> element showing a relative time with the full date as tooltip.
 */
export function RelativeTime({timestamp, className}: {timestamp: number; className?: string}) {
    const now = useNow();
    return (
        <time
            className={className}
            dateTime={new Date(timestamp * 1000).toISOString()}
            title={absoluteTime(timestamp)}
        >
            {relativeTime(timestamp, now)}
        </time>
    );
}
