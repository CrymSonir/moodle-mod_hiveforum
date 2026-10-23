var __defProp = Object.defineProperty;
var __name = (target, value) => __defProp(target, "name", { value, configurable: true });
/**
 * Build the reply tree from the flat list of posts returned by the server.
 *
 * @module     mod_hiveforum/thread/tree
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
function buildTree(posts) {
  const sorted = [...posts].sort((a, b) => a.timecreated - b.timecreated || a.id - b.id);
  const root = sorted.find((p) => p.isfirst) ?? null;
  const nodes = /* @__PURE__ */ new Map();
  for (const post of sorted) {
    nodes.set(post.id, { post, children: [], descendants: 0 });
  }
  const replies = [];
  for (const post of sorted) {
    if (root && post.id === root.id) {
      continue;
    }
    const node = nodes.get(post.id);
    const parent = post.parentid && post.parentid !== root?.id ? nodes.get(post.parentid) : void 0;
    if (parent) {
      parent.children.push(node);
    } else {
      replies.push(node);
    }
  }
  const count = /* @__PURE__ */ __name((node) => {
    node.descendants = node.children.reduce((sum, child) => sum + 1 + count(child), 0);
    return node.descendants;
  }, "count");
  replies.forEach(count);
  return { root, replies };
}
__name(buildTree, "buildTree");
export {
  buildTree
};
//# sourceMappingURL=tree.dev.js.map
