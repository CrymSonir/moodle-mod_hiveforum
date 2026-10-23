var __defProp = Object.defineProperty;
var __name = (target, value) => __defProp(target, "name", { value, configurable: true });
import { jsxDEV } from "react/jsx-dev-runtime";
/**
 * Accessible confirmation dialogue built on the native <dialog> element
 * (focus trap, Escape and backdrop handled by the browser).
 *
 * @module     mod_hiveforum/common/ConfirmDialog
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
import { useEffect, useId, useRef } from "react";
import { useStrings } from "@moodle/lms/mod_hiveforum/common/i18n";
function ConfirmDialog({ open, title, message, confirmLabel, danger = false, onConfirm, onClose }) {
  const t = useStrings();
  const ref = useRef(null);
  const titleId = useId();
  const messageId = useId();
  useEffect(() => {
    const dialog = ref.current;
    if (!dialog) {
      return;
    }
    if (open && !dialog.open) {
      dialog.showModal();
    } else if (!open && dialog.open) {
      dialog.close();
    }
  }, [open]);
  return /* @__PURE__ */ jsxDEV(
    "dialog",
    {
      ref,
      className: "hf-dialog",
      "aria-labelledby": titleId,
      "aria-describedby": messageId,
      onClose,
      onClick: (e) => {
        if (e.target === ref.current) {
          onClose();
        }
      },
      children: /* @__PURE__ */ jsxDEV("div", { className: "hf-dialog__body", children: [
        /* @__PURE__ */ jsxDEV("h2", { id: titleId, className: "hf-dialog__title", children: title }, void 0, false, {
          fileName: "../plugin/hiveforum/js/esm/src/common/ConfirmDialog.tsx",
          lineNumber: 70,
          columnNumber: 17
        }, this),
        /* @__PURE__ */ jsxDEV("p", { id: messageId, className: "hf-dialog__message", children: message }, void 0, false, {
          fileName: "../plugin/hiveforum/js/esm/src/common/ConfirmDialog.tsx",
          lineNumber: 71,
          columnNumber: 17
        }, this),
        /* @__PURE__ */ jsxDEV("div", { className: "hf-dialog__actions", children: [
          /* @__PURE__ */ jsxDEV("button", { type: "button", className: "btn btn-secondary", onClick: onClose, children: t("cancel") }, void 0, false, {
            fileName: "../plugin/hiveforum/js/esm/src/common/ConfirmDialog.tsx",
            lineNumber: 73,
            columnNumber: 21
          }, this),
          /* @__PURE__ */ jsxDEV(
            "button",
            {
              type: "button",
              className: `btn ${danger ? "btn-danger" : "btn-primary"}`,
              onClick: () => {
                onConfirm();
                onClose();
              },
              autoFocus: true,
              children: confirmLabel
            },
            void 0,
            false,
            {
              fileName: "../plugin/hiveforum/js/esm/src/common/ConfirmDialog.tsx",
              lineNumber: 74,
              columnNumber: 21
            },
            this
          )
        ] }, void 0, true, {
          fileName: "../plugin/hiveforum/js/esm/src/common/ConfirmDialog.tsx",
          lineNumber: 72,
          columnNumber: 17
        }, this)
      ] }, void 0, true, {
        fileName: "../plugin/hiveforum/js/esm/src/common/ConfirmDialog.tsx",
        lineNumber: 69,
        columnNumber: 13
      }, this)
    },
    void 0,
    false,
    {
      fileName: "../plugin/hiveforum/js/esm/src/common/ConfirmDialog.tsx",
      lineNumber: 56,
      columnNumber: 9
    },
    this
  );
}
__name(ConfirmDialog, "ConfirmDialog");
export {
  ConfirmDialog as default
};
//# sourceMappingURL=ConfirmDialog.dev.js.map
