<?php
/**
 * Ficheiro Orquestrador do Relatório Maristtela.
 *
 * @package    block_maristtela
 */

require_once('../../config.php');
require_once($CFG->libdir . '/tablelib.php');
require_once($CFG->dirroot . '/blocks/maristtela/classes/report.php');

use \block_maristtela\report;

global $DB, $PAGE, $OUTPUT, $CFG;

$courseid = required_param('courseid', PARAM_INT);
$download = optional_param('download', '', PARAM_ALPHA);

// ============================================================================
// 🔽 ROTA PARA DOWNLOAD DE DADOS (NOVA)
// ============================================================================
if ($download === 'interacoes') {
    require_login();

    // Coleta todos os registros da tabela principal
    $logs = $DB->get_records('block_maristtela_log', [], 'timecreated DESC');

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="interacoes_maristtela.csv"');
    $output = fopen('php://output', 'w');

    // Cabeçalhos do CSV
    fputcsv($output, ['ID', 'Usuário', 'Mensagem', 'Resposta da IA', 'Data/Hora']);

    // Linhas do CSV
    foreach ($logs as $log) {
        $usuario = $DB->get_field('user', $DB->sql_fullname(), ['id' => $log->userid]) ?? 'Desconhecido';
        $datahora = userdate($log->timecreated, '%d/%m/%Y %H:%M');
        fputcsv($output, [
            $log->id,
            $usuario,
            strip_tags($log->usermessage),
            strip_tags($log->airesponse),
            $datahora
        ]);
    }

    fclose($output);
    exit;
}

// ============================================================================
// ROTA AJAX PARA PERGUNTAS À IA
// ============================================================================
$pergunta_ajax = optional_param('ajax_pergunta', '', PARAM_RAW);
if (!empty($pergunta_ajax)) {
    require_login();
    $apiKey = get_config('block_maristtela', 'apikey');
    if (empty($apiKey)) {
        header('Content-Type: application/json');
        echo json_encode(['resposta' => 'ERRO: A chave da API não foi configurada.']);
        exit;
    }

    $endpoint = 'https://api.openai.com/v1/chat/completions';
    $sqlctx = "SELECT ocl.usermessage 
               FROM {block_maristtela_log} ocl 
               JOIN {context} c ON c.id = ocl.contextid 
               WHERE c.instanceid = :courseid";
    $mensagens_log = $DB->get_fieldset_sql($sqlctx, ['courseid' => $courseid]);

    $messages = [['role' => 'system', 'content' => 'Você é um assistente que analisa interações de estudantes no Moodle. Responda a pergunta do professor com base nas mensagens.']];
    foreach ($mensagens_log as $msg) {
        $messages[] = ['role' => 'user', 'content' => 'Mensagem de um aluno: ' . $msg];
    }
    $messages[] = ['role' => 'user', 'content' => 'Com base nisso, responda: ' . $pergunta_ajax];

    $postFields = json_encode(['model' => 'gpt-3.5-turbo', 'messages' => $messages, 'max_tokens' => 500]);
    $ch = curl_init($endpoint);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json", "Authorization: Bearer $apiKey"]);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
    $response = curl_exec($ch);
    $data = [];
    if (!curl_errno($ch)) {
        $decoded = json_decode($response, true);
        $data['resposta'] = $decoded['choices'][0]['message']['content'] ?? "Resposta inválida da API.";
    } else {
        $data['resposta'] = "Erro na API: " . curl_error($ch);
    }
    curl_close($ch);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

// ============================================================================
// CONFIGURAÇÃO PADRÃO DA PÁGINA
// ============================================================================
$course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
require_login($course);
$context = context_course::instance($course->id);
require_capability('block/maristtela:viewreport', $context);

$url = new moodle_url('/blocks/maristtela/report.php', ['courseid' => $courseid]);
$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_pagelayout('report');
$PAGE->set_title(get_string('pluginname', 'block_maristtela'));
$PAGE->set_heading(get_string('pluginname', 'block_maristtela'));

$PAGE->navbar->add($course->shortname, new moodle_url('/course/view.php', ['id' => $course->id]));
$PAGE->navbar->add(get_string('pluginname', 'block_maristtela'));

echo $OUTPUT->header();

// ============================================================================
// CÁLCULOS PARA O PAINEL DE INTERAÇÕES
// ============================================================================
$total_interacoes = $DB->count_records('block_maristtela_log');
$total_feedbacks  = $DB->count_records('block_maristtela_feedback');
$total_likes      = $DB->count_records('block_maristtela_feedback', ['feedback' => 'like']);
$total_dislikes   = $DB->count_records('block_maristtela_feedback', ['feedback' => 'dislike']);
$taxa_acerto = $total_feedbacks > 0 ? round(($total_likes / $total_feedbacks) * 100, 1) : 0;

// Determinar horário de pico
$logdata = $DB->get_records('block_maristtela_log');
$hourcount = array_fill(0, 24, 0);
foreach ($logdata as $row) {
    $h = (int) date('G', $row->timecreated);
    $hourcount[$h]++;
}
$horapico = !empty($hourcount) && max($hourcount) > 0
    ? array_keys($hourcount, max($hourcount))[0]
    : 'N/A';
?>

<div class="container-fluid mb-4">
    <h1 class="display-5">📋 Relatório de Interações com a Maristela</h1>
    <p class="lead">Análise das interações dos estudantes com a assistente virtual Maristela.</p>
</div>

<ul class="nav nav-tabs" id="reportTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" id="general-tab" data-tab-target="#general" type="button" role="tab">📊 Painel Geral</button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="analysis-tab" data-tab-target="#analysis" type="button" role="tab">🤖 Análise Qualitativa da IA</button>
    </li>
</ul>

<div class="tab-content" id="reportTabsContent">
    <div class="tab-pane fade show active" id="general" role="tabpanel">

        <div class="card mt-4 shadow-sm border-0">
            <div class="card-body bg-light rounded text-center">

                <div class="d-flex justify-content-around flex-wrap gap-4">
                    <div>
                        <h3>Total de Interações</h3>
                         <h3><p class="fs-4 fw-bold text-primary mb-0"><?php echo $total_interacoes; ?></p></h3>
                    </div>
                    <div>
                        <h3>Total de Avaliações</h3>
                        <h3 class="fs-4 fw-bold text-warning mb-2"><?php echo $total_feedbacks; ?> Total</h3>
                        <div class="d-flex justify-content-center gap-4">
                            <span class="text-success fw-bold">👍 <?php echo $total_likes; ?> Positivas</span>
                            <span class="text-danger fw-bold">👎 <?php echo $total_dislikes; ?> Negativas</span>
                        </div>
                    </div>
                    <div>
                        <h3>Taxa de Acerto da Maristela</h3>
                        <h3> <p class="fs-4 fw-bold <?php 
                            echo $taxa_acerto >= 70 ? 'text-success' : 
                                 ($taxa_acerto >= 40 ? 'text-warning' : 'text-danger'); 
                        ?> mb-0">
                            <?php echo $taxa_acerto; ?>%
                        </p></h3>
                    </div>
                </div>
                <div class="mt-4">
                    <a href="?courseid=<?php echo $courseid; ?>&download=interacoes" 
                       class="btn btn-outline-primary btn-sm">
                       ⬇️ Baixar Dados das Interações (CSV)
                    </a>
                </div>
            </div>
        </div>

        <?php include_once('tabs/general.php'); ?>
    </div>

    <div class="tab-pane fade" id="analysis" role="tabpanel">
        <?php include_once('tabs/analysis.php'); ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const tabs = document.querySelectorAll('[data-tab-target]');
    const tabContents = document.querySelectorAll('.tab-pane');
    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            const target = document.querySelector(tab.dataset.tabTarget);
            tabContents.forEach(c => c.classList.remove('active', 'show'));
            tabs.forEach(t => t.classList.remove('active'));
            tab.classList.add('active');
            target.classList.add('active', 'show');
        });
    });
});
</script>

<?php echo $OUTPUT->footer(); ?>