<?php

class block_maristtela extends block_base {
    public function init() {
        // Inicializa o bloco com um título
        $this->title = get_string('pluginname', 'block_maristtela');
    }

    public function has_config() {
        // Indica que este bloco possui configurações
        return true;
    }

    function applicable_formats() {
        // Define os formatos onde o bloco pode ser aplicado
        return array('all' => true);
    }

    public function specialization() {
        // Especializa o bloco com base nas configurações específicas
        if (!empty($this->config->title)) {
            $this->title = $this->config->title;
        }
    }

    public function get_content() {
        global $OUTPUT, $PAGE, $USER, $DB;

        // Se o conteúdo já foi definido, retorna-o
        if ($this->content !== null) {
            return $this->content;
        }

        // --- INÍCIO DA LÓGICA DE SUGESTÕES DINÂMICAS ---
        $suggested_questions = [];
        if ($PAGE->cm) { // Se estamos numa atividade
            switch ($PAGE->cm->modname) {
                case 'forum':
                    $suggested_questions = [
                        'Como posso iniciar uma nova discussão?',
                        'Quais são as regras para postar neste fórum?',
                        'Ajuda-me a formular uma resposta para o tópico principal.'
                    ];
                    break;
                case 'quiz':
                    $suggested_questions = [
                        'Quantas tentativas tenho para este questionário?',
                        'Qual é a data limite para responder?',
                        'Podes dar-me uma dica sobre o tipo de questão que vou encontrar?'
                    ];
                    break;
                case 'assign':
                    $suggested_questions = [
                        'Quais são os critérios de avaliação desta tarefa?',
                        'Qual o formato de ficheiro que devo enviar?',
                        'Podes dar-me um exemplo do que é esperado?'
                    ];
                    break;
                default:
                    $suggested_questions = [
                        'Qual o objetivo principal desta atividade?',
                        'Onde encontro o material de apoio para esta atividade?'
                    ];
            }
        } else { // Página principal do curso ou painel
            $suggested_questions = [
                'Qual é o próximo prazo de entrega?',
                'Resume os tópicos da semana.',
                'Onde encontro o plano de ensino?'
            ];
        }
        // --- FIM DA LÓGICA DE SUGESTÕES DINÂMICAS ---

        // --- INÍCIO DA LÓGICA DE GAMIFICAÇÃO/BADGE ---
        $interaction_badge = '';
        if (!isguestuser() && $USER->id != 0) { // Apenas para utilizadores autenticados
            $interaction_count = $DB->count_records('block_maristtela_log', ['userid' => $USER->id]);
            $badge_html = '';
            $badge_text = '';

            if ($interaction_count >= 1 && $interaction_count <= 5) {
                $badge_html = '🥉'; $badge_text = 'Iniciante Curioso';
            } else if ($interaction_count >= 6 && $interaction_count <= 15) {
                $badge_html = '🥈'; $badge_text = 'Explorador Dedicado';
            } else if ($interaction_count >= 16 && $interaction_count <= 30) {
                $badge_html = '🥇'; $badge_text = 'Conversador Empenhado';
            } else if ($interaction_count >= 31 && $interaction_count <= 50) {
                $badge_html = '💎'; $badge_text = 'Colaborador Assíduo';
            } else if ($interaction_count > 50) {
                $badge_html = '🏆'; $badge_text = 'Embaixador Maristela';
            }

            if (!empty($badge_html)) {
                $interaction_badge = "<div class='maristtela_badge' title='{$badge_text} ({$interaction_count} interações)'>{$badge_html} {$badge_text}</div>";
            }
        }
        // --- FIM DA LÓGICA DE GAMIFICAÇÃO/BADGE ---

        // Obtém a configuração para persistência de conversa
        $persistconvo = get_config('block_maristtela', 'persistconvo');
        if (!empty($this->config)) {
            $persistconvo = (property_exists($this->config, 'persistconvo') && get_config('block_maristtela', 'allowinstancesettings')) ? $this->config->persistconvo : $persistconvo;
        }

        // Envia dados para o front-end através de JavaScript
        $this->page->requires->js_call_amd('block_maristtela/lib', 'init', [[
            'blockId' => $this->instance->id,
            'api_type' => get_config('block_maristtela', 'type') ? get_config('block_maristtela', 'type') : 'chat',
            'persistConvo' => $persistconvo,
            'suggestedQuestions' => $suggested_questions
        ]]);

        // Verifica se os rótulos de nome devem ser exibidos
        $showlabelscss = '';
        if (!empty($this->config) && property_exists($this->config, 'showlabels') && !$this->config->showlabels) {
            $showlabelscss = '
                .openai_message:before {
                    display: none;
                }
                .openai_message {
                    margin-bottom: 0.5rem;
                }
            ';
        }


        // Obtém as configurações globais ou padrões
        $assistantname = get_config('block_maristtela', 'assistantname') ? get_config('block_maristtela', 'assistantname') : get_string('defaultassistantname', 'block_maristtela');
        $username = get_config('block_maristtela', 'username') ? get_config('block_maristtela', 'username') : get_string('defaultusername', 'block_maristtela');

  // Substitui pelas configurações locais, se disponíveis
        if (!empty($this->config)) {
            $assistantname = (property_exists($this->config, 'assistantname') && $this->config->assistantname) ? $this->config->assistantname : $assistantname;
            $username = (property_exists($this->config, 'username') && $this->config->username) ? $this->config->username : $username;
        }
        $assistantname = format_string($assistantname, true, ['context' => $this->context]);
        $username = format_string($username, true, ['context' => $this->context]);

        // Define o conteúdo HTML e CSS do bloco
        $this->content = new stdClass;
        $this->content->text = '

            <script>
                var assistantName = "' . $assistantname . '";
                var userName = "' . $username . '";
            </script>

            <style>
                ' . $showlabelscss . '
                .openai_message.user:before {
                    content: "' . $username . '";
                    font-weight: bold;
                }
                .openai_message.bot:before {
                    content: "' . $assistantname . '";
                    font-weight: bold;
                }
           
                .chat-avatar {
                width: 250px;
                height: 250px;
                border-radius: 5%;
                background-size: cover;
                background-position: center;
                background-image: url("https://i.ibb.co/yFGV3rYF/2.png");
                margin: 0 auto;
                display: block;
                }
/* ESTILOS PARA SUGESTÕES E BADGE */
                .maristtela_badge {
                    text-align: center;
                    font-size: 0.9rem;
                    font-weight: 500;
                    color: #333;
                    background-color: #f0f0f0;
                    padding: 0.35rem;
                    border-radius: 0.5rem;
                    margin-bottom: 1rem;
                    border: 1px solid #ddd;
                }
                .maristtela_suggestions {
                    display: flex;
                    flex-wrap: wrap;
                    gap: 0.5rem;
                    margin-bottom: 0.75rem;
                }
                .maristtela_suggestion_btn {
                    background-color: #e0f2f1;
                    color: #006341;
                    border: 1px solid #b2dfdb;
                    border-radius: 1rem;
                    padding: 0.25rem 0.75rem;
                    font-size: 0.8rem;
                    font-weight: 500;
                    cursor: pointer;
                    transition: all 0.2s ease;
                }
                .maristtela_suggestion_btn:hover {
                    background-color: #00796b;
                    color: #fff;
                    border-color: #00695c;
                    transform: translateY(-1px);
                    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                }

            </style>
             ' . $interaction_badge . '

           <div id="openai_chat_log" role="log"></div>

            <div id="maristtela_suggestions" class="maristtela_suggestions"></div>

            <div class="chat-avatar" role="img"></div>

        ';
        // Verifica se a chave da API está configurada
        if (
            empty(get_config('block_maristtela', 'apikey')) && 
            (!get_config('block_maristtela', 'allowinstancesettings') || empty($this->config->apikey))
        ) {
            $this->content->footer = get_string('apikeymissing', 'block_maristtela');
        } else {
            // Prepara dados de contexto para o template do controle
            $contextdata = [
                'logging_enabled' => get_config('block_maristtela', 'logging'),
                'is_edit_mode' => $PAGE->user_is_editing(),
                'pix_popout' => '/blocks/maristtela/pix/arrow-up-right-from-square.svg',
                'pix_logo' => '/blocks/maristtela/pix/arrow-up-right-from-square-logo.svg',
                'pix_arrow_right' => '/blocks/maristtela/pix/arrow-right.svg',
                'pix_refresh' => '/blocks/maristtela/pix/refresh.svg',
            ];

            // Renderiza o rodapé do bloco usando um template
            $this->content->footer = $OUTPUT->render_from_template('block_maristtela/control_bar', $contextdata);
        }

        return $this->content;
    }
}