var __defProp = Object.defineProperty;
var __name = (target, value) => __defProp(target, "name", { value, configurable: true });
import { jsxDEV } from "react/jsx-dev-runtime";
/**
 * Inline "new discussion" panel.
 *
 * @module     mod_hiveforum/forum/NewDiscussion
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
import { useEffect, useId, useRef, useState } from "react";
import Composer from "@moodle/lms/mod_hiveforum/common/Composer";
import Icon from "@moodle/lms/mod_hiveforum/common/Icon";
import { useStrings } from "@moodle/lms/mod_hiveforum/common/i18n";
import { exception } from "@moodle/lms/mod_hiveforum/common/notify";
import { createDiscussion } from "@moodle/lms/mod_hiveforum/common/repository";
import TagInput from "@moodle/lms/mod_hiveforum/common/TagInput";
function NewDiscussion({
  cmid,
  allowtags,
  suggestions,
  fulleditorurl,
  user,
  posttypes,
  targets,
  activegroup,
  onClose
}) {
  const t = useStrings();
  const titleId = useId();
  const titleRef = useRef(null);
  const [name, setName] = useState("");
  const [isquestion, setIsquestion] = useState(posttypes.length === 1 && posttypes[0] === "question");
  const [groupid, setGroupid] = useState(() => targets.some((g) => g.id === activegroup) ? activegroup : targets[0]?.id ?? 0);
  const [tags, setTags] = useState([]);
  const [titleError, setTitleError] = useState(false);
  useEffect(() => titleRef.current?.focus(), []);
  const submit = /* @__PURE__ */ __name(async (message) => {
    if (!name.trim()) {
      setTitleError(true);
      titleRef.current?.focus();
      throw new Error("Title required");
    }
    try {
      const result = await createDiscussion({ cmid, name: name.trim(), message, isquestion, tags, groupid });
      window.location.href = result.url;
    } catch (error) {
      exception(error);
      throw error;
    }
  }, "submit");
  return /* @__PURE__ */ jsxDEV("section", { className: "hf-newdiscussion", "aria-labelledby": `${titleId}-heading`, children: [
    /* @__PURE__ */ jsxDEV("div", { className: "hf-newdiscussion__head", children: [
      /* @__PURE__ */ jsxDEV("h2", { id: `${titleId}-heading`, className: "hf-newdiscussion__heading", children: [
        /* @__PURE__ */ jsxDEV(Icon, { name: "sparkles", size: 18 }, void 0, false, {
          fileName: "../plugin/hiveforum/js/esm/src/forum/NewDiscussion.tsx",
          lineNumber: 81,
          columnNumber: 21
        }, this),
        " ",
        t("newdiscussion")
      ] }, void 0, true, {
        fileName: "../plugin/hiveforum/js/esm/src/forum/NewDiscussion.tsx",
        lineNumber: 80,
        columnNumber: 17
      }, this),
      /* @__PURE__ */ jsxDEV("button", { type: "button", className: "hf-iconbtn", onClick: onClose, "aria-label": t("close"), children: /* @__PURE__ */ jsxDEV(Icon, { name: "x" }, void 0, false, {
        fileName: "../plugin/hiveforum/js/esm/src/forum/NewDiscussion.tsx",
        lineNumber: 84,
        columnNumber: 21
      }, this) }, void 0, false, {
        fileName: "../plugin/hiveforum/js/esm/src/forum/NewDiscussion.tsx",
        lineNumber: 83,
        columnNumber: 17
      }, this)
    ] }, void 0, true, {
      fileName: "../plugin/hiveforum/js/esm/src/forum/NewDiscussion.tsx",
      lineNumber: 79,
      columnNumber: 13
    }, this),
    posttypes.length > 1 && /* @__PURE__ */ jsxDEV("div", { className: "hf-segmented", role: "radiogroup", "aria-label": t("discussiontype"), children: [
      /* @__PURE__ */ jsxDEV(
        "button",
        {
          type: "button",
          role: "radio",
          "aria-checked": !isquestion,
          className: !isquestion ? "is-active" : "",
          onClick: () => setIsquestion(false),
          children: [
            /* @__PURE__ */ jsxDEV(Icon, { name: "chat", size: 16 }, void 0, false, {
              fileName: "../plugin/hiveforum/js/esm/src/forum/NewDiscussion.tsx",
              lineNumber: 92,
              columnNumber: 21
            }, this),
            " ",
            t("typediscussion")
          ]
        },
        void 0,
        true,
        {
          fileName: "../plugin/hiveforum/js/esm/src/forum/NewDiscussion.tsx",
          lineNumber: 90,
          columnNumber: 17
        },
        this
      ),
      /* @__PURE__ */ jsxDEV(
        "button",
        {
          type: "button",
          role: "radio",
          "aria-checked": isquestion,
          className: isquestion ? "is-active" : "",
          onClick: () => setIsquestion(true),
          children: [
            /* @__PURE__ */ jsxDEV(Icon, { name: "question", size: 16 }, void 0, false, {
              fileName: "../plugin/hiveforum/js/esm/src/forum/NewDiscussion.tsx",
              lineNumber: 96,
              columnNumber: 21
            }, this),
            " ",
            t("typequestion")
          ]
        },
        void 0,
        true,
        {
          fileName: "../plugin/hiveforum/js/esm/src/forum/NewDiscussion.tsx",
          lineNumber: 94,
          columnNumber: 17
        },
        this
      )
    ] }, void 0, true, {
      fileName: "../plugin/hiveforum/js/esm/src/forum/NewDiscussion.tsx",
      lineNumber: 89,
      columnNumber: 13
    }, this),
    /* @__PURE__ */ jsxDEV("p", { className: "hf-newdiscussion__help", children: isquestion ? t("typequestion_help") : t("typediscussion_help") }, void 0, false, {
      fileName: "../plugin/hiveforum/js/esm/src/forum/NewDiscussion.tsx",
      lineNumber: 100,
      columnNumber: 13
    }, this),
    /* @__PURE__ */ jsxDEV("label", { htmlFor: titleId, className: "visually-hidden", children: t("discussiontitle") }, void 0, false, {
      fileName: "../plugin/hiveforum/js/esm/src/forum/NewDiscussion.tsx",
      lineNumber: 102,
      columnNumber: 13
    }, this),
    /* @__PURE__ */ jsxDEV(
      "input",
      {
        id: titleId,
        ref: titleRef,
        className: `hf-newdiscussion__title ${titleError ? "is-invalid" : ""}`,
        value: name,
        maxLength: 255,
        placeholder: isquestion ? t("questiontitleplaceholder") : t("discussiontitleplaceholder"),
        "aria-invalid": titleError,
        onChange: (e) => {
          setName(e.target.value);
          setTitleError(false);
        }
      },
      void 0,
      false,
      {
        fileName: "../plugin/hiveforum/js/esm/src/forum/NewDiscussion.tsx",
        lineNumber: 103,
        columnNumber: 13
      },
      this
    ),
    titleError && /* @__PURE__ */ jsxDEV("p", { className: "hf-field-error", children: t("errornameempty") }, void 0, false, {
      fileName: "../plugin/hiveforum/js/esm/src/forum/NewDiscussion.tsx",
      lineNumber: 116,
      columnNumber: 28
    }, this),
    targets.length > 0 && /* @__PURE__ */ jsxDEV("label", { className: "hf-target", children: [
      /* @__PURE__ */ jsxDEV(Icon, { name: "users2", size: 15 }, void 0, false, {
        fileName: "../plugin/hiveforum/js/esm/src/forum/NewDiscussion.tsx",
        lineNumber: 120,
        columnNumber: 21
      }, this),
      /* @__PURE__ */ jsxDEV("span", { children: t("postto") }, void 0, false, {
        fileName: "../plugin/hiveforum/js/esm/src/forum/NewDiscussion.tsx",
        lineNumber: 121,
        columnNumber: 21
      }, this),
      /* @__PURE__ */ jsxDEV("select", { value: groupid, onChange: (e) => setGroupid(Number(e.target.value)), disabled: targets.length === 1, children: targets.map((g) => /* @__PURE__ */ jsxDEV("option", { value: g.id, children: g.name }, g.id, false, {
        fileName: "../plugin/hiveforum/js/esm/src/forum/NewDiscussion.tsx",
        lineNumber: 123,
        columnNumber: 45
      }, this)) }, void 0, false, {
        fileName: "../plugin/hiveforum/js/esm/src/forum/NewDiscussion.tsx",
        lineNumber: 122,
        columnNumber: 21
      }, this)
    ] }, void 0, true, {
      fileName: "../plugin/hiveforum/js/esm/src/forum/NewDiscussion.tsx",
      lineNumber: 119,
      columnNumber: 17
    }, this),
    allowtags && /* @__PURE__ */ jsxDEV(TagInput, { tags, onChange: setTags, suggestions }, void 0, false, {
      fileName: "../plugin/hiveforum/js/esm/src/forum/NewDiscussion.tsx",
      lineNumber: 128,
      columnNumber: 27
    }, this),
    /* @__PURE__ */ jsxDEV(
      Composer,
      {
        draftKey: `new/${cmid}`,
        label: t("message"),
        placeholder: t("messageplaceholder"),
        submitLabel: t("publishdiscussion"),
        onSubmit: submit,
        onCancel: onClose,
        user,
        mentions: { cmid, discussionid: 0 },
        extra: /* @__PURE__ */ jsxDEV("a", { className: "hf-fulleditor", href: fulleditorurl, children: [
          /* @__PURE__ */ jsxDEV(Icon, { name: "expand", size: 14 }, void 0, false, {
            fileName: "../plugin/hiveforum/js/esm/src/forum/NewDiscussion.tsx",
            lineNumber: 141,
            columnNumber: 25
          }, this),
          " ",
          t("fulleditor")
        ] }, void 0, true, {
          fileName: "../plugin/hiveforum/js/esm/src/forum/NewDiscussion.tsx",
          lineNumber: 140,
          columnNumber: 21
        }, this)
      },
      void 0,
      false,
      {
        fileName: "../plugin/hiveforum/js/esm/src/forum/NewDiscussion.tsx",
        lineNumber: 130,
        columnNumber: 13
      },
      this
    )
  ] }, void 0, true, {
    fileName: "../plugin/hiveforum/js/esm/src/forum/NewDiscussion.tsx",
    lineNumber: 78,
    columnNumber: 9
  }, this);
}
__name(NewDiscussion, "NewDiscussion");
export {
  NewDiscussion as default
};
//# sourceMappingURL=NewDiscussion.dev.js.map
