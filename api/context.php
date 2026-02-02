<?php
// Minimal: curso + notas do usuário, mais nada.
// GET /blocks/maristtela/api/context.php?courseid=123
define('AJAX_SCRIPT', true);
require_once(__DIR__ . '/../../config.php');

header('Content-Type: application/json; charset=utf-8');

try {
    $courseid = required_param('courseid', PARAM_INT);
    require_login($courseid);
    $context = context_course::instance($courseid);
    require_capability('moodle/course:view', $context);

    global $USER, $CFG;
    require_once($CFG->libdir.'/gradelib.php');

    $userid = $USER->id;

    $out = [
        'course' => [
            'id' => $courseid,
            'fullname' => format_string(get_course($courseid)->fullname)
        ],
        'user' => [
            'id' => $userid,
            'username' => $USER->username
        ],
        'grades' => []
    ];

    $grades = grade_get_course_grades($courseid, $userid);
    if (!empty($grades->items)) {
        foreach ($grades->items as $item) {
            $gi = [
                'name'     => format_string($item->name),
                'grademin' => isset($item->grademin) ? (float)$item->grademin : null,
                'grademax' => isset($item->grademax) ? (float)$item->grademax : null,
                'grade'    => null,
                'percent'  => null
            ];
            if (!empty($item->grades[$userid])) {
                $g = $item->grades[$userid];
                if ($g && $g->grade !== null) {
                    $gi['grade'] = (float)$g->grade;
                    if (!empty($gi['grademax'])) {
                        $gi['percent'] = round(($gi['grade'] / $gi['grademax']) * 100, 2);
                    }
                }
            }
            $out['grades'][] = $gi;
        }
    }

    echo json_encode($out);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => ['message' => $e->getMessage()]]);
}
