var __defProp = Object.defineProperty;
var __name = (target, value) => __defProp(target, "name", { value, configurable: true });
import { jsxDEV } from "react/jsx-dev-runtime";
/**
 * Language strings.
 *
 * Strings are sent by the server in the initial props (see \mod_hiveforum\output\strings),
 * so the UI renders instantly in the right language, without a string round-trip.
 *
 * @module     mod_hiveforum/common/i18n
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
import { createContext, useCallback, useContext } from "react";
const StringsContext = createContext({});
function StringsProvider({ strings, children }) {
  return /* @__PURE__ */ jsxDEV(StringsContext.Provider, { value: strings, children }, void 0, false, {
    fileName: "../plugin/hiveforum/js/esm/src/common/i18n.tsx",
    lineNumber: 32,
    columnNumber: 12
  }, this);
}
__name(StringsProvider, "StringsProvider");
const interpolate = /* @__PURE__ */ __name((text, a) => {
  if (a === void 0) {
    return text;
  }
  if (typeof a === "object") {
    return text.replace(/\{\$a->(\w+)\}/g, (match, key) => key in a ? String(a[key]) : match);
  }
  return text.replace(/\{\$a\}/g, String(a));
}, "interpolate");
function useStrings() {
  const strings = useContext(StringsContext);
  const t = useCallback((key, a) => interpolate(strings[key] ?? `[[${key}]]`, a), [strings]);
  t.plural = (count, one, many, zero) => {
    if (count === 0 && zero) {
      return t(zero, count);
    }
    return t(count === 1 ? one : many, count);
  };
  return t;
}
__name(useStrings, "useStrings");
export {
  StringsProvider,
  interpolate,
  useStrings
};
//# sourceMappingURL=i18n.dev.js.map
