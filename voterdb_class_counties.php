<?php
/*
 * Name: voterdb_class_counties.php   V4.0 2/16/18
 *
 */
namespace Drupal\voterdb;

require_once "voterdb_constants_hd_tbl.php";  
require_once "voterdb_debug.php";

class NlpCounties {
  

  /** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
  * getCountyNames
  * 
  * 
  * @return string - array of county names.
  */
  function getCountyNames() {
    db_set_active('nlp_voterdb');
    try {
      $ch_hdselect = "SELECT * FROM {".DB_HD_TBL."} WHERE 1";
      $ch_hdquery = db_query($ch_hdselect);
    }
    catch (Exception $e) {
      db_set_active('default');
      voterdb_debug_msg('e', $e->getMessage() );
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
    //voterdb_debug_msg('County Array: ', $ch_county_names );
    // Return the array of HD numbers and the county index.
    return $ch_county_names;
  }
}
