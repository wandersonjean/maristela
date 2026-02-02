<?php
namespace block_maristtela\privacy;

use \core_privacy\local\metadata\collection;
use \core_privacy\local\request\writer;
use \core_privacy\local\request\contextlist;
use \core_privacy\local\request\approved_contextlist;
use \core_privacy\local\request\userlist;
use core_privacy\local\request\approved_userlist;

defined('MOODLE_INTERNAL') || die();

class provider implements 
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\plugin\provider,
    \core_privacy\local\request\core_userlist_provider {

    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table(
            'block_maristtela_log',
             [
                'userid' => 'privacy:metadata:maristtela_log:userid',
                'usermessage' => 'privacy:metadata:maristtela_log:usermessage',
                'airesponse' => 'privacy:metadata:maristtela_log:airesponse',
                'timecreated' => 'privacy:metadata:maristtela_log:timecreated'
             ],
            'privacy:metadata:maristtela_log'
        );
    
        return $collection;
    }

    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new \core_privacy\local\request\contextlist();
        $sql = "SELECT id FROM {context} WHERE contextlevel = 30 AND instanceid = :userid";
        $contextlist->add_from_sql($sql, ['userid' => $userid]);
        return $contextlist;
    }

    public static function get_users_in_context(userlist $userlist) {
        global $DB;
        $context = $userlist->get_context();
        if (!$context instanceof \context_user) {
            return;
        }

        if ($DB->record_exists('block_maristtela_log', ['userid' => $context->instanceid])) {
            $userlist->add_user($context->instanceid);
        }
    }

    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;

        $context = $contextlist->current();
        $user = $contextlist->get_user();
        $userid = $user->id;

        // Mensagens enviadas.
        $sql = "SELECT id, userid, usermessage, airesponse, timecreated FROM {block_maristtela_log} WHERE userid = :userid";
        $records = $DB->get_records_sql($sql, ["userid" => $userid]);

        if (!empty($records)) {
            $messages = new \stdClass();
            foreach ($records as $message) {
                $messages->{$message->id} = [
                    "userid" => $message->userid,
                    "usermessage" => $message->usermessage,
                    "airesponse" => $message->airesponse,
                    "timecreated" => $message->timecreated
                ];
            }
    
            writer::with_context($context)->export_data(
                [get_string('privacy:chatmessagespath', 'block_maristtela')],
                $messages
            );
        }
    }

    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;
        // Só excluir dados para um contexto de usuário.
        if ($context->contextlevel == CONTEXT_USER) {
            $DB->delete_records('block_maristtela_log', ['userid' => $context->instanceid]);
        }
    }

    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;
        foreach ($contextlist as $context) {
            // Vamos ter certeza absoluta de que temos as informações corretas para este usuário.
            if ($context->contextlevel == CONTEXT_USER && $contextlist->get_user()->id == $context->instanceid) {
                $DB->delete_records('block_maristtela_log', ['userid' => $context->instanceid]);
            }
        }
    }

    public static function delete_data_for_users(approved_userlist $userlist) {
        global $DB;
        $context = $userlist->get_context();
        if ($context instanceof \context_user && in_array($context->instanceid, $userlist->get_userids())) {
            $DB->delete_records('block_maristtela_log', ['userid' => $context->instanceid]);
        }
    }
}
