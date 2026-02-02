<?php
// Análise das Respostas da IA com feedback visual sólido
// Plugin Maristtela - Moodle

require_once('../../config.php');
require_login();

global $DB, $PAGE, $OUTPUT, $USER; // Adiciona $USER ao escopo global

$courseid = required_param('courseid', PARAM_INT);
$context = context_course::instance($courseid);
$PAGE->set_context($context);
$PAGE->set_url('/blocks/maristtela/analysis.php', ['courseid' => $courseid]);
$PAGE->set_title('Análise das Respostas da IA - Maristtela');
$PAGE->set_heading('Análise das Respostas da IA');

// ======================
// 1. Carregar logs
// ======================
$logs = $DB->get_records('block_maristtela_log', [], 'timecreated DESC');
$log_count = count($logs);

// ======================
// 2. Função de avaliação automática
// ======================
function maristtela_auto_analysis($response) {
    $length = mb_strlen(strip_tags($response));
    $keywords = ['ajuda', 'erro', 'código', 'Moodle', 'atividade', 'exemplo', 'explicação'];
    $hits = 0;
    foreach ($keywords as $kw) {
        if (stripos($response, $kw) !== false) $hits++;
    }

    $score = min(100, ($length / 200 * 50) + ($hits * 10));
    $rating = $score >= 80 ? 'Excelente' : ($score >= 50 ? 'Boa' : 'Precisa de melhorias');
    $color = $score >= 80 ? 'success' : ($score >= 50 ? 'warning' : 'danger');
    return ['score' => round($score), 'rating' => $rating, 'color' => $color];
}

// ======================
// 3. Criar tabela de feedback (se não existir)
// ======================
if (!$DB->get_manager()->table_exists('block_maristtela_feedback')) {
    $table = new xmldb_table('block_maristtela_feedback');
    $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
    $table->add_field('logid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
    $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL); // CORREÇÃO: Adiciona o ID do usuário
    $table->add_field('feedback', XMLDB_TYPE_CHAR, '10', null, XMLDB_NOTNULL);
    $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
    $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
    $table->add_index('logid_idx', XMLDB_INDEX_NOTUNIQUE, ['logid']);
    $DB->get_manager()->create_table($table);
}
// CORREÇÃO DE MIGRAÇÃO: Se a tabela existir, mas não tiver a coluna userid, adicione-a.
else if (!$DB->get_manager()->field_exists('block_maristtela_feedback', 'userid')) {
    $table = new xmldb_table('block_maristtela_feedback');
    $field = new xmldb_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'logid');
    $DB->get_manager()->add_field($table, $field);
}

// ======================
// 4. Registrar feedback via AJAX
// ======================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && optional_param('action', '', PARAM_TEXT) === 'feedback') {
    $logid = required_param('logid', PARAM_INT);
    $feedback = required_param('feedback', PARAM_ALPHA);

    // CORREÇÃO: Filtra por usuário
    $DB->delete_records('block_maristtela_feedback', ['logid' => $logid, 'userid' => $USER->id]);
    if (!empty($feedback)) {
        $DB->insert_record('block_maristtela_feedback', [
            'logid' => $logid,
            'userid' => $USER->id, // CORREÇÃO: Adiciona o ID do usuário
            'feedback' => $feedback,
            'timecreated' => time()
        ]);
    }
    echo 'OK';
    exit;
}
?>

<style>
.analysis-container {
  font-family: "Segoe UI", Roboto, sans-serif;
}
.log-card {
  background: #fff;
  border: 1px solid #dee2e6;
  border-radius: 0.5rem;
  box-shadow: 0 2px 6px rgba(0,0,0,0.05);
  margin-bottom: 1.5rem;
  overflow: hidden;
  transition: transform 0.2s ease;
}
.log-card:hover { transform: translateY(-3px); }
.log-card-header {
  background: #f8f9fa;
  padding: 0.75rem 1.25rem;
  display: flex; justify-content: space-between; align-items: center;
  border-bottom: 1px solid #dee2e6;
}
.log-card-header h5 {
  color: #00703C;
  margin: 0; font-weight: 600;
}
.log-card-body {
  padding: 1.25rem;
  display: flex; flex-direction: column; gap: 1rem;
}
.message-bubble {
  padding: 0.8rem 1rem;
  border-radius: 0.75rem;
  line-height: 1.4;
}
.message-bubble.user {
  background: #e9f7ef;
  align-self: flex-start;
}
.message-bubble.bot {
  background: #f1f3f4;
  align-self: flex-end;
  text-align: right;
}
.log-card-footer {
  background: #f8f9fa;
  padding: 0.75rem 1rem;
  border-top: 1px solid #dee2e6;
  text-align: right;
}
.analyze-btn {
  background: #00703C;
  color: #fff;
  border: none;
  border-radius: 0.25rem;
  padding: 0.4rem 0.8rem;
  transition: background 0.2s ease;
}
.analyze-btn:hover { background: #005a2e; }
.analysis-result {
  display: none;
  margin-top: 1rem;
  padding: 1rem;
  background: #fff;
  border-left: 4px solid #00703C;
  border-radius: 0.5rem;
}
.feedback-buttons {
  margin-top: 0.8rem;
  display: flex;
  justify-content: flex-end;
  gap: 15px;
}
.feedback-btn {
  border: none;
  background: #e9ecef;
  cursor: pointer;
  font-size: 1.8rem;
  border-radius: 50%;
  width: 48px;
  height: 48px;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.25s ease-in-out;
  color: #6c757d;
}
.feedback-btn:hover {
  transform: scale(1.15);
}
.feedback-btn.active.like {
  background: #00703C;
  color: #fff;
  transform: scale(1.2);
}
.feedback-btn.active.dislike {
  background: #dc3545;
  color: #fff;
  transform: scale(1.2);
}
</style>

<div class="container-fluid mt-4 analysis-container">
  <div class="card shadow-sm">
    <div class="card-header bg-light">
      <h4>📊 Análise das Respostas da IA</h4>
    </div>
    <div class="card-body">
      <p><strong>Total de Registros:</strong> 
         <span class="badge bg-success rounded-pill"><?php echo $log_count; ?></span>
      </p>

      <?php if ($log_count > 0): ?>
        <?php foreach ($logs as $log): ?>
          <?php
            $analysis = maristtela_auto_analysis($log->airesponse);
            // CORREÇÃO: Carrega o feedback do usuário atual
            $feedback = $DB->get_record('block_maristtela_feedback', ['logid' => $log->id, 'userid' => $USER->id]);
            $activeLike = ($feedback && $feedback->feedback === 'like') ? 'active' : '';
            $activeDislike = ($feedback && $feedback->feedback === 'dislike') ? 'active' : '';
          ?>
          <div class="log-card" data-log-id="<?php echo $log->id; ?>">
            <div class="log-card-header">
              <h5>Interação #<?php echo $log->id; ?></h5>
              <small><?php echo userdate($log->timecreated, '%d/%m/%Y %H:%M'); ?></small>
            </div>
            <div class="log-card-body">
              <div class="message-bubble user">
                <strong>Estudante:</strong><br><?php echo format_text($log->usermessage, FORMAT_HTML); ?>
              </div>
              <div class="message-bubble bot">
                <strong>Maristela:</strong><br><?php echo format_text($log->airesponse, FORMAT_HTML); ?>
              </div>
            </div>
            <div class="log-card-footer">
              <button class="btn btn-success btn-sm analyze-btn">Analisar Interação</button>
              <div class="analysis-result">
                <p><strong>Avaliação automática da resposta da IA:</strong> 
                  <span class="badge bg-<?php echo $analysis['color']; ?>">
                    <?php echo $analysis['rating']; ?>
                  </span>
                  <span class="text-muted">(gerada automaticamente)</span>
                </p>
                <p class="text-muted small mb-1">Como você avalia esta resposta?</p>
                <div class="feedback-buttons">
                  <button class="feedback-btn like <?php echo $activeLike; ?>" title="Acertiva">👍</button>
                  <button class="feedback-btn dislike <?php echo $activeDislike; ?>" title="Não acertiva">👎</button>
                </div>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <div class="alert alert-info mt-3">
          Nenhum registro encontrado na base de dados.
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
  // Mostrar/ocultar análise
  document.querySelectorAll('.analyze-btn').forEach(btn => {
    btn.addEventListener('click', function() {
      const result = this.closest('.log-card').querySelector('.analysis-result');
      const visible = result.style.display === 'block';
      result.style.display = visible ? 'none' : 'block';
      this.textContent = visible ? 'Analisar Interação' : 'Ocultar Análise';
    });
  });

  // Feedback visual sólido
  document.querySelectorAll('.feedback-btn').forEach(btn => {
    btn.addEventListener('click', function() {
      const card = this.closest('.log-card');
      const logid = card.dataset.logId;
      const type = this.classList.contains('like') ? 'like' : 'dislike';

      // Toggle ativo/inativo
      const alreadyActive = this.classList.contains('active');
      card.querySelectorAll('.feedback-btn').forEach(b => b.classList.remove('active'));
      if (!alreadyActive) this.classList.add('active');

      // Registrar feedback no backend
      fetch(window.location.href, {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: new URLSearchParams({
          courseid: '<?php echo $courseid; ?>',
          action: 'feedback',
          logid: logid,
          feedback: alreadyActive ? '' : type
        })
      }).catch(e => console.error(e));
    });
  });
});
</script>