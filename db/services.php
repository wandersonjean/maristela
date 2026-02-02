<?php
defined('MOODLE_INTERNAL') || die();

$functions = [
    'block_maristtela_get_completion' => [
        'classname'   => 'block_maristtela\external\api',
        'methodname'  => 'get_completion',
        'classpath'   => 'blocks/maristtela/classes/external/api.php',
        'description' => 'Envia uma mensagem para a IA e obtém a resposta.',
        'type'        => 'write',
        'ajax'        => true,
    ],
    'block_maristtela_get_proactive_insight' => [
        'classname'   => 'block_maristtela\external\api',
        'methodname'  => 'get_proactive_insight',
        'classpath'   => 'blocks/maristtela/classes/external/api.php',
        'description' => 'Obtém uma dica proativa baseada no contexto da página.',
        'type'        => 'read',
        'ajax'        => true,
    ]
];