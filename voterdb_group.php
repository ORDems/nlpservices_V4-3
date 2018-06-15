<?php
/*
 * Name: voterdb_group.php   V3.0  12/26/16
 *
 * Verify that the function was invoked with either a county name or a
 * campaign name.  If a county name, it must be one of the known counties
 * where we have set up the list of house districts.
 */
require_once "voterdb_debug.php";
require_once "voterdb_constants_hd_tbl.php";

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_county_hd
 * 
 * Given the text of a county name, the function returns both the 
 * index for the county and the serialized list of HD numbers in the 
 * county.
 * 
 * @param type $de_county - County name
 * @return string - string of HD numbers
 */
function voterdb_county_hd($ch_county) {

  // Get all the HD numbers for this county.
  db_set_active('nlp_voterdb');
  $ch_hdselect = "SELECT * FROM {".DB_HD_TBL."} WHERE ".HD_COUNTY."= :county";
  $ch_cntyindx = array(':county'=>$ch_county);
  $ch_hdquery = db_query($ch_hdselect,$ch_cntyindx);
  
  // Create an array of HD numbers.
  $ch_i = 0;
  do {
    $ch_hd_rec = $ch_hdquery->fetchAssoc();
    //voterdb_debug_msg('hd rec: ', $ch_hd_rec, __FILE__, __LINE__);
    if(!$ch_hd_rec) {break;}
    $ch_hd_list[$ch_i++] = $ch_hd_rec[HD_NUMBER];
  } while (TRUE);
  db_set_active('default');
  if(empty($ch_hd_list)) {return FALSE;}

  //voterdb_debug_msg('HD Array: ', $ch_hd_list , __FILE__, __LINE__);
  // Return the array of HD numbers and the county index.
  return $ch_hd_list;
}

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_get_group
 * 
 * This function checks the GET parameter to verify the request is for either
 * a campaign or supported county.
 * 
 * @param type $form_state - safe storage for form variables
 * @return string|boolean - FALSE if the county is not supported
 */
function voterdb_get_group(&$form_state) {
  
  $gg_county = filter_input(INPUT_GET,'County',FILTER_SANITIZE_STRING);
  $gg_options = filter_input(INPUT_GET,'Options',FILTER_SANITIZE_STRING);

  if(!$gg_county) {
    drupal_set_message("County not specified",'error');
    return FALSE;
  } 
  
  if($gg_county == 'ALL') {return FALSE;} // All counties requested, still an error.
  // Check if this is a supported county.

  $gg_hd_array = voterdb_county_hd($gg_county);
  if (!$gg_hd_array) {
    drupal_set_message('County unknown or not specified','error');
    return FALSE;
  }

  $form_state['voterdb']['county'] = $gg_county;
  $form_state['voterdb']['hd_array'] = $gg_hd_array;

  $form_state['voterdb']['debug'] = FALSE;
  if( $gg_options != '') {
    $gg_option_list = explode(",", $gg_options);
    foreach ($gg_option_list as $gg_option) {
      $form_state['voterdb'][$gg_option] = TRUE;   
    }
  }

  //voterdb_debug_msg('form_state: ', $form_state, __FILE__, __LINE__);
  return TRUE;
}