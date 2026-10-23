var __defProp = Object.defineProperty;
var __name = (target, value) => __defProp(target, "name", { value, configurable: true });
/**
 * Shared state and actions of the thread view.
 *
 * @module     mod_hiveforum/thread/context
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
import { createContext, useContext } from "react";
const ThreadContext = createContext(null);
const useThread = /* @__PURE__ */ __name(() => {
  const value = useContext(ThreadContext);
  if (!value) {
    throw new Error("useThread() outside of <ThreadContext.Provider>");
  }
  return value;
}, "useThread");
export {
  ThreadContext,
  useThread
};
//# sourceMappingURL=context.dev.js.map
