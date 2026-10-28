var __defProp = Object.defineProperty;
var __name = (target, value) => __defProp(target, "name", { value, configurable: true });
import { jsxDEV } from "react/jsx-dev-runtime";
/**
 * @mention autocomplete for the composer.
 *
 * Typing "@" followed by a few letters lists the people who can see the discussion
 * (the server applies capabilities, enrolment and groups). Choosing one inserts the token
 * "@[Name](user:id)", which the server turns into a profile link and a notification.
 *
 * @module     mod_hiveforum/common/Mentions
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
import { useCallback, useEffect, useId, useRef, useState } from "react";
import { useStrings } from "@moodle/lms/mod_hiveforum/common/i18n";
import { searchUsers } from "@moodle/lms/mod_hiveforum/common/repository";
import UserAvatar from "@moodle/lms/mod_hiveforum/common/UserAvatar";
const TRIGGER = /(^|\s)@([\p{L}\p{N}_.'-]{0,30})$/u;
const DEBOUNCE_MS = 150;
const mentionToken = /* @__PURE__ */ __name((user) => `@[${user.fullname.replace(/[[\]()\r\n]/g, "")}](user:${user.id}) `, "mentionToken");
function useMentions({ source, textareaRef, value, setValue }) {
  const listId = useId();
  const [query, setQuery] = useState(null);
  const [start, setStart] = useState(0);
  const [results, setResults] = useState([]);
  const [active, setActive] = useState(0);
  const requestRef = useRef(0);
  const detect = useCallback(() => {
    const el = textareaRef.current;
    if (!source || !el || el.selectionStart !== el.selectionEnd) {
      setQuery(null);
      return;
    }
    const caret = el.selectionStart;
    const match = el.value.slice(0, caret).match(TRIGGER);
    if (match) {
      setQuery(match[2]);
      setStart(caret - match[2].length - 1);
    } else {
      setQuery(null);
    }
  }, [source, textareaRef]);
  useEffect(detect, [value, detect]);
  useEffect(() => {
    if (query === null || !source) {
      setResults([]);
      return void 0;
    }
    const current = ++requestRef.current;
    const timer = window.setTimeout(() => {
      searchUsers(source.cmid, source.discussionid, query).then((result) => {
        if (current === requestRef.current) {
          setResults(result.users);
          setActive(0);
        }
      }).catch(() => setResults([]));
    }, DEBOUNCE_MS);
    return () => window.clearTimeout(timer);
  }, [query, source]);
  const close = useCallback(() => {
    setQuery(null);
    setResults([]);
  }, []);
  const choose = useCallback((user) => {
    const el = textareaRef.current;
    const caret = el ? el.selectionStart : value.length;
    const token = mentionToken(user);
    const next = value.slice(0, start) + token + value.slice(caret);
    setValue(next);
    close();
    window.requestAnimationFrame(() => {
      el?.focus();
      el?.setSelectionRange(start + token.length, start + token.length);
    });
  }, [textareaRef, value, start, setValue, close]);
  const startMention = useCallback(() => {
    const el = textareaRef.current;
    if (!el) {
      return;
    }
    const { selectionStart: s, selectionEnd: e } = el;
    const needsSpace = s > 0 && !/\s/.test(value[s - 1]);
    const insert = (needsSpace ? " " : "") + "@";
    setValue(value.slice(0, s) + insert + value.slice(e));
    window.requestAnimationFrame(() => {
      el.focus();
      el.setSelectionRange(s + insert.length, s + insert.length);
      detect();
    });
  }, [textareaRef, value, setValue, detect]);
  const onKeyDown = useCallback((e) => {
    if (query === null || !results.length) {
      return false;
    }
    if (e.key === "ArrowDown" || e.key === "ArrowUp") {
      e.preventDefault();
      const delta = e.key === "ArrowDown" ? 1 : -1;
      setActive((i) => (i + delta + results.length) % results.length);
      return true;
    }
    if (e.key === "Enter" || e.key === "Tab") {
      e.preventDefault();
      choose(results[active]);
      return true;
    }
    if (e.key === "Escape") {
      e.preventDefault();
      close();
      return true;
    }
    return false;
  }, [query, results, active, choose, close]);
  const open = query !== null && results.length > 0;
  return {
    open,
    listId,
    activeId: open ? `${listId}-${results[active]?.id}` : void 0,
    detect,
    onKeyDown,
    startMention,
    enabled: Boolean(source),
    list: open ? /* @__PURE__ */ jsxDEV(MentionList, { id: listId, users: results, active, onChoose: choose, onHover: setActive }, void 0, false, {
      fileName: "../plugin/hiveforum/js/esm/src/common/Mentions.tsx",
      lineNumber: 169,
      columnNumber: 13
    }, this) : null
  };
}
__name(useMentions, "useMentions");
function MentionList({ id, users, active, onChoose, onHover }) {
  const t = useStrings();
  return /* @__PURE__ */ jsxDEV("ul", { className: "hf-mentions", id, role: "listbox", "aria-label": t("mentionsomeone"), children: users.map((user, index) => /* @__PURE__ */ jsxDEV(
    "li",
    {
      id: `${id}-${user.id}`,
      role: "option",
      "aria-selected": index === active,
      className: `hf-mentions__item ${index === active ? "is-active" : ""}`,
      onMouseDown: (e) => {
        e.preventDefault();
        onChoose(user);
      },
      onMouseEnter: () => onHover(index),
      children: [
        /* @__PURE__ */ jsxDEV(UserAvatar, { user, size: "md", decorative: true }, void 0, false, {
          fileName: "../plugin/hiveforum/js/esm/src/common/Mentions.tsx",
          lineNumber: 197,
          columnNumber: 21
        }, this),
        /* @__PURE__ */ jsxDEV("span", { className: "hf-mentions__name", children: user.fullname }, void 0, false, {
          fileName: "../plugin/hiveforum/js/esm/src/common/Mentions.tsx",
          lineNumber: 198,
          columnNumber: 21
        }, this),
        user.isstaff && /* @__PURE__ */ jsxDEV("span", { className: "hf-badge hf-badge--staff", children: t("staff") }, void 0, false, {
          fileName: "../plugin/hiveforum/js/esm/src/common/Mentions.tsx",
          lineNumber: 199,
          columnNumber: 38
        }, this)
      ]
    },
    user.id,
    true,
    {
      fileName: "../plugin/hiveforum/js/esm/src/common/Mentions.tsx",
      lineNumber: 185,
      columnNumber: 17
    },
    this
  )) }, void 0, false, {
    fileName: "../plugin/hiveforum/js/esm/src/common/Mentions.tsx",
    lineNumber: 183,
    columnNumber: 9
  }, this);
}
__name(MentionList, "MentionList");
export {
  mentionToken,
  useMentions
};
//# sourceMappingURL=Mentions.dev.js.map
