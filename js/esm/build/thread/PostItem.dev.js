var __defProp = Object.defineProperty;
var __name = (target, value) => __defProp(target, "name", { value, configurable: true });
import { Fragment, jsxDEV } from "react/jsx-dev-runtime";
/**
 * A post and, recursively, its replies.
 *
 * @module     mod_hiveforum/thread/PostItem
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
import { useEffect, useRef, useState } from "react";
import Composer from "@moodle/lms/mod_hiveforum/common/Composer";
import Icon from "@moodle/lms/mod_hiveforum/common/Icon";
import { useStrings } from "@moodle/lms/mod_hiveforum/common/i18n";
import { filterContentUpdated, toast } from "@moodle/lms/mod_hiveforum/common/notify";
import ReactionBar from "@moodle/lms/mod_hiveforum/common/ReactionBar";
import { RelativeTime } from "@moodle/lms/mod_hiveforum/common/time";
import UserAvatar, { StreakChip } from "@moodle/lms/mod_hiveforum/common/UserAvatar";
import { FORMAT_MARKDOWN } from "@moodle/lms/mod_hiveforum/common/types";
import { useThread } from "@moodle/lms/mod_hiveforum/thread/context";
const MAX_DEPTH = 3;
function Message({ html }) {
  const ref = useRef(null);
  useEffect(() => filterContentUpdated(ref.current), [html]);
  return /* @__PURE__ */ jsxDEV("div", { ref, className: "hf-post__message", dangerouslySetInnerHTML: { __html: html } }, void 0, false, {
    fileName: "../plugin/hiveforum/js/esm/src/thread/PostItem.tsx",
    lineNumber: 44,
    columnNumber: 12
  }, this);
}
__name(Message, "Message");
function PostItem({ post, node, depth = 0, parentAuthor, variant = "reply" }) {
  const t = useStrings();
  const thread = useThread();
  const { discussion, capabilities: caps, currentuser } = thread;
  const [collapsed, setCollapsed] = useState(false);
  const isReplying = thread.replyingTo === post.id;
  const isEditing = thread.editing === post.id;
  const inlineEditable = post.messageformat === FORMAT_MARKDOWN;
  const isOwn = currentuser?.id === post.author.id;
  const copyLink = /* @__PURE__ */ __name(() => {
    const url = new URL(window.location.href);
    url.hash = `p${post.id}`;
    navigator.clipboard?.writeText(url.toString()).then(() => toast(t("linkcopied"))).catch(() => void 0);
  }, "copyLink");
  const classes = [
    "hf-post",
    `hf-post--${variant}`,
    post.isaccepted ? "is-accepted" : "",
    thread.highlighted === post.id ? "is-highlighted" : "",
    post.author.isstaff ? "is-staff" : "",
    isOwn ? "is-own" : ""
  ].filter(Boolean).join(" ");
  return /* @__PURE__ */ jsxDEV("li", { className: "hf-thread__item", style: { "--hf-depth": Math.min(depth, MAX_DEPTH) }, children: [
    /* @__PURE__ */ jsxDEV("article", { className: classes, id: `p${post.id}`, "aria-labelledby": `hf-p-${post.id}-author`, children: [
      /* @__PURE__ */ jsxDEV("header", { className: "hf-post__head", children: [
        /* @__PURE__ */ jsxDEV(UserAvatar, { user: post.author, size: variant === "opening" ? "lg" : "md", decorative: true }, void 0, false, {
          fileName: "../plugin/hiveforum/js/esm/src/thread/PostItem.tsx",
          lineNumber: 87,
          columnNumber: 21
        }, this),
        /* @__PURE__ */ jsxDEV("div", { className: "hf-post__who", children: [
          /* @__PURE__ */ jsxDEV("span", { className: "hf-post__line", children: [
            /* @__PURE__ */ jsxDEV("a", { className: "hf-post__author", id: `hf-p-${post.id}-author`, href: post.author.profileurl, children: post.author.fullname }, void 0, false, {
              fileName: "../plugin/hiveforum/js/esm/src/thread/PostItem.tsx",
              lineNumber: 90,
              columnNumber: 29
            }, this),
            post.author.isstaff && /* @__PURE__ */ jsxDEV("span", { className: "hf-badge hf-badge--staff", children: t("staff") }, void 0, false, {
              fileName: "../plugin/hiveforum/js/esm/src/thread/PostItem.tsx",
              lineNumber: 93,
              columnNumber: 53
            }, this),
            /* @__PURE__ */ jsxDEV(StreakChip, { weeks: post.author.streak }, void 0, false, {
              fileName: "../plugin/hiveforum/js/esm/src/thread/PostItem.tsx",
              lineNumber: 94,
              columnNumber: 29
            }, this),
            post.isaccepted && /* @__PURE__ */ jsxDEV("span", { className: "hf-badge hf-badge--resolved", children: [
              /* @__PURE__ */ jsxDEV(Icon, { name: "check", size: 12 }, void 0, false, {
                fileName: "../plugin/hiveforum/js/esm/src/thread/PostItem.tsx",
                lineNumber: 96,
                columnNumber: 79
              }, this),
              t("acceptedanswer")
            ] }, void 0, true, {
              fileName: "../plugin/hiveforum/js/esm/src/thread/PostItem.tsx",
              lineNumber: 96,
              columnNumber: 33
            }, this)
          ] }, void 0, true, {
            fileName: "../plugin/hiveforum/js/esm/src/thread/PostItem.tsx",
            lineNumber: 89,
            columnNumber: 25
          }, this),
          /* @__PURE__ */ jsxDEV("span", { className: "hf-post__time", children: [
            depth > MAX_DEPTH && parentAuthor && /* @__PURE__ */ jsxDEV("span", { className: "hf-post__inreply", children: [
              /* @__PURE__ */ jsxDEV(Icon, { name: "reply", size: 12 }, void 0, false, {
                fileName: "../plugin/hiveforum/js/esm/src/thread/PostItem.tsx",
                lineNumber: 101,
                columnNumber: 68
              }, this),
              " ",
              parentAuthor
            ] }, void 0, true, {
              fileName: "../plugin/hiveforum/js/esm/src/thread/PostItem.tsx",
              lineNumber: 101,
              columnNumber: 33
            }, this),
            /* @__PURE__ */ jsxDEV("a", { href: `#p${post.id}`, className: "hf-post__permalink", children: /* @__PURE__ */ jsxDEV(RelativeTime, { timestamp: post.timecreated }, void 0, false, {
              fileName: "../plugin/hiveforum/js/esm/src/thread/PostItem.tsx",
              lineNumber: 103,
              columnNumber: 85
            }, this) }, void 0, false, {
              fileName: "../plugin/hiveforum/js/esm/src/thread/PostItem.tsx",
              lineNumber: 103,
              columnNumber: 29
            }, this),
            post.edited && /* @__PURE__ */ jsxDEV("span", { className: "hf-post__edited", children: t("edited") }, void 0, false, {
              fileName: "../plugin/hiveforum/js/esm/src/thread/PostItem.tsx",
              lineNumber: 104,
              columnNumber: 45
            }, this)
          ] }, void 0, true, {
            fileName: "../plugin/hiveforum/js/esm/src/thread/PostItem.tsx",
            lineNumber: 99,
            columnNumber: 25
          }, this)
        ] }, void 0, true, {
          fileName: "../plugin/hiveforum/js/esm/src/thread/PostItem.tsx",
          lineNumber: 88,
          columnNumber: 21
        }, this)
      ] }, void 0, true, {
        fileName: "../plugin/hiveforum/js/esm/src/thread/PostItem.tsx",
        lineNumber: 86,
        columnNumber: 17
      }, this),
      isEditing ? /* @__PURE__ */ jsxDEV("div", { className: "hf-post__edit", children: /* @__PURE__ */ jsxDEV(
        Composer,
        {
          label: t("editpost"),
          initialValue: post.rawmessage,
          submitLabel: t("savechanges"),
          autoFocus: true,
          onSubmit: (message) => thread.update(post, message),
          mentions: { cmid: thread.cmid, discussionid: discussion.id },
          onCancel: () => thread.setEditing(null)
        },
        void 0,
        false,
        {
          fileName: "../plugin/hiveforum/js/esm/src/thread/PostItem.tsx",
          lineNumber: 111,
          columnNumber: 25
        },
        this
      ) }, void 0, false, {
        fileName: "../plugin/hiveforum/js/esm/src/thread/PostItem.tsx",
        lineNumber: 110,
        columnNumber: 21
      }, this) : /* @__PURE__ */ jsxDEV(Message, { html: post.message }, void 0, false, {
        fileName: "../plugin/hiveforum/js/esm/src/thread/PostItem.tsx",
        lineNumber: 122,
        columnNumber: 21
      }, this),
      /* @__PURE__ */ jsxDEV("footer", { className: "hf-post__foot", children: [
        caps.allowreactions && /* @__PURE__ */ jsxDEV(
          ReactionBar,
          {
            postid: post.id,
            reactions: post.reactions,
            canreact: caps.canreact,
            onChange: (reactions) => thread.replacePost({ ...post, reactions })
          },
          void 0,
          false,
          {
            fileName: "../plugin/hiveforum/js/esm/src/thread/PostItem.tsx",
            lineNumber: 127,
            columnNumber: 25
          },
          this
        ),
        /* @__PURE__ */ jsxDEV("div", { className: "hf-post__actions", children: [
          discussion.canreply && /* @__PURE__ */ jsxDEV(
            "button",
            {
              type: "button",
              className: `hf-action ${isReplying ? "is-active" : ""}`,
              onClick: () => thread.setReplyingTo(isReplying ? null : post.id),
              "aria-expanded": isReplying,
              children: [
                /* @__PURE__ */ jsxDEV(Icon, { name: "reply", size: 15 }, void 0, false, {
                  fileName: "../plugin/hiveforum/js/esm/src/thread/PostItem.tsx",
                  lineNumber: 138,
                  columnNumber: 33
                }, this),
                " ",
                t("reply")
              ]
            },
            void 0,
            true,
            {
              fileName: "../plugin/hiveforum/js/esm/src/thread/PostItem.tsx",
              lineNumber: 136,
              columnNumber: 29
            },
            this
          ),
          post.canaccept && /* @__PURE__ */ jsxDEV(
            "button",
            {
              type: "button",
              className: `hf-action hf-action--accept ${post.isaccepted ? "is-active" : ""}`,
              onClick: () => thread.accept(post),
              "aria-pressed": post.isaccepted,
              children: [
                /* @__PURE__ */ jsxDEV(Icon, { name: "checkcircle", size: 15 }, void 0, false, {
                  fileName: "../plugin/hiveforum/js/esm/src/thread/PostItem.tsx",
                  lineNumber: 144,
                  columnNumber: 33
                }, this),
                " ",
                post.isaccepted ? t("unaccept") : t("accept")
              ]
            },
            void 0,
            true,
            {
              fileName: "../plugin/hiveforum/js/esm/src/thread/PostItem.tsx",
              lineNumber: 142,
              columnNumber: 29
            },
            this
          ),
          /* @__PURE__ */ jsxDEV("span", { className: "hf-post__tools", children: [
            /* @__PURE__ */ jsxDEV(
              "button",
              {
                type: "button",
                className: "hf-iconbtn hf-iconbtn--sm",
                onClick: copyLink,
                title: t("copylink"),
                "aria-label": t("copylink"),
                children: /* @__PURE__ */ jsxDEV(Icon, { name: "link", size: 15 }, void 0, false, {
                  fileName: "../plugin/hiveforum/js/esm/src/thread/PostItem.tsx",
                  lineNumber: 150,
                  columnNumber: 33
                }, this)
              },
              void 0,
              false,
              {
                fileName: "../plugin/hiveforum/js/esm/src/thread/PostItem.tsx",
                lineNumber: 148,
                columnNumber: 29
              },
              this
            ),
            post.canedit && (inlineEditable ? /* @__PURE__ */ jsxDEV(
              "button",
              {
                type: "button",
                className: "hf-iconbtn hf-iconbtn--sm",
                onClick: () => thread.setEditing(isEditing ? null : post.id),
                title: t("edit"),
                "aria-label": t("edit"),
                children: /* @__PURE__ */ jsxDEV(Icon, { name: "edit", size: 15 }, void 0, false, {
                  fileName: "../plugin/hiveforum/js/esm/src/thread/PostItem.tsx",
                  lineNumber: 156,
                  columnNumber: 37
                }, this)
              },
              void 0,
              false,
              {
                fileName: "../plugin/hiveforum/js/esm/src/thread/PostItem.tsx",
                lineNumber: 153,
                columnNumber: 33
              },
              this
            ) : /* @__PURE__ */ jsxDEV("a", { className: "hf-iconbtn hf-iconbtn--sm", href: post.editurl, title: t("edit"), "aria-label": t("edit"), children: /* @__PURE__ */ jsxDEV(Icon, { name: "edit", size: 15 }, void 0, false, {
              fileName: "../plugin/hiveforum/js/esm/src/thread/PostItem.tsx",
              lineNumber: 160,
              columnNumber: 37
            }, this) }, void 0, false, {
              fileName: "../plugin/hiveforum/js/esm/src/thread/PostItem.tsx",
              lineNumber: 159,
              columnNumber: 33
            }, this)),
            post.candelete && /* @__PURE__ */ jsxDEV(
              "button",
              {
                type: "button",
                className: "hf-iconbtn hf-iconbtn--sm hf-iconbtn--danger",
                onClick: () => thread.remove(post),
                title: t("delete"),
                "aria-label": t("delete"),
                children: /* @__PURE__ */ jsxDEV(Icon, { name: "trash", size: 15 }, void 0, false, {
                  fileName: "../plugin/hiveforum/js/esm/src/thread/PostItem.tsx",
                  lineNumber: 166,
                  columnNumber: 37
                }, this)
              },
              void 0,
              false,
              {
                fileName: "../plugin/hiveforum/js/esm/src/thread/PostItem.tsx",
                lineNumber: 164,
                columnNumber: 33
              },
              this
            )
          ] }, void 0, true, {
            fileName: "../plugin/hiveforum/js/esm/src/thread/PostItem.tsx",
            lineNumber: 147,
            columnNumber: 25
          }, this)
        ] }, void 0, true, {
          fileName: "../plugin/hiveforum/js/esm/src/thread/PostItem.tsx",
          lineNumber: 134,
          columnNumber: 21
        }, this)
      ] }, void 0, true, {
        fileName: "../plugin/hiveforum/js/esm/src/thread/PostItem.tsx",
        lineNumber: 125,
        columnNumber: 17
      }, this),
      isReplying && /* @__PURE__ */ jsxDEV("div", { className: "hf-post__reply", children: /* @__PURE__ */ jsxDEV(
        Composer,
        {
          draftKey: `reply/${discussion.id}/${post.id}`,
          label: t("replyto", post.author.fullname),
          placeholder: t("replyto", post.author.fullname),
          submitLabel: t("reply"),
          autoFocus: true,
          user: currentuser,
          onSubmit: (message) => thread.reply(post.id, message),
          mentions: { cmid: thread.cmid, discussionid: discussion.id },
          onCancel: () => thread.setReplyingTo(null)
        },
        void 0,
        false,
        {
          fileName: "../plugin/hiveforum/js/esm/src/thread/PostItem.tsx",
          lineNumber: 175,
          columnNumber: 25
        },
        this
      ) }, void 0, false, {
        fileName: "../plugin/hiveforum/js/esm/src/thread/PostItem.tsx",
        lineNumber: 174,
        columnNumber: 21
      }, this)
    ] }, void 0, true, {
      fileName: "../plugin/hiveforum/js/esm/src/thread/PostItem.tsx",
      lineNumber: 85,
      columnNumber: 13
    }, this),
    node && node.children.length > 0 && /* @__PURE__ */ jsxDEV(Fragment, { children: [
      /* @__PURE__ */ jsxDEV("button", { type: "button", className: "hf-collapse", onClick: () => setCollapsed(!collapsed), "aria-expanded": !collapsed, children: [
        /* @__PURE__ */ jsxDEV(Icon, { name: collapsed ? "chevronright" : "chevrondown", size: 14 }, void 0, false, {
          fileName: "../plugin/hiveforum/js/esm/src/thread/PostItem.tsx",
          lineNumber: 193,
          columnNumber: 25
        }, this),
        collapsed ? t.plural(node.descendants, "showonereply", "showreplies") : t.plural(node.descendants, "hideonereply", "hidereplies")
      ] }, void 0, true, {
        fileName: "../plugin/hiveforum/js/esm/src/thread/PostItem.tsx",
        lineNumber: 192,
        columnNumber: 21
      }, this),
      !collapsed && /* @__PURE__ */ jsxDEV("ol", { className: "hf-thread__children", children: node.children.map((child) => /* @__PURE__ */ jsxDEV(
        PostItem,
        {
          post: child.post,
          node: child,
          depth: depth + 1,
          parentAuthor: post.author.fullname
        },
        child.post.id,
        false,
        {
          fileName: "../plugin/hiveforum/js/esm/src/thread/PostItem.tsx",
          lineNumber: 201,
          columnNumber: 33
        },
        this
      )) }, void 0, false, {
        fileName: "../plugin/hiveforum/js/esm/src/thread/PostItem.tsx",
        lineNumber: 199,
        columnNumber: 25
      }, this)
    ] }, void 0, true, {
      fileName: "../plugin/hiveforum/js/esm/src/thread/PostItem.tsx",
      lineNumber: 191,
      columnNumber: 17
    }, this)
  ] }, void 0, true, {
    fileName: "../plugin/hiveforum/js/esm/src/thread/PostItem.tsx",
    lineNumber: 84,
    columnNumber: 9
  }, this);
}
__name(PostItem, "PostItem");
export {
  PostItem as default
};
//# sourceMappingURL=PostItem.dev.js.map
