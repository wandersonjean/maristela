<?php
/**
 * Conteúdo da aba "Painel Geral".
 * VERSÃO LIMPA: Removido o bloco "Pergunte à Maristela sobre os dados".
 *
 * @package    block_maristtela
 */

defined('MOODLE_INTERNAL') || die();

global $DB, $CFG, $PAGE, $OUTPUT;

// A classe report já foi incluída por report.php.

// 1. Captura dos parâmetros da URL para os links dos filtros e paginação.
$courseid = required_param('courseid', PARAM_INT);
$user = optional_param('user', '', PARAM_TEXT);
$starttime = optional_param('starttime', '', PARAM_TEXT);
$endtime = optional_param('endtime', '', PARAM_TEXT);
$badgelevel = optional_param('badgelevel', 0, PARAM_INT);

// 2. Inicialização da tabela e configuração da consulta.
$table = new \block_maristtela\report(time());
$table->setup_filtered_sql();

// 3. Configuração final da tabela (URL para filtros/paginação e botões de download).
$baseurl = new moodle_url('/blocks/maristtela/report.php', [
    'courseid' => $courseid,
    'user' => $user,
    'starttime' => $starttime,
    'endtime' => $endtime,
    'badgelevel' => $badgelevel
]);
$table->define_baseurl($baseurl);
$table->show_download_buttons_at([TABLE_P_TOP]);

// 4. Renderização da página HTML.
echo '<div class="mt-4">';
echo '<style>
        .engagement-image { max-width: 350px; height: auto; border-radius: .25rem; }
        #daily_user_date { margin-bottom: 10px; }
        #daily_user_display { font-size: 1.5rem; font-weight: 600; }
        .filter-form { display: flex; gap: 10px; align-items: center; margin-top: 1rem; margin-bottom: 1rem; }
        .filter-form .form-control { min-width: 250px; }
      </style>';

// Lógica para cálculo dos resumos de dados
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
$horapico = !empty($hourcount) && max($hourcount) > 0 ? array_keys($hourcount, max($hourcount))[0] : 'N/A';
arsort($user_interactions);
$topusers = array_slice($user_interactions, 0, 5, true);
$usernames = !empty($topusers) ? $DB->get_records_list('user', 'id', array_keys($topusers), '', 'id, firstname, lastname') : [];

$dailyusers_processed = [];
foreach ($dailyusers as $day => $users) {
    $dailyusers_processed[$day] = count($users);
}

// Renderização dos painéis de interação
echo html_writer::start_div('container-fluid mb-5');
echo html_writer::tag('h3', '📊 Painel de Interações', ['class' => 'mb-4']);
echo html_writer::start_div('row g-4 d-flex align-items-stretch');

// Palavras frequentes
echo html_writer::start_div('col-lg-4 col-md-6');
echo html_writer::start_div('card h-100');
echo html_writer::div('🔤 Palavras Frequentes', 'card-header fw-bold text-white', ['style' => 'background-color: #00703C;']);
echo html_writer::start_div('card-body d-flex align-items-center justify-content-center');
echo html_writer::tag('canvas', '', ['id' => 'chart_palavras']);
echo html_writer::end_div();
echo html_writer::end_div();
echo html_writer::end_div();

// Horário de pico e top usuários
echo html_writer::start_div('col-lg-4 col-md-6');
echo html_writer::start_div('card mb-3', ['style' => 'background-color:#003366; color:white']);
echo html_writer::start_div('card-body text-center');
echo html_writer::tag('h6', '⏰ Horário de Pico', ['class' => 'fw-bold']);
echo html_writer::tag('div', "<span class='display-6'>$horapico</span><span class='h6'>h</span>");
echo html_writer::end_div();
echo html_writer::end_div();

echo html_writer::start_div('card mb-3', ['style' => 'background-color:#00703C; color:white']);
echo html_writer::start_div('card-body');
echo html_writer::tag('h6', '📈 Mais Interações', ['class' => 'fw-bold']);
if (!empty($topusers)) {
    foreach ($topusers as $uid => $count) {
        $fullname = isset($usernames[$uid]) ? fullname($usernames[$uid]) : "Usuário #$uid";
        echo html_writer::tag('div', "$fullname: $count", ['class' => 'small']);
    }
} else {
    echo html_writer::tag('div', 'N/A', ['class' => 'small']);
}
echo html_writer::end_div();
echo html_writer::end_div();

// Usuários por dia
echo html_writer::start_div('card', ['style' => 'background-color:#F2F2F2; border-left: 5px solid #003366']);
echo html_writer::start_div('card-body');
echo html_writer::tag('h6', '👥 Usuários por Dia', ['class' => 'fw-bold text-dark']);
echo html_writer::tag('input', '', ['type' => 'date', 'id' => 'daily_user_date', 'class' => 'form-control']);
echo html_writer::div('<span id="daily_user_display">--</span> usuário(s)', 'text-center mt-2');
echo html_writer::end_div();
echo html_writer::end_div();
echo html_writer::end_div();

// Imagem lateral
echo html_writer::start_div('col-lg-4 col-md-12 d-flex align-items-center justify-content-center mt-4 mt-lg-0');
$imageurl = $OUTPUT->image_url('analitics', 'block_maristtela');
echo html_writer::img($imageurl, 'Imagem de Análise de Engajamento', ['class' => 'engagement-image']);
echo html_writer::end_div();

echo html_writer::end_div(); // row
echo html_writer::end_div(); // container

// Formulário de Filtro
$badge_options = [
    '0' => 'Filtrar por Nível...',
    '1' => '🥉 Iniciante Curioso',
    '2' => '🥈 Explorador Dedicado',
    '3' => '🥇 Conversador Engajado',
    '4' => '💎 Colaborador Assíduo',
    '5' => '🏆 Embaixador Maristela'
];
$form_attributes = ['action' => new moodle_url($PAGE->url), 'method' => 'get', 'class' => 'filter-form'];

echo html_writer::start_tag('form', $form_attributes);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'courseid', 'value' => $courseid]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'user', 'value' => s($user)]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'starttime', 'value' => s($starttime)]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'endtime', 'value' => s($endtime)]);
echo html_writer::select($badge_options, 'badgelevel', $badgelevel, false, ['class' => 'form-control']);
echo html_writer::empty_tag('input', ['type' => 'submit', 'value' => get_string('filter', 'core'), 'class' => 'btn btn-secondary']);
echo html_writer::end_tag('form');

// Renderiza a tabela.
$table->out(10, true);

echo '</div>';
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const dailyUsersData = <?php echo json_encode($dailyusers_processed); ?>;
    const datePicker = document.getElementById('daily_user_date');
    const userDisplay = document.getElementById('daily_user_display');

    if (datePicker && userDisplay) {
        const availableDates = Object.keys(dailyUsersData);
        if (availableDates.length > 0) {
            const mostRecentDate = availableDates.sort().pop();
            datePicker.value = mostRecentDate;
            userDisplay.textContent = dailyUsersData[mostRecentDate] || 0;
        }
        datePicker.addEventListener('change', function() {
            userDisplay.textContent = dailyUsersData[this.value] || 0;
        });
    }

    if (document.getElementById("chart_palavras")) {
        const ctx = document.getElementById("chart_palavras").getContext("2d");
        new Chart(ctx, {
            type: "pie",
            data: {
                labels: <?php echo json_encode(array_keys($topwords)); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_values($topwords)); ?>,
                    backgroundColor: ["#003366","#00703C","#ffc107","#dc3545","#6f42c1","#17a2b8","#fd7e14","#20c997","#6610f2","#343a40"]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: "bottom" } }
            }
        });
    }
});
</script>
