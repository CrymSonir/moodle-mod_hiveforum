var __defProp = Object.defineProperty;
var __name = (target, value) => __defProp(target, "name", { value, configurable: true });
import { jsxDEV } from "react/jsx-dev-runtime";
/**
 * Discussion header: badges, title (renamable), tags, follow / pin / resolve.
 *
 * @module     mod_hiveforum/thread/ThreadHeader
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
import { useState } from "react";
import Icon from "@moodle/lms/mod_hiveforum/common/Icon";
import { useStrings } from "@moodle/lms/mod_hiveforum/common/i18n";
import { exception, toast } from "@moodle/lms/mod_hiveforum/common/notify";
import { setDiscussionState, updateDiscussion } from "@moodle/lms/mod_hiveforum/common/repository";
import TagInput from "@moodle/lms/mod_hiveforum/common/TagInput";
import { RelativeTime } from "@moodle/lms/mod_hiveforum/common/time";
import { AvatarStack } from "@moodle/lms/mod_hiveforum/common/UserAvatar";
function ThreadHeader({ discussion: d, forumurl, replycount, participants, allowtags, onChange }) {
  const t = useStrings();
  const [renaming, setRenaming] = useState(false);
  const [name, setName] = useState(d.rawname);
  const [tags, setTags] = useState(d.tags);
  const [busy, setBusy] = useState(null);
  const toggle = /* @__PURE__ */ __name((state, value, message) => {
    setBusy(state);
    onChange({ ...d, [state]: value });
    setDiscussionState(d.id, state, value).then((result) => {
      onChange(result.discussion);
      toast(message);
    }).catch((error) => {
      onChange(d);
      exception(error);
    }).finally(() => setBusy(null));
  }, "toggle");
  const saveRename = /* @__PURE__ */ __name(() => {
    if (!name.trim()) {
      return;
    }
    setBusy("rename");
    updateDiscussion(d.id, name.trim(), tags).then((result) => {
      onChange(result.discussion);
      setRenaming(false);
    }).catch(exception).finally(() => setBusy(null));
  }, "saveRename");
  return /* @__PURE__ */ jsxDEV("header", { className: "hf-threadhead", children: [
    /* @__PURE__ */ jsxDEV("div", { className: "hf-threadhead__badges", children: [
      d.pinned && /* @__PURE__ */ jsxDEV("span", { className: "hf-badge hf-badge--pinned", children: [
        /* @__PURE__ */ jsxDEV(Icon, { name: "pin", size: 12 }, void 0, false, {
          fileName: "../plugin/hiveforum/js/esm/src/thread/ThreadHeader.tsx",
          lineNumber: 82,
          columnNumber: 74
        }, this),
        t("pinned")
      ] }, void 0, true, {
        fileName: "../plugin/hiveforum/js/esm/src/thread/ThreadHeader.tsx",
        lineNumber: 82,
        columnNumber: 30
      }, this),
      d.isquestion && !d.resolved && /* @__PURE__ */ jsxDEV("span", { className: "hf-badge hf-badge--question", children: [
        /* @__PURE__ */ jsxDEV(Icon, { name: "question", size: 12 }, void 0, false, {
          fileName: "../plugin/hiveforum/js/esm/src/thread/ThreadHeader.tsx",
          lineNumber: 84,
          columnNumber: 67
        }, this),
        t("question")
      ] }, void 0, true, {
        fileName: "../plugin/hiveforum/js/esm/src/thread/ThreadHeader.tsx",
        lineNumber: 84,
        columnNumber: 21
      }, this),
      d.isquestion && d.resolved && /* @__PURE__ */ jsxDEV("span", { className: "hf-badge hf-badge--resolved", children: [
        /* @__PURE__ */ jsxDEV(Icon, { name: "check", size: 12 }, void 0, false, {
          fileName: "../plugin/hiveforum/js/esm/src/thread/ThreadHeader.tsx",
          lineNumber: 87,
          columnNumber: 67
        }, this),
        t("resolved")
      ] }, void 0, true, {
        fileName: "../plugin/hiveforum/js/esm/src/thread/ThreadHeader.tsx",
        lineNumber: 87,
        columnNumber: 21
      }, this),
      d.groupname && /* @__PURE__ */ jsxDEV("span", { className: "hf-badge hf-badge--group", children: [
        /* @__PURE__ */ jsxDEV(Icon, { name: "users2", size: 12 }, void 0, false, {
          fileName: "../plugin/hiveforum/js/esm/src/thread/ThreadHeader.tsx",
          lineNumber: 89,
          columnNumber: 76
        }, this),
        d.groupname
      ] }, void 0, true, {
        fileName: "../plugin/hiveforum/js/esm/src/thread/ThreadHeader.tsx",
        lineNumber: 89,
        columnNumber: 33
      }, this)
    ] }, void 0, true, {
      fileName: "../plugin/hiveforum/js/esm/src/thread/ThreadHeader.tsx",
      lineNumber: 81,
      columnNumber: 13
    }, this),
    renaming ? /* @__PURE__ */ jsxDEV("div", { className: "hf-rename", children: [
      /* @__PURE__ */ jsxDEV("label", { htmlFor: "hf-rename-input", className: "visually-hidden", children: t("discussiontitle") }, void 0, false, {
        fileName: "../plugin/hiveforum/js/esm/src/thread/ThreadHeader.tsx",
        lineNumber: 94,
        columnNumber: 21
      }, this),
      /* @__PURE__ */ jsxDEV(
        "input",
        {
          id: "hf-rename-input",
          className: "hf-rename__input",
          value: name,
          maxLength: 255,
          autoFocus: true,
          onChange: (e) => setName(e.target.value),
          onKeyDown: (e) => {
            if (e.key === "Enter") {
              saveRename();
            } else if (e.key === "Escape") {
              setRenaming(false);
            }
          }
        },
        void 0,
        false,
        {
          fileName: "../plugin/hiveforum/js/esm/src/thread/ThreadHeader.tsx",
          lineNumber: 95,
          columnNumber: 21
        },
        this
      ),
      allowtags && /* @__PURE__ */ jsxDEV(TagInput, { tags, onChange: setTags }, void 0, false, {
        fileName: "../plugin/hiveforum/js/esm/src/thread/ThreadHeader.tsx",
        lineNumber: 110,
        columnNumber: 35
      }, this),
      /* @__PURE__ */ jsxDEV("div", { className: "hf-rename__actions", children: [
        /* @__PURE__ */ jsxDEV("button", { type: "button", className: "btn btn-link btn-sm", onClick: () => setRenaming(false), children: t("cancel") }, void 0, false, {
          fileName: "../plugin/hiveforum/js/esm/src/thread/ThreadHeader.tsx",
          lineNumber: 112,
          columnNumber: 25
        }, this),
        /* @__PURE__ */ jsxDEV(
          "button",
          {
            type: "button",
            className: "btn btn-primary btn-sm",
            onClick: saveRename,
            disabled: busy === "rename" || !name.trim(),
            children: t("savechanges")
          },
          void 0,
          false,
          {
            fileName: "../plugin/hiveforum/js/esm/src/thread/ThreadHeader.tsx",
            lineNumber: 113,
            columnNumber: 25
          },
          this
        )
      ] }, void 0, true, {
        fileName: "../plugin/hiveforum/js/esm/src/thread/ThreadHeader.tsx",
        lineNumber: 111,
        columnNumber: 21
      }, this)
    ] }, void 0, true, {
      fileName: "../plugin/hiveforum/js/esm/src/thread/ThreadHeader.tsx",
      lineNumber: 93,
      columnNumber: 17
    }, this) : /* @__PURE__ */ jsxDEV("h2", { className: "hf-threadhead__title", children: [
      /* @__PURE__ */ jsxDEV("span", { dangerouslySetInnerHTML: { __html: d.name } }, void 0, false, {
        fileName: "../plugin/hiveforum/js/esm/src/thread/ThreadHeader.tsx",
        lineNumber: 119,
        columnNumber: 21
      }, this),
      d.canrename && /* @__PURE__ */ jsxDEV("button", { type: "button", className: "hf-iconbtn hf-iconbtn--sm", onClick: () => {
        setName(d.rawname);
        setTags(d.tags);
        setRenaming(true);
      }, title: t("renamediscussion"), "aria-label": t("renamediscussion"), children: /* @__PURE__ */ jsxDEV(Icon, { name: "edit", size: 15 }, void 0, false, {
        fileName: "../plugin/hiveforum/js/esm/src/thread/ThreadHeader.tsx",
        lineNumber: 126,
        columnNumber: 29
      }, this) }, void 0, false, {
        fileName: "../plugin/hiveforum/js/esm/src/thread/ThreadHeader.tsx",
        lineNumber: 121,
        columnNumber: 25
      }, this)
    ] }, void 0, true, {
      fileName: "../plugin/hiveforum/js/esm/src/thread/ThreadHeader.tsx",
      lineNumber: 118,
      columnNumber: 17
    }, this),
    !renaming && d.tags.length > 0 && /* @__PURE__ */ jsxDEV("div", { className: "hf-threadhead__tags", children: d.tags.map((tag) => /* @__PURE__ */ jsxDEV("a", { className: "hf-tag", href: `${forumurl}&tag=${encodeURIComponent(tag)}`, children: [
      "#",
      tag
    ] }, tag, true, {
      fileName: "../plugin/hiveforum/js/esm/src/thread/ThreadHeader.tsx",
      lineNumber: 135,
      columnNumber: 25
    }, this)) }, void 0, false, {
      fileName: "../plugin/hiveforum/js/esm/src/thread/ThreadHeader.tsx",
      lineNumber: 133,
      columnNumber: 17
    }, this),
    /* @__PURE__ */ jsxDEV("div", { className: "hf-threadhead__bar", children: [
      /* @__PURE__ */ jsxDEV("div", { className: "hf-threadhead__meta", children: [
        /* @__PURE__ */ jsxDEV(AvatarStack, { users: participants, max: 5 }, void 0, false, {
          fileName: "../plugin/hiveforum/js/esm/src/thread/ThreadHeader.tsx",
          lineNumber: 142,
          columnNumber: 21
        }, this),
        /* @__PURE__ */ jsxDEV("span", { children: [
          t("startedby", d.author.fullname),
          " ",
          /* @__PURE__ */ jsxDEV(RelativeTime, { timestamp: d.timecreated }, void 0, false, {
            fileName: "../plugin/hiveforum/js/esm/src/thread/ThreadHeader.tsx",
            lineNumber: 143,
            columnNumber: 63
          }, this)
        ] }, void 0, true, {
          fileName: "../plugin/hiveforum/js/esm/src/thread/ThreadHeader.tsx",
          lineNumber: 143,
          columnNumber: 21
        }, this),
        /* @__PURE__ */ jsxDEV("span", { className: "hf-threadhead__count", children: [
          /* @__PURE__ */ jsxDEV(Icon, { name: "chat", size: 14 }, void 0, false, {
            fileName: "../plugin/hiveforum/js/esm/src/thread/ThreadHeader.tsx",
            lineNumber: 145,
            columnNumber: 25
          }, this),
          " ",
          t.plural(replycount, "onereply", "repliescount", "noreplies")
        ] }, void 0, true, {
          fileName: "../plugin/hiveforum/js/esm/src/thread/ThreadHeader.tsx",
          lineNumber: 144,
          columnNumber: 21
        }, this)
      ] }, void 0, true, {
        fileName: "../plugin/hiveforum/js/esm/src/thread/ThreadHeader.tsx",
        lineNumber: 141,
        columnNumber: 17
      }, this),
      /* @__PURE__ */ jsxDEV("div", { className: "hf-threadhead__actions", children: [
        d.followforced && /* @__PURE__ */ jsxDEV("span", { className: "hf-chip", title: t("modeexplain_1"), children: [
          /* @__PURE__ */ jsxDEV(Icon, { name: "bellcheck", size: 15 }, void 0, false, {
            fileName: "../plugin/hiveforum/js/esm/src/thread/ThreadHeader.tsx",
            lineNumber: 150,
            columnNumber: 78
          }, this),
          " ",
          t("followforced")
        ] }, void 0, true, {
          fileName: "../plugin/hiveforum/js/esm/src/thread/ThreadHeader.tsx",
          lineNumber: 150,
          columnNumber: 25
        }, this),
        d.canfollow && /* @__PURE__ */ jsxDEV(
          "button",
          {
            type: "button",
            className: `btn btn-sm ${d.following ? "btn-secondary hf-btn-following" : "btn-outline-secondary"}`,
            "aria-pressed": d.following,
            disabled: busy === "following",
            onClick: () => toggle("following", !d.following, d.following ? t("unfollowed") : t("followedtoast")),
            children: [
              /* @__PURE__ */ jsxDEV(Icon, { name: d.following ? "bellcheck" : "bell", size: 15 }, void 0, false, {
                fileName: "../plugin/hiveforum/js/esm/src/thread/ThreadHeader.tsx",
                lineNumber: 156,
                columnNumber: 29
              }, this),
              d.following ? t("following_btn") : t("follow")
            ]
          },
          void 0,
          true,
          {
            fileName: "../plugin/hiveforum/js/esm/src/thread/ThreadHeader.tsx",
            lineNumber: 153,
            columnNumber: 25
          },
          this
        ),
        d.canpin && /* @__PURE__ */ jsxDEV(
          "button",
          {
            type: "button",
            className: `btn btn-sm ${d.pinned ? "btn-secondary" : "btn-outline-secondary"}`,
            "aria-pressed": d.pinned,
            disabled: busy === "pinned",
            onClick: () => toggle("pinned", !d.pinned, d.pinned ? t("unpinned") : t("pinnedtoast")),
            children: [
              /* @__PURE__ */ jsxDEV(Icon, { name: "pin", size: 15 }, void 0, false, {
                fileName: "../plugin/hiveforum/js/esm/src/thread/ThreadHeader.tsx",
                lineNumber: 164,
                columnNumber: 29
              }, this),
              " ",
              d.pinned ? t("unpindiscussion") : t("pindiscussion")
            ]
          },
          void 0,
          true,
          {
            fileName: "../plugin/hiveforum/js/esm/src/thread/ThreadHeader.tsx",
            lineNumber: 161,
            columnNumber: 25
          },
          this
        ),
        d.isquestion && d.canresolve && /* @__PURE__ */ jsxDEV(
          "button",
          {
            type: "button",
            className: `btn btn-sm ${d.resolved ? "btn-success" : "btn-outline-success"}`,
            "aria-pressed": d.resolved,
            disabled: busy === "resolved",
            onClick: () => toggle("resolved", !d.resolved, d.resolved ? t("reopenedtoast") : t("resolvedtoast")),
            children: [
              /* @__PURE__ */ jsxDEV(Icon, { name: "checkcircle", size: 15 }, void 0, false, {
                fileName: "../plugin/hiveforum/js/esm/src/thread/ThreadHeader.tsx",
                lineNumber: 171,
                columnNumber: 29
              }, this),
              " ",
              d.resolved ? t("markunresolved") : t("markresolved")
            ]
          },
          void 0,
          true,
          {
            fileName: "../plugin/hiveforum/js/esm/src/thread/ThreadHeader.tsx",
            lineNumber: 168,
            columnNumber: 25
          },
          this
        )
      ] }, void 0, true, {
        fileName: "../plugin/hiveforum/js/esm/src/thread/ThreadHeader.tsx",
        lineNumber: 148,
        columnNumber: 17
      }, this)
    ] }, void 0, true, {
      fileName: "../plugin/hiveforum/js/esm/src/thread/ThreadHeader.tsx",
      lineNumber: 140,
      columnNumber: 13
    }, this)
  ] }, void 0, true, {
    fileName: "../plugin/hiveforum/js/esm/src/thread/ThreadHeader.tsx",
    lineNumber: 80,
    columnNumber: 9
  }, this);
}
__name(ThreadHeader, "ThreadHeader");
export {
  ThreadHeader as default
};
//# sourceMappingURL=ThreadHeader.dev.js.map
