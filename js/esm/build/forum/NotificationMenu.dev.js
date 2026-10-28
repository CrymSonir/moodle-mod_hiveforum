var __defProp = Object.defineProperty;
var __name = (target, value) => __defProp(target, "name", { value, configurable: true });
import { Fragment, jsxDEV } from "react/jsx-dev-runtime";
/**
 * Notification preferences of the current user: forum subscription and daily digest.
 *
 * @module     mod_hiveforum/forum/NotificationMenu
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
import { useEffect, useId, useRef, useState } from "react";
import Icon from "@moodle/lms/mod_hiveforum/common/Icon";
import { useStrings } from "@moodle/lms/mod_hiveforum/common/i18n";
import { exception, toast } from "@moodle/lms/mod_hiveforum/common/notify";
import { setPreferences } from "@moodle/lms/mod_hiveforum/common/repository";
const FORCED = 1;
const DISABLED = 3;
function NotificationMenu({ cmid, preferences }) {
  const t = useStrings();
  const panelId = useId();
  const [open, setOpen] = useState(false);
  const [prefs, setPrefs] = useState(preferences);
  const [busy, setBusy] = useState(false);
  const rootRef = useRef(null);
  useEffect(() => {
    if (!open) {
      return void 0;
    }
    const onClick = /* @__PURE__ */ __name((e) => {
      if (!rootRef.current?.contains(e.target)) {
        setOpen(false);
      }
    }, "onClick");
    const onKey = /* @__PURE__ */ __name((e) => {
      if (e.key === "Escape") {
        setOpen(false);
      }
    }, "onKey");
    document.addEventListener("mousedown", onClick);
    document.addEventListener("keydown", onKey);
    return () => {
      document.removeEventListener("mousedown", onClick);
      document.removeEventListener("keydown", onKey);
    };
  }, [open]);
  const save = /* @__PURE__ */ __name((change, message) => {
    const previous = prefs;
    setPrefs({ ...prefs, ...change });
    setBusy(true);
    setPreferences(cmid, change).then((result) => {
      setPrefs(result.preferences);
      toast(message);
    }).catch((error) => {
      setPrefs(previous);
      exception(error);
    }).finally(() => setBusy(false));
  }, "save");
  const disabled = prefs.subscriptionmode === DISABLED;
  const forced = prefs.subscriptionmode === FORCED;
  const label = prefs.forumsubscribed ? t("forumsubscribed") : t("notifications");
  return /* @__PURE__ */ jsxDEV("div", { className: "hf-notifmenu", ref: rootRef, children: [
    /* @__PURE__ */ jsxDEV(
      "button",
      {
        type: "button",
        className: `btn hf-notifmenu__trigger ${prefs.forumsubscribed ? "is-on" : ""}`,
        "aria-expanded": open,
        "aria-controls": panelId,
        onClick: () => setOpen(!open),
        children: [
          /* @__PURE__ */ jsxDEV(Icon, { name: prefs.forumsubscribed ? "bellcheck" : "bell", size: 17 }, void 0, false, {
            fileName: "../plugin/hiveforum/js/esm/src/forum/NotificationMenu.tsx",
            lineNumber: 93,
            columnNumber: 17
          }, this),
          /* @__PURE__ */ jsxDEV("span", { children: label }, void 0, false, {
            fileName: "../plugin/hiveforum/js/esm/src/forum/NotificationMenu.tsx",
            lineNumber: 94,
            columnNumber: 17
          }, this),
          /* @__PURE__ */ jsxDEV(Icon, { name: "chevrondown", size: 14 }, void 0, false, {
            fileName: "../plugin/hiveforum/js/esm/src/forum/NotificationMenu.tsx",
            lineNumber: 95,
            columnNumber: 17
          }, this)
        ]
      },
      void 0,
      true,
      {
        fileName: "../plugin/hiveforum/js/esm/src/forum/NotificationMenu.tsx",
        lineNumber: 86,
        columnNumber: 13
      },
      this
    ),
    open && /* @__PURE__ */ jsxDEV("div", { className: "hf-notifmenu__panel", id: panelId, role: "dialog", "aria-label": t("notifications"), children: [
      !prefs.notificationsenabled && /* @__PURE__ */ jsxDEV("p", { className: "hf-notifmenu__warning", children: t("notificationsoff") }, void 0, false, {
        fileName: "../plugin/hiveforum/js/esm/src/forum/NotificationMenu.tsx",
        lineNumber: 100,
        columnNumber: 25
      }, this),
      disabled ? /* @__PURE__ */ jsxDEV("p", { className: "hf-notifmenu__text", children: t("modeexplain_3") }, void 0, false, {
        fileName: "../plugin/hiveforum/js/esm/src/forum/NotificationMenu.tsx",
        lineNumber: 103,
        columnNumber: 25
      }, this) : /* @__PURE__ */ jsxDEV(Fragment, { children: [
        /* @__PURE__ */ jsxDEV("label", { className: "hf-switch", children: [
          /* @__PURE__ */ jsxDEV(
            "input",
            {
              type: "checkbox",
              role: "switch",
              checked: prefs.forumsubscribed,
              disabled: !prefs.canchange || busy || forced,
              onChange: (e) => save(
                { forumsubscribed: e.target.checked },
                e.target.checked ? t("forumsubscribedtoast") : t("forumunsubscribedtoast")
              )
            },
            void 0,
            false,
            {
              fileName: "../plugin/hiveforum/js/esm/src/forum/NotificationMenu.tsx",
              lineNumber: 107,
              columnNumber: 33
            },
            this
          ),
          /* @__PURE__ */ jsxDEV("span", { className: "hf-switch__track", "aria-hidden": "true" }, void 0, false, {
            fileName: "../plugin/hiveforum/js/esm/src/forum/NotificationMenu.tsx",
            lineNumber: 117,
            columnNumber: 33
          }, this),
          /* @__PURE__ */ jsxDEV("span", { className: "hf-switch__label", children: [
            /* @__PURE__ */ jsxDEV("strong", { children: t("subscribeforum") }, void 0, false, {
              fileName: "../plugin/hiveforum/js/esm/src/forum/NotificationMenu.tsx",
              lineNumber: 119,
              columnNumber: 37
            }, this),
            /* @__PURE__ */ jsxDEV("small", { children: forced ? t("modeexplain_1") : t("subscribeforum_help") }, void 0, false, {
              fileName: "../plugin/hiveforum/js/esm/src/forum/NotificationMenu.tsx",
              lineNumber: 120,
              columnNumber: 37
            }, this)
          ] }, void 0, true, {
            fileName: "../plugin/hiveforum/js/esm/src/forum/NotificationMenu.tsx",
            lineNumber: 118,
            columnNumber: 33
          }, this)
        ] }, void 0, true, {
          fileName: "../plugin/hiveforum/js/esm/src/forum/NotificationMenu.tsx",
          lineNumber: 106,
          columnNumber: 29
        }, this),
        prefs.notificationsenabled && /* @__PURE__ */ jsxDEV("fieldset", { className: "hf-notifmenu__freq", children: [
          /* @__PURE__ */ jsxDEV("legend", { children: t("frequency") }, void 0, false, {
            fileName: "../plugin/hiveforum/js/esm/src/forum/NotificationMenu.tsx",
            lineNumber: 125,
            columnNumber: 37
          }, this),
          [false, true].map((digest) => /* @__PURE__ */ jsxDEV("label", { className: "hf-radio", children: [
            /* @__PURE__ */ jsxDEV(
              "input",
              {
                type: "radio",
                name: `${panelId}-digest`,
                checked: prefs.digest === digest,
                disabled: busy,
                onChange: () => save(
                  { digest },
                  digest ? t("digestontoast") : t("digestofftoast")
                )
              },
              void 0,
              false,
              {
                fileName: "../plugin/hiveforum/js/esm/src/forum/NotificationMenu.tsx",
                lineNumber: 128,
                columnNumber: 45
              },
              this
            ),
            /* @__PURE__ */ jsxDEV(Icon, { name: digest ? "mail" : "bell", size: 15 }, void 0, false, {
              fileName: "../plugin/hiveforum/js/esm/src/forum/NotificationMenu.tsx",
              lineNumber: 138,
              columnNumber: 45
            }, this),
            /* @__PURE__ */ jsxDEV("span", { children: digest ? t("frequency_digest") : t("frequency_each") }, void 0, false, {
              fileName: "../plugin/hiveforum/js/esm/src/forum/NotificationMenu.tsx",
              lineNumber: 139,
              columnNumber: 45
            }, this)
          ] }, String(digest), true, {
            fileName: "../plugin/hiveforum/js/esm/src/forum/NotificationMenu.tsx",
            lineNumber: 127,
            columnNumber: 41
          }, this))
        ] }, void 0, true, {
          fileName: "../plugin/hiveforum/js/esm/src/forum/NotificationMenu.tsx",
          lineNumber: 124,
          columnNumber: 33
        }, this),
        /* @__PURE__ */ jsxDEV("p", { className: "hf-notifmenu__text", children: t("mentionsalways") }, void 0, false, {
          fileName: "../plugin/hiveforum/js/esm/src/forum/NotificationMenu.tsx",
          lineNumber: 144,
          columnNumber: 29
        }, this)
      ] }, void 0, true, {
        fileName: "../plugin/hiveforum/js/esm/src/forum/NotificationMenu.tsx",
        lineNumber: 105,
        columnNumber: 25
      }, this)
    ] }, void 0, true, {
      fileName: "../plugin/hiveforum/js/esm/src/forum/NotificationMenu.tsx",
      lineNumber: 98,
      columnNumber: 17
    }, this)
  ] }, void 0, true, {
    fileName: "../plugin/hiveforum/js/esm/src/forum/NotificationMenu.tsx",
    lineNumber: 85,
    columnNumber: 9
  }, this);
}
__name(NotificationMenu, "NotificationMenu");
export {
  NotificationMenu as default
};
//# sourceMappingURL=NotificationMenu.dev.js.map
