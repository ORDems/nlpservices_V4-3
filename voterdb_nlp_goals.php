<?php
/**
 * Name: voterdb_nlp_goals.php    V4.0  2/18/18
 * 
 * This include file contains the code to upload a list of potential NLs from
 * MyCampaign into a MySQL database.  We use this data to verify spelling and
 * to manage contact information.
*/
require_once "voterdb_constants_goals_tbl.php";
require_once "voterdb_group.php";
require_once "voterdb_banner.php";
require_once "voterdb_debug.php";
require_once "voterdb_class_button.php";

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_num_val
 * 
 * Verify that a number was entered into the form for a goal.
 *
 * @param type $element
 * @param type $form_state
 * @param type $form
 */
function voterdb_num_val($element,$form_state,$form)  {
  if(!is_numeric($element['#value'])) {
    form_error($element,'value must be numeric');
  }
}
/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_goals
 * 
 * @param type $gg_func
 * @param type $gg_goal_info
 * @return boolean
 */
function voterdb_goals( $gg_func, $gg_goal_info ) {
  switch ($gg_func) {
    // Get the existing goals records, if they exist.
    case 'GET':
      db_set_active('nlp_voterdb');
      try {
        $gg_query = db_select(DB_NLPGOALS_TBL, 'g');
        $gg_query->fields('g');
        $gg_query->condition(NM_COUNTY,$gg_goal_info['county']);
        $gg_query->orderBy(NM_HD);
        $gg_result = $gg_query->execute();
      }
      catch (Exception $e) {
        db_set_active('default');
        voterdb_debug_msg('e', $e->getMessage() );
        return FALSE;
      }
      $gg_goals_list = $gg_result->fetchAll(PDO::FETCH_ASSOC);
      db_set_active('default');
      if(empty($gg_goals_list)) {return FALSE;}
      // Build the internal array of goals for display and update.
      $gg_hdgoals = array();
      foreach ($gg_goals_list as $gg_hd_goal) {
        $gg_hd = $gg_hd_goal[NM_HD];  // The HD.
        $gg_hdgoal = $gg_hd_goal[NM_NLPGOAL];  // And it's goal.
        if ($gg_hd == 'ALL') {
          $gg_hdgoals["ALL"] = $gg_hdgoal;  // We have a county goal.
        } else {
          $gg_hdgoals[$gg_hd] = $gg_hdgoal;  // Else it is HD goal.
        }
      } 
      return $gg_hdgoals;
    // Save the updated goals.
    case 'PUT':
      db_set_active('nlp_voterdb');
      db_merge(DB_NLPGOALS_TBL)
        ->key(array(
            NM_COUNTY => $gg_goal_info['county'],
            NM_HD => $gg_goal_info['hd'],
          ))
        ->fields(array(NM_NLPGOAL => $gg_goal_info['goal'],))
        ->execute();
      db_set_active('default');
      return TRUE;
  }
}

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_goals_form
 *
 * Create the form for entering HD and county NL recruitment goals for the
 * specified county.
 * 
 * @param type $form
 * @param type $form_state
 * @return string - form.
 */
function voterdb_goals_form($form,&$form_state) {
  // Verify we know the group
  if (!isset($form_state['voterdb']['reenter'])) {
    $form_state['voterdb']['pass'] = 'page one';
    $form_state['voterdb']['reenter'] = TRUE;
    if(!voterdb_get_group($form_state)) {return;}
  } 
  $hg_button_obj = new NlpButton;
  $hg_button_obj->setStyle();
  $hg_county = $form_state['voterdb']['county'];
  $hg_hd_array = $form_state['voterdb']['hd_array']; 
  // Create the form to display of all the NLs
  $hg_banner = voterdb_build_banner ($hg_county);
  $form['note'] = array (
    '#type' => 'markup',
    '#markup' => $hg_banner
  ); 
  // Get the current goals if they exist.
  $hg_goal_info['county'] = $hg_county;
  $hg_hdgoals = voterdb_goals( 'GET', $hg_goal_info );
  // If County and HD goals do not exist, create a null goals array to use.
  if(!isset($hg_hdgoals[0])) {
    $hg_hdgoals[0]=0;
    foreach ($hg_hd_array as $hg_hd) {
    $hg_hdgoals[$hg_hd] = 0;
    }
  }
  $form['goal-select'] = array(
    '#title' => 'Select your county goals',
    '#type' => 'fieldset',
    '#prefix' => '<div style="width:250px;">',
    '#suffix' => '</div>',
  );
  // County NL recruitment goal.
  $form['goal-select']['cogoal'] = array(
      '#type'=> 'textfield',
      '#title' => t('County Goal'),
      '#description' =>
            t('The Neighborhood Leader Recruitment goal for the County'),
      '#size' => 5,
      '#required' => TRUE,
      '#element_validate' => array('voterdb_num_val'),
      '#default_value' => $hg_hdgoals[0],
   );
  //Build the header array for the goal data entry form and set the
  //column widths to improve readability.
  $hg_header_data = array(
    array('data' => 'HD'),
    array('data' => 'NLP Goal'),
    );
  
  $form['goal-select']['sdiv'] = array (
    '#type' => 'markup',
    '#markup' => '<div style="width:150px;">',
  ); 
  // Build the form array for the table generator.
  $form['goal-select']['hdgoals'] = array(
    '#tree' => TRUE,
    '#theme' => 'table',
    '#header' => $hg_header_data,
    '#rows' => array(),
  );
  foreach ($hg_hd_array as $hg_hd) {
    if ($hg_hd == 0) {break;}
    $hg_hd_text = "HD$hg_hd";
    $hg_hd_cell = array (
      '#type' => 'markup',
      '#markup' => $hg_hd_text,
    );
    // This cell has a text field to change the NL recruitment goal for
    // the HD.
    $hg_nlpgoal_cell = array (
      '#size' => 4,
      '#maxlength' => 4,
      '#type' => 'textfield',
      '#element_validate' => array('voterdb_num_val'),
      '#required' => TRUE,
      '#default_value' => $hg_hdgoals[$hg_hd],
    );
    // Add all the information for the row into the next row of the form.
    // and remember the id associated with the cell content.
    $form['goal-select']['hdgoals'][] = array(
      'hdid' => &$hg_hd_cell,
      'hdgoal' => &$hg_nlpgoal_cell,
    );
    $form['goal-select']['hdgoals']['#rows'][] = array(
        array('data' => &$hg_hd_cell),
        array('data' => &$hg_nlpgoal_cell),
    );
    unset($hg_hd_cell,$hg_nlpgoal_cell);
  }
  $form['goal-select']['ediv'] = array (
    '#type' => 'markup',
    '#markup' => '</div>',
  ); 
  // A submit button to update the NL recruitment goals.
  $form['submit'] = array(
      '#type' => 'submit',
      '#value' => 'Set Goals',
      '#description' => t('Set the Neighborhood Leaders Recruitment goals'),
  );
  
  $form['done'] = array(
    '#markup' => '<p><a href="nlpadmin?County='.$hg_county.'" class="button ">Return to Admin page >></a></p>',
  );
  return $form;
}

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_goals_form_submit
 *
 * Process the submitted goals.
 * @param type $form
 * @param type $form_state
 */
function voterdb_goals_form_submit($form, &$form_state) {

  $form_state['voterdb']['reenter'] = TRUE;
  $form_state['rebuild'] = TRUE;  // form_state will persist
  $hg_county = $form_state['voterdb']['county'];
  $hg_hd_array = $form_state['voterdb']['hd_array']; 

  $hg_goal_info['county'] = $hg_county;
  $hg_goal_info['hd'] = 0;
  $hg_goal_info['goal'] = $form_state['values']['cogoal'];
  voterdb_goals( 'PUT', $hg_goal_info );
    
  // Update the goal for each HD in county
  //voterdb_debug_msg('goals array', $form_state['values']['hdgoals']);
  foreach ($form_state['values']['hdgoals'] as $hg_hdi => $hg_hdgoal_array) {
    $hg_hd = $hg_hd_array[$hg_hdi];
    $hg_goal_info['hd'] = $hg_hd;
    $hg_goal_info['goal'] = $hg_hdgoal_array['hdgoal'];
    voterdb_goals( 'PUT', $hg_goal_info );    
    }
  drupal_set_message('The NL recruitment goals have been created.','status');
}