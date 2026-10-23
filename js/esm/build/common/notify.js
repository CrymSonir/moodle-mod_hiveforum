import{requireAsync as n}from"@moodle/lms/core/amd";/**
 * Feedback through Moodle's own toasts and exception dialogue (AMD modules), so the
 * plugin looks and behaves like the rest of the site.
 *
 * @module     mod_hiveforum/common/notify
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */const r=(e,t="success")=>{n("core/toast").then(o=>o.add(e,{type:t})).catch(()=>{})},c=e=>{n("core/notification").then(t=>t.exception(e)).catch(()=>window.console.error(e))},d=e=>{e&&n("core_filters/events").then(t=>t.notifyFilterContentUpdated([e])).catch(()=>{})};export{c as exception,d as filterContentUpdated,r as toast};
