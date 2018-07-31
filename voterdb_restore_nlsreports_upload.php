<?php
/*
 * Name: voterdb_restore_nlsreports_upload.php   V4.3   7/29/18
 * This include file contains the code to restore voter contact reports by
 * NLs in previous elections.  It creates the database for historical results
 * that might be of value for this election.
 */

require_once "voterdb_debug.php";
require_once "voterdb_class_nlreports_nlp.php";
// Loop limits.
define("RR_SQL_LMT","100");
define("RR_BATCH_LMT","100");

use Drupal\voterdb\NlpReports;


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
  //voterdb_debug_msg('pos', $um_field_pos);
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
  //voterdb_debug_msg('context', $um_context);
  if(empty($um_context['sandbox']['seek'])) {
    // Read the header record.
    $um_hdr_raw = fgets($um_rpt_fh);
    $um_rcd_cnt = 0;
    $um_context['sandbox']['upload-start'] = voterdb_timer('start',0);
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
  
  $reportsObj = new NlpReports();
  //voterdb_debug_msg('reportsobj', $reportsObj);
  
  $um_loopstart = voterdb_timer('start',0);
  // Overlap the commits for tbe insert.
  $um_transaction = db_transaction();
  // Get the records one at a time.
  set_time_limit(90);
  $um_done = TRUE;

  do {
    $um_raw_record = fgets($um_rpt_fh);
    //voterdb_debug_msg('raw', $um_raw_record);
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
    //voterdb_debug_msg('report', $um_report);
    $um_rcd_cnt++;
    // Avoid a crash from a bad record.  And, let the user know so it can be corrected.
    
    if(empty($um_report[$um_field_pos['vanid']])) {
      //voterdb_debug_msg('opps', '');
      $um_bc = count($um_bad_rcds);
      if ($um_bc < 101) {
        $um_bad_rcds[] = $um_rcd_cnt;
        $um_hr = strToHex($um_txt_record);
        watchdog('bad record', 'Bad record: @rec', 
          array('@rec' =>  $um_hr),WATCHDOG_DEBUG);
      }
      continue;
    }
    //voterdb_debug_msg('ok', '');
    // Convert the date to ISO.  This is only needed if the input file was
    // editted by Excel which changes to the date format.
    //$um_idate = $um_report[$um_field_pos['cdate']];
    //$um_udate = strtotime($um_idate);
    //$um_cdate = date('Y-m-d',$um_udate);
    
    $um_fields = array();  
    foreach ($um_field_pos as $nlpKey => $column) {
      $um_fields[$nlpKey] = $um_report[$column];
    }
    
    
    if(empty($um_fields['recorded'])) {
      $um_fields['recorded'] = '2018-01-01';
    }
    
    $um_idate = $um_fields['cdate'];
    $um_udate = strtotime($um_idate);
    $um_fields['cdate'] = date('Y-m-d',$um_udate);
    //voterdb_debug_msg('fields', $um_fields);
    
    $batchLimit = $reportsObj->insertNlReports($um_fields);
    

    if ($batchLimit) {
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
      $um_loop_time = voterdb_timer('end',$um_loopstart);
      if($um_loop_time > $um_context['sandbox']['loop-max']) {
        $um_context['sandbox']['loop-max'] = $um_loop_time;
      }
      break;
    }
  } while (TRUE);
  // Finish the batch if we are done.
  if($um_done) {
    
    $reportsObj->flushNlReports();
    
    $um_context['finished'] = 1;
    $um_context['results']['rcds'] = $um_rcd_cnt;
    $um_context['results']['bad-rcds'] = $um_bad_rcds;
    $um_upload_time = voterdb_timer('end',$um_context['sandbox']['upload-start']);
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