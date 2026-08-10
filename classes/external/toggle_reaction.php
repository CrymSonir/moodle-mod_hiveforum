<?php
namespace mod_hiveforum\external;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');

use external_api;
use external_function_parameters;
use external_value;
use external_single_structure;

class toggle_reaction extends external_api {

    public static function execute_parameters() {
        return new external_function_parameters([
            'postid' => new external_value(PARAM_INT, 'Post id'),
            'reactiontype' => new external_value(PARAM_ALPHA, 'Reaction type: like, love or idea'),
        ]);
    }

    public static function execute($postid, $reactiontype) {
        global $DB, $USER;

        $params = self::validate_parameters(self::execute_parameters(), [
            'postid' => $postid,
            'reactiontype' => $reactiontype,
        ]);

        $post = $DB->get_record('hiveforum_posts', ['id' => $params['postid']], '*', MUST_EXIST);
        $discussion = $DB->get_record('hiveforum_discussions', ['id' => $post->discussion], '*', MUST_EXIST);
        $hiveforum = $DB->get_record('hiveforum', ['id' => $discussion->hiveforum], '*', MUST_EXIST);
        $cm = get_coursemodule_from_instance('hiveforum', $hiveforum->id, $hiveforum->course, false, MUST_EXIST);
        $context = \context_module::instance($cm->id);

        self::validate_context($context);
        require_capability('mod/hiveforum:react', $context);

        if (!in_array($params['reactiontype'], ['like', 'love', 'idea'], true)) {
            throw new \invalid_parameter_exception('Unsupported reaction type');
        }

        $existing = $DB->get_record('hiveforum_reactions', [
            'postid' => $post->id,
            'userid' => $USER->id,
            'reactiontype' => $params['reactiontype'],
        ]);

        if ($existing) {
            $DB->delete_records('hiveforum_reactions', ['id' => $existing->id]);
        } else {
            $DB->insert_record('hiveforum_reactions', [
                'postid' => $post->id,
                'userid' => $USER->id,
                'reactiontype' => $params['reactiontype'],
                'timecreated' => time(),
            ]);

            $event = \mod_hiveforum\event\reaction_added::create([
                'objectid' => $post->id,
                'context' => $context,
                'other' => ['reactiontype' => $params['reactiontype']],
            ]);
            $event->trigger();
        }

        $count = $DB->count_records('hiveforum_reactions', [
            'postid' => $post->id,
            'reactiontype' => $params['reactiontype'],
        ]);

        return ['count' => $count, 'active' => !$existing];
    }

    public static function execute_returns() {
        return new external_single_structure([
            'count' => new external_value(PARAM_INT, 'Updated reaction count'),
            'active' => new external_value(PARAM_BOOL, 'Whether the current user now has this reaction active'),
        ]);
    }
}
