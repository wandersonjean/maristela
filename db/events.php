<?php
defined('MOODLE_INTERNAL') || die();
$observers = [
  ['eventname'=>'\\mod_assign\\event\\submission_created','callback'=>'\\block_maristtela\\observer::assign_submission_created','includefile'=>'/blocks/maristtela/classes/observer.php','priority'=>999],
  ['eventname'=>'\\mod_quiz\\event\\attempt_submitted','callback'=>'\\block_maristtela\\observer::quiz_attempt_submitted','includefile'=>'/blocks/maristtela/classes/observer.php','priority'=>999],
  ['eventname'=>'\\mod_forum\\event\\post_created','callback'=>'\\block_maristtela\\observer::forum_post_created','includefile'=>'/blocks/maristtela/classes/observer.php','priority'=>999],
];
