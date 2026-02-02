<?php
namespace block_maristtela\output;

defined('MOODLE_INTERNAL') || die();

use plugin_renderer_base;

class renderer extends plugin_renderer_base {

    /**
     * Renderiza a interface principal do bloco.
     * @param \stdClass $data
     * @return string
     */
    public function render_main(\stdClass $data): string {
        $data->badge = $this->get_user_badge();
        $data->suggestions = $this->get_contextual_suggestions();
        
        return parent::render_from_template('block_maristtela/main_template', $data);
    }
    
    private function get_user_badge(): string {
        global $USER, $DB;
        
        if (isguestuser() || $USER->id == 0) {
            return '';
        }

        $interaction_count = $DB->count_records('block_maristtela_log', ['userid' => $USER->id]);
        $badge_html = '';
        $badge_text = '';

        if ($interaction_count >= 1 && $interaction_count <= 5) {
            $badge_html = '🥉'; $badge_text = 'Iniciante Curioso';
        } else if ($interaction_count >= 6 && $interaction_count <= 15) {
            $badge_html = '🥈'; $badge_text = 'Explorador Dedicado';
        } else if ($interaction_count >= 16 && $interaction_count <= 30) {
            $badge_html = '🥇'; $badge_text = 'Conversador Empenhado';
        } else if ($interaction_count > 30) {
            $badge_html = '🏆'; $badge_text = 'Embaixador Maristela';
        }

        if (!empty($badge_html)) {
            return "<div class='maristtela_badge' title='{$badge_text} ({$interaction_count} interações)'>{$badge_html} {$badge_text}</div>";
        }
        
        return '';
    }

    private function get_contextual_suggestions(): array {
        global $PAGE;
        
        $suggestions = [];
        if ($PAGE->cm) {
            $activity_name = $PAGE->cm->name;
            switch ($PAGE->cm->modname) {
                case 'forum':
                    $suggestions = ["Como posso criar um novo tópico em '{$activity_name}'?", 'Quais são as regras deste fórum?'];
                    break;
                case 'quiz':
                    $suggestions = ["Qual é a data limite para '{$activity_name}'?", 'Quantas tentativas eu tenho?'];
                    break;
                case 'assign':
                    $suggestions = ["Quais são os critérios de avaliação de '{$activity_name}'?", 'Qual o formato de arquivo que devo enviar?'];
                    break;
                default:
                    $suggestions = ["Qual o objetivo principal de '{$activity_name}'?", 'Onde encontro material de apoio?'];
            }
        } else {
            $suggestions = ['Quais são os próximos prazos de entrega?', 'Resuma os tópicos da semana.', 'Onde encontro o plano de ensino?'];
        }
        
        $output = [];
        foreach ($suggestions as $suggestion) {
            $output[] = ['text' => $suggestion];
        }
        return $output;
    }
}