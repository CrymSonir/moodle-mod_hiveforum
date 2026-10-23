/**
 * Build the reply tree from the flat list of posts returned by the server.
 *
 * @module     mod_hiveforum/thread/tree
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */function p(c){const s=[...c].sort((e,t)=>e.timecreated-t.timecreated||e.id-t.id),o=s.find(e=>e.isfirst)??null,d=new Map;for(const e of s)d.set(e.id,{post:e,children:[],descendants:0});const r=[];for(const e of s){if(o&&e.id===o.id)continue;const t=d.get(e.id),n=e.parentid&&e.parentid!==o?.id?d.get(e.parentid):void 0;n?n.children.push(t):r.push(t)}const i=e=>(e.descendants=e.children.reduce((t,n)=>t+1+i(n),0),e.descendants);return r.forEach(i),{root:o,replies:r}}export{p as buildTree};
