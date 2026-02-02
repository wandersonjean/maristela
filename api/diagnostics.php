<?php
define('AJAX_SCRIPT', true);
require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/filelib.php');

header('Content-Type: application/json; charset=utf-8');

$result = [
    'ok' => true,
    'env' => [
        'user_logged' => isloggedin() && !isguestuser(),
        'userid' => isset($USER->id) ? $USER->id : null,
        'username' => isset($USER->username) ? $USER->username : null,
        'site' => isset($CFG->wwwroot) ? $CFG->wwwroot : null,
    ],
    'assistant' => [],
    'context' => [],
    'errors' => []
];

try {
    $type = (string) get_config('block_maristtela', 'type');
    $apikey = (string) get_config('block_maristtela', 'apikey');
    $assistant = (string) get_config('block_maristtela', 'assistant');
    $result['assistant'] = [
        'type' => $type,
        'has_apikey' => !empty($apikey),
        'assistant_id' => $assistant ? (substr($assistant,0,5).'...') : null
    ];

    if (!empty($apikey)) {
        $curl = new curl();
        $curl->setHeader([
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apikey,
            'OpenAI-Beta: assistants=v2'
        ]);
        $resp = $curl->get('https://api.openai.com/v1/models');
        if ($curl->get_errno()) {
            $result['errors'][] = 'cURL: '.$curl->error;
        } else {
            $info = $curl->get_info();
            $code = isset($info['http_code']) ? $info['http_code'] : 0;
            $result['assistant']['http_code_models'] = $code;
            if ($code >= 400) {
                $result['errors'][] = 'OpenAI /models HTTP '.$code;
            }
        }
    }

} catch (Exception $e) {
    $result['ok'] = false;
    $result['errors'][] = 'assistant-check: '.$e->getMessage();
}

try {
    $courseid = optional_param('courseid', 0, PARAM_INT);
    if ($courseid) {
        require_login($courseid);
        $context = context_course::instance($courseid);
        require_capability('moodle/course:view', $context);

        require_once($CFG->libdir.'/gradelib.php');
        $modinfo = get_fast_modinfo($courseid, $USER->id);
        $grades = grade_get_course_grades($courseid, $USER->id);

        $result['context'] = [
            'courseid' => $courseid,
            'grades_items' => !empty($grades->items) ? count($grades->items) : 0,
            'cms' => count($modinfo->get_cms())
        ];
    } else {
        $result['context'] = ['courseid'=>0, 'note'=>'Passe ?courseid=ID na URL para testar o contexto do curso.'];
    }

} catch (Exception $e) {
    $result['ok'] = false;
    $result['errors'][] = 'context-check: '.$e->getMessage();
}

echo json_encode($result);
