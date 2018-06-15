<?php
/*
 * Name: voterdb_get_county_names.php   V4.0  11/27/17
 *
 */
require_once "voterdb_constants_hd_tbl.php";  
require_once "voterdb_debug.php";

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_get_county_names
 * 
 * 
 * @return string - array of county names.
 */
function voterdb_get_county_names() {

  db_set_active('nlp_voterdb');
  try {
    $ch_hdselect = "SELECT * FROM {".DB_HD_TBL."} WHERE 1";
    $ch_hdquery = db_query($ch_hdselect);
  }
  catch (Exception $e) {
    db_set_active('default');
    voterdb_debug_msg('e', $e , __FILE__, __LINE__);
    return FALSE;
  }
  
  // Create an array of county names.

  do {
    $ch_hd_rec = $ch_hdquery->fetchAssoc();
    if(!$ch_hd_rec) {break;}
    $ch_county_names[$ch_hd_rec[HD_COUNTY]] = $ch_hd_rec[HD_COUNTY];
  } while (TRUE);
  db_set_active('default');
  if(empty($ch_county_names)) {return FALSE;}

  ksort($ch_county_names);
  //voterdb_debug_msg('County Array: ', $ch_county_names , __FILE__, __LINE__);
  // Return the array of HD numbers and the county index.
  return $ch_county_names;
}