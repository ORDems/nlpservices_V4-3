<?php
/*
 * Name:  voterdb_coordinators.php               V4.3 7/29/18
 */
require_once "voterdb_group.php";
require_once "voterdb_debug.php";
require_once "voterdb_track.php";
require_once "voterdb_banner.php";
require_once "voterdb_class_button.php";
require_once "voterdb_class_coordinators_nlp.php";
require_once "voterdb_coordinators_func.php";
require_once "voterdb_coordinators_func2.php";

use Drupal\voterdb\NlpButton;
use Drupal\voterdb\NlpCoordinators;

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_config_coordinators_form
 *
 * Create the multi-part form for managing the list of NLP coordinators.
 *
 * @param type $form
 * @param type $form_state
 * @return string
 */
function voterdb_coordinators_form($form, &$form_state) {
  $fv_button_obj = new NlpButton();
  $fv_button_obj->setStyle();
  // Check that we know the county.
  if (!isset($form_state['voterdb']['reenter'])) {
    if(!voterdb_get_group($form_state)) {return;}
    $form_state['voterdb']['reenter'] = TRUE;
    // Starting state for local variables.
    $form_state['voterdb']['scope'] = "unknown";  
    $form_state['voterdb']['HD'] = 0;
    $form_state['voterdb']['partial'] = FALSE;
    $form_state['voterdb']['func'] = 'add';
    foreach ($form_state['voterdb']['hd_array'] as $hd) {
      $form_state['voterdb']['hdOptions'][$hd] = $hd;
    }
  }
  $dv_tbl_style = '
    .noborder {border-collapse: collapse; border-style: hidden; table-layout:fixed;}
    .nowhite {margin:0px; padding:0px; line-height:100%;}
    .form-item {margin-top:0px; margin-top:0px;}
    .td-de {margin-left:2px; margin-bottom:2px; line-height:100%;}
    .form-type-textfield {margin: 2px 2px;}
    ';
  drupal_add_css($dv_tbl_style, array('type' => 'inline'));
  $fv_county = $form_state['voterdb']['county'];
  $fv_func = $form_state['voterdb']['func'];
  
  // Create the banner.
  $fv_banner = voterdb_build_banner ($fv_county);
  $form['note'] = array (
    '#type' => 'markup',
    '#markup' => $fv_banner
  );
  
  $coodinatorObj = new NlpCoordinators();
  $fv_coordinators = $coodinatorObj->getCoordinators($fv_county);
  $form_state['voterdb']['coordinators'] = $fv_coordinators;
  //voterdb_debug_msg('coordinators', $fv_coordinators);
  
  // Choose which function for this multi-part form.
  switch ($fv_func) {
    // Create the add page and form fields.
    case 'add':
      $form['note1'] = array (
        '#type' => 'markup',
        '#markup' => '<div style="width:800px;"><span style="font-weight: bold;">'
          . '<p>Either you can select the scope of the role for this new coordinator.</p>'
          . '<hr><br></span>',
      );
      $fv_scope = $form_state['voterdb']['scope'];
      $fv_options = array(
          'chose'=>'Select scope',
          'County'=>'County',
          'HD'=>'House District',
          'Pct'=>'Group of Precincts');
      
      $form_state['voterdb']['scope-options'] = $fv_options;
      $form['scope_selection'] = voterdb_build_scope($fv_scope, $fv_options, $form_state['voterdb']['hdOptions']);  // func.
      $form['note2'] = array (
        '#type' => 'markup',
        '#markup' => '<br><hr><span style="font-weight: bold;"><p>Or, you can edit or delete an existing coordinator.</p></span>',
      );
      
      //voterdb_debug_msg('form', $form);
      $form['editing'] = voterdb_build_coordinator_list($fv_county,$fv_coordinators);  // func.
      
      $form['note3'] = array (
        '#type' => 'markup',
        '#markup' => '</div>',
      );
      break;
    // Edit an existing coordinator record.
    case 'edit':
      $ec_cindex = $form_state['voterdb']['cindex'];
      $ec_coordinator = $fv_coordinators[$ec_cindex];
      $form['edit'] = voterdb_edit_coordinator($ec_coordinator);  // func2.
      break;
    // Delete an existing coordinator record.
    case 'delete':
      $ec_cindex = $form_state['voterdb']['cindex'];
      $ec_coordinator = $fv_coordinators[$ec_cindex];
      $form['delete'] = voterdb_confirm_delete($ec_coordinator);  // func2.
      break;
  }
  $form['done'] = array(
    '#markup' => '<p><a href="nlpadmin?County='.$fv_county.'" class="button ">Return to Admin page >></a></p>',
  );
  return $form;
}

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_coordinators_form_validate
 * 
 * Validate the form field entries.
 * 
 * @param type $form
 * @param type $form_state
 * @return type
 */
function voterdb_coordinators_form_validate($form, &$form_state) {
  $fv_func = $form_state['voterdb']['func'];
  // Choose which fields to look for and validate.
  switch ($fv_func) {
    case 'add':
      $fv_type = $form_state['triggering_element']['#type'];
      // If the button type is select, then the scope of the role for the 
      // coordinator was selected.
      if ($fv_type == 'select') {
        $fv_name = $form_state['triggering_element']['#name'];
        // Check that the actual select was scope, should always be true.
        if ($fv_name == 'scope-select') {
          $fv_scope_select = $form_state['triggering_element']['#value'];
          $fv_options = $form_state['triggering_element']['#options'];
          $form_state['voterdb']['scope'] = $fv_options[$fv_scope_select];
        }
        return;
      }
      // Check if the submit buttons was clicked.
      if ($fv_type == 'submit') {
        $fv_value = $form_state['triggering_element']['#value'];
        // For edit and delete, the processing is in the submit function.
        if($fv_value == 'edit' OR $fv_value == 'delete') {return;}
        // Only the submit info button remains.
        // Check that the email address is in the right format.
        $fv_email = $form_state['values']['cemail'];
        $fv_valid = valid_email_address($fv_email);
        if (!$fv_valid) {
          form_set_error('cemail','The email doesn\'t seem correct.');
          return;
        }
      }
      break;
    // Edit fields may have changed.
    case 'edit':
      // Check that the email address is in the right format.
      $fv_email = $form_state['values']['e-email'];
      $fv_valid = valid_email_address($fv_email);
      if (!$fv_valid) {
        form_set_error('e-email','The email doesn\'t seem correct.');
        return;
      }
      break;
    // Process delete in submit.
    case 'delete':
      break;
  }
  return;
}

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_config_coordinators_form_submit
 *
 * Process the request to add, edit or delete a coordinator.
 * 
 * @param type $form
 * @param type $form_state
 */
function voterdb_coordinators_form_submit($form, &$form_state) {
  $form_state['voterdb']['reenter'] = TRUE;
  $form_state['rebuild'] = TRUE;  // form_state will persist
  $fv_func = $form_state['voterdb']['func'];
  // Choose which fields will be processed.
  switch ($fv_func) {
    // We are adding a new coordinator.
    case 'add':
      $fv_type = $form_state['triggering_element']['#type'];
      // If the submit button was clicked, rebuild the form.
      if ($fv_type == 'submit') {
        $fv_value = $form_state['triggering_element']['#value'];
        if($fv_value == 'edit' OR $fv_value == 'delete') {
          $form_state['voterdb']['func'] = $fv_value;
          $fv_id_array = explode('-', $form_state['triggering_element']['#id']);
          $fv_cindex = $fv_id_array[2];
          $form_state['voterdb']['cindex'] = $fv_cindex;
          break;
        }
      }
      // Add the new coordinator.
      voterdb_save_co($form_state);  // func2.
      break;
    // Update the fields for the existing coordinator.
    case 'edit':
      $coordinatorObj = new NlpCoordinators();
      $req = array(
        'cindex' => $form_state['voterdb']['cindex'],
        'firstName' => $form_state['values']['e-fname'],
        'lastName' => $form_state['values']['e-lname'],
        'email' => $form_state['values']['e-email'],
        'phone' => $form_state['values']['e-phone'],
      );
      $coordinatorObj->updateCoordinator($req);
      $form_state['voterdb']['func'] = 'add';
      break;
    // Delete the selected coordinator.
    case 'delete':
      $fv_type = $form_state['triggering_element']['#type'];
      if ($fv_type != 'submit') {return;}  // Should not happen.
      $coordinatorObj = new NlpCoordinators();
      $fv_name = $form_state['triggering_element']['#name'];
      if($fv_name == 'd-yes') {
        $se_cindex = $form_state['voterdb']['cindex'];
        // Delete the coordinator.
        $coordinatorObj->deleteCoordinator($se_cindex);
      }
      $form_state['voterdb']['func'] = 'add';
      break;
  }

  $form_state['voterdb']['scope'] = "unknown";
  $form_state['voterdb']['HD'] = 0;
  $form_state['voterdb']['partial'] = FALSE;  // start over.
  return;
}