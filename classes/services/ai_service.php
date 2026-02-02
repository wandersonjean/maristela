<?php
namespace block_maristtela\services;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/blocks/maristtela/classes/services/context_service.php');
require_once($CFG->dirroot . '/blocks/maristtela/lib.php');

class ai_service {

    private $contextservice;

    public function __construct() {
        $this->contextservice = new context_service();
    }

    public function get_ai_completion(int $blockid, string $message, array $history, ?string $threadid) {
        global $PAGE, $DB;

        $instance_record = $DB->get_record('block_instances', ['id' => $blockid, 'blockname' => 'maristtela'], '*', MUST_EXIST);
        $instance = block_instance('maristtela', $instance_record);
        $context = \context::instance_by_id($instance_record->parentcontextid);
        $PAGE->set_context($context);

        $context_info = $this->contextservice->get_contexto_formatado($blockid);

        $block_settings = [];
        $setting_names = ['prompt', 'instructions', 'username', 'assistantname', 'apikey', 'model', 'maxlength', 'assistant'];
        foreach ($setting_names as $setting) {
            $block_settings[$setting] = $instance->config->$setting ?? '';
        }

        if (empty($block_settings['apikey'])) $block_settings['apikey'] = get_config('block_maristtela', 'apikey');
        if (empty($block_settings['model'])) $block_settings['model'] = get_config('block_maristtela', 'model');
        if (empty($block_settings['assistant'])) $block_settings['assistant'] = get_config('block_maristtela', 'assistant');

        $base_prompt = !empty($block_settings['prompt']) ? $block_settings['prompt'] : get_string('defaultprompt', 'block_maristtela');
        $block_settings['prompt'] = $context_info . "\n\n" . $base_prompt;

        $api_type = get_config('block_maristtela', 'type') ?: 'chat';
        $engine_class = "\\block_maristtela\\completion\\" . $api_type;

        if (!class_exists($engine_class)) {
            throw new \moodle_exception('apiclassnotfound', 'block_maristtela', $engine_class);
        }

        $completion = new $engine_class($block_settings['model'], $message, $history, $block_settings, $threadid);
        $response = $completion->create_completion($context);

        if (isset($response['id']) && $response['id'] === 'error') {
            throw new \moodle_exception('apierror', 'block_maristtela', $response['message']);
        }

        $response["message"] = format_text($response["message"], FORMAT_MARKDOWN, ['context' => $context, 'trusted' => true]);

        log_message($message, $response['message'], $context, $response['thread_id'] ?? null, json_encode($history));

        return $response;
    }
    
    public function get_proactive_insight(int $blockid): string {
        $context_info = $this->contextservice->get_contexto_detalhado($blockid);
        if (empty($context_info) || empty($context_info['activity'])) {
            return '';
        }
        
        // Exemplo: insight sem IA para performance
        $act = $context_info['activity'];
        $insight_html = '';

        if ($act['type'] === 'assign' && !empty($act['duedate'])) {
            $insight_html = "<strong>Dica da Maristela:</strong> Lembre-se que o prazo para a tarefa '{$act['name']}' é {$act['duedate']}.";
        } else if ($act['type'] === 'quiz' && !empty($act['timeclose'])) {
             $insight_html = "<strong>Atenção:</strong> O questionário '{$act['name']}' encerra em {$act['timeclose']}.";
        }

        if (!empty($insight_html)) {
            return '<div class="maristtela_proactive_insight">' . $insight_html . '</div>';
        }
        return '';
    }
}