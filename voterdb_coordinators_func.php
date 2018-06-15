<?php
/*
 * Name:  voterdb_candidates_func.php               V4.0 11/30/17
 */
define('CL_EDIT', '50');
define('CL_DELETE', '50');
define('CL_NAME', '150');
define('CL_EMAIL', '150');
define('CL_PHONE', '100');
define('CL_HD', '20');
define('CL_PCT', '260');

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_hd_list
 * 
 * Construct a form entry for selecting a house district.  The list
 * is all the HDs available in the county.
 * 
 * @param type $hl_county_index  -  index to the name.
 * @return int - array of HD numbers, in numerical order or FALSE.
 */
function voterdb_build_hd_entry(&$form,$form_state) {
  $he_hd_array = $form_state['voterdb']['hd_array'];
  $form['scope']['hd'] = array(
    '#type' => 'select',
    '#title' => t('Select the HD of the new HD coordinator.'),
    '#options' => array($he_hd_array),
    );
  return;
}

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_pct_entry
 * 
 * Create a form for selection of a list precincts.   
 * 
 * @param type $form_state
 * @param type $tc_pct_name - text string with the precinct number.
 * @return boolean
 */
function voterdb_pct_entry(&$form) {
  $form['scope']['pct'] = array (
    '#title' => 'Enter a list of precincts, separated by commas',
    '#size' => 30,
    '#maxlength' => 60,
    '#type' => 'textfield',
    '#required' => TRUE,
  );
  return ;
}

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_scope_callback
 * 
 * AJAX call back for the selection of the HD
 * 
 * @param type $form
 * @param type $form_state
 * @return type
 */
function voterdb_scope_callback ($form,$form_state) {
  //Rebuild the form to list the NLs in the precinct after the precinct is
  // selected.
  return $form['scope'];
}

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_build_name_entry
 * 
 * Build form entries for name, email and phone number for a new coordinator.
 * 
 * @param type $form
 * @return type
 */
function voterdb_build_name_entry(&$form) {
  $form['scope']['info'] = array(
    '#title' => 'Coordinator\'s contact info',
    '#prefix' => " \n".'<div id="info-div" style="width:400px;">'." \n",
    '#suffix' => " \n".'</div>'." \n",
    '#type' => 'fieldset',
  );
   // First name title
  $form['scope']['info']['fname'] = array (
    '#type' => 'markup',
    '#prefix' => '<table style="width:380px;">
      <tbody style="border-collapse: collapse; border-style: hidden;">
      <tr><td style="text-align:right"><b>',
    '#suffix' => '</b></td>',
    '#markup' => 'First Name:',
  );
  // First name data entry field
  $form['scope']['info']['cfname'] = array (
    '#prefix' => '<td>',
    '#suffix' => '</td></tr>',
    '#size' => 20,
    '#maxlength' => 30,
    '#type' => 'textfield',
    '#required' => TRUE,
  );
  // Last name title.
  $form['scope']['info']['lname'] = array (
    '#type' => 'markup',
    '#prefix' => '<tr><td style="text-align:right"><b>',
    '#suffix' => '</b></td>',
    '#markup' => 'Last Name:',
  );
  // Last name data entry field.
  $form['scope']['info']['clname'] = array (
    '#prefix' => '<td>',
    '#suffix' => '</td></tr>',
    '#size' => 20,
    '#maxlength' => 30,
    '#type' => 'textfield',
    '#required' => TRUE,
  );
  // Email.
  $form['scope']['info']['email'] = array (
    '#type' => 'markup',
    '#prefix' => '<tr><td style="text-align:right"><b>',
    '#suffix' => '</b></td>',
    '#markup' => 'Email:',
  );
  $form['scope']['info']['cemail'] = array (
    '#prefix' => '<td>',
    '#suffix' => '</td></tr>',
    '#size' => 30,
    '#maxlength' => 60,
    '#type' => 'textfield',
    '#required' => TRUE,
  );
  // Phone number.
  $form['scope']['info']['phone'] = array (
    '#type' => 'markup',
    '#prefix' => '<tr><td style="text-align:right"><b>',
    '#suffix' => '</b></td>',
    '#markup' => 'Phone:',
  );
  $form['scope']['info']['phonenumber'] = array (
    '#prefix' => '<td>',
    '#suffix' => '</td></tr></tbody></table>',
    '#size' => 20,
    '#maxlength' => 20,
    '#type' => 'textfield',
    '#required' => TRUE,
  );
  // Submit button
  $form['scope']['submit-co'] = array(
    '#prefix' => '<tr><td></td><td>',
    '#suffix' => '</td></tr></tbody></table><br>',
    '#type' => 'submit',
    '#value' => 'Add this Coordinator >>'
  );
  return;
}

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_build_scope
 * 
 * Build the form entries for selecting the scope of the role of the new
 * coordinator.  This uses and AJAX function to change the other entries 
 * based on scope.
 * 
 * @param type $form
 * @param array $form_state
 * @return type
 */
function voterdb_build_scope(&$form,&$form_state) {
  $fv_scope = $form_state['voterdb']['scope'];
  $fv_options = array('Select scope','County','House District','Group of Precincts');
  $form_state['voterdb']['scope-options'] = $fv_options;
  $form['scope-select'] = array(
    '#type' => 'select',
    '#title' => t('Select the scope.'),
    '#options' => $fv_options,
    '#ajax' => array (
        'callback' => 'voterdb_scope_callback',
        'wrapper' => 'scope-wrapper',
        )
    );
  //Build a wrapper around the part that will change with input.
  $form['scope'] = array(
    '#prefix' => '<div id="scope-wrapper">',
    '#suffix' => '</div>',
    '#type' => 'fieldset',
    '#attributes' => array('style' => array('background-image: none; border: 0px; width: 550px; padding:0px; margin:0px; background-color: rgb(255,255,255);'), ),
   );
   switch ($fv_scope) {
     case 'Group of Precincts':
       voterdb_pct_entry($form);
     case 'House District':
       voterdb_build_hd_entry($form,$form_state);
     case 'County':
       voterdb_build_name_entry($form);
   }
  return;
} 

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_build_coordinator_list
 * 
 * @param type $form
 * @param type $form_state
 * @return type
 */
function voterdb_build_coordinator_list(&$form,&$form_state) {
  $cl_county = $form_state['voterdb']['county'];
  // Get all the coordinators defined for this county.
  db_set_active('nlp_voterdb');
  $cl_tselect = "SELECT * FROM {".DB_COORDINATOR_TBL."} WHERE  ".
    CR_COUNTY. " = :grp ";
  $cl_targs = array(
    ':grp' => $cl_county,);
  $cl_result = db_query($cl_tselect,$cl_targs);
  db_set_active('default');
  $cl_coordinators = $cl_result->fetchAll(PDO::FETCH_ASSOC);
  // Check if we have any existing coordinators.
    if(!$cl_coordinators) {
      $form['scope']['note3'] = array (
      '#type' => 'markup',
      '#markup' => '<p>There are no coordinators assigned as yet.</p>',
      );
    return;
  }
  // We have at least one coordinator defined.
  $form['scope']['cform'] = array(
    '#prefix' => " \n".'<div id="cform-div">'." \n",
    '#suffix' => " \n".'</div>'." \n",
    '#type' => 'fieldset',
    '#attributes' => array(
      'style' => array(
        'background-image: none; border: 0px; padding:0px; margin:0px; '
        . 'background-color: rgb(255,255,255);'), ),
  );
  // Start the table.
  $cl_table = CL_EDIT+CL_DELETE+CL_NAME+CL_EMAIL+CL_PHONE+CL_HD+CL_PCT+17;
  $form['scope']['cform']['table_start'] = array(
    '#prefix' => " \n".'<style type="text/css"> textarea { resize: none;} </style>',
    '#markup' => " \n".'<!-- Coordinator List Table -->'." \n".'<table border="1" style="font-size:x-small; padding:0px; '
    . 'border-color:#d3e7f4; border-width:1px; width:'.$cl_table.'px;">',
  );  
  // Create the header.
  $cl_header_row = " \n ".'<th style="width:'.CL_EDIT.'px;"></th>';
  $cl_header_row .= " \n ".'<th style="width:'.CL_DELETE.'px;"></th>';
  $cl_header_row .= " \n ".'<th style="width:'.CL_NAME.'px;">Name</th>';
  $cl_header_row .= " \n ".'<th style="width:'.CL_EMAIL.'px;">Email</th>';
  $cl_header_row .= " \n ".'<th style="width:'.CL_PHONE.'px;">Phone</th>';
  $cl_header_row .= " \n ".'<th style="width:'.CL_HD.'px;">HD</th>';
  $cl_header_row .= " \n ".'<th style="width:'.CL_PCT.'px;">Pct list</th>';
  $form['scope']['cform']['header_row'] = array(
    '#markup' => " \n".'<thead>'.
    " \n".'<tr>'.$cl_header_row." \n".'</tr>'." \n".'</thead>',
  );
  // Start the body.
  $form['scope']['cform']['body-start'] = array(
    '#markup' => " \n".'<tbody>',
  );
  $cl_odd = TRUE;
  foreach ($cl_coordinators as $cl_coordinator) {
    $cl_cindex = $cl_coordinator[CR_CINDEX];
    // Build the array for edit or delete.
    $form_state['voterdb']['coordinators'][$cl_cindex] = $cl_coordinator;
    $cl_fname = $cl_coordinator[CR_FIRSTNAME];
    $cl_lname = $cl_coordinator[CR_LASTNAME];
    $cl_email = $cl_coordinator[CR_EMAIL];
    $cl_phone = $cl_coordinator[CR_PHONE];
    $cl_hd = ($cl_coordinator[CR_HD]!=0)?$cl_coordinator[CR_HD]:'ALL';
    $cl_pcl_list = '';
    $cl_partial = $cl_coordinator[CR_PARTIAL];
    if ($cl_partial) {
      db_set_active('nlp_voterdb');
      $cl_pselect = "SELECT * FROM {".DB_PCT_COORDINATOR_TBL."} WHERE  ".
        PC_CINDEX. " = :index ";
      $cl_pargs = array(
        ':index' => $cl_cindex,);
      $cl_presult = db_query($cl_pselect,$cl_pargs);
      db_set_active('default');
      $cl_pcts = $cl_presult->fetchAll(PDO::FETCH_ASSOC);
      foreach ($cl_pcts as $cl_pct) {
        $cl_pl[] = $cl_pct[PC_PCT];
      }
      $cl_pcl_list = implode(',', $cl_pl);
    }
    $cl_name = $cl_lname.', '.$cl_fname;
    $cl_class = ($cl_odd)?'<tr class="odd">':'<tr class="even">';
    $cl_odd = !$cl_odd;
    // Use the Drupal class for odd/even table rows and start the row.
    $form['scope']['cform']["row-start-$cl_cindex"] = array(
      '#markup' => " \n".$cl_class.'<!-- '.$cl_name.' row -->',
      );
    // First cell is the edit link.
    $form['scope']['cform']["submit-$cl_cindex-0"] = array(
      '#name' => "submit-$cl_cindex-0",
      '#prefix' => '<td class="td-de">',
      '#suffix' => '</td>',
      '#type' => 'submit',
      '#value' => 'edit',
    );
    // Second cell is the delete link.
    $form['scope']['cform']["submit-$cl_cindex-1"] = array(
      '#name' => "submit-$cl_cindex-0",
      '#prefix' => '<td class="td-de">',
      '#suffix' => '</td>',
      '#type' => 'submit',
      '#value' => 'delete'
    );
    // Third cell is the coordinator's name.
    $form['scope']['cform']["cell-$cl_cindex-2"] = array(
      '#markup' => " \n ".'<td class="td-de">'.$cl_name.'</td>',
      );
    // Fourth cell is the email.
    $form['scope']['cform']["cell-$cl_cindex-3"] = array(
      '#markup' => " \n ".'<td class="td-de">'.$cl_email.'</td>',
      );
    // Fifth cell is the phone number.
    $form['scope']['cform']["cell-$cl_cindex-4"] = array(
      '#markup' => " \n ".'<td class="td-de">'.$cl_phone.'</td>',
      );
    // Sixth cell is the house district.
    $form['scope']['cform']["cell-$cl_cindex-5"] = array(
      '#markup' => " \n ".'<td class="td-de">'.$cl_hd.'</td>',
      );
    // Seventh cell is the precinct list.
    $form['scope']['cform']["cell-$cl_cindex-6"] = array(
      '#markup' => " \n ".'<td class="td-de">'.$cl_pcl_list.'</td>',
      );
    // End the row.
    $form['scope']['cform']["row-end-$cl_cindex"] = array(
      '#markup' => " \n".'</tr>',
      );
  }
  // End of the table.
  $form['scope']['cform']['table_end'] = array(
    '#markup' => " \n".'</tbody>'." \n".'</table>'." \n".'<!-- End of Coordinator List Table -->'." \n",
    );
  return;
}