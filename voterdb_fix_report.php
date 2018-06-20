<?php
/*
 * Name: voterdb_fix_report.php  V4.1 6/1/18
 *
 */
require_once "voterdb_constants_log_tbl.php";
require_once "voterdb_constants_rr_tbl.php";
require_once "voterdb_constants_voter_tbl.php";
require_once "voterdb_constants_nls_tbl.php";
require_once "voterdb_group.php";
require_once "voterdb_fix_report_func.php";
require_once "voterdb_fix_report_func2.php";
require_once "voterdb_banner.php";
require_once "voterdb_track.php";
require_once "voterdb_debug.php";
require_once "voterdb_class_button.php";
require_once "voterdb_class_turfs.php";

use Drupal\voterdb\NlpButton;

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_fix_report_form
 *
 * Using multipass forms, select an  NL, an election cycle, and  a turf (when 
 * more than one turf exists for this NL). Process the reports to be 
 * deactivated and record the information in a MySQL table.
 *
 * @param type $form_id
 * @param type $form_state
 * 
 */
function voterdb_fix_report_form($form_id, &$form_state) {
  $fr_button_obj = new NlpButton();
  $fr_button_obj->setStyle();
  // Verify we know the group.
  if (!isset($form_state['voterdb']['reenter'])) {
    if(!voterdb_get_group($form_state)) {return;}
    $form_state['voterdb']['pass'] = 'select-nl';
    $form_state['voterdb']['reenter'] = TRUE;
    // Associative array for saving current defaults for ajax.
    $fv_iarray = array('hd','pct','nl','cycle','turf');
    foreach ($fv_iarray as $fv_key) {
      $fv_vals[$fv_key] = 0;
    }
    $form_state['voterdb']['saved']= $fv_vals;
  }
  $fr_county = $form_state['voterdb']['county'];
  $fr_banner = voterdb_build_banner ($fr_county);
  $form['note'] = array (
    '#type' => 'markup',
    '#markup' => $fr_banner
  ); 
  $pass = $form_state['voterdb']['pass'];
  switch ($pass) {
/* * * * * * * * * * * * *
 * Now select an NL and a Cycle.
 */
    case 'select-nl':
      $form = voterdb_build_nl_select($form_state); // func.
      break;
/* * * * * * * * * * * * *
 * This NL has more than one turf so pick one.
 */
    case 'turf-select':
      $form  = voterdb_build_turf_select($form_state);  // func2.
      break;
/* * * * * * * * * * * * *
 * Display list of reports.
 * 
 * We have a turf selected so display the list of voters in this turf for
 * data entry. The previous report information is also displayed if it exists.
 * And, the goals for the NL recruitment are displayed for the both the HD
 * and the county.
 */
    case 'display-reports':
      $form  = voterdb_build_report_list($form,$form_state);  // func.
      break;
  }
  $form['done'] = array(
    '#markup' => '<p><a href="nlpadmin?County='.$fr_county.'" class="button ">Return to Admin page >></a></p>',
  );
  return $form;
}

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_fix_report_form_submit
 *
 * Process the form submitted.
 *
 * @param type $form
 * @param type $form_state
 */
function voterdb_fix_report_form_submit($form, &$form_state) {
  $page = $form_state['voterdb']['pass'];
  switch ($page) {
/* * * * * * * * * * * * *
 * The NL is done with this entry, now go back and refresh the page so
 * the status changes are seen.
 */
    case 'select-nl':
      $form_state['voterdb']['reenter'] = TRUE;
      $fv_multi = $form_state['voterdb']['turf-select'];
      $fv_selected_mcid = $form_state['voterdb']['selected']['nl'];
      if(isset($fv_multi[$fv_selected_mcid])) {
        $form_state['voterdb']['pass'] = 'turf-select';
      } else {
        $form_state['voterdb']['pass'] = 'display-reports';
      }
      $form_state['rebuild'] = TRUE;  // form_state will persist.
      break;
/* * * * * * * * * * * * *
 * There was more than one turf and one has been selected.
 */      
    case 'turf-select':   
      $form_state['rebuild'] = TRUE;  // form_state will persist.
      $form_state['voterdb']['pass'] = 'display-reports';
      break;
/* * * * * * * * * * * * *
 * The NL selected something in the data entry page.
 */
    case 'display-reports':
      voterdb_set_active_status($form_state); // func.
      $form_state['voterdb']['reenter'] = TRUE;
      $form_state['rebuild'] = TRUE;  // form_state will persist
      $form_state['voterdb']['pass'] = 'select-nl';
       // Identify the user who is making the corrections.
      if(!isset($form_state['voterdb']['admin-fname'])) {
        $fr_user = voterdb_get_user(); //func.
        $form_state['voterdb']['admin-fname'] = $fr_user['fname'];
        $form_state['voterdb']['admin-lname'] = $fr_user['lname'];
        $fr_info = $fr_user['fname'].' '.$fr_user['lname'];
        voterdb_login_tracking('fix',$form_state['voterdb']['county'], 'NL report fix',$fr_info);
      }
      break;
  }
}
