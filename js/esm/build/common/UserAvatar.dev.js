var __defProp = Object.defineProperty;
var __name = (target, value) => __defProp(target, "name", { value, configurable: true });
import { jsxDEV } from "react/jsx-dev-runtime";
/**
 * Avatars (Moodle design system), avatar stacks and the contribution streak chip.
 *
 * @module     mod_hiveforum/common/UserAvatar
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
import { Avatar } from "@moodlehq/design-system";
import Icon from "@moodle/lms/mod_hiveforum/common/Icon";
import { useStrings } from "@moodle/lms/mod_hiveforum/common/i18n";
const STREAK_MIN = 2;
const hue = /* @__PURE__ */ __name((id) => id * 137 % 360, "hue");
function UserAvatar({ user, size = "md", decorative = false }) {
  return /* @__PURE__ */ jsxDEV("span", { className: "hf-avatar", style: { "--hf-avatar-hue": hue(user.id) }, children: /* @__PURE__ */ jsxDEV(
    Avatar,
    {
      size,
      imageSrc: user.avatarurl || void 0,
      initials: user.initials,
      alt: decorative ? "" : user.fullname
    },
    void 0,
    false,
    {
      fileName: "../plugin/hiveforum/js/esm/src/common/UserAvatar.tsx",
      lineNumber: 44,
      columnNumber: 13
    },
    this
  ) }, void 0, false, {
    fileName: "../plugin/hiveforum/js/esm/src/common/UserAvatar.tsx",
    lineNumber: 43,
    columnNumber: 9
  }, this);
}
__name(UserAvatar, "UserAvatar");
function AvatarStack({ users, max = 4 }) {
  const t = useStrings();
  if (!users.length) {
    return null;
  }
  const shown = users.slice(0, max);
  return /* @__PURE__ */ jsxDEV("span", { className: "hf-avatar-stack", title: users.map((u) => u.fullname).join(", "), children: [
    /* @__PURE__ */ jsxDEV("span", { className: "visually-hidden", children: t("participantsnames", users.map((u) => u.fullname).join(", ")) }, void 0, false, {
      fileName: "../plugin/hiveforum/js/esm/src/common/UserAvatar.tsx",
      lineNumber: 65,
      columnNumber: 13
    }, this),
    shown.map((user) => /* @__PURE__ */ jsxDEV(UserAvatar, { user, size: "md", decorative: true }, user.id, false, {
      fileName: "../plugin/hiveforum/js/esm/src/common/UserAvatar.tsx",
      lineNumber: 66,
      columnNumber: 34
    }, this))
  ] }, void 0, true, {
    fileName: "../plugin/hiveforum/js/esm/src/common/UserAvatar.tsx",
    lineNumber: 64,
    columnNumber: 9
  }, this);
}
__name(AvatarStack, "AvatarStack");
function StreakChip({ weeks, large = false }) {
  const t = useStrings();
  if (weeks < STREAK_MIN) {
    return null;
  }
  const label = t("streakweeks", weeks);
  return /* @__PURE__ */ jsxDEV("span", { className: `hf-streak ${large ? "hf-streak--lg" : ""}`, title: label, children: [
    /* @__PURE__ */ jsxDEV(Icon, { name: "flame", size: large ? 16 : 13 }, void 0, false, {
      fileName: "../plugin/hiveforum/js/esm/src/common/UserAvatar.tsx",
      lineNumber: 82,
      columnNumber: 13
    }, this),
    /* @__PURE__ */ jsxDEV("span", { "aria-hidden": "true", children: weeks }, void 0, false, {
      fileName: "../plugin/hiveforum/js/esm/src/common/UserAvatar.tsx",
      lineNumber: 83,
      columnNumber: 13
    }, this),
    /* @__PURE__ */ jsxDEV("span", { className: "visually-hidden", children: label }, void 0, false, {
      fileName: "../plugin/hiveforum/js/esm/src/common/UserAvatar.tsx",
      lineNumber: 84,
      columnNumber: 13
    }, this)
  ] }, void 0, true, {
    fileName: "../plugin/hiveforum/js/esm/src/common/UserAvatar.tsx",
    lineNumber: 81,
    columnNumber: 9
  }, this);
}
__name(StreakChip, "StreakChip");
export {
  AvatarStack,
  STREAK_MIN,
  StreakChip,
  UserAvatar as default
};
//# sourceMappingURL=UserAvatar.dev.js.map
