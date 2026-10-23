import{useEffect as u,useId as l,useRef as p}from"react";import{useStrings as h}from"@moodle/lms/mod_hiveforum/common/i18n";import{jsx as e,jsxs as r}from"react/jsx-runtime";/**
 * Accessible confirmation dialogue built on the native <dialog> element
 * (focus trap, Escape and backdrop handled by the browser).
 *
 * @module     mod_hiveforum/common/ConfirmDialog
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */function _({open:a,title:d,message:c,confirmLabel:f,danger:g=!1,onConfirm:m,onClose:t}){const b=h(),i=p(null),n=l(),s=l();return u(()=>{const o=i.current;o&&(a&&!o.open?o.showModal():!a&&o.open&&o.close())},[a]),e("dialog",{ref:i,className:"hf-dialog","aria-labelledby":n,"aria-describedby":s,onClose:t,onClick:o=>{o.target===i.current&&t()},children:r("div",{className:"hf-dialog__body",children:[e("h2",{id:n,className:"hf-dialog__title",children:d}),e("p",{id:s,className:"hf-dialog__message",children:c}),r("div",{className:"hf-dialog__actions",children:[e("button",{type:"button",className:"btn btn-secondary",onClick:t,children:b("cancel")}),e("button",{type:"button",className:`btn ${g?"btn-danger":"btn-primary"}`,onClick:()=>{m(),t()},autoFocus:!0,children:f})]})]})})}export{_ as default};
