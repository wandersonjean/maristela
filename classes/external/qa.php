<?php
namespace block_maristtela\external;
defined('MOODLE_INTERNAL') || die();
use external_api;
use external_function_parameters;
use external_single_structure;
use external_value;

class qa extends external_api {

  public static function ask_parameters() {
    return new external_function_parameters([
      'contextid' => new external_value(PARAM_INT, 'Context ID of the page', VALUE_REQUIRED),
      'blockid' => new external_value(PARAM_INT, 'Block instance id', VALUE_DEFAULT, 0),
      'question' => new external_value(PARAM_TEXT, 'User question', VALUE_REQUIRED),
      'sesskey' => new external_value(PARAM_RAW, 'sesskey for CSRF protection', VALUE_REQUIRED),
    ]);
  }

  public static function ask($contextid, $blockid, $question, $sesskey) {
    global $COURSE, $PAGE, $USER;
    self::validate_parameters(self::ask_parameters(), [
      'contextid'=>$contextid, 'blockid'=>$blockid, 'question'=>$question, 'sesskey'=>$sesskey
    ]);
    require_sesskey();
    $context = \context::instance_by_id($contextid);
    self::validate_context($context);
    require_login();
    require_capability('block/maristtela:use', $context);

    $svc = new \block_maristtela\services\context_service();
    $ctx = $svc->get_contexto_detalhado((int)$blockid);
    $answer = self::answer_from_context($ctx, $question);

    return ['answer' => $answer];
  }

  public static function ask_returns() {
    return new external_single_structure([
      'answer' => new external_value(PARAM_RAW, 'Answer built from context')
    ]);
  }

  protected static function answer_from_context(array $ctx, string $q): string {
    // Very simple NLP triggers in PT-BR for "tem atividade", "prazo", etc.
    $qnorm = core_text::strtolower(trim($q));
    $lines = [];

    // 1) Activity specific
    if (!empty($ctx['cm']) && !empty($ctx['cm']['modname'])) {
      $modname = $ctx['cm']['modname'];
      $modtitle = $ctx['cm']['name'] ?? $modname;

      if (!empty($ctx['activity']['assign'])) {
        $a = $ctx['activity']['assign'];
        if (empty($a['submitted'])) {
          if (!empty($a['duedate_human'])) {
            $lines[] = get_string('qa_assign_due', 'block_maristtela', (object)['name'=>$modtitle, 'date'=>$a['duedate_human']]);
          } else {
            $lines[] = get_string('qa_assign_open', 'block_maristtela', (object)['name'=>$modtitle]);
          }
        } else {
          $lines[] = get_string('qa_assign_submitted', 'block_maristtela', (object)['name'=>$modtitle]);
        }
      }

      if (!empty($ctx['activity']['quiz'])) {
        $qz = $ctx['activity']['quiz'];
        if (!empty($qz['timeleftmins']) && $qz['timeleftmins'] <= 60) {
          $lines[] = get_string('qa_quiz_timeleft', 'block_maristtela', (object)['mins'=>$qz['timeleftmins']]);
        }
        if (isset($qz['attemptsremaining']) && $qz['attemptsremaining'] > 0) {
          $lines[] = get_string('qa_quiz_attempts', 'block_maristtela', (object)['left'=>$qz['attemptsremaining']]);
        }
      }

      if (!empty($ctx['activity']['forum']) && isset($ctx['activity']['forum']['userpostcount'])) {
        $count = (int)$ctx['activity']['forum']['userpostcount'];
        if ($count === 0) {
          $lines[] = get_string('qa_forum_no_posts', 'block_maristtela');
        }
      }
    }

    // 2) Next deadlines (calendar) as fallback / global
    if (empty($lines) && !empty($ctx['course']['nextdeadlines'])) {
      $d = $ctx['course']['nextdeadlines'][0];
      $lines[] = get_string('qa_next_deadline', 'block_maristtela', (object)['name'=>$d['name'], 'date'=>$d['timesort_human']]);
    }

    // 3) If still nothing, explain that in-context info isn't available
    if (empty($lines)) {
      return get_string('qa_no_context', 'block_maristtela');
    }

    $prefix = get_string('qa_summary_prefix', 'block_maristtela');
    return $prefix . ' ' . implode(' ', $lines);
  }
}
