<?php
/*
 * Name:  voterdb_coordinators_func2.php               V4.3 7/30/18
 */
/** * * * * * functions supported * * * * * *
 * voterdb_edit_coordinator, voterdb_confirm_delete,
 * voterdb_save_co
 */

use Drupal\voterdb\NlpCoordinators;

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_edit_coordinator
 * 
 * Build the form entries for editing the fields of the coordinator's
 * record.  The initial values are the current values in the database.
 * 
 * @return type
 */
function voterdb_edit_coordinator($ec_coordinator) {

  $form_element['eform'] = array(
    '#title' => '<span style="font-weight: bold;">Edit this coordinator\'s contact info</span>',
    '#prefix' => " \n".'<div id="eform-div" style="width:400px;">'." \n",
    '#suffix' => " \n".'</div>'." \n",
    '#type' => 'fieldset',
  );
   // First name title.
  $form_element['eform']['e-fname-t'] = array (
    '#type' => 'markup',
    '#prefix' => '<table style="width:380px;">
      <tbody style="border-collapse: collapse; border-style: hidden;">
      <tr><td style="text-align:right"><b>',
    '#suffix' => '</b></td>',
    '#markup' => 'First Name:',
  );
  // First name data entry field.
  $form_element['eform']['e-fname'] = array (
    '#prefix' => '<td>',
    '#suffix' => '</td></tr>',
    '#size' => 20,
    '#maxlength' => 30,
    '#type' => 'textfield',
    '#default_value' => $ec_coordinator['firstName'],
  );
  // Last name title.
  $form_element['eform']['e-lname-t'] = array (
    '#type' => 'markup',
    '#prefix' => '<tr><td style="text-align:right"><b>',
    '#suffix' => '</b></td>',
    '#markup' => 'Last Name:',
  );
  // Last name data entry field.
  $form_element['eform']['e-lname'] = array (
    '#prefix' => '<td>',
    '#suffix' => '</td></tr>',
    '#size' => 20,
    '#maxlength' => 30,
    '#type' => 'textfield',
    '#default_value' => $ec_coordinator['lastName'],
  );
  // Email.
  $form_element['eform']['e-email-t'] = array (
    '#type' => 'markup',
    '#prefix' => '<tr><td style="text-align:right"><b>',
    '#suffix' => '</b></td>',
    '#markup' => 'Email:',
  );
  $form_element['eform']['e-email'] = array (
    '#prefix' => '<td>',
    '#suffix' => '</td></tr>',
    '#size' => 30,
    '#maxlength' => 60,
    '#type' => 'textfield',
    '#default_value' => $ec_coordinator['email'],
  );
  // Phone number.
  $form_element['eform']['e-phone-t'] = array (
    '#type' => 'markup',
    '#prefix' => '<tr><td style="text-align:right"><b>',
    '#suffix' => '</b></td>',
    '#markup' => 'Phone:',
  );
  $form_element['eform']['e-phone'] = array (
    '#prefix' => '<td>',
    '#suffix' => '</td></tr>',
    '#size' => 20,
    '#maxlength' => 20,
    '#type' => 'textfield',
    '#default_value' => $ec_coordinator['phone'],
  );
  // Submit button.
  $form_element['eform']['e-submit-edit'] = array(
    '#prefix' => '<tr><td></td><td>',
    '#suffix' => '</td></tr></tbody></table>',
    '#type' => 'submit',
    '#value' => 'Update this Coordinator >>'
  );
  return $form_element;
}

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_confirm_delete
 * 
 * Build the form to confirm that the coordinator is to be deleted.
 * 
 * @param type $form
 * @param type $form_state
 * @return type
 */
function voterdb_confirm_delete($ec_coordinator) {
  
  $ec_fname = $ec_coordinator['firstName'];
  $ec_lname = $ec_coordinator['lastName'];
  $ec_email = $ec_coordinator['email'];
  $ec_phone = $ec_coordinator['phone'];
  $ec_co_info = $ec_lname.', '.$ec_fname.' ['.$ec_email.'] - '.$ec_phone;
  
  $form_element['dform'] = array(
    '#title' => '<span style="font-weight: bold;">Delete this coordinator\'s contact info</span>',
    '#prefix' => " \n".'<div id="dform-div" style="width:500px;">'." \n",
    '#suffix' => " \n".'</div>'." \n",
    '#type' => 'fieldset',
  );
  $form_element['dform']['d-note'] = array (
    '#type' => 'markup',
    '#markup' => 'Please confirm that you want to delete this coordinator.',
  );
  $form_element['dform']['d-info'] = array (
    '#type' => 'markup',
    '#markup' => '<br><span style="font-weight: bold;">'.$ec_co_info.'</span>',
  );
  // Yes, delete.
  $form_element['dform']['d-yes'] = array(
    '#prefix' => '<table style="width:200px;">
      <tbody style="border-collapse: collapse; border-style: hidden;"><tr><td>',
    '#suffix' => '</td>',
    '#type' => 'submit',
    '#name' => 'd-yes',
    '#value' => 'Delete'
  );
  // Nope, cancel.
  $form_element['dform']['d-cancel'] = array(
    '#prefix' => '<td>',
    '#suffix' => '</td></tr></tbody></table>',
    '#type' => 'submit',
    '#name' => 'd-cancel',
    '#value' => 'Cancel'
  );
  return $form_element;
}

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_save_co
 * 
 * Add the new coordinator to the database.
 * 
 * @param type $form_state
 */
function voterdb_save_co($form_state) {
  //voterdb_debug_msg('values', $form_state['values']);
  $coordinatorObj = new NlpCoordinators();
  //$fv_scope = $form_state['voterdb']['scope'];
  $fv_scope = $form_state['values']['scope-select'];
  //voterdb_debug_msg('scope', $fv_scope);
  $hd = $pcts = 0;
  // The scope of the role determines what information is needed.
  switch ($fv_scope) {
    // For a coordinator with a list of precincts, the HD and the list have 
    // to be saved.
    case 'Pct':
      $pcts = strip_tags(filter_var($form_state['values']['pct'], FILTER_SANITIZE_STRING));
      $hd = $form_state['values']['hd'];
      $form_state['voterdb']['HD'] = $hd;
      break;
    // For a coordinator owning a whole HD, only the number is needed.
    case 'HD':
      $hd = $form_state['values']['hd'];
      $form_state['voterdb']['HD'] = $hd;
      break;
  }
  // Insert the new coordinator's record into the database.
  $req = array(
      'county' => $form_state['voterdb']['county'],
      'firstName' => $form_state['values']['cfname'],
      'lastName' => $form_state['values']['clname'],
      'email' => $form_state['values']['cemail'],
      'phone' => $form_state['values']['phonenumber'],
      'scope' => $fv_scope,
      'hd' => $hd,
      'partial' => $pcts,
  );
  //voterdb_debug_msg('req', $req);
  $coordinatorObj->createCoordinator($req);
}
