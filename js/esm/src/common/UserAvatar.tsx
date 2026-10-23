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
 * Avatars (Moodle design system), avatar stacks and the contribution streak chip.
 *
 * @module     mod_hiveforum/common/UserAvatar
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import type {CSSProperties} from 'react';
import {Avatar, type AvatarSize} from '@moodlehq/design-system';
import Icon from '@moodle/lms/mod_hiveforum/common/Icon';
import {useStrings} from '@moodle/lms/mod_hiveforum/common/i18n';
import type {User} from '@moodle/lms/mod_hiveforum/common/types';

/** Streak from which the chip is displayed. */
export const STREAK_MIN = 2;

/**
 * A deterministic hue per user, so initials avatars are told apart at a glance.
 */
const hue = (id: number): number => (id * 137) % 360;

export default function UserAvatar({user, size = 'md', decorative = false}: {
    user: User;
    size?: AvatarSize;
    decorative?: boolean;
}) {
    return (
        <span className="hf-avatar" style={{'--hf-avatar-hue': hue(user.id)} as CSSProperties}>
            <Avatar
                size={size}
                imageSrc={user.avatarurl || undefined}
                initials={user.initials}
                alt={decorative ? '' : user.fullname}
            />
        </span>
    );
}

/**
 * Overlapping avatars of the participants of a discussion.
 */
export function AvatarStack({users, max = 4}: {users: User[]; max?: number}) {
    const t = useStrings();
    if (!users.length) {
        return null;
    }
    const shown = users.slice(0, max);
    return (
        <span className="hf-avatar-stack" title={users.map((u) => u.fullname).join(', ')}>
            <span className="visually-hidden">{t('participantsnames', users.map((u) => u.fullname).join(', '))}</span>
            {shown.map((user) => <UserAvatar key={user.id} user={user} size="md" decorative />)}
        </span>
    );
}

/**
 * "🔥 3" chip: weekly contribution streak.
 */
export function StreakChip({weeks, large = false}: {weeks: number; large?: boolean}) {
    const t = useStrings();
    if (weeks < STREAK_MIN) {
        return null;
    }
    const label = t('streakweeks', weeks);
    return (
        <span className={`hf-streak ${large ? 'hf-streak--lg' : ''}`} title={label}>
            <Icon name="flame" size={large ? 16 : 13} />
            <span aria-hidden="true">{weeks}</span>
            <span className="visually-hidden">{label}</span>
        </span>
    );
}
