<?php
namespace block_maristtela;
defined('MOODLE_INTERNAL') || die;

class completion {

    protected $apikey;
    protected $message;
    protected $history;
    protected $assistantname;
    protected $username;
    protected $prompt;
    protected $model;
    protected $maxlength;
    protected $assistant;
    protected $instructions;

    
    public function __construct($model, $message, $history, $block_settings) {
        // Define valores padrão
        $this->model = $model;
        $this->apikey = get_config('block_maristtela', 'apikey');

        $this->prompt = $this->get_setting('prompt', get_string('defaultprompt', 'block_maristtela'));
        $this->assistantname = $this->get_setting('assistantname', get_string('defaultassistantname', 'block_maristtela'));
        $this->username = $this->get_setting('username', get_string('defaultusername', 'block_maristtela'));
        $this->maxlength = $this->get_setting('maxlength', 100);
        $this->assistant = $this->get_setting('assistant');
        $this->instructions = $this->get_setting('instructions');

        // Em seguida, sobrescreva com as configurações do bloco, se aplicável
        if (get_config('block_maristtela', 'allowinstancesettings') === "1") {
            foreach ($block_settings as $name => $value) {
                if ($value) {
                    $this->$name = $value;
                }
            }
        }

        $this->message = $message;
        $this->history = $history;
    }

    
    protected function get_setting($settingname, $default_value = null) {
        $setting = get_config('block_maristtela', $settingname);
        if (!$setting && (float) $setting != 0) {
            $setting = $default_value;
        }
        return $setting;
    }
}
