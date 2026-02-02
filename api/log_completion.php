<?php
require_once('../../../config.php');
require_once($CFG->dirroot . '/blocks/maristtela/lib.php');

header('Content-Type: application/json');

try {
    global $DB;

    // Apenas utilizadores autenticados podem registar logs.
    require_login();
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new \Exception('Método não permitido.', 405);
    }

    $body = json_decode(file_get_contents('php://input'), true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new \Exception("Corpo do pedido de log inválido.");
    }
    
    // Extrai e limpa os dados do corpo do pedido.
    $usermessage = clean_param($body['usermessage'] ?? '', PARAM_TEXT);
    $airesponse  = clean_param($body['airesponse']  ?? '', PARAM_TEXT);
    $block_id    = clean_param($body['blockId']     ?? 0, PARAM_INT);
    $threadid    = clean_param($body['threadId']    ?? null, PARAM_ALPHANUMEXT);
    $history     = clean_param_array($body['history'] ?? [], PARAM_RAW);

    if (empty($block_id) || empty($usermessage)) {
        throw new \Exception("Dados insuficientes para o registo de log.");
    }

    // Obtém o contexto a partir do ID do bloco.
    $instance_record = $DB->get_record('block_instances', ['id' => $block_id, 'blockname' => 'maristtela'], 'id, parentcontextid');
    if (!$instance_record) {
        throw new \Exception("Instância do bloco não encontrada para o log.");
    }
    $context = \context::instance_by_id($instance_record->parentcontextid);

    // Chama a função de log centralizada.
    log_message($usermessage, $airesponse, $context, $threadid, json_encode($history));

    echo json_encode(['status' => 'success']);

} catch (\Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}