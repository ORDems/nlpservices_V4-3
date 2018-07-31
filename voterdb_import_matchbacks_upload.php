<?php
/*
 * Name: voterdb_import_matchbacks_upload.php   V4.3 7/27/18
 * This include file contains the code to process the ballot received status
 * from the VAN.
 */
//require_once "voterdb_constants_mb_tbl.php";
//require_once "voterdb_constants_date_tbl.php";
require_once "voterdb_debug.php";

require_once "voterdb_class_matchback.php";

define ('READCOUNTLIMIT',12000);

use Drupal\voterdb\NlpMatchback;


/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_upload_matchback
 * 
 * Read the provided file and save the Dems.
 * 
 * @param type $bu_arg
 * @param type $bu_context
 * @return FALSE if error.
 */
function voterdb_import_matchbacks_upload($bu_arg,&$bu_context) {

  $matchbackObj = new NlpMatchback();
  //voterdb_debug_msg('matchbackobj', $matchbackObj);
  $matchbackObj->getBrDates();
  //voterdb_debug_msg('dates', $matchbackObj->dates);

  $bu_br_uri = $bu_arg['uri'];
  $bu_column = $bu_arg['field_pos'];

  //$bu_last_index =  voterdb_br_last_index($bu_date_indexes);
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

  $bu_read_cnt = 0;
  $bu_loop_start = voterdb_timer('start',0);


  $bu_done = TRUE;
  do {
    $bu_voter_raw = fgets($bu_voter_fh);
    if (!$bu_voter_raw) {break;} //We've processed the last voter for this upload.
    $bu_read_cnt++;
    //if($bu_read_cnt>10) {break;}
    $bu_voter_record = trim(strip_tags(htmlentities(stripslashes($bu_voter_raw))));
    // Parse the voter record into the various fields.
    $bu_voter_info = explode("\t", $bu_voter_record);
    // Get the county name, party, and ballot received date (if set) for this voter.
    $bu_vcounty = trim($bu_voter_info[$bu_column['county']]);
    if($bu_vcounty == "Hood River") {$bu_vcounty = "Hood_River";}
    // If we have a ballot recieved date for a Dem, schedule for the insert.
    $bu_br = isset($bu_voter_info[$bu_column['ballotReceived']]);
    $batchLimit = FALSE;
    if ($bu_br) {
      $bu_dcnt++;
      $bu_vanid = $bu_voter_info[$bu_column['vanid']];
      // If the record already exists, skip the insert.
      
      $bu_exists = $matchbackObj->getMatchbackExists($bu_vanid);
      //voterdb_debug_msg('exists', $bu_exists);
      //$bu_exists = db_query('SELECT 1 FROM {'.DB_MATCHBACK_TBL. 
      //  '} WHERE '.MT_VANID.' = :vanid', array(':vanid' => $bu_vanid))->fetchField();
      if(!$bu_exists) {
       
      // Convert date to index.
      $bu_idate = $bu_voter_info[$bu_column['ballotReceived']];
      $bu_udate = strtotime($bu_idate);  // Convert US date to time.
      $bu_cdate = date('Y-m-d',$bu_udate);  // Convert to ISO date.
      $bu_date_index = $matchbackObj->getBrDateIndex($bu_cdate);
      //voterdb_debug_msg('index', $bu_date_index);
      //$bu_date_index = voterdb_get_brdate_index($bu_cdate, $bu_date_indexes,$bu_last_index);  // dates.
      // Create a record for this ballot received status, and add it to a
      // group until there are 100 records to insert.
      $batchLimit = $matchbackObj->insertMatchbacks($bu_vcounty,$bu_vanid,$bu_date_index);
      //voterdb_debug_msg('limit', $batchLimit);
      }
    }
    // When we have completed 100 inserts of 100 records, return to the 
    // queue to continue processing with a refreshed timer.   It also displays
    // progress to the user.
    if ($batchLimit OR $bu_read_cnt>READCOUNTLIMIT) {
      $bu_read_cnt = 0;
      $bu_done = FALSE;
      $matchbackObj->flushMatchbacks();
      // Remember where we are for the resume of processing the file.
      $bu_seek = ftell($bu_voter_fh);
      $bu_context['sandbox']['seek'] = $bu_seek;
      $bu_context['finished'] = $bu_seek/$bu_filesize;
      //voterdb_debug_msg('seek: '.$bu_seek.' progress: '.$bu_context['finished'], '');
      $bu_context['sandbox']['dcnt'] = $bu_dcnt;
      $bu_loop_time = voterdb_timer('end',$bu_loop_start);
      if($bu_loop_time > $bu_context['sandbox']['loop-max']) {
        $bu_context['sandbox']['loop-max'] = $bu_loop_time;
      }
      break;
    }
  } while (TRUE);

  if($bu_done) {
    // We are done, so insert the last fragment of records.
    $matchbackObj->flushMatchbacks();
    $bu_context['finished'] = 1;
    $bu_context['results']['dcnt'] = $bu_dcnt;
    $bu_upload_time = voterdb_timer('end',$bu_context['sandbox']['upload-start']);
    $bu_context['results']['upload-time'] = $bu_upload_time;
    $bu_context['results']['loop-max'] = $bu_context['sandbox']['loop-max'];
    $bu_context['results']['uri'] = $bu_br_uri;
  }

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
function voterdb_import_matchbacks_finished($br_success, $br_results, $br_operations) {
  $matchbackObj = new NlpMatchback();
  $br_uri = $br_results['uri'];
  drupal_unlink($br_uri);
  if ($br_success) {
    // Save all the new date indexes.

    // Report results.
    $bu_dcnt = $br_results['dcnt'];
    drupal_set_message(t('@count ballots received.', 
      array('@count' => $bu_dcnt)));
    $bu_loop_max = round($br_results['loop-max'], 1);
    $bu_upload_time = round($br_results['upload-time'], 1);
    drupal_set_message(t('Upload time: @upload, Max loop time: @loop.', 
      array('@upload' => $bu_upload_time,'@loop'=>$bu_loop_max)),'status');
    drupal_set_message('The NLP voted status successfully updated.','status');
    
    $matchbackObj = new NlpMatchback();
    $matchbackObj->getBrDates();
    $br_date_indexes = $matchbackObj->dates;
    ksort($br_date_indexes);
    end($br_date_indexes);  
    $bu_date = key($br_date_indexes);
    variable_set('voterdb_br_date',$bu_date);
  }
  else {
    drupal_set_message(t('Opps, an error occurred.'),'error');
  }
  watchdog('import matchbacks', 'Import of matchbacks has finished');
}