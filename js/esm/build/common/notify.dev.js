var __defProp = Object.defineProperty;
var __name = (target, value) => __defProp(target, "name", { value, configurable: true });
/**
 * Feedback through Moodle's own toasts and exception dialogue (AMD modules), so the
 * plugin looks and behaves like the rest of the site.
 *
 * @module     mod_hiveforum/common/notify
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
import { requireAsync } from "@moodle/lms/core/amd";
const toast = /* @__PURE__ */ __name((message, type = "success") => {
  requireAsync("core/toast").then((module) => module.add(message, { type })).catch(() => void 0);
}, "toast");
const exception = /* @__PURE__ */ __name((error) => {
  requireAsync("core/notification").then((module) => module.exception(error)).catch(() => window.console.error(error));
}, "exception");
const filterContentUpdated = /* @__PURE__ */ __name((node) => {
  if (!node) {
    return;
  }
  requireAsync("core_filters/events").then((module) => module.notifyFilterContentUpdated([node])).catch(() => void 0);
}, "filterContentUpdated");
export {
  exception,
  filterContentUpdated,
  toast
};
//# sourceMappingURL=notify.dev.js.map
