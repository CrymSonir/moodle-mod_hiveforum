import{createContext as o,useContext as t}from"react";/**
 * Shared state and actions of the thread view.
 *
 * @module     mod_hiveforum/thread/context
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */const s=o(null),r=()=>{const e=t(s);if(!e)throw new Error("useThread() outside of <ThreadContext.Provider>");return e};export{s as ThreadContext,r as useThread};
