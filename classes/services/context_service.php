// … dentro de get_contexto_detalhado()
$cache = \cache::make('block_maristtela', 'contexto');
$key = "ctx:{$USER->id}:{$PAGE->url->out(false)}";
if ($cached = $cache->get($key)) { return $cached; }

// Papel e grupos
$roles = get_user_roles($PAGE->context, $USER->id, true);
$groups = groups_get_user_groups($COURSE->id, $USER->id)[0] ?? [];

// Progresso de conclusão no curso
$completion = new \completion_info($COURSE);
$overallprogress = method_exists($completion, 'get_course_progress_percentage')
    ? (int)($completion->get_course_progress_percentage($USER->id) ?? 0) : null;

// Notas agregadas (categoria do curso)
$grades = grade_get_grades($COURSE->id, 'mod', $PAGE->cm ? $PAGE->cm->modname : null,
    $PAGE->cm ? $PAGE->cm->instance : 0, $USER->id);

// Próximos eventos
$events = \core_calendar\local\api::get_action_events_by_timesort(
    $USER->id, true, time(), time() + 7*DAYSECS, 20);
$nextdeadlines = array_map(fn($e) => ['name'=>$e->get_name(),'timesort'=>userdate($e->get_timesort())], $events);

// Enriquecer atividade atual (forum/quiz/assign…)
if ($PAGE->cm && $activity_record) {
  switch ($cm->modname) {
    case 'forum':
      $count = $DB->count_records_sql(
        "SELECT COUNT(1) FROM {forum_posts} fp
         JOIN {forum_discussions} fd ON fd.id = fp.discussion
         WHERE fd.forum = ? AND fp.userid = ?", [$cm->instance, $USER->id]);
      $contexto['activity']['userpostcount'] = $count;
      break;
    case 'quiz':
      require_once($CFG->dirroot.'/mod/quiz/locallib.php');
      $quizobj = \quiz::create($cm->instance, $USER->id);
      $attemptsrem = $quizobj->get_num_attempts_allowed() - $quizobj->get_num_attempts();
      $contexto['activity']['attemptsremaining'] = max($attemptsrem, 0);
      $contexto['activity']['timeleftmins'] = $quizobj->get_time_left() ? ceil($quizobj->get_time_left()/60) : null;
      break;
  }
}

$contexto['user'] += [
  'roles' => array_values(array_map(fn($r) => $r->shortname, $roles)),
  'groups' => $groups,
  'lang' => current_language(),
  'timezone' => \core_date::get_user_timezone($USER),
  'courseprogress' => $overallprogress,
  'haswarnings' => [], // futuro: preencher com regras
];

$contexto['course']['nextdeadlines'] = $nextdeadlines;
$cache->set($key, $contexto, 60); // 1 min
