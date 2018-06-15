<?php
/**
 * Name:  voteredb_cron_report.php     V4.1  6/1/18
 * @file
 * Implements the nlp voter database
 */

use Drupal\voterdb\NlpTurfs;

define('CR_MAIL_LIMIT','200');  // Limit of number of emails per day.

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_get_unreported_turf
 * 
 * Construct an associative array of all the turfs that have not had any
 * reports entered.   The NL only has to enter one result for a single 
 * voter to satisfy this criteria.   And we skip any NL that does not have
 * an email.  That case has to be handled manually.
 * 
 * The list of NLs to notify is limited to avoid exceeding the limit imposed
 * by our server vendor.   Remaining NLs will be notified after 24 hours.
 * 
 * @return associative array of NLs that need to be reminded to report results.
 */
function voterdb_get_unreported_turf() {
  $ut_unreported = array();
  // Get the list of turfs that have not already been notified and 
  // with an NL with an email.
  $turfObj = new NlpTurfs();
  $ut_turfs = $turfObj->getTurfReminders();
  
  $nn_cnt = 0;
  foreach ($ut_turfs as $ut_turf) {
    // Check if this NL has reported results.
    $ut_mcid = $ut_turf['MCID'];
    db_set_active('nlp_voterdb');
    $ut_sselect = "SELECT * FROM {".DB_NLSSTATUS_TBL."} WHERE  ".NN_MCID. " = :mcid ";
    $ut_sargs = array(':mcid' => $ut_mcid);
    $ut_sresult = db_query($ut_sselect,$ut_sargs);
    db_set_active('default');
    $ut_nlstatus = $ut_sresult->fetchAssoc();
    $ut_resultsreported = $ut_nlstatus[NN_RESULTSREPORTED];
    // Check if NL status shows results of canvass have been reported.
    if(!$ut_resultsreported) {
      // Add this turf to the array of unreported turfs.
      $ut_unreported[$ut_mcid] = $ut_turf;
      $nn_cnt++;
      if($nn_cnt>=CR_MAIL_LIMIT) {break;}
    } else {
      // The NL reported results, no need to look at this turf in the future.
      $turfObj->setTurfReminder($ut_turf['TurfIndex'],'N');
    }
  }
  
  return $ut_unreported;
}

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_report_chk
 * 
 * Check if we are past the date when NLs should have started reporting results,
 * ie just before ballots are mailed, then remind NLs who have not reported 
 * to start their canvass or report results.
 * 
 * @return - associateve array of NLs needing reminders, but limited to a max 
 *           number so we do not exceed the email server limit.
 */
function voterdb_report_chk() {
  watchdog('voterdb_cron_report', 'report chk called');
  // Get the time for today.
  $rc_today = time();
  // Convert notification date to time.
  $rc_ndate = variable_get('voterdb_ndate', '2017-04-19');
  $rc_ntime = strtotime($rc_ndate);
  // If today's date is before the notification date, nothing to do.
  if($rc_today<$rc_ntime) {return NULL;}
  // Search for turfs where the NL has not reported results.
  $rc_unreported = voterdb_get_unreported_turf();
  return $rc_unreported;
}

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_nl_notify
 * 
 * Remind NL who have not reported results to contact voters and enter results.
 * 
 * @param nothing.
 */
function voterdb_nl_notify($nn_mcid,$nn_turf) {
  global $base_url;
  $nn_bdate = variable_get('voterdb_bdate', '2017-04-26');
  $nn_btime = strtotime($nn_bdate);
  $nn_bdisplay = date('F j, Y',$nn_btime);
  $nn_pass = strtolower(variable_get('voterdb_password', 'Password'));
  // Construct a reminder message for the NL.
  $nn_message = "<p>".$nn_turf[NH_NICKNAME].",</p>";
  $nn_message .= "<p>Ballots will be mailed on ".$nn_bdisplay
    . "Please make an attempt to contact your voters before then.  And, it "
    . "is vital to the program for you to report the results of your"
    . "attempt to contact your voters.</p>";
  $nn_message .= "Please use this link to report results.";
  $nn_message .= t('<p><a href="'.$base_url.'/nlpdataentry?County=@grp" target="_blank">Neighborhood Leader Login</a></p>',
    array('@grp' => $nn_turf[NH_COUNTY]));
  $nn_message .= t('<p>'.'The password is @pass'.'</p>',
    array('@pass' => $nn_pass));
  $nn_params['message'] = t($nn_message);
  $nn_to = $nn_turf[NH_NICKNAME].' '.$nn_turf[NH_LNAME].'<'.$nn_turf[NH_EMAIL].'>';
  $nn_admin = variable_get('voterdb_email', 'notifications@nlpservices.org');
  $nn_from = 'NLP Admin<'.$nn_admin.'>';
  $nn_language = language_default();
  $nn_result = drupal_mail('voterdb', 'no report' , $nn_to, $nn_language, $nn_params, $nn_from, TRUE);
  // We remind the NL only once.
  
  $turfObj = new NlpTurfs();
  $turfObj->setTurfReminder($nn_turf['TurfIndex'],'N');
  db_set_active('nlp_voterdb');

  // Track the result of the attempt to send an email.
  if ($nn_result['result'] == TRUE) {
    watchdog('voterdb_co_notify', 'email sent for @email', 
      array('@email' => $nn_to,));
  } else {
    watchdog('voterdb_co_notify', 'Opps, email failed for @email', 
      array('@email' => $nn_to,));
  }
}

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_cron_report_notify
 * 
 * Process the queue of notifications to the coordinator of NLs that have
 * not yet reported results.
 * 
 * @param type $item - names of NLs that have not reported results for this
 *                     coordinator.
 */
function voterdb_cron_report_notify($rc_item) {
  watchdog('voterdb_report_check', 'report check processed item created at @time', 
    array('@time' => date_iso8601($item->created),));
  $rc_mcid = $rc_item->mcid;
  $rc_turf = $rc_item->turf;
  voterdb_nl_notify($lc_mcid,$lc_turf);
}
