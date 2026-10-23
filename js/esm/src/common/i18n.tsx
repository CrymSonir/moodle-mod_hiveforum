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
 * Language strings.
 *
 * Strings are sent by the server in the initial props (see \mod_hiveforum\output\strings),
 * so the UI renders instantly in the right language, without a string round-trip.
 *
 * @module     mod_hiveforum/common/i18n
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {createContext, useCallback, useContext, type ReactNode} from 'react';
import type {Strings} from '@moodle/lms/mod_hiveforum/common/types';

const StringsContext = createContext<Strings>({});

export function StringsProvider({strings, children}: {strings: Strings; children: ReactNode}) {
    return <StringsContext.Provider value={strings}>{children}</StringsContext.Provider>;
}

export type Param = string | number | Record<string, string | number> | undefined;

/**
 * Replace {$a} and {$a->name} placeholders, like M.util.get_string.
 */
export const interpolate = (text: string, a?: Param): string => {
    if (a === undefined) {
        return text;
    }
    if (typeof a === 'object') {
        return text.replace(/\{\$a->(\w+)\}/g, (match, key) => (key in a ? String(a[key]) : match));
    }
    return text.replace(/\{\$a\}/g, String(a));
};

export type Translate = ((key: string, a?: Param) => string) & {
    /** Pick the zero/one/many variant of a counted string. */
    plural: (count: number, one: string, many: string, zero?: string) => string;
};

/**
 * Hook returning the translation function.
 */
export function useStrings(): Translate {
    const strings = useContext(StringsContext);
    const t = useCallback((key: string, a?: Param) => interpolate(strings[key] ?? `[[${key}]]`, a), [strings]) as Translate;
    t.plural = (count, one, many, zero) => {
        if (count === 0 && zero) {
            return t(zero, count);
        }
        return t(count === 1 ? one : many, count);
    };
    return t;
}
