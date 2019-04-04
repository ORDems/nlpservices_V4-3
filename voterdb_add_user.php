<?php
/*
 * Name: voterdb_turf_checkin.php     V4.3  10/13/18
 * This include file contains the code to upload a turf exported from the
 * VAN and add it to the voter database.
 */

require_once "voterdb_constants_nls_tbl.php";
require_once "voterdb_group.php";
require_once "voterdb_banner.php";
require_once "voterdb_debug.php";
require_once "voterdb_class_button.php";
require_once "voterdb_class_paths.php";
require_once "voterdb_class_nls.php";
require_once "voterdb_class_magic_word.php";
require_once "voterdb_class_drupal_users.php";




use Drupal\voterdb\NlpButton;
use Drupal\voterdb\NlpNls;
use Drupal\voterdb\NlpMagicWord;




function voterdb_test_form() {
  $form = drupal_get_form('voterdb_add_user_form');
  return $form;
}




/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_turf_checkin_form
 *
 * Create the form for checking in a turf for an NL.
 * 
 * @return string
 */
function voterdb_add_user_form($form, &$form_state) {
  $fv_button_obj = new NlpButton();
  $fv_button_obj->setStyle();
  if (!isset($form_state['voterdb']['reenter'])) {
    if(!voterdb_get_group($form_state)) {return;}
    $form_state['voterdb']['hd-saved']=$form_state['voterdb']['pct-saved']=0;
    $form_state['voterdb']['turf_file']=$form_state['voterdb']['pdf_file']='';
    $form_state['voterdb']['addr-change']=FALSE;
    if(isset($form_state['voterdb']['Debug'])) {
      variable_set('voterdb_debug',TRUE);
    } else {
      variable_set('voterdb_debug',FALSE);
    }
  }   
  $fv_county = $form_state['voterdb']['county'];
  $fv_hd_saved = $form_state['voterdb']['hd-saved'];
  $fv_pct_saved = $form_state['voterdb']['pct-saved'];

  $nlsObj = new NlpNls();
  $form_state['voterdb']['nlsObj'] = $nlsObj;
  
  // Create the form to display of all the NLs.
  $fv_banner = voterdb_build_banner ($fv_county);
  $form['note'] = array (
    '#type' => 'markup',
    '#markup' => $fv_banner
  );
  // Get the selected HD or 0 if the first build of this page.
  // The current HD is the value last selected just in case it changed.  
  if (!isset($form_state['values']['HD'])) {
    $fv_selected_hd =$fv_previous_hd = 
                           $form_state['voterdb']['PreviousHD'] = $fv_hd_saved;
  } else {
    $fv_selected_hd = $form_state['values']['HD'];
    $fv_previous_hd = $form_state['voterdb']['PreviousHD'];
  }
  // If we have a new HD selected, then the list of Pcts needs to be reset.
  if ($fv_selected_hd != $fv_previous_hd ) {
    $form_state['values']['pct'] = 0;
    $form_state['input']['pct'] = 0;
    $form_state['complete form']['hd-change']['pct']['#input'] = 0;
    $form_state['complete form']['hd-change']['pct']['#value'] = 0;
    $form_state['voterdb']['PreviousHD'] = $fv_selected_hd;
  }
  // Create the list of HD numbers with prospective NLs.
  $fv_hd_options = $nlsObj->getHdList($fv_county);
  //$fv_hd_options = voterdb_hd_list($fv_county);  // func.
  
  //voterdb_debug_msg('hd options', $fv_hd_options);
  if (empty($fv_hd_options)) { 
    drupal_set_message("The prospective NL list has not been uploaded", 
            "status");
    return $form;
  }
  // Create a list of House Districts with prospective NLs.
  // The default is the value last set in case the form is reused.
  // Set the AJAX configuration to rebuild the precinct list if an HD is
  // selected.
  $form_state['voterdb']['hd_options'] = $fv_hd_options;
  $form['nl-select'] = array(
    '#title' => 'Select the Neighborhood Leader',
    '#type' => 'fieldset',
    '#prefix' => '<div style="width:600px;">',
    '#suffix' => '</div>',
  );
  $form['nl-select']['HD'] = array(
      '#type' => 'select',
      '#title' => t('Select a House District'),
      '#options' => $fv_hd_options,
      '#default_value' => $fv_selected_hd,
      '#ajax' => array (
          'callback' => 'voterdb_hd_selected_callback',
          'wrapper' => 'hd-change-wrapper',
      )
  );
  // Put a container around both the Pct and the NL selection, they both
  // reset and haved to be redrawn with a change in the HD.
  $form['nl-select']['hd-change'] = array(
    '#prefix' => '<div id="hd-change-wrapper">',
    '#suffix' => '</div>',
    '#type' => 'fieldset',
    '#attributes' => array('style' => array('background-image: none; border: 0px; width: 550px; padding:0px; margin:0px; background-color: rgb(255,255,255);'), ),
   );
  // Show the list of precincts for the selected HD. 
  // Set the AJAX configuration to build the list of prospective NLs in the
  // selected precinct.

  //$fv_pct_options = voterdb_pct_list($fv_county, $fv_hd_options[$fv_selected_hd]);  // func.
  $fv_pct_options = $nlsObj->getPctList($fv_county,$fv_hd_options[$fv_selected_hd]);
  $form_state['voterdb']['pct_options'] = $fv_pct_options;
  $fv_selected_pct = isset($form_state['values']['pct'])? $form_state['values']['pct']:$fv_pct_saved;
  $form['nl-select']['hd-change']['pct'] = array(
      '#type' => 'select',
      '#title' => t('Select a Precinct Number for HD'). $fv_hd_options[$fv_selected_hd],
      '#prefix' => '<div id="ajax-pct-replace">',
      '#options' => $fv_pct_options,
      '#default_value' => $fv_selected_pct,
      '#ajax' => array(
        'callback' => 'voterdb_pct_selected_callback',
        'wrapper' => 'ajax-nls-replace',
        'effect' => 'fade',
      ),
  );
  // Create the list of known NLs in this precinct for the options list.
  $fv_pct = $fv_pct_options[$fv_selected_pct];
  //$fv_mcid_array = array();
  //$fv_nls_choices = voterdb_nls_list($fv_county,$fv_pct,$fv_mcid_array);  // func.
  
  $fv_nls_choices = $nlsObj->getNlList($fv_county,$fv_pct);
  
  
  //voterdb_debug_msg('nl options', $fv_nls_choices);
  $form_state['voterdb']['mcid_array'] = $fv_nls_choices['mcidArray'];
  $form_state['voterdb']['nls_choices'] = $fv_nls_choices['options'];
  // Offer a set of radio buttons for selection of an NL. 
  $form['nl-select']['hd-change']['nls-select'] = array(
      '#title' => t('Select the NL for the turf checkin'),
      '#type' => 'radios',
      '#default_value' => 0,
      '#prefix' => '<div id="ajax-nls-replace">',
      '#suffix' => '</div></div>',
      '#options' => $fv_nls_choices['options'],
  );
  
  // And, a submit button.
  $form['add_user_submit'] = array(
      '#type' => 'submit',
      '#value' => 'Add an account for the selected user >>',
  );
  $form['done'] = array(
    '#markup' => '<p><a href="nlpadmin?County='.$fv_county.'" class="button ">Return to Admin page >></a></p>',
  );
  return $form;
}

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_turf_checkin_form_validate
 *
 * Validate the form entries.  
 * 1) Verify that the file names are given for both the turf and the walksheet.
 * 2) Verify that the suffix for the turf is txt and the walksheet is pdf.
 * 3) Verify that the turf is a VoteBuilder export with the proper header and
 *    all required fields.
 * 4) Verify that the voters in the turf are all in one precinct.
 *
 * @param type $form
 * @param type $form_state
 * @return boolean
 */
function voterdb_add_user_form_validate($form,&$form_state) {
  $form_state['voterdb']['reenter'] = TRUE;
  $fv_county = $form_state['voterdb']['county'];
  
  return TRUE;
}

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_turf_checkin_form_submit
 *
 * Enter the turf into the MySQL table for voters.  And, save the PDF if
 * submitted so the NL can get it on the website.
 *
 * @param type $form
 * @param type $form_state
 */
function voterdb_turf_checkin_form_submit($form,&$form_state) {
  $form_state['voterdb']['reenter'] = TRUE;
  $form_state['rebuild'] = TRUE;  // form_state will persist.
  
  $tc_success_msg = "A user was selected.";
  drupal_set_message($tc_success_msg,'status');
}
