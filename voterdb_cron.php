<?php
/**
 * Name:  voteredb_cron.php     V4.3  7/30/18
 * @file
 * Implements the nlp voter database
 */
require_once "voterdb_constants_nls_tbl.php";
require_once "voterdb_constants_bounce_tbl.php";
require_once "voterdb_track.php";
require_once "voterdb_class_coordinators_nlp.php";

define('CR_TIME_WINDOW','3');  // Window of time when emails will be sent.  
   // Start time is midnight and the window is the end.  Typically, this is
   // equal to the Drupal CRON run interval.  The intent is to send emails only
   // once a day.

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_cron
 * 
 * Implements the hook cron.  This will run whenever cron runs and can kick off
 * notifications for nlp.  Some NLP tasks will run during the night between
 * midnight and 7am.
 */
function voterdb_cron() {

  watchdog('voterdb_cron', 'called');
  
  // Check for email bounces.
  $cr_bounce_lst = voterdb_bounce_chk();  //cron_bounce.
  if(!empty($cr_bounce_lst)) {
    watchdog('voterdb_cron', 'Bounce notifications to be sent.');
    // There are some NLs emails that bounced.  Notify the sender.
    foreach ($cr_bounce_lst as $cr_cindex => $cr_bounce_info) {
      // Put the array of bounce notices separately into the queue.
      $cr_queue = DrupalQueue::get('voterdb_cron_bounce_queue');
      $cr_item = new stdClass();
      $cr_item->created = time();
      $cr_item->value = $cr_bounce_info;
      $cr_queue->createItem($cr_item);
    }
  }
  
  // Check if we have results to report to VoteBuilder.
  //watchdog('voterdb_cron', 'results chk to be called');
  $cr_results_list = voterdb_results_chk();  //cron_resu;ts.
  if(!empty($cr_results_list)) {  // Results to report.
    //watchdog('voterdb_cron', 'Results to be recorded.');
    foreach ($cr_results_list as $cr_results_batch) {
      // Put the array of results into the queue.
      $cr_queue = DrupalQueue::get('voterdb_cron_results_queue');
      $cr_item = new stdClass();
      $cr_item->created = time();
      $cr_item->value = $cr_results_batch;
      $cr_queue->createItem($cr_item);
    }
  }
  
  // Verify that we run only once a day between midnight and 7am.
  $cr_time = time();
  $cr_interval = 12 * 60 * 60;  // 12 hours in seconds.
  $cr_next = $cr_time + $cr_interval;
  $cr_hour = format_date($cr_time,'custom','G','America/Los_Angeles',NULL);
  if ($cr_hour > CR_TIME_WINDOW) {return;}
  $cr_execute = variable_get('voterdb_cron_next_execution', 0);
  if ($cr_time >= $cr_execute) {
    // It's the right time to check for notifications.
    watchdog('voterdb_cron', 'daily cron ran');
    variable_set('voterdb_cron_next_execution', $cr_next);
    // Check if we have NLs that have not yet logged in.
    $cr_co_array = voterdb_login_chk();  //cron_login.
    if(!empty($cr_co_array)) {
      //watchdog('voterdb_cron', 'Notifications to be sent.');
      // There are some NLs that have not logged in.  Notify the coordinator.
      foreach ($cr_co_array as $cr_cindex => $cr_item_value) {
        // Put the array of turfs associated with a coordinator separately into the queue.
        $cr_queue = DrupalQueue::get('voterdb_cron_login_queue');
        $cr_item = new stdClass();
        $cr_item->created = time();
        $cr_item->value = $cr_item_value;
        $cr_item->cindex = $cr_cindex;
        $cr_queue->createItem($cr_item);
      }
    }
    // Now check if we are close to the election.
    $cr_notification_list = voterdb_report_chk();  //cron_report.
    if(empty($cr_notification_list)) {return;}  // No one to notify.
    foreach ($cr_notification_list as $cr_mcid => $cr_tf) {
      // Put the array of turfs associated with a coordinator separately into the queue.
      $cr_queue = DrupalQueue::get('voterdb_cron_report_queue');
      $cr_item = new stdClass();
      $cr_item->created = time();
      $cr_item->value = $cr_tf;
      $cr_item->mcid = $cr_mcid;
      $cr_queue->createItem($cr_item);
    }
    
  }
}
/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_cron_queue_info
 * 
 * Create two cron queues to send notifications to various participants, 
 * like the coordinators or the NLs.
 * 
 */
function voterdb_cron_queue_info() {
  // Create the queue to check that NLs have logged in to get their turf
  // after being notified the turf is available.
  watchdog('voterdb_cron', 'queue info called');
  
  $queues['voterdb_cron_login_queue'] = array(
    'worker callback' => 'voterdb_cron_login_notify',
    'time' => 90,
  );
  // Create a queue to check that an NL has reported results and remind the
  // coordinator to check on them.
  $queues['voterdb_cron_report_queue'] = array(
    'worker callback' => 'voterdb_cron_report_notify',
    'time' => 90,
  );
  // Create a queue to check that an email sent to an NL has bounced and 
  // notify the sender.
  $queues['voterdb_cron_bounce_queue'] = array(
    'worker callback' => 'voterdb_cron_bounce_notify',
    'time' => 90,
  );
  // Create a queue to record results in VoteBuilder.
  $queues['voterdb_cron_results_queue'] = array(
    'worker callback' => 'voterdb_cron_results_notify', // cron_results.
    'time' => 90,
  );
  return $queues;
}
