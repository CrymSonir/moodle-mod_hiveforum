var __defProp = Object.defineProperty;
var __name = (target, value) => __defProp(target, "name", { value, configurable: true });
/**
 * Data-access layer: every web service call of the plugin lives here.
 *
 * @module     mod_hiveforum/common/repository
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
import { fetchOne } from "@moodle/lms/core/ajax";
const getDiscussions = /* @__PURE__ */ __name((query) => fetchOne({ methodname: "mod_hiveforum_get_discussions", args: { ...query } }), "getDiscussions");
const getDiscussion = /* @__PURE__ */ __name((discussionid, markread = true) => fetchOne({ methodname: "mod_hiveforum_get_discussion", args: { discussionid, markread } }), "getDiscussion");
const createDiscussion = /* @__PURE__ */ __name((args) => fetchOne({ methodname: "mod_hiveforum_create_discussion", args }), "createDiscussion");
const updateDiscussion = /* @__PURE__ */ __name((discussionid, name, tags) => fetchOne({ methodname: "mod_hiveforum_update_discussion", args: { discussionid, name, tags } }), "updateDiscussion");
const setDiscussionState = /* @__PURE__ */ __name((discussionid, state, value) => fetchOne({ methodname: "mod_hiveforum_set_discussion_state", args: { discussionid, state, value } }), "setDiscussionState");
const addPost = /* @__PURE__ */ __name((discussionid, parentid, message) => fetchOne({ methodname: "mod_hiveforum_add_post", args: { discussionid, parentid, message } }), "addPost");
const updatePost = /* @__PURE__ */ __name((postid, message) => fetchOne({ methodname: "mod_hiveforum_update_post", args: { postid, message } }), "updatePost");
const deletePost = /* @__PURE__ */ __name((postid) => fetchOne({ methodname: "mod_hiveforum_delete_post", args: { postid } }), "deletePost");
const toggleReaction = /* @__PURE__ */ __name((postid, reactiontype) => fetchOne({ methodname: "mod_hiveforum_toggle_reaction", args: { postid, reactiontype } }), "toggleReaction");
const toggleAccepted = /* @__PURE__ */ __name((postid) => fetchOne({ methodname: "mod_hiveforum_toggle_accepted", args: { postid } }), "toggleAccepted");
const markAllRead = /* @__PURE__ */ __name((cmid) => fetchOne({ methodname: "mod_hiveforum_mark_all_read", args: { cmid } }), "markAllRead");
const searchUsers = /* @__PURE__ */ __name((cmid, discussionid, query) => fetchOne({ methodname: "mod_hiveforum_search_users", args: { cmid, discussionid, query } }), "searchUsers");
const setPreferences = /* @__PURE__ */ __name((cmid, prefs) => fetchOne({
  methodname: "mod_hiveforum_set_preferences",
  args: {
    cmid,
    forumsubscribed: prefs.forumsubscribed === void 0 ? -1 : Number(prefs.forumsubscribed),
    digest: prefs.digest === void 0 ? -1 : Number(prefs.digest)
  }
}), "setPreferences");
export {
  addPost,
  createDiscussion,
  deletePost,
  getDiscussion,
  getDiscussions,
  markAllRead,
  searchUsers,
  setDiscussionState,
  setPreferences,
  toggleAccepted,
  toggleReaction,
  updateDiscussion,
  updatePost
};
//# sourceMappingURL=repository.dev.js.map
