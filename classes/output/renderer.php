<?php
namespace mod_hiveforum\output;

defined('MOODLE_INTERNAL') || die();

class renderer extends \plugin_renderer_base {

    /**
     * Renders the discussion list screen (filters, search, cards).
     *
     * @param \stdClass $hiveforum
     * @param \stdClass $cm
     * @param array $discussions pre-shaped summaries from locallib
     * @param string $filter
     * @param string $search
     */
    public function render_discussion_list($hiveforum, $cm, array $discussions, $filter, $search) {
        $context = [
            'cmid' => $cm->id,
            'hiveforumid' => $hiveforum->id,
            'search' => $search,
            'filter_all' => $filter === 'all',
            'filter_unread' => $filter === 'unread',
            'filter_following' => $filter === 'following',
            'discussions' => array_map([$this, 'export_discussion'], $discussions),
            'hasdiscussions' => count($discussions) > 0,
            'newdiscussionurl' => (new \moodle_url('/mod/hiveforum/post.php', ['hiveforum' => $hiveforum->id]))->out(false),
            'strings' => [
                'newdiscussion' => get_string('newdiscussion', 'hiveforum'),
                'empty' => get_string('emptydiscussions', 'hiveforum'),
                'all' => get_string('all', 'hiveforum'),
                'unread' => get_string('unread', 'hiveforum'),
                'following' => get_string('following', 'hiveforum'),
                'searchplaceholder' => get_string('searchdiscussions', 'hiveforum'),
            ],
        ];

        return $this->render_from_template('mod_hiveforum/discussion_list', $context);
    }

    /**
     * Renders a full discussion thread with its posts and reaction pills.
     */
    public function render_discussion_thread($discussion, $cm, array $posts, \context $context) {
        global $DB, $USER;

        $exported = [];
        $postsbyid = [];

        foreach ($posts as $p) {
            $author = $DB->get_record('user', ['id' => $p->userid], 'id, firstname, lastname');

            $reactions = [];
            foreach (['like', 'love', 'idea'] as $type) {
                $count = $DB->count_records('hiveforum_reactions', ['postid' => $p->id, 'reactiontype' => $type]);
                $active = $DB->record_exists('hiveforum_reactions', [
                    'postid' => $p->id, 'reactiontype' => $type, 'userid' => $USER->id,
                ]);
                $reactions[] = ['type' => $type, 'count' => $count, 'active' => $active];
            }

            $canmoderate = has_capability('mod/hiveforum:moderate', $context);
            $isauthor = ($p->userid == $USER->id);
            $canedit = ($isauthor && has_capability('mod/hiveforum:editownpost', $context)) || $canmoderate;
            $candelete = ($isauthor && has_capability('mod/hiveforum:deleteownpost', $context)) || $canmoderate;

            $postcontext = [
                'id' => $p->id,
                'parent' => $p->parent,
                'authorname' => fullname($author),
                'initials' => hiveforum_initials($author->firstname, $author->lastname),
                'message' => format_text($p->message, $p->messageformat, ['context' => $context]),
                'timeago' => format_time(time() - $p->timecreated),
                'isaccepted' => (bool) $p->isaccepted,
                'reactions' => $reactions,
                'canreact' => has_capability('mod/hiveforum:react', $context),
                'canedit' => $canedit,
                'candelete' => $candelete,
                'replies' => [],
                'hasreplies' => false,
            ];
            $postsbyid[$p->id] = $postcontext;
        }

        $rootposts = [];
        foreach ($postsbyid as $id => &$post) {
            if ($post['parent'] && isset($postsbyid[$post['parent']])) {
                $postsbyid[$post['parent']]['replies'][] = &$post;
                $postsbyid[$post['parent']]['hasreplies'] = true;
            } else {
                $rootposts[] = &$post;
            }
        }

        return $this->render_from_template('mod_hiveforum/discussion_thread', [
            'discussionname' => format_string($discussion->name),
            'posts' => $rootposts,
        ]);
    }

    /**
     * Renders the "reply to this discussion" composer.
     */
    public function render_reply_form($discussion, $cm) {
        return $this->render_from_template('mod_hiveforum/reply_form', [
            'discussionid' => $discussion->id,
            'posturl' => (new \moodle_url('/mod/hiveforum/post.php'))->out(false),
            'strreply' => get_string('reply', 'hiveforum'),
            'sesskey' => sesskey(),
        ]);
    }

    private function export_discussion($d) {
        return [
            'id' => $d->id,
            'name' => $d->name,
            'authorname' => $d->authorname,
            'initials' => $d->initials,
            'pinned' => $d->pinned,
            'resolved' => $d->resolved,
            'isquestion' => $d->isquestion,
            'replycount' => $d->replycount,
            'timeago' => $d->timeago,
            'unread' => $d->unread,
            'tags' => $d->tags,
            'hastags' => count($d->tags) > 0,
            'reactions' => $d->reactions,
            'hasreactions' => count($d->reactions) > 0,
            'viewurl' => (new \moodle_url('/mod/hiveforum/discuss.php', ['d' => $d->id]))->out(false),
        ];
    }
}
