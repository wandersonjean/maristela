<?php
defined('MOODLE_INTERNAL') || die();
$definitions = [
  'contexto' => ['mode' => cache_store::MODE_REQUEST],
  'assistants' => ['mode' => cache_store::MODE_APPLICATION, 'ttl' => 300],
];
