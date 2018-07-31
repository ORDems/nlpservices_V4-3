<?php
/**
 * Name:  voteredb_cron_login.php     V4.3  7/30/18
 * @file
 * Implements the nlp voter database
 */

use Drupal\voterdb\NlpTurfs;
use Drupal\voterdb\NlpCoordinators;

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_nl_get
 * 
 * Query the database for the information about the NL given the MCID.
 * 
 * @param type $ng_mcid
 * @return associateve array of the NL's database record or NULL.
 */
function voterdb_nl_get($ng_mcid) {
  db_set_active('nlp_voterdb');
  try {
    $ng_tselect = "SELECT * FROM {".DB_NLS_TBL."} WHERE  ".
      NH_MCID." = :mcid";
    $ng_targs = array(
      ':mcid' => $ng_mcid);
    $ng_result = db_query($ng_tselect,$ng_targs);
  }
  catch (Exception $e) {
    db_set_active('default');
    watchdog('voterdb_nl_get', 'NL table query failed');
    return NULL;
  }
  $ng_nl = $ng_result->fetchAssoc();
  db_set_active('default');
  return $ng_nl;
}

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_login_chk
 * 
 * Query the database for any delivered turfs that the NL has not logged in
 * to download the turf.  The check for the NL login starts seven days after
 * the date the turf was delivered to the NL.   If the NL has more than one
 * turf, we look only for the NL login not actually downloading the PDF.
 * 
 * There must be a coordinator for the NL as else there would be no one to 
 * tell of the tardy login by the NL.
 *
 * @return  associate array of tardy logins by NLs who also have coordinators.  
 */
function voterdb_login_chk() {
  watchdog('voterdb_cron_login', 'login chk called');
  
  // Get the ISO date for last week.
  $la_lastWeek = time() - (7 * 24 * 60 * 60);
  $la_isodatetime = date('c',$la_lastWeek);  // date/time in ISO format.
  $la_isodate = explode('T',$la_isodatetime);  // Snip off the time.
  $la_date = $la_isodate[0];  // Just the date.
  // Get the list of NLs with turfs that have not yet logged in.
  //watchdog('voterdb_cron_login', 'coordinators');
  $turfObj = new NlpTurfs();
  $la_turfs = $turfObj->getTardyLogins($la_date);
  //watchdog('voterdb_cron_login', 'tardy complete');
  // For each turf, check if there is a coordinator.  If there is a coordinator,
  // then add the turf to the array for notification.
  
  $coordinatorsObj = new NlpCoordinators();
  $la_coordinators = $coordinatorsObj->getAllCoordinators();
  
  $la_co_array = array();
  foreach ($la_turfs as $la_turf) {
    
    $la_region = array(
      'hd'=>$la_turf['TurfHD'],
      'pct'=>$la_turf['TurfPct'],
      'county'=>$la_turf['County'],
      'coordinators'=>$la_coordinators,
    );
    $la_coordinator = $coordinatorsObj->getCoordinator($la_region);
    
    if(!empty($la_coordinator)) {
      $la_cindex = $la_coordinator['cindex'];
      $la_tindex = $la_turf['TurfIndex'];
      $la_co_array[$la_cindex][$la_tindex] = array(
        'TurfIndex' => $la_turf['TurfIndex'],
        'MCID' => $la_turf['MCID'],
        'TurfPct' => $la_turf['TurfPct'],
        'TurfHD' => $la_turf['TurfHD'],
      );
      // Track the NLs who have not logged in.
      $la_info = 'CO ['.$la_coordinator['firstName'].' '.$la_coordinator['lastName'].
        '] NL ['.$la_turf['NLlname'].' '.$la_turf['NLfname'].']';
      voterdb_login_tracking('turf',$la_turf['County'], 'Login reminder',$la_info);
      }
    }
  return $la_co_array;
}

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_co_notify
 * 
 * Construct information about the NLs who have not logged in and send this
 * information in an email to the coordinator.
 * 
 * @param type $cn_tr  - item from queue, contains the array of turfs with
 *                       NLs who have not logged in.
 * @param type $cn_cindex  - index for the coordinator record.
 */
function voterdb_co_notify($cn_tr,$cn_cindex) {
  // For each of the NLs in the array, create a readable string for the coordinator's email.
  $cn_nl_list = array();
  $cn_nl_str = '';
  foreach ($cn_tr as $cn_tf) {
    $cn_mcid = $cn_tf['MCID'];
    watchdog('voterdb_co_notify', 'login notice for @mcid', 
      array('@mcid' => $cn_mcid,));
    $cn_nl = voterdb_nl_get($cn_mcid);
    if(!$cn_nl) {break;}  // Something is wrong as the NL can't be found.
    watchdog('voterdb_login', 'login reminder for MCID: @mcid, fn: @fn ln: @ln', 
      array('@mcid' => $cn_mcid,'@fn'=>$cn_nl[NH_FNAME],'@ln'=>$cn_nl[NH_LNAME]));
    // Report this NL only once; set last access to not-null.
    $turfObj = new NlpTurfs();
    $turfObj->setLastTurfAccess($cn_tf['TurfIndex'],'2001-01-01');
    
    // Construct a readable string for later display in an email.
    $cn_nl_str = $cn_nl[NH_FNAME].' '.$cn_nl[NH_LNAME].
      ' [HD:'.$cn_tf['TurfHD'].' Pct:'.$cn_tf['TurfPct'].'] '.
      $cn_nl[NH_PHONE].' - '.$cn_nl[NH_EMAIL];
    $cn_nl_list[] = $cn_nl_str;
    }
  // Fetch the coordinator associative array given the index to the table.
  db_set_active('nlp_voterdb');
  try {
    $cn_tselect = "SELECT * FROM {".DB_COORDINATOR_TBL."} WHERE  ".
      CR_CINDEX. " = :index ";
    $cn_targs = array(
      ':index' => $cn_cindex);
    $cn_result = db_query($cn_tselect,$cn_targs);
  }
  catch (Exception $e) {
    db_set_active('default');
    watchdog('voterdb_co_notify', 'coordinator table query failed');
    return NULL;
  }
  $cn_co = $cn_result->fetchAssoc();
  db_set_active('default');
  // Construct the message part of the email for the Coordinator.
  $cn_message = "<p>".$cn_co[CR_FIRSTNAME].",</p>";
  $cn_message .= "<p>The following list of NL's have not yet logged in "
    . "to get their list of voters.  Please contact them to see if there "
    . "is an issue with login.</p>";
  // Add the list of all the NLs who have not logged in.
  foreach ($cn_nl_list as $cn_nl) {
    $cn_message .= "<p>".$cn_nl.",</p>";
  }
  // Set up the drupal call to sent the email.
  $cn_params['county'] = $cn_co[CR_COUNTY];
  $cn_params['message'] = t($cn_message);
  $cn_to = $cn_co[CR_EMAIL];
  $cn_from = variable_get('voterdb_email', 'notifications@nlpservices.org');
  $cn_language = language_default();
  $result = drupal_mail('voterdb', 'no login' , $cn_to, $cn_language, $cn_params, $cn_from, TRUE);
  // Track the result of sending the email.
  if ($result['result'] == TRUE) {
    watchdog('voterdb_co_notify', 'email sent for co @cindex', 
      array('@cindex' => $cn_cindex,));
  } else {
    watchdog('voterdb_co_notify', 'Opps, email failed for co @cindex', 
      array('@cindex' => $cn_cindex,));
  }
}


/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_cron_login_notify
 * 
 * Process the queue with notifications to the coordinators.
 * 
 * @param type $lc_item - names of NLs for this coordinator that have not logged
 *                     in to get there turf.
 */
function voterdb_cron_login_notify($lc_item) {
  watchdog('voterdb_login_check', 'login check processed item created at @time', 
    array('@time' => date_iso8601($lc_item->created),));
  $lc_tr = $lc_item->value;
  $lc_cindex = $lc_item->cindex;
  voterdb_co_notify($lc_tr,$lc_cindex);
}
