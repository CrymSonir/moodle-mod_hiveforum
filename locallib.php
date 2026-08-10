<?php
defined('MOODLE_INTERNAL') || die();

/**
 * Fetch discussion summaries for the list view, with unread state,
 * reaction counts and tags attached - shaped for the mustache template.
 *
 * @param int $hiveforumid
 * @param int $userid
 * @param string $filter all|unread|following
 * @param string $search
 * @return array of stdClass ready for template context
 */
function hiveforum_get_discussion_summaries($hiveforumid, $userid, $filter = 'all', $search = '') {
    global $DB;

    $params = ['hiveforumid' => $hiveforumid];
    $where = 'd.hiveforum = :hiveforumid';

    if ($search !== '') {
        $where .= ' AND ' . $DB->sql_like('d.name', ':search', false);
        $params['search'] = '%' . $DB->sql_like_escape($search) . '%';
    }

    $sql = "SELECT d.*, u.firstname, u.lastname,
                   (SELECT COUNT(*) FROM {hiveforum_posts} p WHERE p.discussion = d.id) AS replycount,
                   (SELECT MAX(p2.timecreated) FROM {hiveforum_posts} p2 WHERE p2.discussion = d.id) AS lastpost
              FROM {hiveforum_discussions} d
              JOIN {user} u ON u.id = d.userid
             WHERE $where
          ORDER BY d.pinned DESC, lastpost DESC, d.timecreated DESC";

    $records = $DB->get_records_sql($sql, $params);

    $summaries = [];
    foreach ($records as $d) {
        $isread = $DB->record_exists_select(
            'hiveforum_reads',
            'discussion = :discussion AND userid = :userid AND timeread >= :lastpost',
            ['discussion' => $d->id, 'userid' => $userid, 'lastpost' => $d->lastpost ?: $d->timecreated]
        );

        if ($filter === 'unread' && $isread) {
            continue;
        }

        $tags = $DB->get_fieldset_select('hiveforum_tags', 'tag', 'discussion = ?', [$d->id]);

        $reactioncounts = $DB->get_records_sql(
            "SELECT r.reactiontype, COUNT(*) AS cnt
               FROM {hiveforum_reactions} r
               JOIN {hiveforum_posts} p ON p.id = r.postid
              WHERE p.discussion = ?
           GROUP BY r.reactiontype",
            [$d->id]
        );

        $summary = new stdClass();
        $summary->id = $d->id;
        $summary->name = format_string($d->name);
        $summary->authorname = fullname($d);
        $summary->initials = hiveforum_initials($d->firstname, $d->lastname);
        $summary->pinned = (bool) $d->pinned;
        $summary->resolved = (bool) $d->resolved;
        $summary->isquestion = ($d->posttype === 'question');
        $summary->replycount = (int) $d->replycount;
        $summary->timeago = format_time(time() - $d->timecreated);
        $summary->unread = !$isread;
        $summary->tags = array_values($tags);
        $summary->reactions = array_map(function ($type, $cnt) {
            return ['type' => $type, 'count' => $cnt];
        }, array_keys((array) $reactioncounts), array_column((array) $reactioncounts, 'cnt'));

        $summaries[] = $summary;
    }

    return $summaries;
}

function hiveforum_initials($firstname, $lastname) {
    $a = mb_substr($firstname, 0, 1);
    $b = mb_substr($lastname, 0, 1);
    return mb_strtoupper($a . $b);
}

/**
 * Updates the weekly contribution streak for a user after they post.
 * Called from post-creation logic (external API / classes/local/posts.php).
 */
function hiveforum_bump_streak($hiveforumid, $userid) {
    global $DB;

    $week = (int) floor(time() / WEEKSECS);
    $streak = $DB->get_record('hiveforum_streaks', ['hiveforum' => $hiveforumid, 'userid' => $userid]);

    if (!$streak) {
        $streak = new stdClass();
        $streak->hiveforum = $hiveforumid;
        $streak->userid = $userid;
        $streak->currentstreak = 1;
        $streak->lastactiveweek = $week;
        $DB->insert_record('hiveforum_streaks', $streak);
        return;
    }

    if ($streak->lastactiveweek === $week) {
        return; // Already counted this week.
    }

    if ($streak->lastactiveweek === $week - 1) {
        $streak->currentstreak++;
    } else {
        $streak->currentstreak = 1;
    }
    $streak->lastactiveweek = $week;
    $DB->update_record('hiveforum_streaks', $streak);
}
