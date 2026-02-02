<?php
// Garante que o arquivo só seja executado no contexto do Moodle.
defined('MOODLE_INTERNAL') || die();

// Verifica se as configurações do site estão disponíveis.
if ($hassiteconfig) {

    // Adiciona uma nova página de relatório ao menu de relatórios do Moodle.
    $ADMIN->add('reports', new admin_externalpage(
        'maristtela_report',
        get_string('openai_chat_logs', 'block_maristtela'),
        new moodle_url("$CFG->wwwroot/blocks/maristtela/report.php", ['courseid' => 1]),
        'moodle/site:config'
    ));

    if ($ADMIN->fulltree) {

        require_once($CFG->dirroot . '/blocks/maristtela/lib.php');

        $type = get_type_to_display();
        $assistant_array = [];

        if ($type === 'assistant') {
            $assistant_array = fetch_assistants_array();
        }

        global $PAGE;
        $PAGE->requires->js_call_amd('block_maristtela/settings', 'init');

        // ==============================
        // 🔐 CHAVE DA API
        // ==============================
        $settings->add(new admin_setting_configtext(
            'block_maristtela/apikey',
            get_string('apikey', 'block_maristtela'),
            get_string('apikeydesc', 'block_maristtela'),
            '',
            PARAM_TEXT
        ));

        // ==============================
        // 🧠 TIPO DE INTERFACE
        // ==============================
        $settings->add(new admin_setting_configselect(
            'block_maristtela/type',
            get_string('type', 'block_maristtela'),
            get_string('typedesc', 'block_maristtela'),
            'assistant',
            ['chat' => 'chat', 'assistant' => 'assistant'] // REMOVIDO 'azure'
        ));

        // ==============================
        // ⚙️ RESTRIÇÃO DE USO
        // ==============================
        $settings->add(new admin_setting_configcheckbox(
            'block_maristtela/restrictusage',
            get_string('restrictusage', 'block_maristtela'),
            get_string('restrictusagedesc', 'block_maristtela'),
            1
        ));

        // ==============================
        // 👩‍🏫 NOMES PADRÃO
        // ==============================
        $settings->add(new admin_setting_configtext(
            'block_maristtela/assistantname',
            get_string('assistantname', 'block_maristtela'),
            get_string('assistantnamedesc', 'block_maristtela'),
            'Maristela',
            PARAM_TEXT
        ));

        $settings->add(new admin_setting_configtext(
            'block_maristtela/username',
            get_string('username', 'block_maristtela'),
            get_string('usernamedesc', 'block_maristtela'),
            'Estudante',
            PARAM_TEXT
        ));

        // ==============================
        // 🤖 CONFIGURAÇÕES DO ASSISTENTE
        // ==============================
        if ($type === 'assistant') {

            $settings->add(new admin_setting_heading(
                'block_maristtela/assistantheading',
                get_string('assistantheading', 'block_maristtela'),
                get_string('assistantheadingdesc', 'block_maristtela')
            ));

            if (count($assistant_array)) {
                $settings->add(new admin_setting_configselect(
                    'block_maristtela/assistant',
                    get_string('assistant', 'block_maristtela'),
                    get_string('assistantdesc', 'block_maristtela'),
                    count($assistant_array) ? reset($assistant_array) : null,
                    $assistant_array
                ));
            } else {
                $settings->add(new admin_setting_description(
                    'block_maristtela/noassistants',
                    get_string('assistant', 'block_maristtela'),
                    get_string('noassistants', 'block_maristtela')
                ));
            }

            $settings->add(new admin_setting_configcheckbox(
                'block_maristtela/persistconvo',
                get_string('persistconvo', 'block_maristtela'),
                get_string('persistconvodesc', 'block_maristtela'),
                1
            ));

        } else {
            // ==============================
            // 💬 CONFIGURAÇÕES DO CHAT
            // ==============================
            $settings->add(new admin_setting_heading(
                'block_maristtela/chatheading',
                get_string('chatheading', 'block_maristtela'),
                get_string('chatheadingdesc', 'block_maristtela')
            ));

            $settings->add(new admin_setting_configtextarea(
                'block_maristtela/prompt',
                get_string('prompt', 'block_maristtela'),
                get_string('promptdesc', 'block_maristtela'),
                "Oi, sou a Maristtela, sua assistente virtual de APC na UnB, pronta para descomplicar programação e ajudar nos desafios de código! 🚀💻",
                PARAM_TEXT
            ));
        }

        // ==============================
        // ⚙️ CONFIGURAÇÕES AVANÇADAS
        // ==============================
        $settings->add(new admin_setting_heading(
            'block_maristtela/advanced',
            get_string('advanced', 'block_maristtela'),
            get_string('advanceddesc', 'block_maristtela')
        ));

        if ($type !== 'assistant') {
            $settings->add(new admin_setting_configselect(
                'block_maristtela/model',
                get_string('model', 'block_maristtela'),
                get_string('modeldesc', 'block_maristtela'),
                'text-davinci-003',
                get_models()['models']
            ));

            $settings->add(new admin_setting_configtext(
                'block_maristtela/maxlength',
                get_string('maxlength', 'block_maristtela'),
                get_string('maxlengthdesc', 'block_maristtela'),
                100,
                PARAM_INT
            ));
        }

        // ============================================================
        // 🏅 CONFIGURAÇÕES DE GAMIFICAÇÃO (NOVO BLOCO)
        // ============================================================
        $settings->add(new admin_setting_heading(
            'block_maristtela_gamificacao',
            '🏅 Configurações de Gamificação',
            'Defina quantas interações o estudante precisa realizar para ganhar cada badge da Maristtela.'
        ));

        $settings->add(new admin_setting_configtext(
            'block_maristtela/badge_bronze',
            'Interações para Badge Bronze',
            'Número mínimo de interações com a Maristtela para conquistar a badge de 🥉 Iniciante Curioso.',
            5,
            PARAM_INT
        ));

        $settings->add(new admin_setting_configtext(
            'block_maristtela/badge_prata',
            'Interações para Badge Prata',
            'Número mínimo de interações com a Maristtela para conquistar a badge de 🥈 Explorador Dedicado.',
            10,
            PARAM_INT
        ));

        $settings->add(new admin_setting_configtext(
            'block_maristtela/badge_ouro',
            'Interações para Badge Ouro',
            'Número mínimo de interações com a Maristtela para conquistar a badge 🥇 Conversador Engajado.',
            20,
            PARAM_INT
        ));

        $settings->add(new admin_setting_configtext(
            'block_maristtela/badge_platina',
            'Interações para Badge Diamante',
            'Número mínimo de interações com a Maristtela para conquistar a badge de 💎 Colaborador Assíduo.',
            40,
            PARAM_INT
        ));

        $settings->add(new admin_setting_configtext(
            'block_maristtela/badge_diamante',
            'Interações para Badge Embaixador',
            'Número mínimo de interações com a Maristtela para conquistar a badge de 🏆 Embaixador Maristela.',
            80,
            PARAM_INT
        ));
    }
}
