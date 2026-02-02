<?php

namespace block_maristtela\completion;

use block_maristtela\completion;
defined('MOODLE_INTERNAL') || die;

class azure extends \block_maristtela\completion\chat {

    private $resourcename;
    private $deploymentid;
    private $apiversion;

    public function __construct($model, $message, $history, $block_settings, $thread_id = null) {
        parent::__construct($model, $message, $history, $block_settings);

        $this->resourcename = $this->get_setting('resourcename');
        $this->deploymentid = $this->get_setting('deploymentid');
        $this->apiversion = $this->get_setting('apiversion');
    }


    public function create_completion($context) {
       
        $this->prompt .= "\n\n";

        $history_json = $this->format_history();
        array_unshift($history_json, ["role" => "system", "content" => $this->prompt]);
        array_push($history_json, ["role" => "user", "content" => $this->message]);

        $response_data = $this->make_api_call($history_json);
        return $response_data;
    }

  
    private function make_api_call($history) {

        $curlbody = [
            "model" => $this->model,
            "messages" => $history,
            "max_tokens" => (int) $this->maxlength,
            "stop" => $this->username . ":"
        ];

        $curl = new \curl();
        $curl->setopt(array(
            'CURLOPT_HTTPHEADER' => array(
                'api-key: ' . $this->apikey,
                'Content-Type: application/json'
            ),
        ));

        $response = $curl->post(
            "https://" . $this->resourcename . ".openai.azure.com/openai/deployments/" . $this->deploymentid . "/chat/completions?api-version=" . $this->apiversion, 
            json_encode($curlbody)
        );
        $response = json_decode($response);

        $message = null;
        if (property_exists($response, 'error')) {
            $message = 'ERRO: ' . $response->error->message;
        } else {
            $message = $response->choices[0]->message->content;
        }

        return [
            "id" => property_exists($response, 'id') ? $response->id : 'erro',
            "message" => $message
        ];
    }
}


