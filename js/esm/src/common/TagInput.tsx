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
 * Chip-style tag input: Enter, comma or Tab adds a tag, Backspace removes the last one.
 *
 * @module     mod_hiveforum/common/TagInput
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {useId, useState, type KeyboardEvent} from 'react';
import Icon from '@moodle/lms/mod_hiveforum/common/Icon';
import {useStrings} from '@moodle/lms/mod_hiveforum/common/i18n';

export const MAX_TAGS = 5;

export const normaliseTag = (tag: string): string =>
    tag.trim().replace(/^#+/, '').toLowerCase().replace(/[,<>"]/g, '').slice(0, 50);

interface TagInputProps {
    tags: string[];
    onChange: (tags: string[]) => void;
    suggestions?: string[];
}

export default function TagInput({tags, onChange, suggestions = []}: TagInputProps) {
    const t = useStrings();
    const id = useId();
    const listId = useId();
    const [draft, setDraft] = useState('');

    const add = (raw: string) => {
        const tag = normaliseTag(raw);
        if (tag && !tags.includes(tag) && tags.length < MAX_TAGS) {
            onChange([...tags, tag]);
        }
        setDraft('');
    };

    const onKeyDown = (e: KeyboardEvent<HTMLInputElement>) => {
        if ((e.key === 'Enter' || e.key === ',' || (e.key === 'Tab' && draft)) && draft.trim()) {
            e.preventDefault();
            add(draft);
        } else if (e.key === 'Backspace' && !draft && tags.length) {
            onChange(tags.slice(0, -1));
        }
    };

    const full = tags.length >= MAX_TAGS;

    return (
        <div className="hf-taginput">
            <Icon name="tag" size={15} className="hf-taginput__icon" />
            {tags.map((tag) => (
                <span key={tag} className="hf-tag hf-tag--removable">
                    #{tag}
                    <button type="button" onClick={() => onChange(tags.filter((x) => x !== tag))}
                        aria-label={t('removetag', tag)}>
                        <Icon name="x" size={12} />
                    </button>
                </span>
            ))}
            {!full && (
                <>
                    <label htmlFor={id} className="visually-hidden">{t('addtags')}</label>
                    <input
                        id={id}
                        className="hf-taginput__field"
                        value={draft}
                        list={suggestions.length ? listId : undefined}
                        placeholder={tags.length ? '' : t('addtags')}
                        onChange={(e) => setDraft(e.target.value)}
                        onKeyDown={onKeyDown}
                        onBlur={() => draft.trim() && add(draft)}
                    />
                    {suggestions.length > 0 && (
                        <datalist id={listId}>
                            {suggestions.filter((s) => !tags.includes(s)).map((s) => <option key={s} value={s} />)}
                        </datalist>
                    )}
                </>
            )}
        </div>
    );
}
