<?php

namespace block_maristtela\completion;

use block_maristtela\completion;
defined('MOODLE_INTERNAL') || die;

class assistant extends \block_maristtela\completion {

    private $assistant_id;

    public function __construct($model, $message, $history, $block_settings, $thread_id = null) {
        parent::__construct($model, $message, $history, $block_settings);
        $this->assistant_id = $this->assistant; // O ID do assistente selecionado nas configurações.
    }

    /**
     * Orquestra a interação com a API de Assistentes.
     * @param \context $context
     * @return array
     * @throws \Exception
     */
    public function create_completion($context) {
        if (empty($this->assistant_id)) {
            throw new \Exception("Nenhum ID de assistente da OpenAI foi configurado.");
        }

        // Passo 1: Obter ou criar um Tópico (Thread).
        $thread_id = $this->get_or_create_thread();

        // Passo 2: Adicionar a mensagem do utilizador ao Tópico.
        $this->add_message_to_thread($thread_id, $this->message);

        // Passo 3: Executar o Assistente no Tópico.
        $run_id = $this->create_run($thread_id);

        // Passo 4: Aguardar a conclusão da execução.
        $this->poll_run_status($thread_id, $run_id);

        // Passo 5: Obter a última mensagem do assistente.
        $latest_message = $this->get_latest_assistant_message($thread_id);

        return [
            'id' => $latest_message->id ?? 'no-id',
            'message' => $latest_message->content[0]->text->value ?? 'Não foi possível obter uma resposta.',
            'thread_id' => $thread_id
        ];
    }

    /**
     * Executa uma chamada cURL para a API de Assistentes com timeout otimizado.
     * @param string $url
     * @param string $method
     * @param array|null $data
     * @return mixed
     * @throws \Exception
     */
    private function call_api(string $url, string $method = 'GET', ?array $data = null) {
        $curl = new \curl();
        $headers = [
            'Authorization: Bearer ' . $this->apikey,
            'Content-Type: application/json',
            'OpenAI-Beta: assistants=v2'
        ];

        // --- SOLUÇÃO DE TIMEOUT ---
        // Aumenta o tempo limite da ligação para 60 segundos.
        // Isto dá à API de Assistentes tempo suficiente para processar e responder.
        $curl_options = [
            'CURLOPT_HTTPHEADER' => $headers,
            'CURLOPT_CONNECTTIMEOUT' => 10, // Tempo para estabelecer a ligação.
            'CURLOPT_TIMEOUT' => 60,        // Tempo total máximo para a operação.
        ];
        
        $curl->setopt($curl_options);

        $response_body = null;
        if ($method === 'POST') {
            $response_body = $curl->post($url, json_encode($data));
        } else {
            $response_body = $curl->get($url);
        }

        if ($curl->error) {
            throw new \Exception("Erro de cURL (Timeout ou outro): " . $curl->error);
        }

        $response = json_decode($response_body);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception("Resposta inválida da API (não é um JSON válido). Resposta: " . substr($response_body, 0, 500));
        }

        if (isset($response->error)) {
            throw new \Exception("Erro da API OpenAI: " . $response->error->message);
        }

        return $response;
    }

    private function get_or_create_thread(): string {
        if (!empty($this->threadid)) {
            return $this->threadid;
        }
        $response = $this->call_api("https://api.openai.com/v1/threads", 'POST', []);
        return $response->id;
    }

    private function add_message_to_thread(string $thread_id, string $message): void {
        $data = ['role' => 'user', 'content' => $message];
        $this->call_api("https://api.openai.com/v1/threads/{$thread_id}/messages", 'POST', $data);
    }

    private function create_run(string $thread_id): string {
        $data = ['assistant_id' => $this->assistant_id];
        $response = $this->call_api("https://api.openai.com/v1/threads/{$thread_id}/runs", 'POST', $data);
        return $response->id;
    }

    private function poll_run_status(string $thread_id, string $run_id): void {
        $start_time = time();
        $timeout = 45; // Timeout da lógica de polling (ligeiramente menor que o timeout do cURL).

        while (time() - $start_time < $timeout) {
            $run = $this->call_api("https://api.openai.com/v1/threads/{$thread_id}/runs/{$run_id}");
            if (in_array($run->status, ['completed', 'failed', 'cancelled', 'expired'])) {
                if ($run->status !== 'completed') {
                    $error_message = "A execução do assistente falhou com o estado: " . $run->status;
                    if (!empty($run->last_error->message)) {
                        $error_message .= " - " . $run->last_error->message;
                    }
                    throw new \Exception($error_message);
                }
                return;
            }
            sleep(1); 
        }
        throw new \Exception("Timeout ao aguardar a resposta do assistente (Polling).");
    }

    private function get_latest_assistant_message(string $thread_id) {
        $messages_response = $this->call_api("https://api.openai.com/v1/threads/{$thread_id}/messages?limit=1&order=desc");
        
        if (empty($messages_response->data)) {
            throw new \Exception("Nenhuma mensagem retornada pelo assistente.");
        }
        return $messages_response->data[0];
    }
}