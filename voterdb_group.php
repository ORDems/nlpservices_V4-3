<?php
/*
 * Name: voterdb_group.php   V4.3  8/8/16
 *
 * Verify that the function was invoked with either a county name or a
 * campaign name.  If a county name, it must be one of the known counties
 * where we have set up the list of house districts.
 */

require_once "voterdb_class_counties.php";

use Drupal\voterdb\NlpCounties;

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
  $countiesObj = new NlpCounties();
  $hdNames = $countiesObj->getHdNames($gg_county);
  if (empty($hdNames)) {
    drupal_set_message('County unknown or not specified','error');
    return FALSE;
  }
  $form_state['voterdb']['county'] = strtoupper($gg_county);
  $form_state['voterdb']['hd_array'] = $hdNames;
  $form_state['voterdb']['debug'] = FALSE;
  if( $gg_options != '') {
    $gg_option_list = explode(",", $gg_options);
    foreach ($gg_option_list as $gg_option) {
      $form_state['voterdb'][$gg_option] = TRUE;   
    }
  }
  return TRUE;
}
