<?php

use \block_maristtela\report;

require_once('../../config.php');
require_once($CFG->libdir . '/tablelib.php');
global $DB;

$courseid = required_param('courseid', PARAM_INT);
$download = optional_param('download', '', PARAM_ALPHA);
$user = optional_param('user', '', PARAM_TEXT);
$starttime = optional_param('starttime', '', PARAM_TEXT);
$endtime = optional_param('endtime', '', PARAM_TEXT);
$pergunta_ajax = optional_param('ajax_pergunta', '', PARAM_RAW); // pergunta via AJAX

// Parâmetros auxiliares
$starttime_ts = strtotime($starttime);
$endtime_ts = strtotime($endtime);
$course = $DB->get_record('course', ['id' => $courseid]);

$PAGE->set_url(new moodle_url('/blocks/maristtela/report.php', [
    'courseid' => $courseid,
    'user' => $user,
    'starttime' => $starttime,
    'endtime' => $endtime
]));

require_login($course);
$context = context_course::instance($courseid);
require_capability('block/maristtela:viewreport', $context);

$datetime = new DateTime();
$table = new report(time());
$table->show_download_buttons_at([TABLE_P_BOTTOM]);
$table->is_downloading($download, 'maristtela_relatorio_' . $datetime->format('Ymd_His'));

// Função para chamada da API OpenAI
function call_openai_api($messages) {
    $apiKey = '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';
    $endpoint = 'https://api.openai.com/v1/chat/completions';

    $postFields = json_encode([
        'model' => 'gpt-3.5-turbo',
        'messages' => $messages,
        'max_tokens' => 500,
        'temperature' => 0.7
    ]);

    $ch = curl_init($endpoint);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Content-Type: application/json",
        "Authorization: Bearer $apiKey"
    ]);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);

    $response = curl_exec($ch);

    if(curl_errno($ch)) {
        curl_close($ch);
        return "Erro na chamada da API: " . curl_error($ch);
    }

    curl_close($ch);

    $data = json_decode($response, true);

    if (isset($data['choices'][0]['message']['content'])) {
        return trim($data['choices'][0]['message']['content']);
    } else {
        return "Resposta inválida da API OpenAI.";
    }
}

// Se a requisição for AJAX (POST com 'ajax_pergunta'), processa e retorna JSON
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($pergunta_ajax)) {
    // Construir contexto da conversa a partir do banco
    $sqlctx = "
        SELECT ocl.usermessage
          FROM {block_maristtela_log} ocl
          JOIN {user} u ON u.id = ocl.userid
          JOIN {context} c ON c.id = ocl.contextid
          LEFT JOIN {course} co ON co.id = c.instanceid
         WHERE 1=1";

    $params = [];
    if ($courseid !== 1) {
        $sqlctx .= " AND c.contextlevel = 50 AND co.id = :courseid";
        $params['courseid'] = $courseid;
    }
    if ($user) {
        $sqlctx .= " AND CONCAT(u.firstname, ' ', u.lastname) LIKE :user";
        $params['user'] = "%$user%";
    }
    if ($starttime_ts) {
        $sqlctx .= " AND ocl.timecreated > :starttime";
        $params['starttime'] = $starttime_ts;
    }
    if ($endtime_ts) {
        $sqlctx .= " AND ocl.timecreated < :endtime";
        $params['endtime'] = $endtime_ts;
    }

    $sqlctx .= " ORDER BY ocl.timecreated ASC LIMIT 100";

    $mensagens = $DB->get_fieldset_sql($sqlctx, $params);

    $messages = [
        ['role' => 'system', 'content' => 'Você é um assistente que analisa interações dos estudantes no Moodle. Use as mensagens fornecidas para responder a pergunta.']
    ];

    foreach ($mensagens as $msg) {
        $cleanmsg = trim(strip_tags($msg));
        if (!empty($cleanmsg)) {
            $messages[] = ['role' => 'user', 'content' => $cleanmsg];
        }
    }

    $messages[] = ['role' => 'user', 'content' => $pergunta_ajax];

    $resposta = call_openai_api($messages);

    // Retorna JSON e encerra
    header('Content-Type: application/json');
    echo json_encode(['resposta' => $resposta]);
    exit;
}

// --- A PARTIR DAQUI, renderiza a página normal ---

if (!$table->is_downloading()) {
    $PAGE->set_pagelayout('report');
    $PAGE->set_title('📋 Relatório de Interações com a Maristela');
    $PAGE->set_heading('📋 Relatório de Interações com a Maristela');
    $PAGE->navbar->add($course->shortname, new moodle_url('/course/view.php', ['id' => $course->id]));
    $PAGE->navbar->add('Relatório Maristela', $PAGE->url);
    echo $OUTPUT->header();

    // Estilos
    echo '<style>
        body, .card, h1, h2, h3, h4, h5, h6, .lead, label, textarea, input, .form-control, .btn {
            font-family: "Segoe UI", "Helvetica Neue", Roboto, Arial, sans-serif;
            font-weight: 400;
        }
        h1, h2, h3 {
            font-weight: 500;
        }
        .card-header {
            font-weight: 600;
            font-size: 1rem;
        }
    </style>';

    // Título
    echo html_writer::start_div('container-fluid mb-4');
    echo html_writer::tag('h1', '📋 Relatório de Interações com a Maristela', ['class' => 'display-5']);
    echo html_writer::tag('p', 'Análise das interações dos estudantes com a assistente virtual Maristela.', ['class' => 'lead']);
    echo html_writer::end_div();

    // Formulário com textarea e botão (sem reload)
    echo html_writer::start_div('container-fluid mb-5');
    echo html_writer::start_div('card shadow-sm');
    echo html_writer::start_div('card-body');
    echo html_writer::tag('h3', '💬 Pergunte à Maristela', ['class' => 'card-title mb-3']);

    echo '<div class="mb-3">';
    echo html_writer::tag('label', 'Pergunta:', ['for' => 'pergunta_ia', 'class' => 'form-label']);
    echo html_writer::tag('textarea', '', [
        'id' => 'pergunta_ia',
        'class' => 'form-control',
        'rows' => 3,
        'placeholder' => 'Ex: Maristela, quais são os temas mais recorrentes?'
    ]);
    echo html_writer::tag('button', '📨 Perguntar', [
        'id' => 'btnPerguntar',
        'class' => 'btn btn-primary mt-2'
    ]);
    echo '</div>';

    // Div para mostrar a resposta da IA
    echo '<div id="resposta_ia" class="p-3 border bg-light rounded" style="white-space: pre-wrap;"></div>';

    echo html_writer::end_div(); // card-body
    echo html_writer::end_div(); // card
    echo html_writer::end_div(); // container-fluid

    // Código para gerar resumo, tabelas e gráficos (mantido igual ao original)

    // RESUMO DE DADOS
    $logdata = $DB->get_records('block_maristtela_log');
    $wordcount = [];
    $hourcount = array_fill(0, 24, 0);
    $dailyusers = [];
    $user_interactions = [];

    foreach ($logdata as $row) {
        $words = preg_split('/\s+/', strtolower(strip_tags($row->usermessage ?? '')));
        foreach ($words as $w) {
            $w = trim($w, ".,!?()[]{}\"");
            if (mb_strlen($w) > 3) {
                $wordcount[$w] = ($wordcount[$w] ?? 0) + 1;
            }
        }

        $h = (int) date('G', $row->timecreated);
        $hourcount[$h]++;
        $dia = date('Y-m-d', $row->timecreated);
        $dailyusers[$dia][$row->userid] = true;
        $user_interactions[$row->userid] = ($user_interactions[$row->userid] ?? 0) + 1;
    }

    arsort($wordcount);
    $topwords = array_slice($wordcount, 0, 10);
    $horapico = array_keys($hourcount, max($hourcount))[0];
    arsort($user_interactions);
    $topusers = array_slice($user_interactions, 0, 5, true);
    asort($user_interactions);
    $bottomusers = array_slice($user_interactions, 0, 5, true);
    $usernames = $DB->get_records_list('user', 'id', array_keys($topusers + $bottomusers), '', 'id, firstname, lastname');

    echo html_writer::start_div('container-fluid mb-5');
    echo html_writer::tag('h3', '📊 Painel de Interações', ['class' => 'mb-4']);
    echo html_writer::start_div('row g-4');

    // GRÁFICO DE PALAVRAS
    echo html_writer::start_div('col-md-6 col-lg-4');
    echo html_writer::start_div('card shadow-sm h-100');
    echo html_writer::div('🔤 Palavras Frequentes', 'card-header fw-bold text-white', ['style' => 'background-color: #00703C;']);
    echo html_writer::start_div('card-body');
    echo html_writer::tag('canvas', '', ['id' => 'chart_palavras', 'width' => 300, 'height' => 300]);
    echo html_writer::end_div();
    echo html_writer::end_div();
    echo html_writer::end_div();

    // COLUNA COM 4 CARDS EMPILHADOS
    echo html_writer::start_div('col-md-3');

    // ⏰ Horário de Pico
    echo html_writer::start_div('card mb-3 shadow-sm', [
        'style' => 'background-color:#003366; color:white'
    ]);
    echo html_writer::start_div('card-body text-center');
    echo html_writer::tag('h6', '⏰ Horário de Pico', ['class' => 'fw-bold']);
    echo html_writer::tag('div', "<span class='display-6'>$horapico</span><span class='h6'>h</span>");
    echo html_writer::end_div();
    echo html_writer::end_div();

    // 📈 Mais Interações
    echo html_writer::start_div('card mb-3 shadow-sm', [
        'style' => 'background-color:#00703C; color:white'
    ]);
    echo html_writer::start_div('card-body');
    echo html_writer::tag('h6', '📈 Mais Interações', ['class' => 'fw-bold']);
    foreach ($topusers as $uid => $count) {
        $fullname = isset($usernames[$uid]) ? fullname($usernames[$uid]) : "Usuário #$uid";
        echo html_writer::tag('div', "$fullname: $count", ['class' => 'small']);
    }
    echo html_writer::end_div();
    echo html_writer::end_div();

    // 📉 Menos Interações
    echo html_writer::start_div('card mb-3 shadow-sm', [
        'style' => 'background-color:#F2F2F2; border-left: 5px solid #00703C'
    ]);
    echo html_writer::start_div('card-body');
    echo html_writer::tag('h6', '📉 Menos Interações', ['class' => 'fw-bold text-dark']);
    foreach ($bottomusers as $uid => $count) {
        $fullname = isset($usernames[$uid]) ? fullname($usernames[$uid]) : "Usuário #$uid";
        echo html_writer::tag('div', "$fullname: $count", ['class' => 'small text-dark']);
    }
    echo html_writer::end_div();
    echo html_writer::end_div();

    // 👥 Usuários únicos por Dia
    echo html_writer::start_div('card shadow-sm', [
        'style' => 'background-color:#F2F2F2; border-left: 5px solid #003366'
    ]);
    echo html_writer::start_div('card-body');
    echo html_writer::tag('h6', '👥 Usuários por Dia', ['class' => 'fw-bold text-dark']);
    foreach (array_slice($dailyusers, 0, 5) as $dia => $usuarios) {
        echo html_writer::tag('div', "$dia: " . count($usuarios), ['class' => 'small text-dark']);
    }
    echo html_writer::end_div();
    echo html_writer::end_div();

    echo html_writer::end_div(); // col-md-3
    echo html_writer::end_div(); // row
    echo html_writer::end_div(); // container-fluid

    // Construção da cláusula WHERE para a tabela
    $where = "1=1";
    $params = [];

    if ($courseid !== 1) {
        $where = "c.contextlevel = 100 AND co.id = :courseid";
        $params['courseid'] = $courseid;
    }
    if ($user) {
        $where .= " AND CONCAT(u.firstname, ' ', u.lastname) LIKE :user";
        $params['user'] = "%$user%";
    }
    if ($starttime_ts) {
        $where .= " AND ocl.timecreated > :starttime";
        $params['starttime'] = $starttime_ts;
    }
    if ($endtime_ts) {
        $where .= " AND ocl.timecreated < :endtime";
        $params['endtime'] = $endtime_ts;
    }

    $table->set_sql(
        "ocl.*, CONCAT(u.firstname, ' ', u.lastname) AS user_name",
        "{block_maristtela_log} ocl
         JOIN {user} u ON u.id = ocl.userid
         JOIN {context} c ON c.id = ocl.contextid
         LEFT JOIN {course} co ON co.id = c.instanceid",
        $where,
        $params
    );
    $table->define_baseurl($PAGE->url);
    $table->out(10, true);

    // GRÁFICO DE PIZZA COM Chart.js
    echo '<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>';
    echo '<script>
    document.addEventListener("DOMContentLoaded", function () {
        const ctx = document.getElementById("chart_palavras").getContext("2d");
        new Chart(ctx, {
            type: "pie",
            data: {
                labels: ' . json_encode(array_keys($topwords)) . ',
                datasets: [{
                    data: ' . json_encode(array_values($topwords)) . ',
                    backgroundColor: [
                        "#003366", "#00703C", "#ffc107", "#dc3545",
                        "#6f42c1", "#17a2b8", "#fd7e14", "#20c997",
                        "#6610f2", "#343a40"
                    ]
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { position: "bottom" }
                }
            }
        });
    });
    </script>';

    // SCRIPT AJAX para interação sem reload
    echo '<script>
    document.getElementById("btnPerguntar").addEventListener("click", function() {
        const pergunta = document.getElementById("pergunta_ia").value.trim();
        const respostaDiv = document.getElementById("resposta_ia");

        if (!pergunta) {
            alert("Por favor, digite uma pergunta.");
            return;
        }

        respostaDiv.textContent = "💭 A Maristela está pensando...";

        fetch("' . $PAGE->url->out(false) . '", {
            method: "POST",
            headers: {
                "Content-Type": "application/x-www-form-urlencoded"
            },
            body: new URLSearchParams({
                ajax_pergunta: pergunta
            })
        })
        .then(response => response.json())
        .then(data => {
            if(data.resposta) {
                respostaDiv.textContent = data.resposta;
            } else {
                respostaDiv.textContent = "Erro: resposta vazia da API.";
            }
        })
        .catch(err => {
            respostaDiv.textContent = "Erro na requisição: " + err.message;
        });
    });
    </script>';

    echo $OUTPUT->footer();
}

