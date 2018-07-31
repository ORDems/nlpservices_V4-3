<?php
/*
 * Name:  voterdb_candidates_func.php               V4.3 7/29/18
 */

use Drupal\voterdb\NlpCoordinators;

define('CL_EDIT', '50');
define('CL_DELETE', '50');
define('CL_NAME', '150');
define('CL_EMAIL', '150');
define('CL_PHONE', '100');
define('CL_HD', '20');
define('CL_PCT', '260');


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
  return $form['scope_selection']['scope'];
}

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_build_name_entry
 * 
 * Build form entries for name, email and phone number for a new coordinator.
 * 
 * @return type
 */
function voterdb_build_name_entry() {
  $form_element['info'] = array(
    '#title' => 'Coordinator\'s contact info',
    '#prefix' => " \n".'<div id="info-div" style="width:400px;">'." \n",
    '#suffix' => " \n".'</div>'." \n",
    '#type' => 'fieldset',
  );
   // First name title
  $form_element['info']['fname'] = array (
    '#type' => 'markup',
    '#prefix' => '<table style="width:380px;">
      <tbody style="border-collapse: collapse; border-style: hidden;">
      <tr><td style="text-align:right"><b>',
    '#suffix' => '</b></td>',
    '#markup' => 'First Name:',
  );
  // First name data entry field
  $form_element['info']['cfname'] = array (
    '#prefix' => '<td>',
    '#suffix' => '</td></tr>',
    '#size' => 20,
    '#maxlength' => 30,
    '#type' => 'textfield',
    '#required' => TRUE,
  );
  // Last name title.
  $form_element['info']['lname'] = array (
    '#type' => 'markup',
    '#prefix' => '<tr><td style="text-align:right"><b>',
    '#suffix' => '</b></td>',
    '#markup' => 'Last Name:',
  );
  // Last name data entry field.
  $form_element['info']['clname'] = array (
    '#prefix' => '<td>',
    '#suffix' => '</td></tr>',
    '#size' => 20,
    '#maxlength' => 30,
    '#type' => 'textfield',
    '#required' => TRUE,
  );
  // Email.
  $form_element['info']['email'] = array (
    '#type' => 'markup',
    '#prefix' => '<tr><td style="text-align:right"><b>',
    '#suffix' => '</b></td>',
    '#markup' => 'Email:',
  );
  $form_element['info']['cemail'] = array (
    '#prefix' => '<td>',
    '#suffix' => '</td></tr>',
    '#size' => 30,
    '#maxlength' => 60,
    '#type' => 'textfield',
    '#required' => TRUE,
  );
  // Phone number.
  $form_element['info']['phone'] = array (
    '#type' => 'markup',
    '#prefix' => '<tr><td style="text-align:right"><b>',
    '#suffix' => '</b></td>',
    '#markup' => 'Phone:',
  );
  $form_element['info']['phonenumber'] = array (
    '#prefix' => '<td>',
    '#suffix' => '</td></tr></tbody></table>',
    '#size' => 20,
    '#maxlength' => 20,
    '#type' => 'textfield',
    '#required' => TRUE,
  );
  // Submit button
  $form_element['submit-co'] = array(
    '#prefix' => '<tr><td></td><td>',
    '#suffix' => '</td></tr></tbody></table><br>',
    '#type' => 'submit',
    '#value' => 'Add this Coordinator >>'
  );
  return $form_element;
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
function voterdb_build_scope($fv_scope,$fv_options,$fv_hd_array) {
  
  $form_element['scope-select'] = array(
    '#type' => 'select',
    '#title' => t('Select the scope.'),
    '#options' => $fv_options,
    '#ajax' => array (
        'callback' => 'voterdb_scope_callback',
        'wrapper' => 'scope-wrapper',
        )
    );
  //Build a wrapper around the part that will change with input.
  $form_element['scope'] = array(
    '#prefix' => '<div id="scope-wrapper">',
    '#suffix' => '</div>',
    '#type' => 'fieldset',
    '#attributes' => array(
        'style' => array(
            'background-image: none; '
          . 'border: 0px; '
          . 'width: 550px; '
          . 'padding:0px; '
          . 'margin:0px; '
          . 'background-color: rgb(255,255,255);'), ),
   );
   switch ($fv_scope) {
     case 'Group of Precincts':
       $form_element['scope']['pct'] = array (
        '#title' => 'Enter a list of precincts, separated by commas',
        '#size' => 30,
        '#maxlength' => 60,
        '#type' => 'textfield',
        '#required' => TRUE,
       );
     case 'House District':
       
       $form_element['scope']['hd'] = array(
         '#type' => 'select',
         '#title' => t('Select the HD of the new HD coordinator.'),
         '#options' => array($fv_hd_array),
       );
     case 'County':
       $form_element['scope']['co_name'] = voterdb_build_name_entry();
   }
  return $form_element;
} 

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_build_coordinator_list
 * 
 * @param type $form_state
 * @return type
 */
function voterdb_build_coordinator_list($cl_county,$cl_coordinators) {

  // Check if we have any existing coordinators.
  if(empty($cl_coordinators)) {
    $form_element['note3'] = array (
    '#type' => 'markup',
    '#markup' => '<p>There are no coordinators assigned as yet.</p>',
    );
    return $form_element;
  }
  
  // We have at least one coordinator defined.
  $form_element['cform'] = array(
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
  $form_element['cform']['table_start'] = array(
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
  $form_element['cform']['header_row'] = array(
    '#markup' => " \n".'<thead>'.
    " \n".'<tr>'.$cl_header_row." \n".'</tr>'." \n".'</thead>',
  );
  // Start the body.
  $form_element['cform']['body-start'] = array(
    '#markup' => " \n".'<tbody>',
  );
  $cl_odd = TRUE;
  foreach ($cl_coordinators as $cl_cindex=>$cl_coordinator) {
    //$cl_cindex = $cl_coordinator[CR_CINDEX];
    // Build the array for edit or delete.
    //$form_state['voterdb']['coordinators'][$cl_cindex] = $cl_coordinator;
    $cl_fname = $cl_coordinator['firstName'];
    $cl_lname = $cl_coordinator['lastName'];
    $cl_email = $cl_coordinator['email'];
    $cl_phone = $cl_coordinator['phone'];
    $cl_hd = ($cl_coordinator['hd']!=0)?$cl_coordinator['hd']:'ALL';
   
    $cl_name = $cl_lname.', '.$cl_fname;
    $cl_class = ($cl_odd)?'<tr class="odd">':'<tr class="even">';
    $cl_odd = !$cl_odd;
    // Use the Drupal class for odd/even table rows and start the row.
    $form_element['cform']["row-start-$cl_cindex"] = array(
      '#markup' => " \n".$cl_class.'<!-- '.$cl_name.' row -->',
      );
    // First cell is the edit link.
    $form_element['cform']["submit-$cl_cindex-0"] = array(
      '#name' => "submit-$cl_cindex-0",
      '#prefix' => '<td class="td-de">',
      '#suffix' => '</td>',
      '#type' => 'submit',
      '#value' => 'edit',
    );
    // Second cell is the delete link.
    $form_element['cform']["submit-$cl_cindex-1"] = array(
      '#name' => "submit-$cl_cindex-0",
      '#prefix' => '<td class="td-de">',
      '#suffix' => '</td>',
      '#type' => 'submit',
      '#value' => 'delete'
    );
    // Third cell is the coordinator's name.
    $form_element['cform']["cell-$cl_cindex-2"] = array(
      '#markup' => " \n ".'<td class="td-de">'.$cl_name.'</td>',
      );
    // Fourth cell is the email.
    $form_element['cform']["cell-$cl_cindex-3"] = array(
      '#markup' => " \n ".'<td class="td-de">'.$cl_email.'</td>',
      );
    // Fifth cell is the phone number.
    $form_element['cform']["cell-$cl_cindex-4"] = array(
      '#markup' => " \n ".'<td class="td-de">'.$cl_phone.'</td>',
      );
    // Sixth cell is the house district.
    $form_element['cform']["cell-$cl_cindex-5"] = array(
      '#markup' => " \n ".'<td class="td-de">'.$cl_hd.'</td>',
      );
    // Seventh cell is the precinct list.
    $form_element['cform']["cell-$cl_cindex-6"] = array(
      '#markup' => " \n ".'<td class="td-de">'.$cl_coordinator['pctList'].'</td>',
      );
    // End the row.
    $form_element['cform']["row-end-$cl_cindex"] = array(
      '#markup' => " \n".'</tr>',
      );
  }
  // End of the table.
  $form_element['cform']['table_end'] = array(
    '#markup' => " \n".'</tbody>'." \n".'</table>'." \n".'<!-- End of Coordinator List Table -->'." \n",
    );
  return $form_element;
}
