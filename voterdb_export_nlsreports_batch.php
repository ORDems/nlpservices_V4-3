<?php
/*
 * Name: voterdb_export_nlsreports_batch.php   V4.0   2/17/18
 * This include file contains the code to export voter contact reports by
 * NLs.  It creates a file that the browser can download to an admin's PC.
 */
require_once "voterdb_constants_rr_tbl.php";
require_once "voterdb_constants_nls_tbl.php"; 
require_once "voterdb_debug.php";
// Loop limits.

define("EN_BATCH_LMT","1000");


/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_get_nl
 * 
 * Given the MCID, get the nickname and last name of the NL.
 * 
 * @param type $gn_mcid
 * @return assocative array with the nickname and last name.
 */
function voterdb_get_nl($gn_mcid) {
  $gn_name[NH_NICKNAME] = $gn_name[NH_LNAME] = '';
  db_set_active('nlp_voterdb');
  try {
    $gn_nquery = db_select(DB_NLS_TBL, 'n');
    $gn_nquery->fields('n');
    $gn_nquery->addField('n', NH_NICKNAME);
    $gn_nquery->addField('n', NH_LNAME);
    $gn_nquery->condition('n.'.NH_MCID,$gn_mcid);
    $gn_nresult = $gn_nquery->execute();
    }
  catch (Exception $e) {
    db_set_active('default');
    watchdog('voterdb_export_restore_batch', 'select NL error record: @rec', 
          array('@rec' =>  print_r($e->getMessage(), true)),WATCHDOG_DEBUG);
    return $gn_name;
  }
  db_set_active('default');
  $gn_nls_record = $gn_nresult->fetchAssoc();
  if(!empty($gn_nls_record)) {
    $gn_name[NH_NICKNAME] = $gn_nls_record[NH_NICKNAME];
    $gn_name[NH_LNAME] = $gn_nls_record[NH_LNAME];
  }
  return $gn_name;
}


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
 
  db_set_active('nlp_voterdb');
  try {
    $en_query = db_select(DB_NLPRESULTS_TBL, 'r');
    $en_query->fields('r');
    //$en_query->orderBy('r.'.NC_COUNTY);
    //$en_query->orderBy('r.'.NC_CYCLE);
    $en_query->range($en_next_record, EN_BATCH_LMT);
    $en_result = $en_query->execute();
  }
  catch (Exception $e) {
    db_set_active('default');
    watchdog('voterdb_export_restore_batch', 'select error record: @rec', 
          array('@rec' =>  print_r($e->getMessage(), true)),WATCHDOG_DEBUG);
    $en_context['finished'] = TRUE;
    return;
  }
  db_set_active('default');
  
  //watchdog('voterdb_export_restore_batch', 'query: @qry',array('@qry' =>  print_r($en_query, true)),WATCHDOG_DEBUG);
  
  // Get the records one at a time.
  $en_rcnt = 0;
  do {
    
    
    $en_record_raw = $en_result->fetchAssoc();
    if (!$en_record_raw) {break;}  // Last record processed.
    $en_rcnt++;
    // Get the name of the NL who recorded this report, if we have it.
    $en_name = voterdb_get_nl($en_record_raw[NC_MCID]);
    $en_record_raw[NH_NICKNAME] = $en_name[NH_NICKNAME];
    $en_record_raw[NH_LNAME] = $en_name[NH_LNAME];
    // Convert the associative array to tab delimited string.
    $en_record_row = str_replace(array("\n","\r"), '', $en_record_raw);
    $en_string = implode("\t", $en_record_row);
    $en_string .= "\tEOR\n";
    fwrite($en_rpt_fh,$en_string);
    
  } while (TRUE);
  
  fclose($en_rpt_fh);
  
  
  //watchdog('voterdb_export_restore_batch', 'record count: @rec', array('@rec' =>  $en_rcnt),WATCHDOG_DEBUG);
  // Finish the batch if we are done.
  if($en_rcnt != EN_BATCH_LMT) {
    // We're done with the last record.
    $en_context['finished'] = 1;
    $en_context['results']['rcds'] = $en_next_record+$en_rcnt;
    $en_context['results']['uri'] = $en_nlreports_uri;
  } else {
    // Not done.
    $en_next_record += EN_BATCH_LMT;
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
