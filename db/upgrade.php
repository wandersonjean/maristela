<?php
/**
 * Script de atualização para o bloco Maristtela.
 * @package block_maristtela
 */
defined('MOODLE_INTERNAL') || die();

function xmldb_block_maristtela_upgrade($oldversion) {
    global $DB;
    $dbman = $DB->get_manager();

    // --- PASSO 1: CRIAÇÃO DA TABELA DE LOG (2024081601) ---
    if ($oldversion < 2024081601) {
        $table = new xmldb_table('block_maristtela_log');

        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('usermessage', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
        $table->add_field('airesponse', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('contextid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('userid_fk', XMLDB_KEY_FOREIGN, ['userid'], 'user', ['id']);

        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }
        upgrade_block_savepoint(true, 2024081601, 'maristtela');
    }
    
    // --- PASSO 2: CRIAÇÃO DA TABELA DE FEEDBACK E CAMPO USERID (202511051116) ---
    // Esta etapa consolida a criação da tabela de feedback e a adição do campo 'userid',
    // resolvendo o erro de instalação/downgrade.
    if ($oldversion < 202511051116) { 
        $table = new xmldb_table('block_maristtela_feedback');
        
        // 1. Cria a tabela se não existir
        if (!$dbman->table_exists($table)) {
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
            $table->add_field('logid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
            $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('feedback', XMLDB_TYPE_CHAR, '10', null, XMLDB_NOTNULL);
            $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);

            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $table->add_index('logid_idx', XMLDB_INDEX_NOTUNIQUE, ['logid']);

            $dbman->create_table($table);
        } else {
            // 2. Se a tabela já existia, garante o campo 'userid'
            if (!$dbman->field_exists($table, 'userid')) {
                $field = new xmldb_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'logid');
                $dbman->add_field($table, $field);
            }
        }
        
        upgrade_block_savepoint(true, 202511051116, 'maristtela');
    }

    return true;
}
?>