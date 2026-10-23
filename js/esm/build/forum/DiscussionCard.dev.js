var __defProp = Object.defineProperty;
var __name = (target, value) => __defProp(target, "name", { value, configurable: true });
import { jsxDEV } from "react/jsx-dev-runtime";
/**
 * A discussion in the list.
 *
 * The whole card is clickable through a stretched title link; tags remain
 * separate buttons above it (filtering by tag).
 *
 * @module     mod_hiveforum/forum/DiscussionCard
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
import Icon from "@moodle/lms/mod_hiveforum/common/Icon";
import { useStrings } from "@moodle/lms/mod_hiveforum/common/i18n";
import { ReactionSummary } from "@moodle/lms/mod_hiveforum/common/ReactionBar";
import { RelativeTime } from "@moodle/lms/mod_hiveforum/common/time";
import UserAvatar, { AvatarStack } from "@moodle/lms/mod_hiveforum/common/UserAvatar";
function DiscussionCard({ discussion: d, activeTag, onTag }) {
  const t = useStrings();
  const classes = [
    "hf-card",
    d.unread ? "is-unread" : "",
    d.pinned ? "is-pinned" : "",
    d.isquestion ? d.resolved ? "is-resolved" : "is-open-question" : ""
  ].filter(Boolean).join(" ");
  return /* @__PURE__ */ jsxDEV("article", { className: classes, "aria-labelledby": `hf-d-${d.id}`, children: [
    /* @__PURE__ */ jsxDEV("div", { className: "hf-card__avatar", children: /* @__PURE__ */ jsxDEV(UserAvatar, { user: d.author, size: "lg", decorative: true }, void 0, false, {
      fileName: "../plugin/hiveforum/js/esm/src/forum/DiscussionCard.tsx",
      lineNumber: 51,
      columnNumber: 17
    }, this) }, void 0, false, {
      fileName: "../plugin/hiveforum/js/esm/src/forum/DiscussionCard.tsx",
      lineNumber: 50,
      columnNumber: 13
    }, this),
    /* @__PURE__ */ jsxDEV("div", { className: "hf-card__main", children: [
      /* @__PURE__ */ jsxDEV("div", { className: "hf-card__badges", children: [
        d.pinned && /* @__PURE__ */ jsxDEV("span", { className: "hf-badge hf-badge--pinned", children: [
          /* @__PURE__ */ jsxDEV(Icon, { name: "pin", size: 12 }, void 0, false, {
            fileName: "../plugin/hiveforum/js/esm/src/forum/DiscussionCard.tsx",
            lineNumber: 55,
            columnNumber: 78
          }, this),
          t("pinned")
        ] }, void 0, true, {
          fileName: "../plugin/hiveforum/js/esm/src/forum/DiscussionCard.tsx",
          lineNumber: 55,
          columnNumber: 34
        }, this),
        d.isquestion && !d.resolved && /* @__PURE__ */ jsxDEV("span", { className: "hf-badge hf-badge--question", children: [
          /* @__PURE__ */ jsxDEV(Icon, { name: "question", size: 12 }, void 0, false, {
            fileName: "../plugin/hiveforum/js/esm/src/forum/DiscussionCard.tsx",
            lineNumber: 57,
            columnNumber: 71
          }, this),
          t("question")
        ] }, void 0, true, {
          fileName: "../plugin/hiveforum/js/esm/src/forum/DiscussionCard.tsx",
          lineNumber: 57,
          columnNumber: 25
        }, this),
        d.isquestion && d.resolved && /* @__PURE__ */ jsxDEV("span", { className: "hf-badge hf-badge--resolved", children: [
          /* @__PURE__ */ jsxDEV(Icon, { name: "check", size: 12 }, void 0, false, {
            fileName: "../plugin/hiveforum/js/esm/src/forum/DiscussionCard.tsx",
            lineNumber: 60,
            columnNumber: 71
          }, this),
          t("resolved")
        ] }, void 0, true, {
          fileName: "../plugin/hiveforum/js/esm/src/forum/DiscussionCard.tsx",
          lineNumber: 60,
          columnNumber: 25
        }, this),
        d.following && /* @__PURE__ */ jsxDEV("span", { className: "hf-badge hf-badge--ghost", children: [
          /* @__PURE__ */ jsxDEV(Icon, { name: "bellcheck", size: 12 }, void 0, false, {
            fileName: "../plugin/hiveforum/js/esm/src/forum/DiscussionCard.tsx",
            lineNumber: 62,
            columnNumber: 80
          }, this),
          t("followed")
        ] }, void 0, true, {
          fileName: "../plugin/hiveforum/js/esm/src/forum/DiscussionCard.tsx",
          lineNumber: 62,
          columnNumber: 37
        }, this)
      ] }, void 0, true, {
        fileName: "../plugin/hiveforum/js/esm/src/forum/DiscussionCard.tsx",
        lineNumber: 54,
        columnNumber: 17
      }, this),
      /* @__PURE__ */ jsxDEV("h3", { className: "hf-card__title", id: `hf-d-${d.id}`, children: [
        d.unread && /* @__PURE__ */ jsxDEV("span", { className: "hf-dot", title: t("unreadactivity"), children: /* @__PURE__ */ jsxDEV("span", { className: "visually-hidden", children: t("unreadactivity") }, void 0, false, {
          fileName: "../plugin/hiveforum/js/esm/src/forum/DiscussionCard.tsx",
          lineNumber: 65,
          columnNumber: 87
        }, this) }, void 0, false, {
          fileName: "../plugin/hiveforum/js/esm/src/forum/DiscussionCard.tsx",
          lineNumber: 65,
          columnNumber: 34
        }, this),
        /* @__PURE__ */ jsxDEV("a", { href: d.url, className: "hf-card__link", dangerouslySetInnerHTML: { __html: d.name } }, void 0, false, {
          fileName: "../plugin/hiveforum/js/esm/src/forum/DiscussionCard.tsx",
          lineNumber: 66,
          columnNumber: 21
        }, this)
      ] }, void 0, true, {
        fileName: "../plugin/hiveforum/js/esm/src/forum/DiscussionCard.tsx",
        lineNumber: 64,
        columnNumber: 17
      }, this),
      d.excerpt && /* @__PURE__ */ jsxDEV("p", { className: "hf-card__excerpt", children: d.excerpt }, void 0, false, {
        fileName: "../plugin/hiveforum/js/esm/src/forum/DiscussionCard.tsx",
        lineNumber: 68,
        columnNumber: 31
      }, this),
      /* @__PURE__ */ jsxDEV("div", { className: "hf-card__meta", children: [
        /* @__PURE__ */ jsxDEV("span", { className: "hf-card__author", children: d.author.fullname }, void 0, false, {
          fileName: "../plugin/hiveforum/js/esm/src/forum/DiscussionCard.tsx",
          lineNumber: 70,
          columnNumber: 21
        }, this),
        d.lastauthor ? /* @__PURE__ */ jsxDEV("span", { children: [
          t("lastreplyby", d.lastauthor.fullname),
          " ",
          /* @__PURE__ */ jsxDEV(RelativeTime, { timestamp: d.timemodified }, void 0, false, {
            fileName: "../plugin/hiveforum/js/esm/src/forum/DiscussionCard.tsx",
            lineNumber: 72,
            columnNumber: 73
          }, this)
        ] }, void 0, true, {
          fileName: "../plugin/hiveforum/js/esm/src/forum/DiscussionCard.tsx",
          lineNumber: 72,
          columnNumber: 25
        }, this) : /* @__PURE__ */ jsxDEV(RelativeTime, { timestamp: d.timecreated }, void 0, false, {
          fileName: "../plugin/hiveforum/js/esm/src/forum/DiscussionCard.tsx",
          lineNumber: 74,
          columnNumber: 25
        }, this),
        d.tags.length > 0 && /* @__PURE__ */ jsxDEV("span", { className: "hf-card__tags", children: d.tags.map((tag) => /* @__PURE__ */ jsxDEV(
          "button",
          {
            type: "button",
            className: `hf-tag ${tag === activeTag ? "is-active" : ""}`,
            onClick: () => onTag(tag === activeTag ? "" : tag),
            "aria-pressed": tag === activeTag,
            children: [
              "#",
              tag
            ]
          },
          tag,
          true,
          {
            fileName: "../plugin/hiveforum/js/esm/src/forum/DiscussionCard.tsx",
            lineNumber: 79,
            columnNumber: 33
          },
          this
        )) }, void 0, false, {
          fileName: "../plugin/hiveforum/js/esm/src/forum/DiscussionCard.tsx",
          lineNumber: 77,
          columnNumber: 25
        }, this)
      ] }, void 0, true, {
        fileName: "../plugin/hiveforum/js/esm/src/forum/DiscussionCard.tsx",
        lineNumber: 69,
        columnNumber: 17
      }, this)
    ] }, void 0, true, {
      fileName: "../plugin/hiveforum/js/esm/src/forum/DiscussionCard.tsx",
      lineNumber: 53,
      columnNumber: 13
    }, this),
    /* @__PURE__ */ jsxDEV("div", { className: "hf-card__side", children: [
      /* @__PURE__ */ jsxDEV(
        "span",
        {
          className: `hf-replies ${d.replycount === 0 ? "is-zero" : ""}`,
          title: t.plural(d.replycount, "onereply", "repliescount", "noreplies"),
          children: [
            /* @__PURE__ */ jsxDEV(Icon, { name: "chat", size: 15 }, void 0, false, {
              fileName: "../plugin/hiveforum/js/esm/src/forum/DiscussionCard.tsx",
              lineNumber: 96,
              columnNumber: 21
            }, this),
            /* @__PURE__ */ jsxDEV("span", { "aria-hidden": "true", children: d.replycount }, void 0, false, {
              fileName: "../plugin/hiveforum/js/esm/src/forum/DiscussionCard.tsx",
              lineNumber: 97,
              columnNumber: 21
            }, this),
            /* @__PURE__ */ jsxDEV("span", { className: "visually-hidden", children: t.plural(d.replycount, "onereply", "repliescount", "noreplies") }, void 0, false, {
              fileName: "../plugin/hiveforum/js/esm/src/forum/DiscussionCard.tsx",
              lineNumber: 98,
              columnNumber: 21
            }, this)
          ]
        },
        void 0,
        true,
        {
          fileName: "../plugin/hiveforum/js/esm/src/forum/DiscussionCard.tsx",
          lineNumber: 94,
          columnNumber: 17
        },
        this
      ),
      /* @__PURE__ */ jsxDEV(ReactionSummary, { reactions: d.reactions }, void 0, false, {
        fileName: "../plugin/hiveforum/js/esm/src/forum/DiscussionCard.tsx",
        lineNumber: 100,
        columnNumber: 17
      }, this),
      /* @__PURE__ */ jsxDEV(AvatarStack, { users: d.participants }, void 0, false, {
        fileName: "../plugin/hiveforum/js/esm/src/forum/DiscussionCard.tsx",
        lineNumber: 101,
        columnNumber: 17
      }, this)
    ] }, void 0, true, {
      fileName: "../plugin/hiveforum/js/esm/src/forum/DiscussionCard.tsx",
      lineNumber: 93,
      columnNumber: 13
    }, this)
  ] }, void 0, true, {
    fileName: "../plugin/hiveforum/js/esm/src/forum/DiscussionCard.tsx",
    lineNumber: 49,
    columnNumber: 9
  }, this);
}
__name(DiscussionCard, "DiscussionCard");
function DiscussionCardSkeleton() {
  return /* @__PURE__ */ jsxDEV("div", { className: "hf-card hf-card--skeleton", "aria-hidden": "true", children: [
    /* @__PURE__ */ jsxDEV("span", { className: "hf-skel hf-skel--avatar" }, void 0, false, {
      fileName: "../plugin/hiveforum/js/esm/src/forum/DiscussionCard.tsx",
      lineNumber: 113,
      columnNumber: 13
    }, this),
    /* @__PURE__ */ jsxDEV("div", { className: "hf-card__main", children: [
      /* @__PURE__ */ jsxDEV("span", { className: "hf-skel hf-skel--title" }, void 0, false, {
        fileName: "../plugin/hiveforum/js/esm/src/forum/DiscussionCard.tsx",
        lineNumber: 115,
        columnNumber: 17
      }, this),
      /* @__PURE__ */ jsxDEV("span", { className: "hf-skel hf-skel--line" }, void 0, false, {
        fileName: "../plugin/hiveforum/js/esm/src/forum/DiscussionCard.tsx",
        lineNumber: 116,
        columnNumber: 17
      }, this),
      /* @__PURE__ */ jsxDEV("span", { className: "hf-skel hf-skel--line hf-skel--short" }, void 0, false, {
        fileName: "../plugin/hiveforum/js/esm/src/forum/DiscussionCard.tsx",
        lineNumber: 117,
        columnNumber: 17
      }, this)
    ] }, void 0, true, {
      fileName: "../plugin/hiveforum/js/esm/src/forum/DiscussionCard.tsx",
      lineNumber: 114,
      columnNumber: 13
    }, this)
  ] }, void 0, true, {
    fileName: "../plugin/hiveforum/js/esm/src/forum/DiscussionCard.tsx",
    lineNumber: 112,
    columnNumber: 9
  }, this);
}
__name(DiscussionCardSkeleton, "DiscussionCardSkeleton");
export {
  DiscussionCardSkeleton,
  DiscussionCard as default
};
//# sourceMappingURL=DiscussionCard.dev.js.map
