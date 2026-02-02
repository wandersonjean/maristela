<?php
namespace block_maristtela\external;

use external_api;
use external_function_parameters;
use external_multiple_structure;
use external_single_structure;
use external_value;

require_once($CFG->dirroot . '/blocks/maristtela/classes/services/ai_service.php');

class api extends external_api {

    /**
     * Retorna os parâmetros para o método get_completion.
     * @return external_function_parameters
     */
    public static function get_completion_parameters() {
        return new external_function_parameters([
            'blockid' => new external_value(PARAM_INT, 'ID da instância do bloco'),
            'message' => new external_value(PARAM_TEXT, 'Mensagem do usuário'),
            'history' => new external_multiple_structure(
                new external_single_structure([
                    'role' => new external_value(PARAM_ALPHA, 'Role (user or assistant)'),
                    'content' => new external_value(PARAM_RAW, 'Message content'),
                ]),
                'Histórico da conversa',
                VALUE_OPTIONAL
            ),
            'threadid' => new external_value(PARAM_ALPHANUMEXT, 'ID da thread do assistente', VALUE_OPTIONAL)
        ]);
    }

    /**
     * Executa a chamada à IA e retorna a resposta.
     * @param int $blockid
     * @param string $message
     * @param array $history
     * @param string|null $threadid
     * @return array
     * @throws \moodle_exception
     */
    public static function get_completion($blockid, $message, $history = [], $threadid = null) {
        global $PAGE;

        $params = self::validate_parameters(self::get_completion_parameters(), [
            'blockid' => $blockid,
            'message' => $message,
            'history' => $history,
            'threadid' => $threadid
        ]);

        $service = new \block_maristtela\services\ai_service();
        $response = $service->get_ai_completion($params['blockid'], $params['message'], $params['history'], $params['threadid']);
        
        return [
            'message' => $response['message'],
            'thread_id' => $response['thread_id'] ?? null
        ];
    }

    /**
     * Retorna os tipos de retorno para get_completion.
     * @return external_single_structure
     */
    public static function get_completion_returns() {
        return new external_single_structure([
            'message' => new external_value(PARAM_RAW, 'Resposta da IA formatada em HTML'),
            'thread_id' => new external_value(PARAM_ALPHANUMEXT, 'ID da thread (se aplicável)', VALUE_OPTIONAL)
        ]);
    }

    /**
     * Retorna os parâmetros para o método get_proactive_insight.
     * @return external_function_parameters
     */
    public static function get_proactive_insight_parameters() {
        return new external_function_parameters([
            'blockid' => new external_value(PARAM_INT, 'ID da instância do bloco')
        ]);
    }

    /**
     * Analisa o contexto e retorna uma dica proativa.
     * @param int $blockid
     * @return array
     */
    public static function get_proactive_insight($blockid) {
        $params = self::validate_parameters(self::get_proactive_insight_parameters(), ['blockid' => $blockid]);
        
        $service = new \block_maristtela\services\ai_service();
        $insight = $service->get_proactive_insight($params['blockid']);

        return ['insight' => $insight];
    }

    /**
     * Retorna os tipos de retorno para get_proactive_insight.
     * @return external_single_structure
     */
    public static function get_proactive_insight_returns() {
        return new external_single_structure([
            'insight' => new external_value(PARAM_RAW, 'Dica proativa da IA, pode ser HTML ou vazio.', VALUE_OPTIONAL)
        ]);
    }
}