var __defProp = Object.defineProperty;
var __name = (target, value) => __defProp(target, "name", { value, configurable: true });
import { jsxDEV } from "react/jsx-dev-runtime";
/**
 * Relative times ("il y a 5 min") that refresh themselves, in the user's language and timezone.
 *
 * @module     mod_hiveforum/common/time
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
import { useEffect, useState } from "react";
import config from "@moodle/lms/core/config";
const locale = /* @__PURE__ */ __name(() => {
  const [lang, region] = (config.language || "en").split("_");
  return region ? `${lang}-${region.toUpperCase()}` : lang;
}, "locale");
const timeZone = /* @__PURE__ */ __name(() => {
  const tz = config.usertimezone;
  if (!tz || tz === "99") {
    return void 0;
  }
  try {
    new Intl.DateTimeFormat("en", { timeZone: tz });
    return tz;
  } catch {
    return void 0;
  }
}, "timeZone");
const UNITS = [
  ["year", 31536e3],
  ["month", 2592e3],
  ["week", 604800],
  ["day", 86400],
  ["hour", 3600],
  ["minute", 60]
];
const relativeTime = /* @__PURE__ */ __name((timestamp, now = Date.now() / 1e3) => {
  const diff = timestamp - now;
  const abs = Math.abs(diff);
  const rtf = new Intl.RelativeTimeFormat(locale(), { numeric: "auto", style: "short" });
  if (abs < 45) {
    return rtf.format(0, "second");
  }
  for (const [unit, seconds] of UNITS) {
    if (abs >= seconds || unit === "minute") {
      return rtf.format(Math.round(diff / seconds), unit);
    }
  }
  return rtf.format(0, "second");
}, "relativeTime");
const absoluteTime = /* @__PURE__ */ __name((timestamp) => new Intl.DateTimeFormat(locale(), { dateStyle: "full", timeStyle: "short", timeZone: timeZone() }).format(new Date(timestamp * 1e3)), "absoluteTime");
function useNow(intervalMs = 6e4) {
  const [now, setNow] = useState(() => Date.now() / 1e3);
  useEffect(() => {
    const id = window.setInterval(() => setNow(Date.now() / 1e3), intervalMs);
    return () => window.clearInterval(id);
  }, [intervalMs]);
  return now;
}
__name(useNow, "useNow");
function RelativeTime({ timestamp, className }) {
  const now = useNow();
  return /* @__PURE__ */ jsxDEV(
    "time",
    {
      className,
      dateTime: new Date(timestamp * 1e3).toISOString(),
      title: absoluteTime(timestamp),
      children: relativeTime(timestamp, now)
    },
    void 0,
    false,
    {
      fileName: "../plugin/hiveforum/js/esm/src/common/time.tsx",
      lineNumber: 97,
      columnNumber: 9
    },
    this
  );
}
__name(RelativeTime, "RelativeTime");
export {
  RelativeTime,
  absoluteTime,
  locale,
  relativeTime,
  useNow
};
//# sourceMappingURL=time.dev.js.map
