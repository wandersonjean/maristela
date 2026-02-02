<?php
namespace block_maristtela;
defined('MOODLE_INTERNAL') || die();
class observer {
  const TABLE = 'block_maristtela_user_activity';
  protected static function write(\core\event\base $event, string $eventname): void {
    global $DB;
    try {
      $record = (object)['userid'=>(int)$event->userid,'courseid'=>(int)$event->courseid,'cmid'=>isset($event->contextinstanceid)?(int)$event->contextinstanceid:0,'modname'=>$event->component??'','instanceid'=>isset($event->objectid)?(int)$event->objectid:0,'eventname'=>$eventname,'timecreated'=>time()];
      if ($DB->get_manager()->table_exists(self::TABLE)) { $DB->insert_record(self::TABLE, $record); }
    } catch (\Throwable $e) {}
  }
  public static function assign_submission_created(\core\event\base $event): void { self::write($event,'assign_submission_created'); }
  public static function quiz_attempt_submitted(\core\event\base $event): void { self::write($event,'quiz_attempt_submitted'); }
  public static function forum_post_created(\core\event\base $event): void { self::write($event,'forum_post_created'); }
}
