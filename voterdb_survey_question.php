<?php
/*
 * Name: voterdb_survey_question.php   V4.0 2/19/18
 * Sets the global variables for an election cycle.
 */
require_once "voterdb_group.php";
require_once "voterdb_banner.php";
require_once "voterdb_debug.php";
require_once "voterdb_path.php";
require_once "voterdb_class_button.php";

define('SQ_TITLE_LEN','16');
define('SQ_RESPONSE_LEN','16');

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_survey_question_form
 *
 *
 * @param type $form
 * @param type $form_state
 * @return $form.
 */
function voterdb_survey_question_form($form, &$form_state) {
  $ad_button_obj = new NlpButton;
  $ad_button_obj->setStyle();
  
  if (!isset($form_state['voterdb']['reenter'])) {
    $form_state['voterdb']['reenter'] = TRUE;
    if(!voterdb_get_group($form_state)) {return;}
  } 
  $ad_county = $form_state['voterdb']['county'];
  $ad_banner = voterdb_build_banner ($ad_county);
  $form['note'] = array (
    '#type' => 'markup',
    '#markup' => $ad_banner
  ); 
  // Survey question block.
  $form['survey'] = array(
    '#title' => 'Survey Question Configuration',
    '#prefix' => " \n".'<div  style="width:310px;">'." \n",
    '#suffix' => " \n".'</div>'." \n",
    '#type' => 'fieldset',
  );
  $form['survey']['voterdb_survey_title'] = array(
    '#type' => 'textfield',
    '#id' => 'title',
    '#title' => t('Survey question title (keep short)'),
    '#default_value' => variable_get('voterdb_survey_title', ''),
    '#size' => 20,
    '#maxlength' => 30,
    '#description' => t("Title of a survey question for this cycle"),
  );
  $ad_response_options = variable_get('voterdb_survey_responses', '');
  $ad_response_list = array();
  if(!empty($ad_response_options) AND is_array($ad_response_options)) {
    reset($ad_response_options);
    unset($ad_response_options[0]);
    $ad_response_list = implode(',', $ad_response_options);
  } elseif (!empty($ad_response_options) AND !is_array($ad_response_options)) {
    $ad_response_list = $ad_response_options;
  }
  $form['survey']['voterdb_survey_responses'] = array(
    '#type' => 'textfield',
    '#id' => 'responses',
    '#title' => t('Survey question response list.'),
    '#default_value' => $ad_response_list,
    '#size' => 40,
    '#maxlength' => 60,
    '#description' => t("CSV list of allowed survey question responses."),
  );
  // Submit button
  $form['submit'] = array(
    '#type' => 'submit',
    '#value' => 'Submit Changes >>'
  );  
  
  $form['done'] = array(
    '#markup' => '<p><a href="nlpadmin?County='.$ad_county.'" class="button ">Return to Admin page >></a></p>',
  );
  return $form;
}

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_survey_question_form_validate
 *
 * Verify that the survey title and responses are not too long.
 *
 * @param type $form
 * @param type $form_state
 * @return boolean
 */
function voterdb_survey_question_form_validate($form, &$form_state) {
  
  // Verity that a title and response are set if either has a value.
  $ad_title = $form_state['values']['voterdb_survey_title'];
  $ad_resp = $form_state['values']['voterdb_survey_responses'];
  if(!empty($ad_title) XOR !empty($ad_resp)) {
    form_set_error('voterdb_survey_title', t('The title and responses both have to have a value.'));
    form_set_error('voterdb_survey_responses', '');
  }
  
  $form_state['title'] = $form_state['values']['voterdb_survey_title'];
  if(strlen($form_state['title'])>SQ_TITLE_LEN) {
    form_set_error('voterdb_survey_title', t('The survey question title is limited to 16 characters '));
    return;
  }
  //$form_state['responses'] = $form_state['values']['voterdb_survey_responses'];
  $ad_response_list = $form_state['values']['voterdb_survey_responses'];
  if (!empty($ad_response_list)) {
    $ad_response_options = explode(',',$form_state['values']['voterdb_survey_responses']);
    //voterdb_debug_msg('options', $ad_response_options, __FILE__, __LINE__);
    $ad_rend = count($ad_response_options);
    for ($ad_ri = 1; $ad_ri < $ad_rend; $ad_ri++) {
      $ad_text = trim(preg_replace('/\s+/',' ', $ad_response_options[$ad_ri]));
      if (strlen($ad_text) > SQ_RESPONSE_LEN OR strlen($ad_text) < 1) {
        form_set_error('voterdb_survey_responses', t('Survey responses must be 1 to 16 characters '));
        return;
      }
      $ad_response_options[$ad_ri] = $ad_text;
    }
    $form_state['responses'] = implode(',',$ad_response_options);
  }
  return;
}
/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_survey_question_form_submit
 *
 * Set the global variable for the survey question and responses.
 *
 * @param type $form
 * @param type $form_state
 */
function voterdb_survey_question_form_submit($form, &$form_state) {
  variable_set('voterdb_survey_title',$form_state['title']);
  variable_set('voterdb_survey_responses',$form_state['responses']);
  drupal_set_message('The survey question is updated.','status');
  return;
}