<?php
defined('MOODLE_INTERNAL') || die();

function block_maristtela_get_context_data(int $courseid, int $userid): array {
    global $CFG, $DB;
    require_once($CFG->libdir.'/gradelib.php');

    $context = context_course::instance($courseid);

    $data = [
        'grades' => [],
        'upcoming' => [],
        'forums' => [],
    ];

    $grades = grade_get_course_grades($courseid, $userid);
    if (!empty($grades->items)) {
        foreach ($grades->items as $item) {
            $gi = [
                'name' => format_string($item->name),
                'grademin' => isset($item->grademin) ? (float)$item->grademin : null,
                'grademax' => isset($item->grademax) ? (float)$item->grademax : null,
                'grade' => null,
            ];
            if (!empty($item->grades[$userid])) {
                $gg = $item->grades[$userid];
                $gi['grade'] = is_null($gg->grade) ? null : (float)$gg->grade;
            }
            $data['grades'][] = $gi;
        }
    }

    $modinfo = get_fast_modinfo($courseid, $userid);
    $cms = $modinfo->get_cms();
    $now = time();

    foreach ($cms as $cm) {
        if (!$cm->uservisible) { continue; }
        $rec = $cm->get_course_module_record(true);
        if (!$rec) { continue; }

        $deadline = null;
        $title = format_string($cm->name);
        $url = $cm->url ? $cm->url->out(false) : null;

        switch ($cm->modname) {
            case 'assign':
                if (!empty($rec->duedate)) { $deadline = (int)$rec->duedate; }
                if (!$deadline && !empty($rec->cutoffdate)) { $deadline = (int)$rec->cutoffdate; }
                break;
            case 'quiz':
                if (!empty($rec->timeclose)) { $deadline = (int)$rec->timeclose; }
                break;
            case 'forum':
                if (!empty($rec->duedate)) { $deadline = (int)$rec->duedate; }
                if (!$deadline && !empty($rec->assesstimefinish)) { $deadline = (int)$rec->assesstimefinish; }
                if (!$deadline && !empty($rec->cutoffdate)) { $deadline = (int)$rec->cutoffdate; }
                break;
            default:
                foreach (['duedate','cutoffdate','timeclose','timedue','deadline','timeend'] as $field) {
                    if (!$deadline && property_exists($rec, $field) && !empty($rec->{$field})) {
                        $deadline = (int)$rec->{$field};
                    }
                }
                break;
        }

        if ($deadline && $deadline >= $now) {
            $data['upcoming'][] = [
                'modname' => $cm->modname,
                'title' => $title,
                'deadline' => $deadline,
                'url' => $url,
            ];
        }
    }

    $rs = $DB->get_records_sql("
        SELECT d.*, f.name AS forumname,
               f.duedate AS forumduedate, f.assesstimefinish AS forumassesstimefinish, f.cutoffdate AS forumcutoffdate,
               cm.id AS cmid
          FROM {forum_discussions} d
          JOIN {forum} f ON f.id = d.forum
          JOIN {course_modules} cm ON cm.instance = f.id
          JOIN {modules} m ON m.id = cm.module
         WHERE f.course = :courseid AND m.name = 'forum'
         ORDER BY d.timemodified DESC
         LIMIT 10
    ", ['courseid'=>$courseid]);

    foreach ($rs as $d) {
        $cmcontext = context_module::instance($d->cmid);
        if (!has_capability('mod/forum:viewdiscussion', $cmcontext, $userid)) { continue; }
        $deadline = null;
        if (!empty($d->forumduedate)) { $deadline = (int)$d->forumduedate; }
        else if (!empty($d->forumassesstimefinish)) { $deadline = (int)$d->forumassesstimefinish; }
        else if (!empty($d->forumcutoffdate)) { $deadline = (int)$d->forumcutoffdate; }

        $data['forums'][] = [
            'forum' => format_string($d->forumname),
            'discussion' => format_string($d->name),
            'timemodified' => (int)$d->timemodified,
            'deadline' => $deadline,
            'url' => (new moodle_url('/mod/forum/discuss.php', ['d'=>$d->id]))->out(false),
        ];
    }

    return $data;
}
