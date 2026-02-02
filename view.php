<?php
require('../../config.php');
require_once($CFG->dirroot . '/blocks/maristtela/classes/report.php');

require_login();
$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/blocks/maristtela/view.php'));
$PAGE->set_title('Relatório Maristtela com IA');
$PAGE->set_heading('Relatório Maristtela');
$PAGE->set_pagelayout('report');

echo $OUTPUT->header();

echo \html_writer::tag('h2', '📋 Relatório Maristtela com IA');

/// ===============================
/// CAIXA DE PERGUNTA PARA A IA
/// ===============================
echo \html_writer::start_div('ia-dialog', ['style' => 'margin-bottom: 30px; padding: 15px; border: 1px solid #ccc; background: #f9f9f9']);
echo \html_writer::tag('h3', '🤖 Pergunte à IA com base nas mensagens dos estudantes');

$pergunta = optional_param('pergunta_ia', '', PARAM_TEXT);
$resposta = '';

if (!empty($pergunta)) {
    global $DB;
    // Corrigido o nome da tabela de 'block_maristtela_logs' para 'block_maristtela_log'.
    $mensagens = $DB->get_fieldset_select('block_maristtela_log', 'usermessage', "usermessage IS NOT NULL");
    $contexto = implode("\n", array_slice($mensagens, 0, 100)); // Limita a 100 mensagens

    $prompt = "Estas são mensagens de estudantes:\n\n" . $contexto . "\n\nAgora, responda à pergunta: " . $pergunta;

    $report = new \block_maristtela\report('ia_temp');
    $resposta = $report->analisar_com_ia($prompt);
}

// Formulário
echo '<form method="get">';
echo '<label for="pergunta_ia">Sua pergunta:</label><br>';
echo '<textarea name="pergunta_ia" rows="3" cols="80" placeholder="Ex: Quais os principais temas abordados pelos alunos?"></textarea><br><br>';
echo '<input type="submit" value="Perguntar à IA">';
echo '</form>';

// Exibe resposta se houver
if (!empty($resposta)) {
    echo \html_writer::tag('h4', 'Resposta da IA:');
    echo \html_writer::tag('div', s($resposta), ['style' => 'border:1px solid #ccc; padding:10px; background:#fff; margin-top:10px']);
}

echo \html_writer::end_div();


/// ===============================
/// RESUMOS ESTATÍSTICOS
/// ===============================
echo \block_maristtela\report::gerar_resumos_estatisticos();


/// ===============================
/// TABELA PRINCIPAL
/// ===============================
$table = new \block_maristtela\report('relatoriomaristtela');
$table->setup_sql();
$table->out(50, true); // exibe tabela + botões de exportação


/// ===============================
/// ANÁLISE INDIVIDUAL POR MENSAGEM
/// ===============================
echo \block_maristtela\report::gerar_analise_ia_html($table->rawdata, $table);

echo $OUTPUT->footer();