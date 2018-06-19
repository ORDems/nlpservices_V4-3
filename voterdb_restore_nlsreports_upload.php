<?php
/*
 * Name: voterdb_restore_nlsreports_upload.php   V4.0   2/17/18
 * This include file contains the code to restore voter contact reports by
 * NLs in previous elections.  It creates the database for historical results
 * that might be of value for this election.
 */
require_once "voterdb_constants_rr_tbl.php";
require_once "voterdb_constants_nls_tbl.php"; 
require_once "voterdb_debug.php";
// Loop limits.
define("RR_SQL_LMT","100");
define("RR_BATCH_LMT","100");

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_timer2
 * 
 * Calculate the duration of an event.
 *
 * @param type $vt_event - start or stop.
 * @param type $vt_stime - the starting time.
 * @return either the start time or the elapsed time.
 */
function voterdb_timer2($vt_event,$vt_stime) {
  $vt_ctime = microtime();
  $vt_atime = explode(' ', $vt_ctime);
  $vt_time = $vt_atime[1] + $vt_atime[0];
  switch ($vt_event) {
    case 'start':
      $vt_rtime = $vt_time;
      break;
    case 'end':
      $vt_rtime = ($vt_time - $vt_stime);
      break;
  }
return $vt_rtime;
}

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_restore_nlsreports_upload
 * 
 * Read the provided file and restore the historical voter contact reports.
 * 
 * @param type $form_state
 * @return  FALSE if error.
 */
function voterdb_restore_nlsreports_upload($um_arg,&$um_context) {
  // Retrieve the values determined when we validated the form submittal
  $um_nlreports_uri = $um_arg['uri'];
  $um_field_pos = $um_arg['field_pos'];
  $um_delimiter = $um_arg['delimiter'];
  // Open the input file and position for reading.
  $um_rpt_fh = fopen($um_nlreports_uri, "r");
  if (!$um_rpt_fh) {
    watchdog('voterdb_reports_restore_upload', 'File open error: @name', 
      array('@name' => $um_nlreports_uri,),WATCHDOG_DEBUG);
    $um_context['finished'] = TRUE;
    return;
  }
  $um_filesize = filesize($um_nlreports_uri);
  $um_context['finished'] = 0;
  if(empty($um_context['sandbox']['seek'])) {
    // Read the header record.
    $um_voter_raw = fgets($um_rpt_fh);
    $um_rcd_cnt = 0;
    $um_context['sandbox']['upload-start'] = voterdb_timer2('start',0);
    $um_context['sandbox']['loop-max'] = 0;
    $um_seek = 0;
    $um_bad_rcds = array();
  } else {
    // Seek to where we will restart.
    $um_seek = $um_context['sandbox']['seek'];
    fseek($um_rpt_fh, $um_seek);
    $um_rcd_cnt = $um_context['sandbox']['rcds'];
    $um_bad_rcds = $um_context['sandbox']['bad-rcds'];
  }
  $um_loopstart = voterdb_timer2('start',0);
  // Overlap the commits for tbe insert.
  $um_transaction = db_transaction();
  // Get the records one at a time.
  set_time_limit(90);
  $um_done = TRUE;
  $um_loopcnt = $um_sqlcnt = 0;
  $um_previous = "";
  $um_field_names = array(NC_CYCLE,NC_COUNTY,NC_ACTIVE,NC_VANID,NC_MCID,NC_CDATE,NC_TYPE,NC_VALUE,NC_TEXT);
  $um_values = array();
  do {
    $um_raw_record = fgets($um_rpt_fh);
    if (empty($um_raw_record)) {break;} //We've processed the last report record.
    $um_txt_record = html_entity_decode($um_raw_record);
    if($um_delimiter == ',') {
      $um_report = str_getcsv($um_txt_record,",",'"');
      // With a CSV there is an extra new line for each record.
      if($um_txt_record === '"'."\r\n" OR $um_txt_record === '"'."\n") {
        continue;
      }
    } else {
      $um_report = explode($um_delimiter, $um_txt_record);
    }
    $um_rcd_cnt++;
    // Avoid a crash from a bad record.  And, let the user know so it can be corrected.
    if(empty($um_report[$um_field_pos[NU_VANID]])) {
      $um_bc = count($um_bad_rcds);
      if ($um_bc < 101) {
        $um_bad_rcds[] = $um_rcd_cnt;
        $um_hr = strToHex($um_txt_record);
        watchdog('bad record', 'Bad record: @rec', 
          array('@rec' =>  $um_hr),WATCHDOG_DEBUG);
      }
      continue;
    }
    // Convert the date to ISO.  This is only needed if the input file was
    // editted by Excel which changes to the date format.
    $um_idate = $um_report[$um_field_pos[NU_CDATE]];
    $um_udate = strtotime($um_idate);
    $um_cdate = date('Y-m-d',$um_udate);
    $um_fields = array(
      NC_CYCLE=>$um_report[$um_field_pos[NU_CYCLE]], 
      NC_COUNTY=>$um_report[$um_field_pos[NU_COUNTY]], 
      NC_ACTIVE=>$um_report[$um_field_pos[NU_ACTIVE]],
      NC_VANID=>$um_report[$um_field_pos[NU_VANID]], 
      NC_MCID=>$um_report[$um_field_pos[NU_MCID]],  
      NC_CDATE=>$um_cdate, 
      NC_TYPE=>$um_report[$um_field_pos[NU_TYPE]], 
      NC_VALUE=>$um_report[$um_field_pos[NU_VALUE]], 
      NC_TEXT=>$um_report[$um_field_pos[NU_TEXT]],
    );
    $um_values[$um_sqlcnt++] = $um_fields;
    if($um_sqlcnt==RR_SQL_LMT) {
      $um_bc = count($um_bad_rcds);
      // Insert this result record.
      $um_sqlcnt = 0;
      db_set_active('nlp_voterdb');
      $um_loopcnt++;
      try {
        $um_query = db_insert(DB_NLPRESULTS_TBL)->fields($um_field_names);
        foreach ($um_values as $um_record) {
          $um_query->values($um_record);
        }
        $um_query->execute();
      }
      catch (Exception $e) {
        db_set_active('default');
        watchdog('voterdb_reports_restore_upload', 'insert error record: @rec', 
          array('@rec' =>  print_r($e->getMessage() true)),WATCHDOG_DEBUG);
        break;
      }
      db_set_active('default');
      $um_values = array();
    } 
    // Stop and recycle the batch request every 100 inserts.
    $um_previous = $um_raw_record;
    if ($um_loopcnt == RR_BATCH_LMT) {
      $um_loopcnt = 0;
      $um_done = FALSE;
      $um_seek = ftell($um_rpt_fh);
      $um_context['sandbox']['seek'] = $um_seek;
      $um_percent = $um_seek/$um_filesize;
      $um_context['finished'] = $um_percent;
      // We are done when the read pointer is at the end.
      if($um_seek == $um_filesize) {
        $um_done = TRUE;
      }
      $um_context['sandbox']['rcds'] = $um_rcd_cnt;
      $um_context['sandbox']['bad-rcds'] = $um_bad_rcds;
      $um_loop_time = voterdb_timer2('end',$um_loopstart);
      if($um_loop_time > $um_context['sandbox']['loop-max']) {
        $um_context['sandbox']['loop-max'] = $um_loop_time;
      }
      break;
    }
  } while (TRUE);
  // Finish the batch if we are done.
  if($um_done) {
    if($um_sqlcnt != 0) {
      db_set_active('nlp_voterdb');
      try {
        $um_query = db_insert(DB_NLPRESULTS_TBL)->fields($um_field_names);
        foreach ($um_values as $um_record) {
          $um_query->values($um_record);
        }
        $um_query->execute();
      }
      catch (Exception $e) {
        db_set_active('default');
        watchdog('voterdb_reports_restore_upload', 'insert error record: @rec', 
          array('@rec' =>  print_r($e->getMessage() true)),WATCHDOG_DEBUG);
      }
      db_set_active('default');
    }
    $um_context['finished'] = 1;
    $um_context['results']['rcds'] = $um_rcd_cnt;
    $um_context['results']['bad-rcds'] = $um_bad_rcds;
    $um_upload_time = voterdb_timer2('end',$um_context['sandbox']['upload-start']);
    $um_context['results']['upload-time'] = $um_upload_time;
    $um_context['results']['loop-max'] = $um_context['sandbox']['loop-max'];
    $um_context['results']['uri'] = $um_nlreports_uri;
  }
  db_set_active('default');
  set_time_limit(30);
  return TRUE;
}

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_restore_nlsreports_finished
 * 
 * A nice report when we are done.
 * 
 * @param type $um_success
 * @param type $um_results
 * @param type $um_operations
 */
function voterdb_restore_nlsreports_finished($um_success, $um_results, $um_operations) {
  $um_nlreports_uri = $um_results['uri'];
  drupal_unlink($um_nlreports_uri);
  if ($um_success) {
    $um_rcd_cnt = $um_results['rcds'];
    $um_bad_cnt = count($um_results['bad-rcds']);
    drupal_set_message(t('@count reports restored.  Bad records: @bad', 
      array('@count' => $um_rcd_cnt,
          '@bad' => $um_bad_cnt )));
    $um_loop_max = round($um_results['loop-max'], 1);
    $um_upload_time = round($um_results['upload-time'], 1);
    drupal_set_message(t('Upload time: @upload, Max loop time: @loop.', 
      array('@upload' => $um_upload_time,'@loop'=>$um_loop_max)),'status');
    drupal_set_message('NL reports successfully restored.','status');
    watchdog('voterdb_ballot_received_finished', 'Upload of NL reports finished');
  }
  else {
    drupal_set_message(t('Opps, an error occurred.'),'error');
    watchdog('voterdb_ballot_received_finished', 'Upload of NL reports failed');
  }
  if(!empty($um_results['bad-rcds'])) {
    $um_bad_rcds = $um_results['bad-rcds'];
    $um_rcd_nums = '';
    $um_rcnt = 0;
    foreach ($um_bad_rcds as $um_rcd_num) {
      if($um_rcnt != 0) {
        $um_rcd_nums .= ', ';
      }
      $um_rcd_nums .= $um_rcd_num;
      if($um_rcnt++ == 9) {
        $um_rcnt = 0;
        drupal_set_message('Bad record(s): '.$um_rcd_nums,'warning');
        $um_rcd_nums = '';
      } 
    }
    if($um_rcd_nums != '') {
      drupal_set_message('Bad record(s): '.$um_rcd_nums,'warning');
    }
  }
}