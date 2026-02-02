<?php

namespace block_maristtela\completion;

use block_maristtela\completion;
defined('MOODLE_INTERNAL') || die;

class chat extends \block_maristtela\completion {

    public function __construct($model, $message, $history, $block_settings, $thread_id = null) {
        parent::__construct($model, $message, $history, $block_settings);
    }

    public function create_completion($context) {
        $this->prompt .= "\n\n";

        // Obtém o histórico validado.
        $history_json = $this->format_history();
        
        // Adiciona o prompt do sistema e a mensagem atual do utilizador.
        array_unshift($history_json, ["role" => "system", "content" => $this->prompt]);
        array_push($history_json, ["role" => "user", "content" => $this->message]);

        try {
            $response_data = $this->make_api_call($history_json);
        } catch (\Exception $e) {
            return [
                "id" => 'error',
                "message" => 'Erro na API: ' . $e->getMessage()
            ];
        }
        return $response_data;
    }

    /**
     * Formata e valida rigorosamente o histórico da conversa.
     * Esta função é agora à prova de falhas e descarta quaisquer entradas malformadas.
     * @return array
     */
    protected function format_history() {
        $formatted_history = [];
        if (empty($this->history) || !is_array($this->history)) {
            return [];
        }

        foreach ($this->history as $message) {
            // VERIFICAÇÃO RIGOROSA: Garante que cada mensagem no histórico é um array
            // e contém as chaves 'role' e 'content' com valores não vazios.
            if (
                is_array($message) &&
                !empty($message['role']) &&
                isset($message['content']) // O conteúdo pode ser uma string vazia.
            ) {
                // Adiciona apenas as mensagens válidas ao histórico formatado.
                $formatted_history[] = [
                    'role'    => $message['role'],
                    'content' => $message['content']
                ];
            }
        }
        return $formatted_history;
    }

    /**
     * @throws \Exception
     */
    private function make_api_call($history) {
        $curlbody = [
            "model" => $this->model,
            "messages" => $history,
            "max_tokens" => (int) $this->maxlength,
            "stop" => $this->username ? $this->username . ":" : null
        ];

        // Remove a chave 'stop' se estiver vazia.
        if (is_null($curlbody['stop'])) {
            unset($curlbody['stop']);
        }

        $curl = new \curl();
        $curl->setopt(array(
            'CURLOPT_HTTPHEADER' => array(
                'Authorization: Bearer ' . $this->apikey,
                'Content-Type: application/json'
            ),
            'CURLOPT_RETURNTRANSFER' => true,
            'CURLOPT_FAILONERROR' => false,
        ));

        $response_body = $curl->post("https://api.openai.com/v1/chat/completions", json_encode($curlbody));
        $curl_error = $curl->error;

        if ($curl_error) {
            throw new \Exception("Erro de comunicação cURL: " . $curl_error);
        }

        $response = json_decode($response_body);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception("Resposta inválida da API (não é um JSON válido).");
        }

        if (property_exists($response, 'error') && $response->error) {
            throw new \Exception($response->error->message);
        }

        if (!property_exists($response, 'choices') || !is_array($response->choices) || empty($response->choices[0]->message->content)) {
            throw new \Exception("Resposta da API com formato inesperado.");
        }

        $message = $response->choices[0]->message->content;

        return [
            "id" => property_exists($response, 'id') ? $response->id : 'no-id',
            "message" => $message
        ];
    }
}