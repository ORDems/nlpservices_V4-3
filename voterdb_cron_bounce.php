<?php
/**
 * Name:  voteredb_cron_bounce.php     V4.1  6/1/18
 * @file
 * Implements the nlp voter database
 */

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_bounce_chk
 * 
 * Check if any emails sent to NLs have bounced.  If any are found, the
 * sender is notified.
 * 
 * @return an associative list of NL records where the email has bounced.  Or,
 * if there is a bug, NULL.
 */
function voterdb_bounce_chk() {
  watchdog('voterdb_cron_bounce', 'bounce chk called');
  // Get the reported non-delivery emails.  This is the complete list held by
  // the Bounce module.
  try {
    $bc_query = db_select(DB_BOUNCE_NON_DELIVERY_TBL, 'n');
    $bc_query->addField('n', BN_REPORT_ID);
    $bc_query->addField('n', BN_MAIL);
    $bc_query->addField('n', BN_CODE);
    $bc_query->addField('n', BN_REPORT);
    $bc_query->addField('n', BN_CREATED);
    $bc_result = $bc_query->execute();
  }
  catch (Exception $e) {
    watchdog('voterdb_bounce_chk', 'Opps, email failed search for bounces');
    return NULL;
  }
  $bc_bounce_lst = array();
  // Get the non-delivery reports that are new.
  $bc_idx = 0;
  do {
    $bc_report = $bc_result->fetchAssoc();
    if (!$bc_report) {break;}
    // Check if the bounce has already been processed.
    try {
      $bc_query = db_select(DB_BOUNCE_REPORT_NOTIFY_TBL, 'n');
      $bc_query->addField('n', BA_NOTIFIED);
      $bc_query->condition(BA_NLEMAIL,$bc_report[BN_MAIL]);
      $bc_nresult = $bc_query->execute();
     }
    catch (Exception $e) {
      watchdog('voterdb_bounce_chk', 'Opps, search for report status failed');
      return NULL;
    }
    $bc_notified_result = $bc_nresult->fetchAssoc();
    $bc_bounced_notify = (!empty($bc_notified_result) ? $bc_notified_result[BA_NOTIFIED]:BQ_NOT_NOTIFIED);
    // If we have a new bounce, let the person who sent the turf know of the 
    // bounce so they can fix the email address.
    if ($bc_bounced_notify == BQ_NOT_NOTIFIED) {
      // We haven't processed this one, now check if it is a turf delivery.
      $bc_code = $bc_report[BN_CODE];
      // Get the text description of the code.
      try {
        $bc_cquery = db_select(DB_BOUNCE_CODE_SCORE_TBL, 'c');
        $bc_cquery->addField('c', BC_DESCRIPTION);
        $bc_cquery->condition(BC_CODE,$bc_code);
        $bc_cresult = $bc_cquery->execute();
      }
      catch (Exception $e) {
        watchdog('voterdb_bounce_chk', 'Opps, email failed search for bounce code.');
        return NULL;
      }
      $bc_desc_result = $bc_cresult->fetchAssoc();
      $bc_description = (!empty($bc_desc_result))?$bc_desc_result[BC_DESCRIPTION]:'Unknown';
      $bc_date = date("F j, Y, g:i a",$bc_report[BN_CREATED]);
      // Check if we have the voterdb marker and the eor in the header.
      $bc_report_blob = $bc_report[BN_REPORT];
      $bc_needle = "x-voterdb-notify";
      $bc_needle_len = strlen($bc_needle)+1;
      $bc_pos = stripos($bc_report_blob, $bc_needle);
      if($bc_pos !== FALSE) {
        $bc_end = stripos($bc_report_blob, "<eor>");
        if($bc_end !== FALSE) {
          // We have a new bounce, add it to the list for notification.
          $bc_start = $bc_pos+$bc_needle_len;
          $bc_notify_str = substr($bc_report_blob, $bc_start, $bc_end-$bc_start);
          // Decode the voterdb turf delivery header record.
          $bc_notify = json_decode($bc_notify_str,TRUE);
          $bc_county = $bc_notify['sender']['county'];
          $bc_remail = $bc_notify['recipient']['r-email'];
          // Add this NL to the list of bouncing email addresses.
          $bc_bounce_lst[$bc_idx] = array(
            "county"=>$bc_county,
            "remail"=>$bc_remail,
            "report-id"=>$bc_report[BN_REPORT_ID],
            "code"=>$bc_code,
            "date"=>$bc_date,
            "description"=>$bc_description,
            "notify"=>$bc_notify);
          $bc_idx++;
        }
      }
    }  
  } while (TRUE);
  return $bc_bounce_lst;
}

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_bounce_notify
 * 
 * Send an email to the person who sent a turf to an NL to notify them that 
 * the email bounced.   
 * 
 * @param type $bn_bouncer - associative array of info about the bounced email.
 * @return nothing.
 */
function voterdb_bounce_notify($bn_bouncer) {
  //watchdog('voterdb_cron_bounce', 'bounce notify called');
  // Construct an email to send to the person who sent a turf to an NL and
  // let them know the email bounced.
  $bn_module = 'voterdb';
  $bn_key = 'notify bounce';
  $bn_language = language_default();
  $bn_send = TRUE;
  $bn_admin = variable_get('voterdb_email', 'notifications@nlpservices.org');
  $bn_from = 'NLP Admin<'.$bn_admin.'>';
  $bn_scounty = $bn_bouncer['county'];
  $bn_desc = $bn_bouncer['description'];
  $bn_code = $bn_bouncer['code'];
  $bn_rfn = $bn_bouncer['notify']['recipient']['r-fn'];
  $bn_rln = $bn_bouncer['notify']['recipient']['r-ln']; 
  $bn_remail = $bn_bouncer['notify']['recipient']['r-email']; 
  $bn_sfn = $bn_bouncer['notify']['sender']['s-fn'];
  $bn_sln = $bn_bouncer['notify']['sender']['s-ln'];
  $bn_semail = $bn_bouncer['notify']['sender']['s-email'];
  // Construct the message for the sender.
  $bn_message = "<p>".$bn_sfn.",";
  $bn_message .= '<br>'.t('The email you sent to the NL below was not delivered.');
  $bn_message .= '<br><br><b>'.t('@fn @ln - @email <br>Code: @code - Description: @desc '.'</b><br>',
    array(
        '@fn' => $bn_rfn,
        '@ln' => $bn_rln,
        '@email' => $bn_remail,
        '@code' => $bn_code,
        '@desc' => $bn_desc,
        ));
  $bn_params['message'] = $bn_message;
  $bn_to = $bn_sfn.' '.$bn_sln.'<'.$bn_semail.'>';
  $bn_eresult = drupal_mail($bn_module, $bn_key, $bn_to, $bn_language, $bn_params, $bn_from, $bn_send);
  if (!$bn_eresult) {
    watchdog('voterdb_bounce_notify', 'Bounce notification email failed.');
  }
  // Track the sending of these emails.
  $outputr = 'From: '. $bn_semail. ' To: '.$bn_remail.' NL: '.$bn_rfn.' '.$bn_rln.' Code: '.$bn_code.' Desc: '.$bn_desc;
  $output = substr($outputr, 0, 250);
  watchdog('voterdb_cron_bounce', $output);
  voterdb_login_tracking('email',$bn_scounty, 'Bounce notification sent',$output);
  // Notify the sender only once by adding an entry in the notify table with
  // the report_id.
  $bn_date = date("Y-m-d H:i:s",time());
  try {
    db_insert(DB_BOUNCE_REPORT_NOTIFY_TBL)
    ->fields(array(
      BA_REPORT_ID => $bn_bouncer['report-id'],
      BA_BLOCK_ID => NULL,
      BA_COUNTY => $bn_scounty,
      BA_NLFNAME => $bn_rfn,
      BA_NLLNAME => $bn_rln,
      BA_NLEMAIL => $bn_remail,
      BA_SFNAME => $bn_sfn,
      BA_SLNAME => $bn_sln,
      BA_SEMAIL => $bn_semail,
      BA_CODE => $bn_code,
      BA_DESCRIPTION => $bn_desc,
      BA_NOTIFIED => 'Y',
      BA_DATE => $bn_date,
    ))
    ->execute();
  }
  catch (Exception $e) {
    $bn_error = $e->getMessage();
    watchdog('voterdb_cron_bounce','notification insert failed: '.$bn_error,WATCHDOG_DEBUG);
    //watchdog_exception('my_module', $e->getMessage() t('Caught an error: ' . $bn_error));
    return;
  }
}

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_cron_bounce_notify
 * 
 * Process the queue of notifications to the coordinator of NLs that have
 * not yet reported results.
 * 
 * @param type $rc_item - names of NLs that have not reported results for this
 *                     coordinator.
 */
function voterdb_cron_bounce_notify($rc_item) {
  watchdog('voterdb_bounce_notify', 'processed item created at @time', 
    array('@time' => date_iso8601($rc_item->created),));
  voterdb_bounce_notify($rc_item->value);  //cron.
}
