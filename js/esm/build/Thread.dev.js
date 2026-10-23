var __defProp = Object.defineProperty;
var __name = (target, value) => __defProp(target, "name", { value, configurable: true });
import { jsxDEV } from "react/jsx-dev-runtime";
/**
 * Discussion page: opening post, accepted answer, threaded replies, inline reply / edit,
 * and a live check for new replies while the tab is visible.
 *
 * Mounted by core/react_autoinit from data-react-component="@moodle/lms/mod_hiveforum/Thread".
 *
 * @module     mod_hiveforum/Thread
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
import { useCallback, useEffect, useMemo, useRef, useState } from "react";
import Composer from "@moodle/lms/mod_hiveforum/common/Composer";
import ConfirmDialog from "@moodle/lms/mod_hiveforum/common/ConfirmDialog";
import Icon from "@moodle/lms/mod_hiveforum/common/Icon";
import { StringsProvider, useStrings } from "@moodle/lms/mod_hiveforum/common/i18n";
import { exception, toast } from "@moodle/lms/mod_hiveforum/common/notify";
import { addPost, deletePost, getDiscussion, toggleAccepted, updatePost } from "@moodle/lms/mod_hiveforum/common/repository";
import { ThreadContext } from "@moodle/lms/mod_hiveforum/thread/context";
import PostItem from "@moodle/lms/mod_hiveforum/thread/PostItem";
import ThreadHeader from "@moodle/lms/mod_hiveforum/thread/ThreadHeader";
import { buildTree } from "@moodle/lms/mod_hiveforum/thread/tree";
const focusPost = /* @__PURE__ */ __name((postid, setHighlighted) => {
  window.requestAnimationFrame(() => {
    const el = document.getElementById(`p${postid}`);
    if (el) {
      const reduce = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
      el.scrollIntoView({ behavior: reduce ? "auto" : "smooth", block: "center" });
    }
    setHighlighted(postid);
    window.setTimeout(() => setHighlighted(null), 2400);
  });
}, "focusPost");
function ThreadView(props) {
  const t = useStrings();
  const { capabilities, currentuser, pollinterval } = props;
  const [discussion, setDiscussion] = useState(props.discussion);
  const [posts, setPosts] = useState(props.posts);
  const [replyingTo, setReplyingTo] = useState(null);
  const [editing, setEditing] = useState(null);
  const [highlighted, setHighlighted] = useState(null);
  const [incoming, setIncoming] = useState(null);
  const [toDelete, setToDelete] = useState(null);
  const postsRef = useRef(posts);
  postsRef.current = posts;
  const { root, replies } = useMemo(() => buildTree(posts), [posts]);
  const accepted = discussion.isquestion ? posts.find((p) => p.isaccepted) : void 0;
  const participants = useMemo(() => {
    const seen = /* @__PURE__ */ new Map();
    [...posts].reverse().forEach((p) => seen.has(p.author.id) || seen.set(p.author.id, p.author));
    return [...seen.values()];
  }, [posts]);
  useEffect(() => {
    const match = window.location.hash.match(/^#p(\d+)$/);
    if (match) {
      focusPost(Number(match[1]), setHighlighted);
    }
  }, []);
  useEffect(() => {
    if (!pollinterval) {
      return void 0;
    }
    const check = /* @__PURE__ */ __name(() => {
      if (document.visibilityState !== "visible") {
        return;
      }
      getDiscussion(discussion.id, false).then((result) => {
        const known = new Set(postsRef.current.map((p) => p.id));
        const fresh = result.posts.filter((p) => !known.has(p.id) && p.author.id !== currentuser?.id);
        if (fresh.length) {
          setIncoming({ discussion: result.discussion, posts: result.posts, count: fresh.length });
        }
      }).catch(() => void 0);
    }, "check");
    const id = window.setInterval(check, pollinterval * 1e3);
    document.addEventListener("visibilitychange", check);
    return () => {
      window.clearInterval(id);
      document.removeEventListener("visibilitychange", check);
    };
  }, [discussion.id, pollinterval, currentuser?.id]);
  const showIncoming = /* @__PURE__ */ __name(() => {
    if (!incoming) {
      return;
    }
    const known = new Set(posts.map((p) => p.id));
    const first = incoming.posts.find((p) => !known.has(p.id));
    setDiscussion(incoming.discussion);
    setPosts(incoming.posts);
    setIncoming(null);
    getDiscussion(discussion.id, true).catch(() => void 0);
    if (first) {
      focusPost(first.id, setHighlighted);
    }
  }, "showIncoming");
  const replacePost = useCallback((post) => {
    setPosts((prev) => prev.map((p) => p.id === post.id ? post : p));
  }, []);
  const reply = useCallback(async (parentid, message) => {
    try {
      const result = await addPost(discussion.id, parentid, message);
      setPosts((prev) => [...prev, result.post]);
      setReplyingTo(null);
      setDiscussion((prev) => ({ ...prev, following: true }));
      focusPost(result.post.id, setHighlighted);
    } catch (error) {
      exception(error);
      throw error;
    }
  }, [discussion.id]);
  const update = useCallback(async (post, message) => {
    try {
      const result = await updatePost(post.id, message);
      replacePost(result.post);
      setEditing(null);
      toast(t("postupdated"));
    } catch (error) {
      exception(error);
      throw error;
    }
  }, [replacePost, t]);
  const confirmDelete = /* @__PURE__ */ __name((post) => {
    deletePost(post.id).then((result) => {
      if (result.discussiondeleted) {
        window.location.href = result.forumurl;
        return;
      }
      setPosts((prev) => {
        const removed = /* @__PURE__ */ new Set([post.id]);
        let changed = true;
        while (changed) {
          changed = false;
          for (const p of prev) {
            if (!removed.has(p.id) && removed.has(p.parentid)) {
              removed.add(p.id);
              changed = true;
            }
          }
        }
        return prev.filter((p) => !removed.has(p.id));
      });
      if (post.isaccepted) {
        setDiscussion((prev) => ({ ...prev, resolved: false }));
      }
      toast(t("postdeleted"));
    }).catch(exception);
  }, "confirmDelete");
  const accept = useCallback((post) => {
    toggleAccepted(post.id).then((result) => {
      setPosts((prev) => prev.map((p) => ({ ...p, isaccepted: p.id === post.id ? result.accepted : false })));
      setDiscussion((prev) => ({ ...prev, resolved: result.resolved }));
      toast(result.accepted ? t("answeraccepted") : t("answerunaccepted"));
    }).catch(exception);
  }, [t]);
  const actions = {
    discussion,
    capabilities,
    currentuser,
    replyingTo,
    editing,
    highlighted,
    setReplyingTo: /* @__PURE__ */ __name((id) => {
      setReplyingTo(id);
      setEditing(null);
    }, "setReplyingTo"),
    setEditing: /* @__PURE__ */ __name((id) => {
      setEditing(id);
      setReplyingTo(null);
    }, "setEditing"),
    reply,
    update,
    remove: setToDelete,
    accept,
    replacePost
  };
  const replycount = Math.max(0, posts.length - 1);
  return /* @__PURE__ */ jsxDEV(ThreadContext.Provider, { value: actions, children: /* @__PURE__ */ jsxDEV("div", { className: "hf hf-thread", children: [
    /* @__PURE__ */ jsxDEV(
      ThreadHeader,
      {
        discussion,
        forumurl: props.forumurl,
        replycount,
        participants,
        allowtags: capabilities.allowtags,
        onChange: setDiscussion
      },
      void 0,
      false,
      {
        fileName: "../plugin/hiveforum/js/esm/src/Thread.tsx",
        lineNumber: 220,
        columnNumber: 17
      },
      this
    ),
    root && /* @__PURE__ */ jsxDEV("ol", { className: "hf-thread__list hf-thread__list--opening", children: /* @__PURE__ */ jsxDEV(PostItem, { post: root, variant: "opening" }, void 0, false, {
      fileName: "../plugin/hiveforum/js/esm/src/Thread.tsx",
      lineNumber: 231,
      columnNumber: 25
    }, this) }, void 0, false, {
      fileName: "../plugin/hiveforum/js/esm/src/Thread.tsx",
      lineNumber: 230,
      columnNumber: 21
    }, this),
    accepted && /* @__PURE__ */ jsxDEV("a", { className: "hf-answer", href: `#p${accepted.id}`, onClick: (e) => {
      e.preventDefault();
      focusPost(accepted.id, setHighlighted);
    }, children: [
      /* @__PURE__ */ jsxDEV("span", { className: "hf-answer__icon", children: /* @__PURE__ */ jsxDEV(Icon, { name: "checkcircle", size: 20 }, void 0, false, {
        fileName: "../plugin/hiveforum/js/esm/src/Thread.tsx",
        lineNumber: 240,
        columnNumber: 59
      }, this) }, void 0, false, {
        fileName: "../plugin/hiveforum/js/esm/src/Thread.tsx",
        lineNumber: 240,
        columnNumber: 25
      }, this),
      /* @__PURE__ */ jsxDEV("span", { className: "hf-answer__body", children: [
        /* @__PURE__ */ jsxDEV("strong", { children: t("acceptedanswerby", accepted.author.fullname) }, void 0, false, {
          fileName: "../plugin/hiveforum/js/esm/src/Thread.tsx",
          lineNumber: 242,
          columnNumber: 29
        }, this),
        /* @__PURE__ */ jsxDEV("span", { className: "hf-answer__excerpt", children: new DOMParser().parseFromString(accepted.message, "text/html").body.textContent?.slice(0, 180) }, void 0, false, {
          fileName: "../plugin/hiveforum/js/esm/src/Thread.tsx",
          lineNumber: 243,
          columnNumber: 29
        }, this)
      ] }, void 0, true, {
        fileName: "../plugin/hiveforum/js/esm/src/Thread.tsx",
        lineNumber: 241,
        columnNumber: 25
      }, this),
      /* @__PURE__ */ jsxDEV(Icon, { name: "arrowdown", size: 16 }, void 0, false, {
        fileName: "../plugin/hiveforum/js/esm/src/Thread.tsx",
        lineNumber: 247,
        columnNumber: 25
      }, this)
    ] }, void 0, true, {
      fileName: "../plugin/hiveforum/js/esm/src/Thread.tsx",
      lineNumber: 236,
      columnNumber: 21
    }, this),
    /* @__PURE__ */ jsxDEV("div", { className: "hf-thread__repliesheading", children: /* @__PURE__ */ jsxDEV("h3", { children: t.plural(replycount, "onereply", "repliescount", "noreplies") }, void 0, false, {
      fileName: "../plugin/hiveforum/js/esm/src/Thread.tsx",
      lineNumber: 252,
      columnNumber: 21
    }, this) }, void 0, false, {
      fileName: "../plugin/hiveforum/js/esm/src/Thread.tsx",
      lineNumber: 251,
      columnNumber: 17
    }, this),
    replies.length > 0 ? /* @__PURE__ */ jsxDEV("ol", { className: "hf-thread__list", children: replies.map((node) => /* @__PURE__ */ jsxDEV(PostItem, { post: node.post, node }, node.post.id, false, {
      fileName: "../plugin/hiveforum/js/esm/src/Thread.tsx",
      lineNumber: 257,
      columnNumber: 48
    }, this)) }, void 0, false, {
      fileName: "../plugin/hiveforum/js/esm/src/Thread.tsx",
      lineNumber: 256,
      columnNumber: 21
    }, this) : /* @__PURE__ */ jsxDEV("p", { className: "hf-thread__empty", children: t("noreplies_help") }, void 0, false, {
      fileName: "../plugin/hiveforum/js/esm/src/Thread.tsx",
      lineNumber: 260,
      columnNumber: 21
    }, this),
    capabilities.canreply && root && /* @__PURE__ */ jsxDEV("section", { className: "hf-thread__composer", "aria-label": t("addreply"), children: /* @__PURE__ */ jsxDEV(
      Composer,
      {
        draftKey: `reply/${discussion.id}/0`,
        label: t("addreply"),
        placeholder: discussion.isquestion ? t("answerplaceholder") : t("replyplaceholder"),
        submitLabel: t("reply"),
        user: currentuser,
        compact: true,
        onSubmit: (message) => reply(root.id, message)
      },
      void 0,
      false,
      {
        fileName: "../plugin/hiveforum/js/esm/src/Thread.tsx",
        lineNumber: 265,
        columnNumber: 25
      },
      this
    ) }, void 0, false, {
      fileName: "../plugin/hiveforum/js/esm/src/Thread.tsx",
      lineNumber: 264,
      columnNumber: 21
    }, this),
    incoming && /* @__PURE__ */ jsxDEV("div", { className: "hf-incoming", role: "status", children: /* @__PURE__ */ jsxDEV("button", { type: "button", className: "hf-incoming__btn", onClick: showIncoming, children: [
      /* @__PURE__ */ jsxDEV(Icon, { name: "arrowdown", size: 16 }, void 0, false, {
        fileName: "../plugin/hiveforum/js/esm/src/Thread.tsx",
        lineNumber: 280,
        columnNumber: 29
      }, this),
      t.plural(incoming.count, "newreplyone", "newreplymany")
    ] }, void 0, true, {
      fileName: "../plugin/hiveforum/js/esm/src/Thread.tsx",
      lineNumber: 279,
      columnNumber: 25
    }, this) }, void 0, false, {
      fileName: "../plugin/hiveforum/js/esm/src/Thread.tsx",
      lineNumber: 278,
      columnNumber: 21
    }, this),
    /* @__PURE__ */ jsxDEV(
      ConfirmDialog,
      {
        open: toDelete !== null,
        title: toDelete?.isfirst ? t("deletediscussion") : t("deletepost"),
        message: toDelete?.isfirst ? t("deletediscussionconfirm") : t("deleteconfirm"),
        confirmLabel: t("delete"),
        danger: true,
        onConfirm: () => toDelete && confirmDelete(toDelete),
        onClose: () => setToDelete(null)
      },
      void 0,
      false,
      {
        fileName: "../plugin/hiveforum/js/esm/src/Thread.tsx",
        lineNumber: 286,
        columnNumber: 17
      },
      this
    )
  ] }, void 0, true, {
    fileName: "../plugin/hiveforum/js/esm/src/Thread.tsx",
    lineNumber: 219,
    columnNumber: 13
  }, this) }, void 0, false, {
    fileName: "../plugin/hiveforum/js/esm/src/Thread.tsx",
    lineNumber: 218,
    columnNumber: 9
  }, this);
}
__name(ThreadView, "ThreadView");
function Thread(props) {
  return /* @__PURE__ */ jsxDEV(StringsProvider, { strings: props.strings, children: /* @__PURE__ */ jsxDEV(ThreadView, { ...props }, void 0, false, {
    fileName: "../plugin/hiveforum/js/esm/src/Thread.tsx",
    lineNumber: 303,
    columnNumber: 13
  }, this) }, void 0, false, {
    fileName: "../plugin/hiveforum/js/esm/src/Thread.tsx",
    lineNumber: 302,
    columnNumber: 9
  }, this);
}
__name(Thread, "Thread");
export {
  Thread as default
};
//# sourceMappingURL=Thread.dev.js.map
