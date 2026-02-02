<?php
namespace block_maristtela\task;
defined('MOODLE_INTERNAL') || die();
class maintenance_task extends \core\task\scheduled_task {
  public function get_name() { return get_string('task_maintenance', 'block_maristtela'); }
  public function execute() {
    global $DB;
    $retentiondays = (int)get_config('block_maristtela','retentiondays');
    if ($retentiondays <= 0) { $retentiondays = 30; }
    $threshold = time() - ($retentiondays * DAYSECS);
    if ($DB->get_manager()->table_exists('block_maristtela_user_activity')) {
      $DB->delete_records_select('block_maristtela_user_activity', 'timecreated < :t', ['t'=>$threshold]);
    }
    if ($DB->get_manager()->table_exists('block_maristtela_log')) {
      $DB->delete_records_select('block_maristtela_log', 'timecreated < :t', ['t'=>$threshold]);
    }
  }
}
