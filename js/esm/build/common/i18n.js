import{createContext as a,useCallback as g,useContext as p}from"react";import{jsx as m}from"react/jsx-runtime";/**
 * Language strings.
 *
 * Strings are sent by the server in the initial props (see \mod_hiveforum\output\strings),
 * so the UI renders instantly in the right language, without a string round-trip.
 *
 * @module     mod_hiveforum/common/i18n
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */const s=a({});function c({strings:n,children:r}){return m(s.Provider,{value:n,children:r})}const u=(n,r)=>r===void 0?n:typeof r=="object"?n.replace(/\{\$a->(\w+)\}/g,(t,e)=>e in r?String(r[e]):t):n.replace(/\{\$a\}/g,String(r));function d(){const n=p(s),r=g((t,e)=>u(n[t]??`[[${t}]]`,e),[n]);return r.plural=(t,e,o,i)=>r(t===0&&i?i:t===1?e:o,t),r}export{c as StringsProvider,u as interpolate,d as useStrings};
