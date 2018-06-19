<?php
/*
 * Name:  voterdb_turf_delete.php               V4.1 5/29/18
 */
require_once "voterdb_constants_turf_tbl.php";
require_once "voterdb_constants_voter_tbl.php";
require_once "voterdb_group.php";
require_once "voterdb_nls_status.php";
require_once "voterdb_debug.php";
require_once "voterdb_track.php";
require_once "voterdb_banner.php";
require_once "voterdb_class_button.php";
require_once "voterdb_class_turfs.php";
require_once "voterdb_class_paths.php";

use Drupal\voterdb\NlpButton;
use Drupal\voterdb\NlpTurfs;
use Drupal\voterdb\NlpPaths;


/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_hd_selected_callback
 * 
 * AJAX call back for the selection of the HD
 * 
 * @param type $form
 * @param type $form_state
 * @return type
 */
function voterdb_hd_selected_callback ($form,$form_state) {
  //Rebuild the form to list the NLs in the precinct after the precinct is selected.
  return $form['hd-change'];
}

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_pct_selected_callback
 * 
 * AJAX callback for the selection of an NL to associate with a turf.
 *
 * @return array
 */
function voterdb_pct_selected_callback ($form,$form_state) {
  //Rebuild the form to list the NLs in the precinct after the precinct is selected.
  return $form['hd-change']['turf-select'];
}

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_turf_delete_form
 *
 * Create the form for deleting one or more turfs.
 *
 * @param type $form
 * @param type $form_state
 * @return string
 */
function voterdb_turf_delete_form($form, &$form_state) {
  $fv_button_obj = new NlpButton();
  $fv_button_obj->setStyle();
  if (!isset($form_state['voterdb']['reenter'])) {
    if(!voterdb_get_group($form_state)) {return;}
    $form_state['voterdb']['hd-saved']=$form_state['voterdb']['pct-saved']=0;
    $form_state['voterdb']['reenter'] = TRUE;
  }
  $fv_county = $form_state['voterdb']['county'];
  $fv_hd_saved = $form_state['voterdb']['hd-saved'];
  $fv_pct_saved = $form_state['voterdb']['pct-saved'];
  // Create the banner.
  $fv_banner = voterdb_build_banner ($fv_county);
  $form['note'] = array (
    '#type' => 'markup',
    '#markup' => $fv_banner
  );
  // Request the user select either a HD or a Precinct.
  if (!isset($form_state['values']['HD'])) {
    $fv_selected_hd =$fv_previous_hd =$form_state['voterdb']['PreviousHD'] = $fv_hd_saved;
  } else {
    $fv_selected_hd = $form_state['values']['HD'];
    $fv_previous_hd = $form_state['voterdb']['PreviousHD'];
  }
  // If the user changed the HD, then reset the pct to zero.
  if ($fv_selected_hd != $fv_previous_hd ) {
    $form_state['values']['pct'] = 0;
    $form_state['input']['pct'] = 0;
    $form_state['complete form']['hd-change']['pct']['#input'] = 0;
    $form_state['complete form']['hd-change']['pct']['#value'] = 0;
    $form_state['voterdb']['PreviousHD'] = $fv_selected_hd;
  }
  /* 
  $form['nl-select'] = array(
    '#title' => 'Select a turf to delete',
    '#type' => 'fieldset',
    '#prefix' => '<div style="width:600px;">',
    '#suffix' => '</div>',
  );
   * 
   */
  
  // Get the list of HDs with existing turfs.
  $turfsObj = new NlpTurfs();
  $form_state['voterdb']['turfsObj'] = $turfsObj;
  $fv_hd_options = $turfsObj->getTurfHD($fv_county);
  
  if ($fv_hd_options) {
    // House Districts exist.
    $form_state['voterdb']['hd_options'] = $fv_hd_options;
    //$form['nl-select']['HD'] = array(
    $form['HD'] = array(
        '#type' => 'select',
        '#title' => t('House District Number'),
        '#options' => $fv_hd_options,
        '#default_value' => $fv_selected_hd,
        '#ajax' => array (
            'callback' => 'voterdb_hd_selected_callback',
            'wrapper' => 'hd-change-wrapper',
            )
        );
  }
  // Put a container around both the pct and the NL selection, they both
  // reset and have to be redrawn with a change in the HD.
  //$form['nl-select']['hd-change'] = array(
  $form['hd-change'] = array( 
    '#prefix' => '<div id="hd-change-wrapper">',
    '#suffix' => '</div>',
    '#type' => 'fieldset',
    '#attributes' => array('style' => array('background-image: none; border: 0px; width: 550px; padding:0px; margin:0px; background-color: rgb(255,255,255);'), ),
   );
  $fv_selected_pct = (isset($form_state['values']['pct']))? $form_state['values']['pct']:0;
  $fv_selected_hd_name = $fv_hd_options[$fv_selected_hd];

  $fv_pct_options = $turfsObj->getTurfPct($fv_county,$fv_selected_hd_name);
  
  $form_state['voterdb']['pct_options'] = $fv_pct_options;
  if (!$fv_pct_options) {
    drupal_set_message("No turfs exist","status");
  } else {
    // Precincts exist.
    $form_state['voterdb']['pct_options'] = $fv_pct_options;
    //$form['nl-select']['hd-change']['pct'] = array(
    $form['hd-change']['pct'] = array(
        '#type' => 'select',
        '#title' => t('Precinct Number'),
        '#options' => $fv_pct_options,
        '#default_value' => $fv_selected_pct,
        '#ajax' => array(
          'callback' => 'voterdb_pct_selected_callback',
          'wrapper' => 'ajax-turf-replace',
          'effect' => 'fade',
        ),
    );
  }
  // The user selected a precinct, now create the list of turfs

  $form_state['voterdb']['turfsObj'] = $turfsObj;
  $turfReq['county'] = $fv_county;
  $turfReq['pct'] = $fv_pct_options[$fv_selected_pct];
  $turfArray = $turfsObj->getTurfs($turfReq);

  if(!empty($turfArray)) {
    $turfDisplay = $turfsObj->createTurfDisplay($turfArray);
    $form_state['voterdb']['turfs'] = $turfArray;
    $fv_turf_choices = $turfDisplay;
    //$form['nl-select']['hd-change']['turf-select'] = array(
    $form['hd-change']['turf-select'] = array(
          '#title' => t('Select the turf(s) to delete'),
          '#type' => 'checkboxes',
          '#options' => $fv_turf_choices,
          '#prefix' => '<div id="ajax-turf-replace">',
          '#suffix' => '</div>',
          '#description' => t('Remember, this delete is permanent.')
    );
  } else {
    drupal_set_message('There are no turfs for this selection','status');
  }
  // add a submit button to delete the selected turf or turfs.
  $form['submit'] = array(
      '#type' => 'submit',
      '#value' => 'Delete Selected Turf(s) >>',
  );
  $form['done'] = array(
    '#markup' => '<p><a href="nlpadmin?County='.$fv_county.'" class="button ">Return to Admin page >></a></p>',
  );
  return $form;
}

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_turf_delete_form_submit
 *
 * Process request to delete one or more turfs..
 * 
 * @param type $form
 * @param type $form_state
 */
function voterdb_turf_delete_form_submit($form, &$form_state) {
  $form_state['voterdb']['reenter'] = TRUE;
  $form_state['rebuild'] = TRUE;  // form_state will persist
  $form_state['voterdb']['pass'] = 'page_one';
  //voterdb_debug_msg('form state', $form_state);
  $fv_county = $form_state['voterdb']['county'];
  // From the list of turfs in the list, find the ones to be deleted.
  $turf_select = $form_state['input']['turf-select'];
  foreach ($turf_select as $fv_key => $fv_turf_option) {
    if ($fv_turf_option != '') {
      //voterdb_debug_msg('turf option, key: '.$fv_key, $fv_turf_option);
      $fv_turf_delete = $fv_key;
      $fv_turf_choice = $form_state['voterdb']['turfs'][$fv_turf_delete];
      // Clear the assigned flag in each voter record
      $fv_fname = $fv_turf_choice['NLfname'];
      $fv_lname = $fv_turf_choice['NLlname'];
      $fv_tname = $fv_turf_choice['TurfName'];
      $fv_mcid = $fv_turf_choice['MCID'];
      $fv_turf_index = $fv_turf_choice['TurfIndex'];
      
      $turfsObj = $form_state['voterdb']['turfsObj'];
      
      $turf['county'] = $fv_county;
      $turf['turfIndex'] = $fv_turf_index;
      $turf['pathObj'] = new NlpPaths();
      //voterdb_debug_msg('turf', $turf);
      $status = $turfsObj->removeTurf($turf);
      //voterdb_debug_msg('status', $status);
      if(!$status) {
        voterdb_debug_msg("DEBUG",'Turf remove failed');
        return;
      }
      // remove the list of voters in the turf from the grp table.
      db_set_active('nlp_voterdb');
      db_delete(DB_NLPVOTER_GRP_TBL)
        ->condition(NV_COUNTY, $fv_county)
        ->condition(NV_MCID, $fv_mcid)
        ->condition(NV_NLTURFINDEX, $fv_turf_index)
        ->execute();
      db_set_active('default');
      // Clear the turf cut and turf delivered status.
      $fv_nls_status = voterdb_nls_status("GET",$fv_mcid,$fv_county,NULL);
      $fv_nls_status[NN_TURFCUT] =  $fv_nls_status[NN_TURFDELIVERED] = '';
      voterdb_nls_status("PUT",$fv_mcid,$fv_county,$fv_nls_status);
      // Track the delete incase we need to help the turf cutter.
      $fv_info = $fv_fname." ".$fv_lname." ".$fv_tname;
      voterdb_login_tracking('turf',$fv_county,'Deleted turf', $fv_info);
      // successful!
      $fv_status_msg = "$fv_fname, $fv_lname, $fv_tname successfully deleted";
      drupal_set_message($fv_status_msg,'status');
    }
  }
}