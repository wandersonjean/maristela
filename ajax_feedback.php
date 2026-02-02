<?php
require_once('../../config.php');
require_login();
global $DB, $USER;

// Cabeçalho JSON
header('Content-Type: application/json; charset=utf-8');

// Parâmetros recebidos via AJAX
$logid = required_param('logid', PARAM_INT);
$type = required_param('type', PARAM_ALPHA); // 'like' ou 'dislike'

// Cria tabela se ainda não existir
if (!$DB->get_manager()->table_exists('block_maristtela_feedback')) {
    $table = new xmldb_table('block_maristtela_feedback');
    $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
    $table->add_field('logid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
    $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
    $table->add_field('feedback', XMLDB_TYPE_CHAR, '10', null, XMLDB_NOTNULL);
    $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
    $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
    $DB->get_manager()->create_table($table);
}

// Remove feedback anterior do mesmo usuário para a mesma interação
$DB->delete_records('block_maristtela_feedback', ['logid' => $logid, 'userid' => $USER->id]);

// Insere novo registro
$DB->insert_record('block_maristtela_feedback', [
    'logid' => $logid,
    'userid' => $USER->id,
    'feedback' => $type,
    'timecreated' => time()
]);

echo json_encode(['success' => true]);
exit;
