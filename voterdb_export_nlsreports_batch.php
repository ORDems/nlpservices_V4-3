<?php
/*
 * Name: voterdb_export_nlsreports_batch.php   V4.3   7/29/18
 * This include file contains the code to export voter contact reports by
 * NLs.  It creates a file that the browser can download to an admin's PC.
 */
 
require_once "voterdb_class_nls.php";
require_once "voterdb_class_nlreports_nlp.php";
require_once "voterdb_debug.php";

use Drupal\voterdb\NlpNls;
use Drupal\voterdb\NlpReports;

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_export_nlsreports_batch
 * 
 * Query the databasse and write reconds to a file.
 * 
 * @param type $form_state
 * @return  FALSE if error.
 */
function voterdb_export_nlsreports_batch($en_arg,&$en_context) {
  // Retrieve the values determined when we validated the form submittal
  $en_nlreports_uri = $en_arg['uri'];
  $en_col_names = $en_arg['col_names'];
  $en_num_rows = $en_arg['num_rows'];

  // Open the input file and position for writing at the end.
  $en_rpt_fh = fopen($en_nlreports_uri, "a");
  if (!$en_rpt_fh) {
    watchdog('voterdb_export_restore_batch', 'File open error: @name', 
      array('@name' => $en_nlreports_uri,),WATCHDOG_DEBUG);
    $en_context['finished'] = TRUE;
    return;
  }
  $en_context['finished'] = 0;
  if(empty($en_context['sandbox']['next_record'])) {
    $en_next_record = 0;
  } else {
    // Seek to where we will restart.
    $en_next_record = $en_context['sandbox']['next_record'];
  }
  $nlObj = new NlpNls();
  $nlpreportsObj = new NlpReports();
  $en_result = $nlpreportsObj->selectAllReports($en_next_record);
  // Get the records one at a time.
  $en_rcnt = 0;
  do {
    $en_record_raw = $en_result->fetchAssoc();
    if (!$en_record_raw) {break;}  // Last record processed.
    $en_rcnt++;
    // Get the name of the NL who recorded this report, if we have it.
    $nl = $nlObj->getNlById($en_record_raw['MCID']);
    //$en_record_raw['RecordedVan'] = $en_record_raw['RecordedVan']."\t";
    //$en_record_raw['MiniVanRecorded'] = $en_record_raw['MiniVanRecorded']."\t";
    //$en_record_raw['Cdate'] = $en_record_raw['Cdate']."\t";
    $en_record_raw['nickname'] = $nl['nickname'];
    $en_record_raw['lastName'] = $nl['lastName'];
    // Convert the associative array to tab delimited string.
    $en_record_row = str_replace(array("\n","\r"), '', $en_record_raw);
    $en_string = implode("\t", $en_record_row);
    $en_string .= "\tEOR\n";
    fwrite($en_rpt_fh,$en_string);
  } while (TRUE);
  fclose($en_rpt_fh);
  // Finish the batch if we are done.
  if($en_rcnt != $nlpreportsObj::BATCHLIMIT) {
    // We're done with the last record.
    $en_context['finished'] = 1;
    $en_context['results']['rcds'] = $en_next_record+$en_rcnt;
    $en_context['results']['uri'] = $en_nlreports_uri;
  } else {
    // Not done.
    $en_next_record += $nlpreportsObj::BATCHLIMIT;
    $en_context['sandbox']['next_record'] = $en_next_record;
    $en_percent = $en_next_record/$en_num_rows;
    if($en_percent == 1) {
      $en_percent = .999;
    }
    $en_context['finished'] = $en_percent;
  }
  db_set_active('default');
  return TRUE;
}

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_restore_nlsreports_finished
 * 
 * A nice report when we are done.
 * 
 * @param type $en_success
 * @param type $en_results
 * @param type $en_operations
 */
function voterdb_export_nlsreports_finished($en_success, $en_results, $en_operations) {
  if ($en_success) {
    $en_rcd_cnt = $en_results['rcds'];
    drupal_set_message(t('@count reports exported. ', 
      array('@count' => $en_rcd_cnt,)));
    drupal_set_message('NL reports successfully exported.','status');
    watchdog('voterdb_export_nlsreports_finished', 'Export of NL reports finished');
  }
  else {
    drupal_set_message(t('Opps, an error occurred.'),'error');
    watchdog('voterdb_export_nlsreports_finished', 'Upload of NL reports failed');
  }
}
