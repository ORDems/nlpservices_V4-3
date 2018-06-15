<?php
/*
 * Name: voterdb_ballots_received_upload.php   V4.0 1/17/17
 * This include file contains the code to process the ballot received status
 * from the VAN.
 */
require_once "voterdb_constants_mb_tbl.php";
require_once "voterdb_constants_date_tbl.php";

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_timer
 * 
 * Calculate the duration of an event.
 *
 * @param type $vt_event - start or stop.
 * @param type $vt_stime - the starting time.
 * @return either the start time or the elapsed time.
 */
function voterdb_timer($vt_event,$vt_stime) {
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
 * voterdb_get_brdate_index
 * 
 * As unique ballot return dates are encountered, they are added to the array
 * of dates and an index is assigned.  If the date is already know, that index
 * is returned.
 * 
 * @param type $di_date
 * @param type $di_date_indexes
 * @param type $di_last
 * @return int:  an index into the date array for a specific date string.
 */
function voterdb_get_brdate_index($di_date, &$di_date_indexes,&$di_last) {
  if(isset($di_date_indexes[$di_date])) {
    $di_date_index = $di_date_indexes[$di_date];
  } else {
    $di_date_indexes[$di_date] = ++$di_last;
    $di_date_index = $di_last;
  }
  return $di_date_index;
}

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_save_brdate_indexes
 * 
 * The date index array contains all the dates in string format that are
 * recorded for ballots actually returned.  The index points to the 
 * string for later retrieval.
 *
 * @param type $gd_date_indexes
 * @return boolean: FALSE if error.
 */
function voterdb_save_brdate_indexes($gd_date_indexes) {
  // Get existing indexes.
  db_set_active('nlp_voterdb');
  $gd_dselect = "SELECT * FROM {".DB_DATE_TBL."} WHERE  1";
  $gd_dates = db_query($gd_dselect);
  $gd_existing_indexes = array();
  do {
    $gd_date_rec = $gd_dates->fetchAssoc();
    if(!$gd_date_rec) {break;}
    $gd_date_index = $gd_date_rec[DA_INDEX];
    $gd_date_name = $gd_date_rec[DA_DATE];
    $gd_existing_indexes[$gd_date_name] = $gd_date_index;
  } while (TRUE);
  // Save the array of date strings and the indexes to retreive them.
  foreach ($gd_date_indexes as $gd_date_name => $gd_date_index) {
    if (!isset($gd_existing_indexes[$gd_date_name])) {
      db_insert(DB_DATE_TBL)
        ->fields(array(
          DA_DATE => $gd_date_name,
          DA_INDEX => $gd_date_index,
        ))
        ->execute();
    }
  }
  db_set_active('default');
return TRUE;
}

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_br_last_index
 * 
 * Get the largest index in use.
 * 
 * @param type $li_date_indexes
 * @return type
 */
function voterdb_br_last_index($li_date_indexes) {
  $li_last_index = 0;
  foreach ($li_date_indexes as $li_index) {
    if ($li_index > $li_last_index) {
      $li_last_index = $li_index;
    }
  }
  return $li_last_index++;
}

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_upload_matchback
 * 
 * Read the provided file and save the Dems.
 * 
 * @param type $bu_arg
 * @param type $bu_context
 * @return FALSE if error.
 */
function voterdb_ballots_received_upload($bu_arg,&$bu_context) {
  //$brsb = '<pre>'.print_r($bu_context, true).'</pre>';
  //watchdog('voterdb_ballot_received_upload', 'Context: @bri', array('@bri' => $brsb ),WATCHDOG_DEBUG);
  $bu_date_indexes = $bu_arg['date_indexes'];
  if (!empty($bu_context['sandbox']['date_indexes'])) {
    $bu_date_indexes =  $bu_context['sandbox']['date_indexes'];
    $bri = '<pre>'.print_r($bu_date_indexes, true).'</pre>';
    watchdog('voterdb_ballot_received_upload', 'BR Indexes start: @bri', array('@bri' => $bri ),WATCHDOG_DEBUG);
  }
  $bu_br_uri = $bu_arg['uri'];
  $bu_column = $bu_arg['field_pos'];
  $bu_last_index =  voterdb_br_last_index($bu_date_indexes);
  // Open the ballot received file.
  $bu_voter_fh = fopen($bu_br_uri, "r");
  if (!$bu_voter_fh) {
    watchdog('voterdb_ballot_received_upload', 'File open error: @name', array('@name' => $bu_br_uri,),WATCHDOG_DEBUG);
    $bu_context['finished'] = TRUE;
    return;
  }
  $bu_filesize = filesize($bu_br_uri);
  $bu_context['finished'] = 0;
  // Position file at the start or where we left off for the previous batch.
  if(empty($bu_context['sandbox']['seek'])) {
    // Read the header record.
    $bu_voter_raw = fgets($bu_voter_fh);
    $bu_dcnt = 0;
    $bu_context['sandbox']['upload-start'] = voterdb_timer('start',0);
    $bu_context['sandbox']['loop-max'] = 0;
  } else {
    // Seek to where we will restart.
    $bu_seek = $bu_context['sandbox']['seek'];
    fseek($bu_voter_fh, $bu_seek);
    $bu_dcnt = $bu_context['sandbox']['dcnt'];
  }

  // Let indexing happen in the background (much, much faster).
  $bu_transaction = db_transaction();

  $bu_fields = array(MT_VANID,MT_DATE_INDEX,MT_COUNTY);
  $bu_lcnt = $bu_sql_cnt = 0;
  $bu_loop_start = voterdb_timer('start',0);
  $bu_values = array();
  db_set_active('nlp_voterdb');
  $bu_done = TRUE;
  do {
    $bu_voter_raw = fgets($bu_voter_fh);
    if (!$bu_voter_raw) {break;} //We've processed the last voter for this upload.
    $bu_voter_record = trim(strip_tags(htmlentities(stripslashes($bu_voter_raw))));
    // Parse the voter record into the various fields.
    $bu_voter_info = explode("\t", $bu_voter_record);
    // Get the county name, party, and ballot received date (if set) for this voter.
    $bu_vcounty = trim($bu_voter_info[$bu_column[BR_COUNTY]]);
    if($bu_vcounty == "Hood River") {$bu_vcounty = "Hood_River";}
    // If we have a ballot recieved date for a Dem, schedule for the insert.
    $bu_br = isset($bu_voter_info[$bu_column[BR_BALLOT_RECEIVED]]);
    if ($bu_br) {
      $bu_dcnt++;
      $bu_vanid = $bu_voter_info[$bu_column[BR_VANID]];
      // If the record already exists, skip the insert.
      $bu_exists = db_query('SELECT 1 FROM {'.DB_MATCHBACK_TBL. 
        '} WHERE '.MT_VANID.' = :vanid', array(':vanid' => $bu_vanid))->fetchField();
      if($bu_exists) {continue;}
      // Convert date to index.
      $bu_idate = $bu_voter_info[$bu_column[BR_BALLOT_RECEIVED]];
      $bu_udate = strtotime($bu_idate);  // Convert US date to time.
      $bu_cdate = date('Y-m-d',$bu_udate);  // Convert to ISO date.
      $bu_date_index = voterdb_get_brdate_index($bu_cdate, $bu_date_indexes,$bu_last_index);  // dates.
      // Create a record for this ballot received status, and add it to a
      // group until there are 100 records to insert.
      $bu_record = array(
        MT_VANID => $bu_vanid,
        MT_DATE_INDEX => $bu_date_index,
        MT_COUNTY => $bu_vcounty,
      );
      $bu_values[$bu_sql_cnt++] = $bu_record;
      // When we reach 100 records, insert all of them in one query.
      if ($bu_sql_cnt == 100) {
        $bu_sql_cnt = 0;
        $bu_lcnt++;
        $bu_query = db_insert(DB_MATCHBACK_TBL)->fields($bu_fields);
        foreach ($bu_values as $bu_record) {
          $bu_query->values($bu_record);
        }
        $bu_query->execute();
        $bu_values = array();
      }
    }
    // When we have completed 100 inserts of 100 records, return to the 
    // queue to continue processing with a refreshed timer.   It also displays
    // progress to the user.
    if ($bu_lcnt == 100) {
      $bu_lcnt = 0;
      $bu_done = FALSE;
      // Remember where we are for the resume of processing the file.
      $bu_seek = ftell($bu_voter_fh);
      $bu_context['sandbox']['seek'] = $bu_seek;
      $bu_context['finished'] = $bu_seek/$bu_filesize;
      $bu_context['sandbox']['dcnt'] = $bu_dcnt;
      $bu_loop_time = voterdb_timer('end',$bu_loop_start);
      if($bu_loop_time > $bu_context['sandbox']['loop-max']) {
        $bu_context['sandbox']['loop-max'] = $bu_loop_time;
      }
      break;
    }
  } while (TRUE);
  $bu_context['sandbox']['date_indexes'] = $bu_date_indexes;
  if($bu_done) {
    // We are done, so insert the last fragment of records.
    if ($bu_sql_cnt != 0) {
      $bu_query = db_insert(DB_MATCHBACK_TBL)->fields($bu_fields);
      foreach ($bu_values as $bu_record) {
        $bu_query->values($bu_record);
      }
      $bu_query->execute();
    }
    $bu_context['finished'] = 1;
    $bu_context['results']['date_indexes'] = $bu_date_indexes;
    $bu_context['results']['dcnt'] = $bu_dcnt;
    $bu_upload_time = voterdb_timer('end',$bu_context['sandbox']['upload-start']);
    $bu_context['results']['upload-time'] = $bu_upload_time;
    $bu_context['results']['loop-max'] = $bu_context['sandbox']['loop-max'];
    $bu_context['results']['uri'] = $bu_br_uri;
  }
  db_set_active('default');
  return;
}

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_ballots_received_finished
 * 
 * The batch operation is finished.  Report the results.
 * 
 * @param type $br_success
 * @param type $br_results
 * @param type $br_operations
 */
function voterdb_ballots_received_finished($br_success, $br_results, $br_operations) {
  $br_uri = $br_results['uri'];
  drupal_unlink($br_uri);
  if ($br_success) {
    // Save all the new date indexes.
    $br_date_indexes = $br_results['date_indexes'];
    voterdb_save_brdate_indexes($br_date_indexes);
    // Report results.
    $bu_dcnt = $br_results['dcnt'];
    drupal_set_message(t('@count ballots received.', 
      array('@count' => $bu_dcnt)));
    $bu_loop_max = round($br_results['loop-max'], 1);
    $bu_upload_time = round($br_results['upload-time'], 1);
    drupal_set_message(t('Upload time: @upload, Max loop time: @loop.', 
      array('@upload' => $bu_upload_time,'@loop'=>$bu_loop_max)),'status');
    drupal_set_message('The NLP voted status successfully updated.','status');
    ksort($br_date_indexes);
    end($br_date_indexes);  
    $bu_date = key($br_date_indexes);
    variable_set('voterdb_br_date',$bu_date);
  }
  else {
    drupal_set_message(t('Opps, an error occurred.'),'error');
  }
  watchdog('voterdb_ballot_received_finished', 'Upload of ballots received finished');
}