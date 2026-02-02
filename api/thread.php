<?php

require_once('../../../config.php');
require_once($CFG->libdir . '/filelib.php');
require_once($CFG->dirroot . '/blocks/maristtela/lib.php');

header('Content-Type: application/json');

try {
    if (get_config('block_maristtela', 'restrictusage') !== "0") {
        require_login();
    }

    $thread_id = required_param('thread_id', PARAM_ALPHANUMEXT);
    $apikey = get_config('block_maristtela', 'apikey');
    
    if (empty($apikey)) {
        throw new \Exception("A chave da API principal não está configurada.");
    }

    $curl = new \curl();
    $curl->setopt(array(
        'CURLOPT_HTTPHEADER' => array(
            'Authorization: Bearer ' . $apikey,
            'Content-Type: application/json',
            'OpenAI-Beta: assistants=v2'
        ),
        'CURLOPT_RETURNTRANSFER' => true,
        'CURLOPT_FAILONERROR' => false
    ));

    // Obtém as mensagens por ordem cronológica (as mais antigas primeiro).
    $response_body = $curl->get("https://api.openai.com/v1/threads/$thread_id/messages?order=asc");

    if ($curl->error) {
        throw new \Exception("Erro de cURL ao obter o histórico: " . $curl->error);
    }
    
    $response = json_decode($response_body);

    if (isset($response->error)) {
        throw new \Exception("Erro da API ao obter o histórico: " . $response->error->message);
    }

    $api_response = [];
    if (!empty($response->data)) {
        foreach ($response->data as $message) {
            if (!empty($message->content[0]->text->value)) {
                $api_response[] = [
                    "id" => $message->id,
                    "role" => $message->role,
                    "message" => $message->content[0]->text->value
                ];
            }
        }
    }

    echo json_encode($api_response);

} catch (\Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => ['message' => $e->getMessage()]]);
}