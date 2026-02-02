<?php
use \block_maristtela\completion;

require_once('../../../config.php');
require_once($CFG->libdir . '/filelib.php');
require_once($CFG->dirroot . '/blocks/maristtela/lib.php');

// Define o cabeçalho como JSON desde o início para todas as respostas.
header('Content-Type: application/json');

try {
    if (get_config('block_maristtela', 'restrictusage') !== "0") {
        require_login();
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new \Exception('Método não permitido.', 405);
    }
    
    $body = json_decode(file_get_contents('php://input'), true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new \Exception("Corpo do pedido inválido (não é um JSON válido).");
    }

    $message = clean_param($body['message'] ?? '', PARAM_TEXT);
    $history = clean_param_array($body['history'] ?? [], PARAM_RAW, true);
    $block_id = clean_param($body['blockId'] ?? 0, PARAM_INT);
    $thread_id = clean_param($body['threadId'] ?? null, PARAM_NOTAGS, true);
    
    if (empty($block_id)) {
        throw new \Exception("ID do bloco não fornecido.");
    }

    $instance_record = $DB->get_record('block_instances', ['id' => $block_id, 'blockname' => 'maristtela'], '*');
    if (!$instance_record) {
        throw new \Exception("Instância do bloco (#{$block_id}) não encontrada.");
    }

    $instance = block_instance('maristtela', $instance_record);
    if (!$instance) {
        throw new \Exception("Não foi possível instanciar o objeto do bloco.");
    }

    $context = context::instance_by_id($instance_record->parentcontextid);
    $PAGE->set_context($context);

    // Lógica de contexto adicional.
    $context_info = '';
    if ($PAGE->cm) {
        try {
            $module_name = get_string('modulename', $PAGE->cm->modname);
        } catch (Exception $e) { $module_name = $PAGE->cm->modname; }
        $instance_name = $PAGE->cm->name;
        $context_info = "Contexto Adicional: O aluno está a visualizar a atividade '{$instance_name}' do tipo '{$module_name}'.";
    }

    $block_settings = [];
    $setting_names = ['prompt', 'instructions', 'username', 'assistantname', 'apikey', 'model', 'maxlength', 'assistant'];
    foreach ($setting_names as $setting) {
        $block_settings[$setting] = (isset($instance->config) && property_exists($instance->config, $setting)) ? $instance->config->$setting : "";
    }
    
    // Fallback para as configurações globais se as do bloco estiverem vazias
    if(empty($block_settings['apikey'])) $block_settings['apikey'] = get_config('block_maristtela', 'apikey');
    if(empty($block_settings['model'])) $block_settings['model'] = get_config('block_maristtela', 'model');
    if(empty($block_settings['assistant'])) $block_settings['assistant'] = get_config('block_maristtela', 'assistant');

    if (!empty($context_info)) {
        $base_prompt = !empty($block_settings['prompt']) ? $block_settings['prompt'] : get_string('defaultprompt', 'block_maristtela');
        $block_settings['prompt'] = $context_info . "\n\n" . $base_prompt;
    }

    $api_type = get_config('block_maristtela', 'type') ?: 'chat';
    $engine_class = "\\block_maristtela\\completion\\" . $api_type;

    if (!class_exists($engine_class)) {
        throw new \Exception("Classe da API '{$engine_class}' não encontrada.");
    }
    
    // Passa o $thread_id para o construtor.
    $completion = new $engine_class($block_settings['model'], $message, $history, $block_settings, $thread_id);
    $response = $completion->create_completion($PAGE->context);

    if (isset($response['id']) && $response['id'] === 'error') {
        throw new \Exception($response['message']);
    }

    $response["message"] = format_text($response["message"], FORMAT_MARKDOWN, ['context' => $context, 'trusted' => true]);

    log_message($message, $response['message'], $context, $response['thread_id'] ?? null, json_encode($history));

    echo json_encode($response);

} catch (\Exception $e) {
    // Define um código de erro HTTP apropriado (4xx para erros do cliente, 5xx para erros do servidor)
    $code = $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 500;
    http_response_code($code);
    
    echo json_encode([
        'error' => [
            'message' => $e->getMessage(),
            'code' => $code
        ]
    ]);
}