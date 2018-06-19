<?php
/*
 * Name: voterdb_dates.php   V3.0 12/4/16
 * This include file contains the code to to manage date strings and the
 * indexes needed to retreive them.
 */
require_once "voterdb_constants_date_tbl.php"; // Matchback

require_once "voterdb_debug.php";

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_get_brdates
 * 
 * The Date table contains a row for each possible date that a ballot can
 * be returned for an election.  This date is typically limited to 30 days 
 * before the election.  The index is used to recover the string when needed
 * for display.    
 * 
 * @return: an associate array that points the index to a date in text string
 *          for display.
 */
function voterdb_get_brindexes() {
  // Create the array that relates county name to county index.
  //voterdb_debug_msg('brdates: ', ''); 
  db_set_active('nlp_voterdb');
  $gd_dselect = "SELECT * FROM {".DB_DATE_TBL."} WHERE  1";
  $gd_dates = db_query($gd_dselect);
  
  $gd_date_indexes = array();
  do {
    $gd_date_rec = $gd_dates->fetchAssoc();
    if(!$gd_date_rec) {break;}
    $gd_date_index = $gd_date_rec[DA_INDEX];
    $gd_date_name = $gd_date_rec[DA_DATE];
    $gd_date_indexes[$gd_date_name] = $gd_date_index;
  } while (TRUE);
  db_set_active('default');

  //voterdb_debug_msg('Date Indexes: ', $gd_date_indexes);
return $gd_date_indexes;
}

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_save_brdate_indexes
 * 
 * The date index array contains all the dates in string format that are
 * recorded for ballots actually returned.  The index points to the 
 * string for later retrieval.
 * 
 * @param type $gd_link
 * @param type $gd_date_indexes
 * @return boolean: FALSE if error.
 */
function voterdb_save_brdate_indexes($gd_link,$gd_date_indexes) {
  // Save the array of date strings and the indexes to retreive them.
  foreach ($gd_date_indexes as $gd_date_name => $gd_date_index) {
    db_set_active('nlp_voterdb');
    db_insert(DB_DATE_TBL)
      ->fields(array(
        DA_DATE => $gd_date_name,
        DA_INDEX => $gd_date_index,
      ))
      ->execute();
  }
  db_set_active('default');
return TRUE;
}

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_get_brdates
 * 
 * The Date table contains a row for each possible date that a ballot can
 * be returned for an election.  This date is typically limited to 30 days 
 * before the election.  The index is used to recover the string when needed
 * for display.    
 * 
 * @return: an associate array that points the index to a date in text string
 *          for display.
 */
function voterdb_get_brdates() {
  // Create the array that relates county name to county index.
  //voterdb_debug_msg('brdates: ', ''); 
  db_set_active('nlp_voterdb');
  $gd_dselect = "SELECT * FROM {".DB_DATE_TBL."} WHERE  1";
  $gd_dates = db_query($gd_dselect);
  
  $gd_date_indexes = array();
  do {
    $gd_date_rec = $gd_dates->fetchAssoc();
    if(!$gd_date_rec) {break;}
    $gd_date_index = $gd_date_rec[DA_INDEX];
    $gd_date_name = $gd_date_rec[DA_DATE];
    $gd_date_indexes[$gd_date_index] = $gd_date_name;
  } while (TRUE);
  db_set_active('default');

  //voterdb_debug_msg('Date Indexes: ', $gd_date_indexes);
return $gd_date_indexes;
}


/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_get_brdate_index
 * 
 * As unique ballot return dates are encounted, they are added to the array
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