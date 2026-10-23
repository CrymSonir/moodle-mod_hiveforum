var __defProp = Object.defineProperty;
var __name = (target, value) => __defProp(target, "name", { value, configurable: true });
import { Fragment, jsxDEV } from "react/jsx-dev-runtime";
/**
 * Reactions: interactive chips on posts (optimistic update), read-only summary on cards.
 *
 * @module     mod_hiveforum/common/ReactionBar
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
import { useState } from "react";
import { useStrings } from "@moodle/lms/mod_hiveforum/common/i18n";
import { exception } from "@moodle/lms/mod_hiveforum/common/notify";
import { toggleReaction } from "@moodle/lms/mod_hiveforum/common/repository";
import { REACTIONS } from "@moodle/lms/mod_hiveforum/common/types";
const emojiOf = /* @__PURE__ */ __name((type) => REACTIONS.find((r) => r.type === type)?.emoji ?? "", "emojiOf");
function ReactionBar({ postid, reactions, canreact, onChange }) {
  const t = useStrings();
  const [pending, setPending] = useState(null);
  const [pop, setPop] = useState(null);
  const toggle = /* @__PURE__ */ __name((type) => {
    if (pending) {
      return;
    }
    const previous = reactions;
    onChange(reactions.map((r) => r.type === type ? { ...r, active: !r.active, count: r.count + (r.active ? -1 : 1) } : r));
    if (!previous.find((r) => r.type === type)?.active) {
      setPop(type);
      window.setTimeout(() => setPop(null), 450);
    }
    setPending(type);
    toggleReaction(postid, type).then((result) => onChange(result.reactions)).catch((error) => {
      onChange(previous);
      exception(error);
    }).finally(() => setPending(null));
  }, "toggle");
  const visible = canreact ? reactions : reactions.filter((r) => r.count > 0);
  if (!visible.length) {
    return null;
  }
  return /* @__PURE__ */ jsxDEV("div", { className: "hf-reactions", role: "group", "aria-label": t("reactions"), children: visible.map((r) => {
    const label = t(`reaction_${r.type}`);
    const content = /* @__PURE__ */ jsxDEV(Fragment, { children: [
      /* @__PURE__ */ jsxDEV("span", { className: `hf-reaction__emoji ${pop === r.type ? "is-popping" : ""}`, "aria-hidden": "true", children: emojiOf(r.type) }, void 0, false, {
        fileName: "../plugin/hiveforum/js/esm/src/common/ReactionBar.tsx",
        lineNumber: 77,
        columnNumber: 25
      }, this),
      /* @__PURE__ */ jsxDEV("span", { className: "hf-reaction__count", "aria-hidden": "true", children: r.count > 0 ? r.count : "" }, void 0, false, {
        fileName: "../plugin/hiveforum/js/esm/src/common/ReactionBar.tsx",
        lineNumber: 80,
        columnNumber: 25
      }, this),
      /* @__PURE__ */ jsxDEV("span", { className: "visually-hidden", children: t("reactioncount", { reaction: label, count: r.count }) }, void 0, false, {
        fileName: "../plugin/hiveforum/js/esm/src/common/ReactionBar.tsx",
        lineNumber: 81,
        columnNumber: 25
      }, this)
    ] }, void 0, true, {
      fileName: "../plugin/hiveforum/js/esm/src/common/ReactionBar.tsx",
      lineNumber: 76,
      columnNumber: 21
    }, this);
    if (!canreact) {
      return /* @__PURE__ */ jsxDEV("span", { className: "hf-reaction is-static", children: content }, r.type, false, {
        fileName: "../plugin/hiveforum/js/esm/src/common/ReactionBar.tsx",
        lineNumber: 85,
        columnNumber: 28
      }, this);
    }
    return /* @__PURE__ */ jsxDEV(
      "button",
      {
        type: "button",
        className: `hf-reaction hf-reaction--${r.type} ${r.active ? "is-active" : ""} ${r.count === 0 ? "is-empty" : ""}`,
        "aria-pressed": r.active,
        title: label,
        onClick: () => toggle(r.type),
        children: content
      },
      r.type,
      false,
      {
        fileName: "../plugin/hiveforum/js/esm/src/common/ReactionBar.tsx",
        lineNumber: 88,
        columnNumber: 21
      },
      this
    );
  }) }, void 0, false, {
    fileName: "../plugin/hiveforum/js/esm/src/common/ReactionBar.tsx",
    lineNumber: 72,
    columnNumber: 9
  }, this);
}
__name(ReactionBar, "ReactionBar");
function ReactionSummary({ reactions }) {
  const t = useStrings();
  const total = reactions.reduce((sum, r) => sum + r.count, 0);
  if (!total) {
    return null;
  }
  return /* @__PURE__ */ jsxDEV("span", { className: "hf-reaction-summary", title: reactions.filter((r) => r.count).map((r) => `${emojiOf(r.type)} ${r.count}`).join("  "), children: [
    /* @__PURE__ */ jsxDEV("span", { className: "hf-reaction-summary__emojis", "aria-hidden": "true", children: reactions.filter((r) => r.count > 0).map((r) => /* @__PURE__ */ jsxDEV("span", { children: emojiOf(r.type) }, r.type, false, {
      fileName: "../plugin/hiveforum/js/esm/src/common/ReactionBar.tsx",
      lineNumber: 117,
      columnNumber: 66
    }, this)) }, void 0, false, {
      fileName: "../plugin/hiveforum/js/esm/src/common/ReactionBar.tsx",
      lineNumber: 116,
      columnNumber: 13
    }, this),
    /* @__PURE__ */ jsxDEV("span", { "aria-hidden": "true", children: total }, void 0, false, {
      fileName: "../plugin/hiveforum/js/esm/src/common/ReactionBar.tsx",
      lineNumber: 119,
      columnNumber: 13
    }, this),
    /* @__PURE__ */ jsxDEV("span", { className: "visually-hidden", children: t("reactionstotal", total) }, void 0, false, {
      fileName: "../plugin/hiveforum/js/esm/src/common/ReactionBar.tsx",
      lineNumber: 120,
      columnNumber: 13
    }, this)
  ] }, void 0, true, {
    fileName: "../plugin/hiveforum/js/esm/src/common/ReactionBar.tsx",
    lineNumber: 114,
    columnNumber: 9
  }, this);
}
__name(ReactionSummary, "ReactionSummary");
export {
  ReactionSummary,
  ReactionBar as default
};
//# sourceMappingURL=ReactionBar.dev.js.map
