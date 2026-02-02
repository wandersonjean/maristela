<?php

defined('MOODLE_INTERNAL') || die();

$capabilities = array(

    'block/maristtela:myaddinstance' => array(
        'captype' => 'write', // Tipo de capacidade: escrita
        'contextlevel' => CONTEXT_SYSTEM, // Nível de contexto: sistema (disponível para todo o site)
        'archetypes' => array(
            'user' => CAP_ALLOW // Permite que qualquer usuário adicione o bloco à sua página pessoal
        ),
        'clonepermissionsfrom' => 'moodle/my:manageblocks'
    ),

    'block/maristtela:addinstance' => array(
        'riskbitmask' => RISK_SPAM | RISK_XSS, // Define os riscos associados: spam e XSS
        'captype' => 'write', // Tipo de capacidade: escrita
        'contextlevel' => CONTEXT_BLOCK, // Nível de contexto: bloco (disponível em um bloco específico)
        'archetypes' => array(
            'editingteacher' => CAP_ALLOW, // Permite que professores editores adicionem o bloco
            'manager' => CAP_ALLOW // Permite que gerentes adicionem o bloco
        ),
        'clonepermissionsfrom' => 'moodle/site:manageblocks'
    ),

    'block/maristtela:viewreport' => array(
        'riskbitmask' => RISK_PERSONAL, // Define o risco associado: dados pessoais
        'captype' => 'read', // Tipo de capacidade: leitura
        'contextlevel' => CONTEXT_COURSE, // Nível de contexto: curso (disponível em um curso específico)
        'archetypes' => array(
            'teacher' => CAP_ALLOW, // Permite que professores vejam o relatório
            'editingteacher' => CAP_ALLOW, // Permite que professores editores vejam o relatório
            'manager' => CAP_ALLOW // Permite que gerentes vejam o relatório
        ),
        'clonepermissionsfrom' => 'coursereport/participation:view',
    ),
);



?>
