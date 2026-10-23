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
 * Reactions: interactive chips on posts (optimistic update), read-only summary on cards.
 *
 * @module     mod_hiveforum/common/ReactionBar
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {useState} from 'react';
import {useStrings} from '@moodle/lms/mod_hiveforum/common/i18n';
import {exception} from '@moodle/lms/mod_hiveforum/common/notify';
import {toggleReaction} from '@moodle/lms/mod_hiveforum/common/repository';
import {REACTIONS, type Reaction, type ReactionType} from '@moodle/lms/mod_hiveforum/common/types';

const emojiOf = (type: ReactionType): string => REACTIONS.find((r) => r.type === type)?.emoji ?? '';

interface ReactionBarProps {
    postid: number;
    reactions: Reaction[];
    canreact: boolean;
    onChange: (reactions: Reaction[]) => void;
}

export default function ReactionBar({postid, reactions, canreact, onChange}: ReactionBarProps) {
    const t = useStrings();
    const [pending, setPending] = useState<ReactionType | null>(null);
    const [pop, setPop] = useState<ReactionType | null>(null);

    const toggle = (type: ReactionType) => {
        if (pending) {
            return;
        }
        const previous = reactions;
        // Optimistic update.
        onChange(reactions.map((r) => r.type === type
            ? {...r, active: !r.active, count: r.count + (r.active ? -1 : 1)}
            : r));
        if (!previous.find((r) => r.type === type)?.active) {
            setPop(type);
            window.setTimeout(() => setPop(null), 450);
        }
        setPending(type);
        toggleReaction(postid, type)
            .then((result) => onChange(result.reactions))
            .catch((error) => {
                onChange(previous);
                exception(error);
            })
            .finally(() => setPending(null));
    };

    const visible = canreact ? reactions : reactions.filter((r) => r.count > 0);
    if (!visible.length) {
        return null;
    }

    return (
        <div className="hf-reactions" role="group" aria-label={t('reactions')}>
            {visible.map((r) => {
                const label = t(`reaction_${r.type}`);
                const content = (
                    <>
                        <span className={`hf-reaction__emoji ${pop === r.type ? 'is-popping' : ''}`} aria-hidden="true">
                            {emojiOf(r.type)}
                        </span>
                        <span className="hf-reaction__count" aria-hidden="true">{r.count > 0 ? r.count : ''}</span>
                        <span className="visually-hidden">{t('reactioncount', {reaction: label, count: r.count})}</span>
                    </>
                );
                if (!canreact) {
                    return <span key={r.type} className="hf-reaction is-static">{content}</span>;
                }
                return (
                    <button
                        key={r.type}
                        type="button"
                        className={`hf-reaction hf-reaction--${r.type} ${r.active ? 'is-active' : ''} ${r.count === 0 ? 'is-empty' : ''}`}
                        aria-pressed={r.active}
                        title={label}
                        onClick={() => toggle(r.type)}
                    >
                        {content}
                    </button>
                );
            })}
        </div>
    );
}

/**
 * Compact read-only summary (cards in the list).
 */
export function ReactionSummary({reactions}: {reactions: Reaction[]}) {
    const t = useStrings();
    const total = reactions.reduce((sum, r) => sum + r.count, 0);
    if (!total) {
        return null;
    }
    return (
        <span className="hf-reaction-summary" title={reactions.filter((r) => r.count).map((r) =>
            `${emojiOf(r.type)} ${r.count}`).join('  ')}>
            <span className="hf-reaction-summary__emojis" aria-hidden="true">
                {reactions.filter((r) => r.count > 0).map((r) => <span key={r.type}>{emojiOf(r.type)}</span>)}
            </span>
            <span aria-hidden="true">{total}</span>
            <span className="visually-hidden">{t('reactionstotal', total)}</span>
        </span>
    );
}
