<?php
require_once($CFG->dirroot .'/blocks/maristtela/lib.php'); // Inclui a biblioteca de funções do bloco.

class block_maristtela_edit_form extends block_edit_form {

    protected function specific_definition($mform) {
        $block_id = $this->_ajaxformdata["blockid"]; // Obtém o ID do bloco atual.
        $type = get_type_to_display(); // Obtém o tipo de interface a ser exibida.

        $mform->addElement('header', 'config_header', get_string('blocksettings', 'block'));

        $mform->addElement('text', 'config_title', get_string('blocktitle', 'block_maristtela'));
        $mform->setDefault('config_title', 'Maristtela');
        $mform->setType('config_title', PARAM_TEXT);

        $mform->addElement('advcheckbox', 'config_showlabels', get_string('showlabels', 'block_maristtela'));
        $mform->setDefault('config_showlabels', 1);
        
    }
}
