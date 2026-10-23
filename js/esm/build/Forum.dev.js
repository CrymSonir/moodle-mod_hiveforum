var __defProp = Object.defineProperty;
var __name = (target, value) => __defProp(target, "name", { value, configurable: true });
import { jsxDEV } from "react/jsx-dev-runtime";
/**
 * Discussion list page.
 *
 * Mounted by core/react_autoinit from data-react-component="@moodle/lms/mod_hiveforum/Forum".
 * The first page comes in the props; filters, search, tags, sort and "load more" then
 * use mod_hiveforum_get_discussions without reloading the page, and the URL is kept in
 * sync so reload / back / sharing a link restore the same view.
 *
 * @module     mod_hiveforum/Forum
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
import { useCallback, useEffect, useMemo, useRef, useState } from "react";
import Icon from "@moodle/lms/mod_hiveforum/common/Icon";
import { StringsProvider, useStrings } from "@moodle/lms/mod_hiveforum/common/i18n";
import { exception, toast } from "@moodle/lms/mod_hiveforum/common/notify";
import { getDiscussions, markAllRead } from "@moodle/lms/mod_hiveforum/common/repository";
import { StreakChip } from "@moodle/lms/mod_hiveforum/common/UserAvatar";
import DiscussionCard, { DiscussionCardSkeleton } from "@moodle/lms/mod_hiveforum/forum/DiscussionCard";
import NewDiscussion from "@moodle/lms/mod_hiveforum/forum/NewDiscussion";
const SEARCH_DEBOUNCE_MS = 350;
const FILTERS = [
  { id: "all", label: "filter_all" },
  { id: "unread", label: "filter_unread", count: "unread" },
  { id: "following", label: "filter_following", count: "following" },
  { id: "questions", label: "filter_questions", count: "questions" },
  { id: "mine", label: "filter_mine" }
];
const SORTS = ["recent", "newest", "popular"];
const syncUrl = /* @__PURE__ */ __name((cmid, filter, search, tag, sort) => {
  const params = new URLSearchParams({ id: String(cmid) });
  if (filter !== "all") {
    params.set("filter", filter);
  }
  if (search) {
    params.set("search", search);
  }
  if (tag) {
    params.set("tag", tag);
  }
  if (sort !== "recent") {
    params.set("sort", sort);
  }
  window.history.replaceState(null, "", `${window.location.pathname}?${params.toString()}`);
}, "syncUrl");
function ForumView(props) {
  const t = useStrings();
  const { cmid, capabilities: caps, currentuser, stats } = props;
  const [filter, setFilter] = useState(props.filter);
  const [search, setSearch] = useState(props.search);
  const [searchInput, setSearchInput] = useState(props.search);
  const [tag, setTag] = useState(props.tag);
  const [sort, setSort] = useState(props.sort);
  const [discussions, setDiscussions] = useState(props.discussions);
  const [total, setTotal] = useState(props.total);
  const [counts, setCounts] = useState(props.counts);
  const [page, setPage] = useState(0);
  const [loading, setLoading] = useState(false);
  const [loadingMore, setLoadingMore] = useState(false);
  const [composing, setComposing] = useState(false);
  const firstRender = useRef(true);
  const requestId = useRef(0);
  useEffect(() => {
    const id = window.setTimeout(() => setSearch(searchInput.trim()), SEARCH_DEBOUNCE_MS);
    return () => window.clearTimeout(id);
  }, [searchInput]);
  useEffect(() => {
    if (firstRender.current) {
      firstRender.current = false;
      return;
    }
    syncUrl(cmid, filter, search, tag, sort);
    const current = ++requestId.current;
    setLoading(true);
    getDiscussions({ cmid, filter, search, tag, sort, page: 0 }).then((result) => {
      if (current !== requestId.current) {
        return;
      }
      setDiscussions(result.discussions);
      setTotal(result.total);
      setCounts(result.counts);
      setPage(0);
    }).catch(exception).finally(() => current === requestId.current && setLoading(false));
  }, [cmid, filter, search, tag, sort]);
  const loadMore = useCallback(() => {
    const next = page + 1;
    setLoadingMore(true);
    getDiscussions({ cmid, filter, search, tag, sort, page: next }).then((result) => {
      setDiscussions((prev) => {
        const known = new Set(prev.map((d) => d.id));
        return [...prev, ...result.discussions.filter((d) => !known.has(d.id))];
      });
      setTotal(result.total);
      setPage(next);
    }).catch(exception).finally(() => setLoadingMore(false));
  }, [cmid, filter, search, tag, sort, page]);
  const onMarkAllRead = /* @__PURE__ */ __name(() => {
    markAllRead(cmid).then(() => {
      setDiscussions((prev) => prev.map((d) => ({ ...d, unread: false })));
      setCounts((prev) => ({ ...prev, unread: 0 }));
      toast(t("allmarkedread"));
      if (filter === "unread") {
        setDiscussions([]);
        setTotal(0);
      }
    }).catch(exception);
  }, "onMarkAllRead");
  const tagSuggestions = useMemo(() => props.populartags.map((p) => p.name), [props.populartags]);
  const hasQuery = search !== "" || tag !== "" || filter !== "all";
  const remaining = total - discussions.length;
  return /* @__PURE__ */ jsxDEV("div", { className: "hf hf-forum", children: [
    /* @__PURE__ */ jsxDEV("header", { className: "hf-hero", children: [
      /* @__PURE__ */ jsxDEV("div", { className: "hf-hero__stats", children: [
        /* @__PURE__ */ jsxDEV("span", { className: "hf-stat", children: [
          /* @__PURE__ */ jsxDEV(Icon, { name: "layers", size: 16 }, void 0, false, {
            fileName: "../plugin/hiveforum/js/esm/src/Forum.tsx",
            lineNumber: 156,
            columnNumber: 47
          }, this),
          /* @__PURE__ */ jsxDEV("strong", { children: stats.discussions }, void 0, false, {
            fileName: "../plugin/hiveforum/js/esm/src/Forum.tsx",
            lineNumber: 157,
            columnNumber: 25
          }, this),
          " ",
          t.plural(stats.discussions, "stat_discussion", "stat_discussions")
        ] }, void 0, true, {
          fileName: "../plugin/hiveforum/js/esm/src/Forum.tsx",
          lineNumber: 156,
          columnNumber: 21
        }, this),
        /* @__PURE__ */ jsxDEV("span", { className: "hf-stat", children: [
          /* @__PURE__ */ jsxDEV(Icon, { name: "chat", size: 16 }, void 0, false, {
            fileName: "../plugin/hiveforum/js/esm/src/Forum.tsx",
            lineNumber: 159,
            columnNumber: 47
          }, this),
          /* @__PURE__ */ jsxDEV("strong", { children: stats.posts }, void 0, false, {
            fileName: "../plugin/hiveforum/js/esm/src/Forum.tsx",
            lineNumber: 160,
            columnNumber: 25
          }, this),
          " ",
          t.plural(stats.posts, "stat_message", "stat_messages")
        ] }, void 0, true, {
          fileName: "../plugin/hiveforum/js/esm/src/Forum.tsx",
          lineNumber: 159,
          columnNumber: 21
        }, this),
        /* @__PURE__ */ jsxDEV("span", { className: "hf-stat", children: [
          /* @__PURE__ */ jsxDEV(Icon, { name: "users", size: 16 }, void 0, false, {
            fileName: "../plugin/hiveforum/js/esm/src/Forum.tsx",
            lineNumber: 162,
            columnNumber: 47
          }, this),
          /* @__PURE__ */ jsxDEV("strong", { children: stats.participants }, void 0, false, {
            fileName: "../plugin/hiveforum/js/esm/src/Forum.tsx",
            lineNumber: 163,
            columnNumber: 25
          }, this),
          " ",
          t.plural(stats.participants, "stat_participant", "stat_participants")
        ] }, void 0, true, {
          fileName: "../plugin/hiveforum/js/esm/src/Forum.tsx",
          lineNumber: 162,
          columnNumber: 21
        }, this)
      ] }, void 0, true, {
        fileName: "../plugin/hiveforum/js/esm/src/Forum.tsx",
        lineNumber: 155,
        columnNumber: 17
      }, this),
      /* @__PURE__ */ jsxDEV("div", { className: "hf-hero__actions", children: [
        currentuser && /* @__PURE__ */ jsxDEV(StreakChip, { weeks: currentuser.streak, large: true }, void 0, false, {
          fileName: "../plugin/hiveforum/js/esm/src/Forum.tsx",
          lineNumber: 167,
          columnNumber: 37
        }, this),
        caps.canstart && !composing && /* @__PURE__ */ jsxDEV("button", { type: "button", className: "btn btn-primary hf-btn-new", onClick: () => setComposing(true), children: [
          /* @__PURE__ */ jsxDEV(Icon, { name: "plus", size: 18 }, void 0, false, {
            fileName: "../plugin/hiveforum/js/esm/src/Forum.tsx",
            lineNumber: 170,
            columnNumber: 29
          }, this),
          " ",
          t("newdiscussion")
        ] }, void 0, true, {
          fileName: "../plugin/hiveforum/js/esm/src/Forum.tsx",
          lineNumber: 169,
          columnNumber: 25
        }, this)
      ] }, void 0, true, {
        fileName: "../plugin/hiveforum/js/esm/src/Forum.tsx",
        lineNumber: 166,
        columnNumber: 17
      }, this)
    ] }, void 0, true, {
      fileName: "../plugin/hiveforum/js/esm/src/Forum.tsx",
      lineNumber: 154,
      columnNumber: 13
    }, this),
    composing && /* @__PURE__ */ jsxDEV(
      NewDiscussion,
      {
        cmid,
        allowtags: caps.allowtags,
        suggestions: tagSuggestions,
        fulleditorurl: props.fulleditorurl,
        user: currentuser,
        onClose: () => setComposing(false)
      },
      void 0,
      false,
      {
        fileName: "../plugin/hiveforum/js/esm/src/Forum.tsx",
        lineNumber: 177,
        columnNumber: 17
      },
      this
    ),
    /* @__PURE__ */ jsxDEV("div", { className: "hf-toolbar", children: [
      /* @__PURE__ */ jsxDEV("nav", { className: "hf-tabs", "aria-label": t("filters"), children: FILTERS.filter((f) => f.id !== "mine" || currentuser).map((f) => {
        const count = f.count ? counts[f.count] : 0;
        return /* @__PURE__ */ jsxDEV(
          "button",
          {
            type: "button",
            className: `hf-tab ${filter === f.id ? "is-active" : ""}`,
            "aria-pressed": filter === f.id,
            onClick: () => setFilter(f.id),
            children: [
              t(f.label),
              count > 0 && /* @__PURE__ */ jsxDEV("span", { className: `hf-tab__count ${f.id === "unread" ? "is-accent" : ""}`, children: count }, void 0, false, {
                fileName: "../plugin/hiveforum/js/esm/src/Forum.tsx",
                lineNumber: 200,
                columnNumber: 47
              }, this)
            ]
          },
          f.id,
          true,
          {
            fileName: "../plugin/hiveforum/js/esm/src/Forum.tsx",
            lineNumber: 192,
            columnNumber: 29
          },
          this
        );
      }) }, void 0, false, {
        fileName: "../plugin/hiveforum/js/esm/src/Forum.tsx",
        lineNumber: 188,
        columnNumber: 17
      }, this),
      /* @__PURE__ */ jsxDEV("div", { className: "hf-toolbar__right", children: [
        /* @__PURE__ */ jsxDEV("div", { className: "hf-search", role: "search", children: [
          /* @__PURE__ */ jsxDEV(Icon, { name: "search", size: 16, className: "hf-search__icon" }, void 0, false, {
            fileName: "../plugin/hiveforum/js/esm/src/Forum.tsx",
            lineNumber: 207,
            columnNumber: 25
          }, this),
          /* @__PURE__ */ jsxDEV("label", { htmlFor: "hf-search-input", className: "visually-hidden", children: t("searchdiscussions") }, void 0, false, {
            fileName: "../plugin/hiveforum/js/esm/src/Forum.tsx",
            lineNumber: 208,
            columnNumber: 25
          }, this),
          /* @__PURE__ */ jsxDEV(
            "input",
            {
              id: "hf-search-input",
              type: "search",
              className: "hf-search__input",
              placeholder: t("searchdiscussions"),
              value: searchInput,
              onChange: (e) => setSearchInput(e.target.value)
            },
            void 0,
            false,
            {
              fileName: "../plugin/hiveforum/js/esm/src/Forum.tsx",
              lineNumber: 209,
              columnNumber: 25
            },
            this
          ),
          searchInput && /* @__PURE__ */ jsxDEV(
            "button",
            {
              type: "button",
              className: "hf-search__clear",
              onClick: () => setSearchInput(""),
              "aria-label": t("clearsearch"),
              children: /* @__PURE__ */ jsxDEV(Icon, { name: "x", size: 14 }, void 0, false, {
                fileName: "../plugin/hiveforum/js/esm/src/Forum.tsx",
                lineNumber: 220,
                columnNumber: 33
              }, this)
            },
            void 0,
            false,
            {
              fileName: "../plugin/hiveforum/js/esm/src/Forum.tsx",
              lineNumber: 218,
              columnNumber: 29
            },
            this
          )
        ] }, void 0, true, {
          fileName: "../plugin/hiveforum/js/esm/src/Forum.tsx",
          lineNumber: 206,
          columnNumber: 21
        }, this),
        /* @__PURE__ */ jsxDEV("label", { className: "hf-sort", children: [
          /* @__PURE__ */ jsxDEV("span", { className: "visually-hidden", children: t("sortby") }, void 0, false, {
            fileName: "../plugin/hiveforum/js/esm/src/Forum.tsx",
            lineNumber: 225,
            columnNumber: 25
          }, this),
          /* @__PURE__ */ jsxDEV(Icon, { name: "sort", size: 16 }, void 0, false, {
            fileName: "../plugin/hiveforum/js/esm/src/Forum.tsx",
            lineNumber: 226,
            columnNumber: 25
          }, this),
          /* @__PURE__ */ jsxDEV("select", { value: sort, onChange: (e) => setSort(e.target.value), children: SORTS.map((s) => /* @__PURE__ */ jsxDEV("option", { value: s, children: t(`sort_${s}`) }, s, false, {
            fileName: "../plugin/hiveforum/js/esm/src/Forum.tsx",
            lineNumber: 228,
            columnNumber: 47
          }, this)) }, void 0, false, {
            fileName: "../plugin/hiveforum/js/esm/src/Forum.tsx",
            lineNumber: 227,
            columnNumber: 25
          }, this)
        ] }, void 0, true, {
          fileName: "../plugin/hiveforum/js/esm/src/Forum.tsx",
          lineNumber: 224,
          columnNumber: 21
        }, this)
      ] }, void 0, true, {
        fileName: "../plugin/hiveforum/js/esm/src/Forum.tsx",
        lineNumber: 205,
        columnNumber: 17
      }, this)
    ] }, void 0, true, {
      fileName: "../plugin/hiveforum/js/esm/src/Forum.tsx",
      lineNumber: 187,
      columnNumber: 13
    }, this),
    (props.populartags.length > 0 || tag) && /* @__PURE__ */ jsxDEV("div", { className: "hf-tagbar", "aria-label": t("populartags"), children: [
      /* @__PURE__ */ jsxDEV(Icon, { name: "tag", size: 15, className: "hf-tagbar__icon" }, void 0, false, {
        fileName: "../plugin/hiveforum/js/esm/src/Forum.tsx",
        lineNumber: 236,
        columnNumber: 21
      }, this),
      tag && !tagSuggestions.includes(tag) && /* @__PURE__ */ jsxDEV("button", { type: "button", className: "hf-tag is-active", "aria-pressed": "true", onClick: () => setTag(""), children: [
        "#",
        tag,
        " ",
        /* @__PURE__ */ jsxDEV(Icon, { name: "x", size: 12 }, void 0, false, {
          fileName: "../plugin/hiveforum/js/esm/src/Forum.tsx",
          lineNumber: 239,
          columnNumber: 36
        }, this)
      ] }, void 0, true, {
        fileName: "../plugin/hiveforum/js/esm/src/Forum.tsx",
        lineNumber: 238,
        columnNumber: 25
      }, this),
      props.populartags.map((p) => /* @__PURE__ */ jsxDEV(
        "button",
        {
          type: "button",
          className: `hf-tag ${tag === p.name ? "is-active" : ""}`,
          "aria-pressed": tag === p.name,
          onClick: () => setTag(tag === p.name ? "" : p.name),
          children: [
            "#",
            p.name,
            " ",
            /* @__PURE__ */ jsxDEV("span", { className: "hf-tag__count", children: p.count }, void 0, false, {
              fileName: "../plugin/hiveforum/js/esm/src/Forum.tsx",
              lineNumber: 250,
              columnNumber: 39
            }, this),
            tag === p.name && /* @__PURE__ */ jsxDEV(Icon, { name: "x", size: 12 }, void 0, false, {
              fileName: "../plugin/hiveforum/js/esm/src/Forum.tsx",
              lineNumber: 251,
              columnNumber: 48
            }, this)
          ]
        },
        p.name,
        true,
        {
          fileName: "../plugin/hiveforum/js/esm/src/Forum.tsx",
          lineNumber: 243,
          columnNumber: 25
        },
        this
      ))
    ] }, void 0, true, {
      fileName: "../plugin/hiveforum/js/esm/src/Forum.tsx",
      lineNumber: 235,
      columnNumber: 17
    }, this),
    /* @__PURE__ */ jsxDEV("div", { className: "hf-listhead", "aria-live": "polite", children: [
      /* @__PURE__ */ jsxDEV("span", { children: hasQuery ? t.plural(total, "resultsone", "resultsmany", "resultsnone") : t.plural(total, "stat_discussion_n", "stat_discussions_n") }, void 0, false, {
        fileName: "../plugin/hiveforum/js/esm/src/Forum.tsx",
        lineNumber: 258,
        columnNumber: 17
      }, this),
      counts.unread > 0 && currentuser && /* @__PURE__ */ jsxDEV("button", { type: "button", className: "btn btn-link btn-sm hf-markread", onClick: onMarkAllRead, children: [
        /* @__PURE__ */ jsxDEV(Icon, { name: "checkcircle", size: 14 }, void 0, false, {
          fileName: "../plugin/hiveforum/js/esm/src/Forum.tsx",
          lineNumber: 263,
          columnNumber: 25
        }, this),
        " ",
        t("markallread")
      ] }, void 0, true, {
        fileName: "../plugin/hiveforum/js/esm/src/Forum.tsx",
        lineNumber: 262,
        columnNumber: 21
      }, this)
    ] }, void 0, true, {
      fileName: "../plugin/hiveforum/js/esm/src/Forum.tsx",
      lineNumber: 257,
      columnNumber: 13
    }, this),
    /* @__PURE__ */ jsxDEV("div", { className: `hf-list ${loading ? "is-loading" : ""}`, "aria-busy": loading, children: [
      loading && discussions.length === 0 && [0, 1, 2].map((i) => /* @__PURE__ */ jsxDEV(DiscussionCardSkeleton, {}, i, false, {
        fileName: "../plugin/hiveforum/js/esm/src/Forum.tsx",
        lineNumber: 269,
        columnNumber: 78
      }, this)),
      discussions.map((d) => /* @__PURE__ */ jsxDEV(DiscussionCard, { discussion: d, activeTag: tag, onTag: setTag }, d.id, false, {
        fileName: "../plugin/hiveforum/js/esm/src/Forum.tsx",
        lineNumber: 271,
        columnNumber: 21
      }, this)),
      !loading && discussions.length === 0 && /* @__PURE__ */ jsxDEV("div", { className: "hf-empty", children: [
        /* @__PURE__ */ jsxDEV("div", { className: "hf-empty__art", "aria-hidden": "true", children: /* @__PURE__ */ jsxDEV("svg", { viewBox: "0 0 120 104", width: "120", height: "104", children: [
          /* @__PURE__ */ jsxDEV("path", { className: "hf-hex hf-hex--a", d: "M30 4l26 15v30L30 64 4 49V19z" }, void 0, false, {
            fileName: "../plugin/hiveforum/js/esm/src/Forum.tsx",
            lineNumber: 277,
            columnNumber: 33
          }, this),
          /* @__PURE__ */ jsxDEV("path", { className: "hf-hex hf-hex--b", d: "M90 4l26 15v30L90 64 64 49V19z" }, void 0, false, {
            fileName: "../plugin/hiveforum/js/esm/src/Forum.tsx",
            lineNumber: 278,
            columnNumber: 33
          }, this),
          /* @__PURE__ */ jsxDEV("path", { className: "hf-hex hf-hex--c", d: "M60 40l26 15v30L60 100 34 85V55z" }, void 0, false, {
            fileName: "../plugin/hiveforum/js/esm/src/Forum.tsx",
            lineNumber: 279,
            columnNumber: 33
          }, this)
        ] }, void 0, true, {
          fileName: "../plugin/hiveforum/js/esm/src/Forum.tsx",
          lineNumber: 276,
          columnNumber: 29
        }, this) }, void 0, false, {
          fileName: "../plugin/hiveforum/js/esm/src/Forum.tsx",
          lineNumber: 275,
          columnNumber: 25
        }, this),
        /* @__PURE__ */ jsxDEV("h3", { className: "hf-empty__title", children: hasQuery ? t("emptyresults") : t("emptydiscussions") }, void 0, false, {
          fileName: "../plugin/hiveforum/js/esm/src/Forum.tsx",
          lineNumber: 282,
          columnNumber: 25
        }, this),
        /* @__PURE__ */ jsxDEV("p", { className: "hf-empty__text", children: hasQuery ? t("emptyresults_help") : t("emptydiscussions_help") }, void 0, false, {
          fileName: "../plugin/hiveforum/js/esm/src/Forum.tsx",
          lineNumber: 283,
          columnNumber: 25
        }, this),
        hasQuery ? /* @__PURE__ */ jsxDEV("button", { type: "button", className: "btn btn-outline-secondary", onClick: () => {
          setFilter("all");
          setSearchInput("");
          setTag("");
        }, children: t("resetfilters") }, void 0, false, {
          fileName: "../plugin/hiveforum/js/esm/src/Forum.tsx",
          lineNumber: 285,
          columnNumber: 29
        }, this) : caps.canstart && !composing && /* @__PURE__ */ jsxDEV("button", { type: "button", className: "btn btn-primary", onClick: () => setComposing(true), children: [
          /* @__PURE__ */ jsxDEV(Icon, { name: "plus", size: 16 }, void 0, false, {
            fileName: "../plugin/hiveforum/js/esm/src/Forum.tsx",
            lineNumber: 292,
            columnNumber: 33
          }, this),
          " ",
          t("startfirst")
        ] }, void 0, true, {
          fileName: "../plugin/hiveforum/js/esm/src/Forum.tsx",
          lineNumber: 291,
          columnNumber: 29
        }, this)
      ] }, void 0, true, {
        fileName: "../plugin/hiveforum/js/esm/src/Forum.tsx",
        lineNumber: 274,
        columnNumber: 21
      }, this)
    ] }, void 0, true, {
      fileName: "../plugin/hiveforum/js/esm/src/Forum.tsx",
      lineNumber: 268,
      columnNumber: 13
    }, this),
    remaining > 0 && /* @__PURE__ */ jsxDEV("div", { className: "hf-more", children: /* @__PURE__ */ jsxDEV("button", { type: "button", className: "btn btn-outline-secondary", onClick: loadMore, disabled: loadingMore, children: [
      loadingMore && /* @__PURE__ */ jsxDEV("span", { className: "spinner-border spinner-border-sm me-2", "aria-hidden": "true" }, void 0, false, {
        fileName: "../plugin/hiveforum/js/esm/src/Forum.tsx",
        lineNumber: 302,
        columnNumber: 41
      }, this),
      t("loadmore", remaining)
    ] }, void 0, true, {
      fileName: "../plugin/hiveforum/js/esm/src/Forum.tsx",
      lineNumber: 301,
      columnNumber: 21
    }, this) }, void 0, false, {
      fileName: "../plugin/hiveforum/js/esm/src/Forum.tsx",
      lineNumber: 300,
      columnNumber: 17
    }, this)
  ] }, void 0, true, {
    fileName: "../plugin/hiveforum/js/esm/src/Forum.tsx",
    lineNumber: 153,
    columnNumber: 9
  }, this);
}
__name(ForumView, "ForumView");
function Forum(props) {
  return /* @__PURE__ */ jsxDEV(StringsProvider, { strings: props.strings, children: /* @__PURE__ */ jsxDEV(ForumView, { ...props }, void 0, false, {
    fileName: "../plugin/hiveforum/js/esm/src/Forum.tsx",
    lineNumber: 314,
    columnNumber: 13
  }, this) }, void 0, false, {
    fileName: "../plugin/hiveforum/js/esm/src/Forum.tsx",
    lineNumber: 313,
    columnNumber: 9
  }, this);
}
__name(Forum, "Forum");
export {
  Forum as default
};
//# sourceMappingURL=Forum.dev.js.map
