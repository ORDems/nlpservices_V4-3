<?php
/*
 * Name:  voterdb_coordinators_func2.php               V4.0 3/26/18
 */
/** * * * * * functions supported * * * * * *
 * voterdb_edit_coordinator, voterdb_confirm_delete,
 * voterdb_save_co, voterdb_save_editted_co, voterdb_delete_co,
 */

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_edit_coordinator
 * 
 * Build the form entries for editing the fields of the coordinator's
 * record.  The initial values are the current values in the database.
 * 
 * @param type $form
 * @param type $form_state
 * @return type
 */
function voterdb_edit_coordinator(&$form,$form_state) {
  $ec_cindex = $form_state['voterdb']['cindex'];
  $ec_coordinator = $form_state['voterdb']['coordinators'][$ec_cindex];
  $ec_fname = $ec_coordinator[CR_FIRSTNAME];
  $ec_lname = $ec_coordinator[CR_LASTNAME];
  $ec_email = $ec_coordinator[CR_EMAIL];
  $ec_phone = $ec_coordinator[CR_PHONE];
  $form['eform'] = array(
    '#title' => '<span style="font-weight: bold;">Edit this coordinator\'s contact info</span>',
    '#prefix' => " \n".'<div id="eform-div" style="width:400px;">'." \n",
    '#suffix' => " \n".'</div>'." \n",
    '#type' => 'fieldset',
  );
   // First name title.
  $form['eform']['e-fname-t'] = array (
    '#type' => 'markup',
    '#prefix' => '<table style="width:380px;">
      <tbody style="border-collapse: collapse; border-style: hidden;">
      <tr><td style="text-align:right"><b>',
    '#suffix' => '</b></td>',
    '#markup' => 'First Name:',
  );
  // First name data entry field.
  $form['eform']['e-fname'] = array (
    '#prefix' => '<td>',
    '#suffix' => '</td></tr>',
    '#size' => 20,
    '#maxlength' => 30,
    '#type' => 'textfield',
    '#default_value' => $ec_fname,
  );
  // Last name title.
  $form['eform']['e-lname-t'] = array (
    '#type' => 'markup',
    '#prefix' => '<tr><td style="text-align:right"><b>',
    '#suffix' => '</b></td>',
    '#markup' => 'Last Name:',
  );
  // Last name data entry field.
  $form['eform']['e-lname'] = array (
    '#prefix' => '<td>',
    '#suffix' => '</td></tr>',
    '#size' => 20,
    '#maxlength' => 30,
    '#type' => 'textfield',
    '#default_value' => $ec_lname,
  );
  // Email.
  $form['eform']['e-email-t'] = array (
    '#type' => 'markup',
    '#prefix' => '<tr><td style="text-align:right"><b>',
    '#suffix' => '</b></td>',
    '#markup' => 'Email:',
  );
  $form['eform']['e-email'] = array (
    '#prefix' => '<td>',
    '#suffix' => '</td></tr>',
    '#size' => 30,
    '#maxlength' => 60,
    '#type' => 'textfield',
    '#default_value' => $ec_email,
  );
  // Phone number.
  $form['eform']['e-phone-t'] = array (
    '#type' => 'markup',
    '#prefix' => '<tr><td style="text-align:right"><b>',
    '#suffix' => '</b></td>',
    '#markup' => 'Phone:',
  );
  $form['eform']['e-phone'] = array (
    '#prefix' => '<td>',
    '#suffix' => '</td></tr>',
    '#size' => 20,
    '#maxlength' => 20,
    '#type' => 'textfield',
    '#default_value' => $ec_phone,
  );
  // Submit button.
  $form['eform']['e-submit-edit'] = array(
    '#prefix' => '<tr><td></td><td>',
    '#suffix' => '</td></tr></tbody></table>',
    '#type' => 'submit',
    '#value' => 'Update this Coordinator >>'
  );
  return;
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
function voterdb_confirm_delete(&$form,$form_state) {
  $ec_cindex = $form_state['voterdb']['cindex'];
  $ec_coordinator = $form_state['voterdb']['coordinators'][$ec_cindex];
  $ec_fname = $ec_coordinator[CR_FIRSTNAME];
  $ec_lname = $ec_coordinator[CR_LASTNAME];
  $ec_email = $ec_coordinator[CR_EMAIL];
  $ec_phone = $ec_coordinator[CR_PHONE];
  $ec_co_info = $ec_lname.', '.$ec_fname.' ['.$ec_email.'] - '.$ec_phone;
  
  $form['dform'] = array(
    '#title' => '<span style="font-weight: bold;">Delete this coordinator\'s contact info</span>',
    '#prefix' => " \n".'<div id="dform-div" style="width:500px;">'." \n",
    '#suffix' => " \n".'</div>'." \n",
    '#type' => 'fieldset',
  );
  $form['dform']['d-note'] = array (
    '#type' => 'markup',
    '#markup' => 'Please confirm that you want to delete this coordinator.',
  );
  $form['dform']['d-info'] = array (
    '#type' => 'markup',
    '#markup' => '<br><span style="font-weight: bold;">'.$ec_co_info.'</span>',
  );
  // Yes, delete.
  $form['dform']['d-yes'] = array(
    '#prefix' => '<table style="width:200px;">
      <tbody style="border-collapse: collapse; border-style: hidden;"><tr><td>',
    '#suffix' => '</td>',
    '#type' => 'submit',
    '#name' => 'd-yes',
    '#value' => 'Delete'
  );
  // Nope, cancel.
  $form['dform']['d-cancel'] = array(
    '#prefix' => '<td>',
    '#suffix' => '</td></tr></tbody></table>',
    '#type' => 'submit',
    '#name' => 'd-cancel',
    '#value' => 'Cancel'
  );
  return;
}

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_save_co
 * 
 * Add the new coordinator to the database.
 * 
 * @param type $form_state
 */
function voterdb_save_co($form_state) {
  $fv_county = $form_state['voterdb']['county'];
  $fv_email = $form_state['values']['cemail'];
  $fv_scope = $form_state['voterdb']['scope'];
  // The scope of the role determines what information is needed.
  switch ($fv_scope) {
    // For a coordinator with a list of precincts, the HD and the list have 
    // to be saved.
    case 'Group of Precincts':
      $form_state['voterdb']['partial'] = TRUE;
      $fv_pct_list = strip_tags(filter_var($form_state['values']['pct'], FILTER_SANITIZE_STRING));
      $fv_pct_array = explode(',', $fv_pct_list);
      $fv_hdi = $form_state['values']['hd'];
      $fv_hd_array = $form_state['voterdb']['hd_array'];
      $fv_hd = $fv_hd_array[$fv_hdi];
      $form_state['voterdb']['HD'] = $fv_hd;
      $fv_scope_value = CS_PCT;
      break;
    // For a coordinator owning a whole HD, only the number is needed.
    case 'House District':
      $fv_hdi = $form_state['values']['hd'];
      $fv_hd_array = $form_state['voterdb']['hd_array'];
      $fv_hd = $fv_hd_array[$fv_hdi];
      $form_state['voterdb']['HD'] = $fv_hd;
      $fv_scope_value = CS_HD;
      break;
    // A county coordinator doesn't need additional information.
    case 'County':
      $fv_scope_value = CS_COUNTY;
      break;
  }
  // Insert the new coordinator's record into the database.
  $fv_partial = ($form_state['voterdb']['partial'])?1:0;
  db_set_active('nlp_voterdb');
  $fv_cindex = db_insert(DB_COORDINATOR_TBL)
    ->fields(array(
      CR_COUNTY => $fv_county,
      CR_FIRSTNAME => $form_state['values']['cfname'],
      CR_LASTNAME => $form_state['values']['clname'],
      CR_EMAIL => $fv_email,
      CR_PHONE => $form_state['values']['phonenumber'],
      CR_SCOPE => $fv_scope_value,
      CR_HD => $form_state['voterdb']['HD'],
      CR_PARTIAL => $fv_partial,
    ))
    ->execute();
  db_set_active('default');
  // If we have a list of precincts for this coordinator, add the list to the
  // database.
  if($fv_scope == 'Group of Precincts') {
    db_set_active('nlp_voterdb');
    foreach ($fv_pct_array as $fv_pct) {
      db_insert(DB_PCT_COORDINATOR_TBL)
        ->fields(array(
          PC_CINDEX => $fv_cindex,
          PC_PCT => $fv_pct,
        ))
        ->execute();
    }
    db_set_active('default');
  }
}

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_save_edited_co
 * 
 * The user edited the fields in the coordinator's record.  Now
 * update the database.
 * 
 * @param type $form_state
 * @return 
 */
function voterdb_save_edited_co($form_state) {
  $se_cindex = $form_state['voterdb']['cindex'];
  db_set_active('nlp_voterdb');
  db_merge(DB_COORDINATOR_TBL)
      ->key(array(CR_CINDEX=> $se_cindex))
      ->fields(array(
        CR_FIRSTNAME => $form_state['values']['e-fname'],
        CR_LASTNAME => $form_state['values']['e-lname'],
        CR_EMAIL => $form_state['values']['e-email'],
        CR_PHONE => $form_state['values']['e-phone'],
      ))
      ->execute();
  db_set_active('default');
  return;
}

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_delete_co
 * 
 * The user confirmed they want a coordinator deleted, so the information is 
 * removed from the database.
 * 
 * @param type $form_state
 * @return 
 */
function voterdb_delete_co($form_state) {
  $dc_type = $form_state['triggering_element']['#type'];
  if ($dc_type != 'submit') {return;}  // Should not happen.
  $dc_name = $form_state['triggering_element']['#name'];
  if($dc_name == 'd-yes') {
    $se_cindex = $form_state['voterdb']['cindex'];
    // Delete the coordinator.
    db_set_active('nlp_voterdb');
    db_delete(DB_COORDINATOR_TBL)
      ->condition(CR_CINDEX, $se_cindex)
      ->execute();
    // Delete any precincts defined for this coordinator, if any.
    db_delete(DB_PCT_COORDINATOR_TBL)
      ->condition(CR_CINDEX, $se_cindex)
      ->execute();
    db_set_active('default');
  }
  return;
}