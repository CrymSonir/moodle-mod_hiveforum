<?php
defined('MOODLE_INTERNAL') || die();

$functions = [
    'mod_hiveforum_toggle_reaction' => [
        'classname'   => 'mod_hiveforum\external\toggle_reaction',
        'methodname'  => 'execute',
        'description' => 'Toggle a reaction (like/love/idea) on a hiveforum post.',
        'type'        => 'write',
        'ajax'        => true,
        'capabilities' => 'mod/hiveforum:react',
    ],
];
