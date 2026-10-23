var __defProp = Object.defineProperty;
var __name = (target, value) => __defProp(target, "name", { value, configurable: true });
import { jsxDEV } from "react/jsx-dev-runtime";
/**
 * Lightweight markdown composer: toolbar, auto-growing textarea, Ctrl/⌘+Enter to send,
 * Escape to cancel, and an unsent draft kept in the browser.
 *
 * @module     mod_hiveforum/common/Composer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
import { useCallback, useEffect, useId, useLayoutEffect, useRef, useState } from "react";
import Icon from "@moodle/lms/mod_hiveforum/common/Icon";
import { useStrings } from "@moodle/lms/mod_hiveforum/common/i18n";
import UserAvatar from "@moodle/lms/mod_hiveforum/common/UserAvatar";
const drafts = {
  get(key) {
    try {
      return window.localStorage.getItem(`mod_hiveforum/draft/${key}`) ?? "";
    } catch {
      return "";
    }
  },
  set(key, value) {
    try {
      if (value.trim()) {
        window.localStorage.setItem(`mod_hiveforum/draft/${key}`, value);
      } else {
        window.localStorage.removeItem(`mod_hiveforum/draft/${key}`);
      }
    } catch {
    }
  }
};
function Composer({
  draftKey,
  initialValue = "",
  placeholder,
  submitLabel,
  onSubmit,
  onCancel,
  autoFocus = false,
  user,
  compact = false,
  extra,
  label
}) {
  const t = useStrings();
  const id = useId();
  const textareaRef = useRef(null);
  const [value, setValue] = useState(() => initialValue || (draftKey ? drafts.get(draftKey) : ""));
  const [busy, setBusy] = useState(false);
  const [focused, setFocused] = useState(autoFocus);
  const formats = [
    { icon: "bold", label: t("format_bold"), before: "**", after: "**", placeholder: t("format_bold") },
    { icon: "italic", label: t("format_italic"), before: "_", after: "_", placeholder: t("format_italic") },
    { icon: "link", label: t("format_link"), before: "[", after: "](https://)", placeholder: t("format_linktext") },
    { icon: "code", label: t("format_code"), before: "`", after: "`", placeholder: "code" },
    { icon: "quote", label: t("format_quote"), before: "> ", linePrefix: true },
    { icon: "list", label: t("format_list"), before: "- ", linePrefix: true }
  ];
  useLayoutEffect(() => {
    const el = textareaRef.current;
    if (!el) {
      return;
    }
    el.style.height = "auto";
    el.style.height = `${Math.min(el.scrollHeight, 480)}px`;
  }, [value]);
  useEffect(() => {
    if (autoFocus) {
      const el = textareaRef.current;
      el?.focus();
      el?.setSelectionRange(el.value.length, el.value.length);
    }
  }, [autoFocus]);
  useEffect(() => {
    if (draftKey) {
      drafts.set(draftKey, value);
    }
  }, [draftKey, value]);
  const applyFormat = useCallback((format) => {
    const el = textareaRef.current;
    if (!el) {
      return;
    }
    const { selectionStart: start, selectionEnd: end } = el;
    const selected = value.slice(start, end);
    let next;
    let cursorStart;
    let cursorEnd;
    if (format.linePrefix) {
      const lineStart = value.lastIndexOf("\n", start - 1) + 1;
      const block = value.slice(lineStart, end);
      const prefixed = block.split("\n").map((line) => format.before + line).join("\n");
      next = value.slice(0, lineStart) + prefixed + value.slice(end);
      cursorStart = lineStart;
      cursorEnd = lineStart + prefixed.length;
    } else {
      const inner = selected || format.placeholder || "";
      next = value.slice(0, start) + format.before + inner + (format.after ?? "") + value.slice(end);
      cursorStart = start + format.before.length;
      cursorEnd = cursorStart + inner.length;
    }
    setValue(next);
    window.requestAnimationFrame(() => {
      el.focus();
      el.setSelectionRange(cursorStart, cursorEnd);
    });
  }, [value]);
  const submit = useCallback(async () => {
    if (!value.trim() || busy) {
      return;
    }
    setBusy(true);
    try {
      await onSubmit(value);
      setValue("");
      if (draftKey) {
        drafts.set(draftKey, "");
      }
    } catch {
    } finally {
      setBusy(false);
    }
  }, [value, busy, onSubmit, draftKey]);
  const onKeyDown = /* @__PURE__ */ __name((e) => {
    if (e.key === "Enter" && (e.ctrlKey || e.metaKey)) {
      e.preventDefault();
      submit();
    } else if (e.key === "Escape" && onCancel) {
      e.preventDefault();
      onCancel();
    } else if ((e.ctrlKey || e.metaKey) && (e.key === "b" || e.key === "i")) {
      e.preventDefault();
      applyFormat(formats[e.key === "b" ? 0 : 1]);
    }
  }, "onKeyDown");
  const expanded = focused || value.length > 0 || !compact;
  return /* @__PURE__ */ jsxDEV("div", { className: `hf-composer ${expanded ? "is-expanded" : ""} ${compact ? "hf-composer--compact" : ""}`, children: [
    user && /* @__PURE__ */ jsxDEV("div", { className: "hf-composer__avatar", children: /* @__PURE__ */ jsxDEV(UserAvatar, { user, size: "md", decorative: true }, void 0, false, {
      fileName: "../plugin/hiveforum/js/esm/src/common/Composer.tsx",
      lineNumber: 187,
      columnNumber: 59
    }, this) }, void 0, false, {
      fileName: "../plugin/hiveforum/js/esm/src/common/Composer.tsx",
      lineNumber: 187,
      columnNumber: 22
    }, this),
    /* @__PURE__ */ jsxDEV("div", { className: "hf-composer__box", children: [
      /* @__PURE__ */ jsxDEV("label", { htmlFor: id, className: "visually-hidden", children: label }, void 0, false, {
        fileName: "../plugin/hiveforum/js/esm/src/common/Composer.tsx",
        lineNumber: 189,
        columnNumber: 17
      }, this),
      /* @__PURE__ */ jsxDEV(
        "textarea",
        {
          id,
          ref: textareaRef,
          className: "hf-composer__input",
          value,
          placeholder,
          rows: compact && !expanded ? 1 : 3,
          onChange: (e) => setValue(e.target.value),
          onKeyDown,
          onFocus: () => setFocused(true),
          onBlur: () => setFocused(false),
          disabled: busy
        },
        void 0,
        false,
        {
          fileName: "../plugin/hiveforum/js/esm/src/common/Composer.tsx",
          lineNumber: 190,
          columnNumber: 17
        },
        this
      ),
      expanded && /* @__PURE__ */ jsxDEV("div", { className: "hf-composer__bar", children: [
        /* @__PURE__ */ jsxDEV("div", { className: "hf-composer__tools", role: "toolbar", "aria-label": t("formatting"), children: [
          formats.map((format) => /* @__PURE__ */ jsxDEV(
            "button",
            {
              type: "button",
              className: "hf-iconbtn hf-iconbtn--sm",
              title: format.label,
              "aria-label": format.label,
              onMouseDown: (e) => e.preventDefault(),
              onClick: () => applyFormat(format),
              children: /* @__PURE__ */ jsxDEV(Icon, { name: format.icon, size: 15 }, void 0, false, {
                fileName: "../plugin/hiveforum/js/esm/src/common/Composer.tsx",
                lineNumber: 216,
                columnNumber: 37
              }, this)
            },
            format.icon,
            false,
            {
              fileName: "../plugin/hiveforum/js/esm/src/common/Composer.tsx",
              lineNumber: 207,
              columnNumber: 33
            },
            this
          )),
          /* @__PURE__ */ jsxDEV("span", { className: "hf-composer__hint", children: t("markdownhint") }, void 0, false, {
            fileName: "../plugin/hiveforum/js/esm/src/common/Composer.tsx",
            lineNumber: 219,
            columnNumber: 29
          }, this)
        ] }, void 0, true, {
          fileName: "../plugin/hiveforum/js/esm/src/common/Composer.tsx",
          lineNumber: 205,
          columnNumber: 25
        }, this),
        /* @__PURE__ */ jsxDEV("div", { className: "hf-composer__actions", children: [
          extra,
          onCancel && /* @__PURE__ */ jsxDEV("button", { type: "button", className: "btn btn-link btn-sm", onClick: onCancel, disabled: busy, children: t("cancel") }, void 0, false, {
            fileName: "../plugin/hiveforum/js/esm/src/common/Composer.tsx",
            lineNumber: 224,
            columnNumber: 33
          }, this),
          /* @__PURE__ */ jsxDEV(
            "button",
            {
              type: "button",
              className: "btn btn-primary btn-sm hf-btn-send",
              onClick: submit,
              disabled: busy || !value.trim(),
              title: t("sendshortcut"),
              children: [
                busy ? /* @__PURE__ */ jsxDEV("span", { className: "spinner-border spinner-border-sm", "aria-hidden": "true" }, void 0, false, {
                  fileName: "../plugin/hiveforum/js/esm/src/common/Composer.tsx",
                  lineNumber: 235,
                  columnNumber: 41
                }, this) : null,
                /* @__PURE__ */ jsxDEV("span", { children: submitLabel }, void 0, false, {
                  fileName: "../plugin/hiveforum/js/esm/src/common/Composer.tsx",
                  lineNumber: 236,
                  columnNumber: 33
                }, this)
              ]
            },
            void 0,
            true,
            {
              fileName: "../plugin/hiveforum/js/esm/src/common/Composer.tsx",
              lineNumber: 228,
              columnNumber: 29
            },
            this
          )
        ] }, void 0, true, {
          fileName: "../plugin/hiveforum/js/esm/src/common/Composer.tsx",
          lineNumber: 221,
          columnNumber: 25
        }, this)
      ] }, void 0, true, {
        fileName: "../plugin/hiveforum/js/esm/src/common/Composer.tsx",
        lineNumber: 204,
        columnNumber: 21
      }, this)
    ] }, void 0, true, {
      fileName: "../plugin/hiveforum/js/esm/src/common/Composer.tsx",
      lineNumber: 188,
      columnNumber: 13
    }, this)
  ] }, void 0, true, {
    fileName: "../plugin/hiveforum/js/esm/src/common/Composer.tsx",
    lineNumber: 186,
    columnNumber: 9
  }, this);
}
__name(Composer, "Composer");
export {
  Composer as default
};
//# sourceMappingURL=Composer.dev.js.map
