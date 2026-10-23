var __defProp = Object.defineProperty;
var __name = (target, value) => __defProp(target, "name", { value, configurable: true });
import { Fragment, jsxDEV } from "react/jsx-dev-runtime";
/**
 * Chip-style tag input: Enter, comma or Tab adds a tag, Backspace removes the last one.
 *
 * @module     mod_hiveforum/common/TagInput
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
import { useId, useState } from "react";
import Icon from "@moodle/lms/mod_hiveforum/common/Icon";
import { useStrings } from "@moodle/lms/mod_hiveforum/common/i18n";
const MAX_TAGS = 5;
const normaliseTag = /* @__PURE__ */ __name((tag) => tag.trim().replace(/^#+/, "").toLowerCase().replace(/[,<>"]/g, "").slice(0, 50), "normaliseTag");
function TagInput({ tags, onChange, suggestions = [] }) {
  const t = useStrings();
  const id = useId();
  const listId = useId();
  const [draft, setDraft] = useState("");
  const add = /* @__PURE__ */ __name((raw) => {
    const tag = normaliseTag(raw);
    if (tag && !tags.includes(tag) && tags.length < MAX_TAGS) {
      onChange([...tags, tag]);
    }
    setDraft("");
  }, "add");
  const onKeyDown = /* @__PURE__ */ __name((e) => {
    if ((e.key === "Enter" || e.key === "," || e.key === "Tab" && draft) && draft.trim()) {
      e.preventDefault();
      add(draft);
    } else if (e.key === "Backspace" && !draft && tags.length) {
      onChange(tags.slice(0, -1));
    }
  }, "onKeyDown");
  const full = tags.length >= MAX_TAGS;
  return /* @__PURE__ */ jsxDEV("div", { className: "hf-taginput", children: [
    /* @__PURE__ */ jsxDEV(Icon, { name: "tag", size: 15, className: "hf-taginput__icon" }, void 0, false, {
      fileName: "../plugin/hiveforum/js/esm/src/common/TagInput.tsx",
      lineNumber: 65,
      columnNumber: 13
    }, this),
    tags.map((tag) => /* @__PURE__ */ jsxDEV("span", { className: "hf-tag hf-tag--removable", children: [
      "#",
      tag,
      /* @__PURE__ */ jsxDEV(
        "button",
        {
          type: "button",
          onClick: () => onChange(tags.filter((x) => x !== tag)),
          "aria-label": t("removetag", tag),
          children: /* @__PURE__ */ jsxDEV(Icon, { name: "x", size: 12 }, void 0, false, {
            fileName: "../plugin/hiveforum/js/esm/src/common/TagInput.tsx",
            lineNumber: 71,
            columnNumber: 25
          }, this)
        },
        void 0,
        false,
        {
          fileName: "../plugin/hiveforum/js/esm/src/common/TagInput.tsx",
          lineNumber: 69,
          columnNumber: 21
        },
        this
      )
    ] }, tag, true, {
      fileName: "../plugin/hiveforum/js/esm/src/common/TagInput.tsx",
      lineNumber: 67,
      columnNumber: 17
    }, this)),
    !full && /* @__PURE__ */ jsxDEV(Fragment, { children: [
      /* @__PURE__ */ jsxDEV("label", { htmlFor: id, className: "visually-hidden", children: t("addtags") }, void 0, false, {
        fileName: "../plugin/hiveforum/js/esm/src/common/TagInput.tsx",
        lineNumber: 77,
        columnNumber: 21
      }, this),
      /* @__PURE__ */ jsxDEV(
        "input",
        {
          id,
          className: "hf-taginput__field",
          value: draft,
          list: suggestions.length ? listId : void 0,
          placeholder: tags.length ? "" : t("addtags"),
          onChange: (e) => setDraft(e.target.value),
          onKeyDown,
          onBlur: () => draft.trim() && add(draft)
        },
        void 0,
        false,
        {
          fileName: "../plugin/hiveforum/js/esm/src/common/TagInput.tsx",
          lineNumber: 78,
          columnNumber: 21
        },
        this
      ),
      suggestions.length > 0 && /* @__PURE__ */ jsxDEV("datalist", { id: listId, children: suggestions.filter((s) => !tags.includes(s)).map((s) => /* @__PURE__ */ jsxDEV("option", { value: s }, s, false, {
        fileName: "../plugin/hiveforum/js/esm/src/common/TagInput.tsx",
        lineNumber: 90,
        columnNumber: 86
      }, this)) }, void 0, false, {
        fileName: "../plugin/hiveforum/js/esm/src/common/TagInput.tsx",
        lineNumber: 89,
        columnNumber: 25
      }, this)
    ] }, void 0, true, {
      fileName: "../plugin/hiveforum/js/esm/src/common/TagInput.tsx",
      lineNumber: 76,
      columnNumber: 17
    }, this)
  ] }, void 0, true, {
    fileName: "../plugin/hiveforum/js/esm/src/common/TagInput.tsx",
    lineNumber: 64,
    columnNumber: 9
  }, this);
}
__name(TagInput, "TagInput");
export {
  MAX_TAGS,
  TagInput as default,
  normaliseTag
};
//# sourceMappingURL=TagInput.dev.js.map
