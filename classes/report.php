<?php
namespace block_maristtela;

defined('MOODLE_INTERNAL') || die();

use context;
use html_writer;
use moodle_url;
use core\dataformat;

class report extends \table_sql {

    public $badgelevel = 0;

    public function __construct($uniqueid) {
        parent::__construct($uniqueid);

        $this->define_columns([
            'userid',
            'user_name',
            'interactioncount',
            'usermessage',
            'airesponse',
            'contextid',
            'timecreated'
        ]);

        $this->define_headers([
            'ID do Usuário',
            'Nome do Usuário',
            'Nível de Engajamento',
            'Mensagem do Usuário',
            'Resposta da IA',
            'Contexto',
            'Hora'
        ]);

        $this->no_sorting('usermessage');
        $this->no_sorting('airesponse');
        $this->no_sorting('interactioncount');

        $this->sortable(true, 'timecreated', SORT_ASC);

        $this->is_downloadable(true);
        $this->show_download_buttons_at([TABLE_P_TOP, TABLE_P_BOTTOM]);
    }

    /**
     * Constrói a consulta SQL filtrada com base nos parâmetros da URL.
     * Esta função é a única fonte de verdade para a consulta da tabela.
     */
    public function setup_filtered_sql() {
        global $DB;

        // Captura de todos os parâmetros da URL.
        $courseid = required_param('courseid', PARAM_INT);
        $user = optional_param('user', '', PARAM_TEXT);
        $starttime = optional_param('starttime', '', PARAM_TEXT);
        $endtime = optional_param('endtime', '', PARAM_TEXT);
        $this->badgelevel = optional_param('badgelevel', 0, PARAM_INT); // Armazena na propriedade da classe

        // Preparação da consulta SQL com base nos filtros.
        $where_table = "1=1"; 
        $params_table = [];
        $starttime_ts = strtotime($starttime); 
        $endtime_ts = strtotime($endtime);

        if ($courseid > 1) { 
            $where_table .= " AND c.instanceid = :courseid AND c.contextlevel = :contextlevel"; 
            $params_table['courseid'] = $courseid; 
            $params_table['contextlevel'] = CONTEXT_COURSE; 
        }
        if ($user) { 
            $where_table .= " AND CONCAT(u.firstname, ' ', u.lastname) LIKE :user"; 
            $params_table['user'] = "%$user%"; 
        }
        if ($starttime_ts) { 
            $where_table .= " AND ocl.timecreated >= :starttime"; 
            $params_table['starttime'] = $starttime_ts; 
        }
        if ($endtime_ts) { 
            $where_table .= " AND ocl.timecreated <= :endtime"; 
            $params_table['endtime'] = $endtime_ts; 
        }

        if ($this->badgelevel > 0) {
            $usercounts = $DB->get_records_sql("SELECT userid, COUNT(id) AS total FROM {block_maristtela_log} GROUP BY userid");
            $filtered_userids = [];
            foreach ($usercounts as $userid => $data) {
                $count = $data->total;
                $matches = false;
                switch ($this->badgelevel) {
                    case 1: if ($count >= 1 && $count <= 5) $matches = true; break;
                    case 2: if ($count >= 6 && $count <= 15) $matches = true; break;
                    case 3: if ($count >= 16 && $count <= 30) $matches = true; break;
                    case 4: if ($count >= 31 && $count <= 50) $matches = true; break;
                    case 5: if ($count > 50) $matches = true; break;
                }
                if ($matches) {
                    $filtered_userids[] = $userid;
                }
            }
            if (empty($filtered_userids)) {
                $where_table .= " AND 1=0";
            } else {
                list($insql, $inparams) = $DB->get_in_or_equal($filtered_userids);
                $where_table .= " AND ocl.userid $insql";
                $params_table = array_merge($params_table, $inparams);
            }
        }

        $sql_fields = "ocl.*, CONCAT(u.firstname, ' ', u.lastname) AS user_name, COALESCE(counts.interactioncount, 0) AS interactioncount";
        $sql_from = "{block_maristtela_log} ocl 
                     JOIN {user} u ON u.id = ocl.userid 
                     JOIN {context} c ON c.id = ocl.contextid
                     LEFT JOIN (
                         SELECT userid, COUNT(id) AS interactioncount
                         FROM {block_maristtela_log}
                         GROUP BY userid
                     ) AS counts ON counts.userid = ocl.userid";

        $this->set_sql($sql_fields, $sql_from, $where_table, $params_table);
        $this->set_count_sql("SELECT COUNT(DISTINCT ocl.id) FROM $sql_from WHERE $where_table", $params_table);
    }

    public function download() {
        if (!$this->is_downloading()) {
            return;
        }

        // A SOLUÇÃO DEFINITIVA:
        // Garante que a consulta seja configurada DENTRO do processo de download.
        // Isso torna o download autossuficiente e imune a problemas de estado da página.
        $this->setup_filtered_sql();

        $this->query_db(0, false);

        $data = [];
        foreach ($this->rawdata as $row) {
            $data[] = [
                $row->userid,
                $this->col_user_name($row),
                $this->col_interactioncount($row),
                $row->usermessage,
                $row->airesponse,
                $this->col_contextid($row),
                $this->col_timecreated($row)
            ];
        }

        $filename = 'relatorio_maristtela_' . userdate(time(), 'Ymd_Hi');

        dataformat::download_data(
            $filename,
            $this->downloaddataformat,
            $this->headers,
            $data
        );

        exit;
    }

    public function col_user_name($values) {
        global $DB;
        $user = $DB->get_record('user', ['id' => $values->userid], '*', MUST_EXIST);
        $fullname = fullname($user);

        if ($this->is_downloading()) {
            return $fullname;
        }

        $url = new moodle_url('/user/profile.php', ['id' => $values->userid]);
        return html_writer::link($url, $fullname);
    }

    public function col_interactioncount($values) {
        $count = (int) $values->interactioncount;

        if ($this->is_downloading()) {
            if ($count >= 1 && $count <= 5) return 'Iniciante Curioso (1-5 interações)';
            if ($count >= 6 && $count <= 15) return 'Explorador Dedicado (6-15 interações)';
            if ($count >= 16 && $count <= 30) return 'Conversador Engajado (16-30 interações)';
            if ($count >= 31 && $count <= 50) return 'Colaborador Assíduo (31-50 interações)';
            if ($count > 50) return 'Embaixador Maristela (51+ interações)';
            return 'Nenhuma interação';
        }

        if ($count >= 1 && $count <= 5) return '<span title="Iniciante Curioso (1-5 interações)">🥉 Iniciante Curioso</span>';
        if ($count >= 6 && $count <= 15) return '<span title="Explorador Dedicado (6-15 interações)">🥈 Explorador Dedicado</span>';
        if ($count >= 16 && $count <= 30) return '<span title="Conversador Engajado (16-30 interações)">🥇 Conversador Engajado</span>';
        if ($count >= 31 && $count <= 50) return '<span title="Colaborador Assíduo (31-50 interações)">💎 Colaborador Assíduo</span>';
        if ($count > 50) return '<span title="Embaixador Maristela (51+ interações)">🏆 Embaixador Maristela</span>';
        return '<span title="Nenhuma interação">-</span>';
    }

    public function col_usermessage($values) {
        if ($this->is_downloading()) {
            return $values->usermessage;
        }
        return html_writer::tag('pre', s($values->usermessage));
    }

    public function col_airesponse($values) {
        if ($this->is_downloading()) {
            return $values->airesponse;
        }
        return html_writer::tag('pre', s($values->airesponse));
    }

    public function col_contextid($values) {
        if ($this->is_downloading()) {
            return $values->contextid;
        }
        $context = context::instance_by_id($values->contextid, IGNORE_MISSING);
        if (!$context) {
            return '-';
        }
        $url = $context->get_url();
        $name = $context->get_context_name();
        return html_writer::link($url, $name);
    }

    public function col_timecreated($values) {
        return userdate($values->timecreated);
    }
}