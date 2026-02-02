<?php

defined('MOODLE_INTERNAL') || die();

/**
 * Retorna o tipo de interface configurado (chat/assistant).
 */
function get_type_to_display() {
    $stored_type = get_config('block_maristtela', 'type');
    return !empty($stored_type) ? $stored_type : 'chat';
}

/**
 * Busca assistentes configurados (id => nome).
 */
function fetch_assistants_array($block_id = null) {
    global $DB;

    $assistants = [];
    if ($DB->get_manager()->table_exists('block_maristtela_assistants')) {
        $records = $DB->get_records('block_maristtela_assistants', [], 'name ASC', 'id, name');
        foreach ($records as $r) {
            $assistants[$r->id] = $r->name;
        }
    }
    return $assistants;
}

/**
 * Registra a interação no banco.
 */
function log_message($usermessage, $airesponse, $context, $threadid = null, $history = null) {
    global $USER, $DB;

    // Mesmo que logging esteja desligado, vamos salvar para debug.
    // Remova esta linha se quiser respeitar a config:
    // $logging = get_config('block_maristtela', 'logging');
    // if (empty($logging)) { return; }

    if (!$context || !isset($context->id)) {
        $context = context_system::instance();
    }

    $record = (object)[
        'userid'      => (int)($USER->id ?? 0),
        'usermessage' => mb_substr((string)$usermessage, 0, 65000), // evita estouro
        'airesponse'  => mb_substr((string)$airesponse, 0, 65000),  // idem
        'contextid'   => (int)$context->id,
        'timecreated' => time()
    ];

    // Adiciona colunas opcionais apenas se existirem.
    try {
        $columns = $DB->get_columns('block_maristtela_log');
        if (!empty($threadid) && isset($columns['threadid'])) {
            $record->threadid = (string)$threadid;
        }
        if (!empty($history) && isset($columns['history'])) {
            $record->history = (string)$history;
        }
    } catch (\Throwable $e) {
        // ignora erros ao checar colunas
    }

    try {
        $DB->insert_record('block_maristtela_log', $record, false);
    } catch (\Throwable $e) {
        debugging("Erro ao salvar log_message: " . $e->getMessage(), DEBUG_DEVELOPER);
    }
}


/**
 * Retorna a lista de modelos disponíveis para o seletor da configuração.
 * Se houver API key configurada, tenta buscar de forma resiliente; caso contrário,
 * usa um conjunto estável de modelos conhecidos.
 * @return array ['models' => ['id'=>'rótulo', ...]]
 */
function get_models(): array {
    global $CFG;
    $fallback = [
        'gpt-4o-mini' => 'gpt-4o-mini',
        'gpt-4o' => 'gpt-4o',
        'gpt-4.1-mini' => 'gpt-4.1-mini',
        'gpt-3.5-turbo' => 'gpt-3.5-turbo',
        'text-davinci-003' => 'text-davinci-003'
    ];
    $apikey = (string) get_config('block_maristtela', 'apikey');
    if (empty($apikey)) {
        return ['models' => $fallback];
    }
    try {
        require_once($CFG->libdir . '/filelib.php');
        $curl = new curl();
        $curl->setHeader([
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apikey,
            'OpenAI-Beta: assistants=v2'
        ]);
        $resp = $curl->get('https://api.openai.com/v1/models');
        $code = $curl->get_info()['http_code'] ?? 0;
        if ($code >= 200 && $code < 300 && $resp) {
            $data = json_decode($resp, true);
            if (isset($data['data']) && is_array($data['data'])) {
                $options = [];
                foreach ($data['data'] as $m) {
                    if (!empty($m['id'])) {
                        $options[$m['id']] = $m['id'];
                    }
                }
                if (!empty($options)) {
                    return ['models' => $options];
                }
            }
        }
    } catch (\Throwable $e) {
        // silencia e usa fallback
    }
    return ['models' => $fallback];
}


/**
 * Retorna a lista de modelos disponíveis para o seletor da configuração.
 * Se houver API key configurada, tenta buscar de forma resiliente; caso contrário,
 * usa um conjunto estável de modelos conhecidos.
 * @return array ['models' => ['id'=>'rótulo', ...]]
 */
if (!function_exists('get_models')) {
    function get_models(): array {
        global $CFG;
        $fallback = [
            'gpt-4o-mini' => 'gpt-4o-mini',
            'gpt-4o' => 'gpt-4o',
            'gpt-4.1-mini' => 'gpt-4.1-mini',
            'gpt-3.5-turbo' => 'gpt-3.5-turbo',
            'text-davinci-003' => 'text-davinci-003'
        ];
        $apikey = (string) get_config('block_maristtela', 'apikey');
        if (empty($apikey)) {
            return ['models' => $fallback];
        }
        try {
            require_once($CFG->libdir . '/filelib.php');
            $curl = new curl();
            $curl->setHeader([
                'Content-Type: application/json',
                'Authorization: Bearer ' . $apikey,
                'OpenAI-Beta: assistants=v2'
            ]);
            $resp = $curl->get('https://api.openai.com/v1/models');
            $code = $curl->get_info()['http_code'] ?? 0;
            if ($code >= 200 && $code < 300 && $resp) {
                $data = json_decode($resp, true);
                if (isset($data['data']) && is_array($data['data'])) {
                    $options = [];
                    foreach ($data['data'] as $m) {
                        if (!empty($m['id'])) {
                            $options[$m['id']] = $m['id'];
                        }
                    }
                    if (!empty($options)) {
                        return ['models' => $options];
                    }
                }
            }
        } catch (\Throwable $e) {
            // silencia e usa fallback
        }
        return ['models' => $fallback];
    }
}

