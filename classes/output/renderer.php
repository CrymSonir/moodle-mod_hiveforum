<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace mod_hiveforum\output;

/**
 * Renderer for mod_hiveforum.
 *
 * Pages are React components (mounted automatically by core/react_autoinit); a
 * light server-side rendering of the same data is emitted in a <noscript> block
 * so the content stays readable without JavaScript.
 *
 * @package    mod_hiveforum
 * @copyright  2026 Hiveforum contributors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class renderer extends \plugin_renderer_base {
    /**
     * Render the discussion list.
     *
     * @param forum_page $page
     * @return string
     */
    public function render_forum_page(forum_page $page): string {
        $props = $page->get_props();
        return $this->mount($page->get_react_component_name(), $props, 'forum')
            . \html_writer::tag('noscript', $this->render_from_template('mod_hiveforum/noscript_forum', $props));
    }

    /**
     * Render a discussion thread.
     *
     * @param thread_page $page
     * @return string
     */
    public function render_thread_page(thread_page $page): string {
        $props = $page->get_props();
        $context = $props;
        $context['sesskey'] = sesskey();
        $context['posts'] = array_map(function (array $post): array {
            $post['timecreatedstr'] = userdate($post['timecreated']);
            return $post;
        }, $props['posts']);
        return $this->mount($page->get_react_component_name(), $props, 'thread')
            . \html_writer::tag('noscript', $this->render_from_template('mod_hiveforum/noscript_thread', $context));
    }

    /**
     * Mount point for a React component, with a skeleton shown until the module is loaded.
     *
     * @param string $component component path inside @moodle/lms
     * @param array $props
     * @param string $variant skeleton variant
     * @return string
     */
    private function mount(string $component, array $props, string $variant): string {
        return \html_writer::div('', 'hf-mount hf-mount--' . $variant, [
            'data-react-component' => '@moodle/lms/' . $component,
            'data-react-props' => json_encode($props, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        ]);
    }
}
