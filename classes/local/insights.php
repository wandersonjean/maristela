<?php
namespace block_maristtela\local;
defined('MOODLE_INTERNAL') || die();

class insights {
  public static function get_cards(int $userid, ?\stdClass $course, $cm, array $contexto): array {
    $cards = [];
    if (!empty($contexto['course']['nextdeadlines'])) {
      $d = $contexto['course']['nextdeadlines'][0];
      $cards[] = [
        'type'=>'deadline','severity'=>'info',
        'title'=>get_string('card_upcoming_deadline_title','block_maristtela'),
        'text'=>get_string('card_upcoming_deadline_text','block_maristtela',(object)['name'=>$d['name'],'date'=>$d['timesort_human']]),
        'actionurl'=>$d['url']??''
      ];
    }
    if (!empty($contexto['activity']['quiz'])) {
      $q = $contexto['activity']['quiz'];
      if (!empty($q['timeleftmins']) && $q['timeleftmins'] <= 60) {
        $cards[] = [
          'type'=>'quiz-timeleft','severity'=>'warning',
          'title'=>get_string('card_quiz_time_title','block_maristtela'),
          'text'=>get_string('card_quiz_time_text','block_maristtela',(object)['mins'=>$q['timeleftmins']]),
          'actionurl'=>''
        ];
      }
      if (isset($q['attemptsremaining']) && $q['attemptsremaining'] > 0) {
        $cards[] = [
          'type'=>'quiz-attempts','severity'=>'info',
          'title'=>get_string('card_quiz_attempts_title','block_maristtela'),
          'text'=>get_string('card_quiz_attempts_text','block_maristtela',(object)['left'=>$q['attemptsremaining']]),
          'actionurl'=>''
        ];
      }
    }
    if (!empty($contexto['activity']['assign'])) {
      $a = $contexto['activity']['assign'];
      if (empty($a['submitted']) && !empty($a['duedate_human'])) {
        $cards[] = [
          'type'=>'assign-missing','severity'=>'important',
          'title'=>get_string('card_assign_missing_title','block_maristtela'),
          'text'=>get_string('card_assign_missing_text','block_maristtela',(object)['date'=>$a['duedate_human']]),
          'actionurl'=>''
        ];
      }
    }
    if (!empty($contexto['activity']['forum']) && isset($contexto['activity']['forum']['userpostcount'])) {
      $count = (int)$contexto['activity']['forum']['userpostcount'];
      if ($count === 0) {
        $cards[] = [
          'type'=>'forum-low','severity'=>'info',
          'title'=>get_string('card_forum_low_title','block_maristtela'),
          'text'=>get_string('card_forum_low_text','block_maristtela'),
          'actionurl'=>''
        ];
      }
    }
    return array_slice($cards, 0, 3);
  }
}
