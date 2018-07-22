<?php
/*
 * Name: voterdb_nlpconfig_func2.php   V4.2 7/11/18
 * Sets the global variables for an election cycle.
 */

use Drupal\voterdb\NlpCounties;
use Drupal\voterdb\ApiResponseCodes;
use Drupal\voterdb\ApiAuthentication;
use Drupal\voterdb\NlpResponseCodes;
use Drupal\voterdb\ApiSurveyQuestions;
use Drupal\voterdb\NlpSurveyResponse;

use Drupal\voterdb\NlpSurveyQuestion;
use Drupal\voterdb\ApiActivistCodes;
use Drupal\voterdb\NlpActivistCodes;

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_process_vbverify
 * 
 * 
 */
function voterdb_process_vbverify(&$form_state) {
  //voterdb_debug_msg('form state', $form_state);
  //voterdb_debug_msg('form state input', $form_state['input']);
  //voterdb_debug_msg('form state values', $form_state['values']);
  //voterdb_debug_msg('form state voterdb', $form_state['voterdb']);
  
  $questionObj = $form_state['voterdb']['questionObj'];
  
  if(isset($form_state['values']['removeQuestion']) AND $form_state['values']['removeQuestion']) {
    drupal_set_message('The current survey question is deselected','status');
    $qid = $form_state['voterdb']['surveyQuestionQid'];
    $questionObj->deleteSurveyQuestion($qid);
  }
  
  if($form_state['values']['questionChoice'] != 1) {
    $surveyQuestionId = $form_state['values']['questionChoice'];
    drupal_set_message('The survey question is selected.','status');
    $questionsArray = $form_state['voterdb']['questionsArray'];
    //voterdb_debug_msg('questions array',$questionsArray);
    $questionObj->setSurveyQuestion($questionsArray[$surveyQuestionId],$surveyQuestionId);
  }
  
  if(isset($form_state['values']['removeAC']) AND $form_state['values']['removeAC']) {
    drupal_set_message('The current activist code is deselected','status');
    //$functionName = $form_state['voterdb']['functionName'];
    $questionObj->deleteActivistCode('NotADem');
  }
  
  //$form_state['voterdb']['activistCodeId'] = $currentActivistCode['activistCodeId'];
  
  if($form_state['values']['activistCode'] > 1) {
    $activistCodes = $form_state['voterdb']['activistCodes'];
    //voterdb_debug_msg('activist codes',$activistCodes);
    $nlpActivistCodeObj = new NlpActivistCodes();
    $activistCode = $activistCodes[$form_state['values']['activistCode']];
    $activistCode['functionName'] = 'NotADem';
    //voterdb_debug_msg('activist code',$activistCode);
    $nlpActivistCodeObj->setActivistCode($activistCode);
  }
  
  
  if(isset($form_state['values']['removeVoterAC']) AND $form_state['values']['removeAC']) {
    drupal_set_message('The current activist code is deselected','status');
    //$functionName = $form_state['voterdb']['functionName'];
    $questionObj->deleteActivistCode('NLPVoter');
  }
  
  //$form_state['voterdb']['activistCodeId'] = $currentActivistCode['activistCodeId'];
  
  if($form_state['values']['voterActivistCode'] > 1) {
    $activistCodes = $form_state['voterdb']['activistCodes'];
    voterdb_debug_msg('activist codes',$activistCodes);
    $nlpActivistCodeObj = new NlpActivistCodes();
    $activistCode = $activistCodes[$form_state['values']['voterActivistCode']];
    $activistCode['functionName'] = 'NLPVoter';
    voterdb_debug_msg('activist code',$activistCode);
    $nlpActivistCodeObj->setActivistCode($activistCode);
  }
  
}


/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_build_verify_votebuilder
 * 
 * @param type $form
 * @param type $form_state
 * @return string
 */
function voterdb_build_verify_votebuilder(&$form, &$form_state) {
  
  $stateCommittee = variable_get('voterdb_state_committee', '');
  $database = 0;
  
  $apiAuthenticationObj = new ApiAuthentication();
  $countyAuthenticationObj = $apiAuthenticationObj->getApiAuthentication($stateCommittee);
  
  $apiResultCodesObj = new ApiResponseCodes();
 
  $apiKnownResultCodes = $apiResultCodesObj->getApiKnownResultCodes($countyAuthenticationObj,$database);
  //voterdb_debug_msg('known', $apiKnownResultCodes);
  
  $apiExpectedResultCodes = $apiResultCodesObj->getApiExpectedResultCodes();
  //voterdb_debug_msg('expected', $apiExpectedResultCodes);
  
  //$apiContactTtpes = $apiResultCodesObj->getApiContactTypes($countyAuthenticationObj,$database);
  //voterdb_debug_msg('contact types', $apiContactTtpes);
   
    
  //
  // - - - Survey question choice. - - - - - - - - - - - - - -
  //
  
  $form['surveyq'] = array(
    '#title' => 'Selection of the survey question',
    '#prefix' => " \n".'<div style="width:750px;">'." \n",
    '#suffix' => " \n".'</div>'." \n",
    '#type' => 'fieldset',
  );
  
  $responseCodesObj = new NlpResponseCodes();
  $responseCodesObj->setNlpResponseCodes($apiKnownResultCodes);
  
  
  $surveyResponseObj = new NlpSurveyResponse();
  
  $questionObj = new NlpSurveyQuestion($surveyResponseObj);
  //voterdb_debug_msg('questionobj', $questionObj);
  $form_state['voterdb']['questionObj'] = $questionObj;
  $surveyQuestionArray = $questionObj->getSurveyQuestion();
  //voterdb_debug_msg('questions', $surveyQuestionArray );
  if(empty($surveyQuestionArray)) {
    $form['surveyq']['noQuestion'] = array(
      '#markup' => "<p>There is no survey question chosen. </p>",
    );
  } else {
    $form_state['voterdb']['surveyQuestionQid'] = $surveyQuestionArray['qid'];
    $currentQuestion = '<b>name: </b>'.$surveyQuestionArray['questionName']
      . '<b> cycle: </b>'.$surveyQuestionArray['cycle']
      . '<b> type: </b>'.$surveyQuestionArray['questionType']
      . '<b> scriptQuestion: </b>'.$surveyQuestionArray['scriptQuestion'];
    $form['surveyq']['currentQuestion'] = array(
      '#markup' => "<p><b>The currently chosen survey question is:</b><br>".$currentQuestion."</p>",
    );
    $form['surveyq']['removeQuestion'] = array(
      '#type' => 'checkbox',
      '#title' => t('Remove the currently chosen survey question'),
      //'#default_value' => isset($node->active) ? $node->active : 1,
      //'#options' => $candidateList,
      //'#description' => t('Remove the currently chosen survey question.'),
    );
  }

  
  $apiQuestionsObj = new ApiSurveyQuestions(NULL);
  //voterdb_debug_msg('apiquestions', $apiquestionsObj);
  $form_state['voterdb']['apiQuestionObj'] = $apiQuestionsObj;
  
  $questionsInfoObj = $apiQuestionsObj->getApiSurveyQuestions($countyAuthenticationObj,$database,'All');
  //voterdb_debug_msg('Questions info', $questionsInfoObj);
  $form_state['voterdb']['questionsArray'] = $questionsInfoObj->result;
  
  $questionList = array();
  $questionList[1] = '<b>no change </b>';
  foreach ($questionsInfoObj->result as $surveyQuestion) {
    
    if($surveyQuestion['type'] != 'Candidate') {
      $questionList[$surveyQuestion['qid']] = '<b>name: </b>'.$surveyQuestion['name']
        . '<b> cycle: </b>'.$surveyQuestion['cycle']
        . '<b> type: </b>'.$surveyQuestion['type']
        . '<b> scriptQuestion: </b>'.$surveyQuestion['scriptQuestion'];
    }
    
  }
  
  if(empty($questionList)) {
    $form['surveyq']['note'] = array(
      '#markup' => "<p>There are no survey questions visible to the API </p>",
    );
  } else {
    
    $form['surveyq']['questionChoice'] = array(
      '#type' => 'radios',
      '#title' => t('Survey Question Choice'),
      '#default_value' => 1,
      '#options' => $questionList,
      '#description' => t('Choose a survey question for this cycle.'),
    );

  }
  $form['surveyq']['saveQC'] = array(
    '#type' => 'submit',
    '#name' => 'saveQC',
    '#value' => 'Save >>'
  ); 
  
  
  //
  // - - - "Not a Dem" activist code choice. - - - - - - - - - - - - - -
  //
  
  $nlpActivistCodeObj = new NlpActivistCodes();
  $nlpActivistCode = $nlpActivistCodeObj->getActivistCode('NotADem');
  if(empty($nlpActivistCode)) {
    $currentActivistCode = 'Not chosen yet';
  } else {
    $currentActivistCode = $nlpActivistCodeObj->getNlpActivistCodeDisplay($nlpActivistCode);
    //$form_state['voterdb']['functionName'] = $currentActivistCode['functionName'];
  }
  
  $activistCodeObj = new ApiActivistCodes();
  $activistCodes = $activistCodeObj->getApiActivistCodes($countyAuthenticationObj,$database);
  $form_state['voterdb']['activistCodes'] = $activistCodes;
  //voterdb_debug_msg('Activist Codes', $activistCodes);
  
  $activistCodeList = $activistCodeObj->getActivistCodeList($activistCodes);
  
  $form['activist'] = array(
    '#title' => '"Not a Dem" AC Select',
    '#prefix' => " \n".'<div style="width:750px;">'." \n",
    '#suffix' => " \n".'</div>'." \n",
    '#type' => 'fieldset',
  );
  
  $form['activist']['currentAC'] = array(
      '#markup' => '<p><b>The currently chosen Activist code for "Not a Dem" is:</b><br>'.$currentActivistCode."</p>",
    );
  if(!empty($nlpActivistCode)) {
    $form['activist']['removeAC'] = array(
        '#type' => 'checkbox',
        '#title' => t('Remove the currently chosen activist code'),
        //'#default_value' => isset($node->active) ? $node->active : 1,
        //'#options' => $candidateList,
        //'#description' => t('Remove the currently chosen survey question.'),
      );
  }
  
  $form['activist']['activistCode'] = array(
     '#type' => 'select',
     '#title' => '"Not a Dem" activist code selection',
     '#options' => $activistCodeList,
     '#size' =>2,
     '#description' => t('Select the activist code to be set when a voter is declared to be "Not a Dem"'),
  );
  
  $form['activist']['saveAC'] = array(
    '#type' => 'submit',
    '#name' => 'saveAC',
    '#value' => 'Save >>'
  ); 
  
  
  
  //
  // - - - "NLP Voter" activist code choice. - - - - - - - - - - - - - -
  //
  
  //$nlpActivistCodeObj = new NlpActivistCodes();
  $voterActivistCode = $nlpActivistCodeObj->getActivistCode('NLPVoter');
  if(empty($voterActivistCode)) {
    $currentVoterActivistCode = 'Not chosen yet';
  } else {
    $currentVoterActivistCode = $nlpActivistCodeObj->getNlpActivistCodeDisplay($voterActivistCode);
    //$form_state['voterdb']['functionName'] = $currentActivistCode['functionName'];
  }
  
  //$activistCodeObj = new ApiActivistCodes();
  //$activistCodes = $activistCodeObj->getApiActivistCodes($countyAuthenticationObj,$database);
  //$form_state['voterdb']['activistCodes'] = $activistCodes;
  //voterdb_debug_msg('Activist Codes', $activistCodes);
  
  //$activistCodeList = $activistCodeObj->getActivistCodeList($activistCodes);
  
  $form['nlpvoter'] = array(
    '#title' => '"NLP Voter" AC Select',
    '#prefix' => " \n".'<div style="width:750px;">'." \n",
    '#suffix' => " \n".'</div>'." \n",
    '#type' => 'fieldset',
  );
  
  $form['nlpvoter']['currentVoterAC'] = array(
      '#markup' => '<p><b>The currently chosen Activist code for identifing an NLP voter is:</b><br>'.$currentVoterActivistCode."</p>",
    );
  if(!empty($nlpActivistCode)) {
    $form['nlpvoter']['removeVoterAC'] = array(
        '#type' => 'checkbox',
        '#title' => t('Remove the currently chosen activist code'),
        //'#default_value' => isset($node->active) ? $node->active : 1,
        //'#options' => $candidateList,
        //'#description' => t('Remove the currently chosen survey question.'),
      );
  }
  
  $form['nlpvoter']['voterActivistCode'] = array(
     '#type' => 'select',
     '#title' => '"NLP Voter" activist code selection',
     '#options' => $activistCodeList,
     '#size' =>2,
     '#description' => t('Select the activist code to be set when a voter is assigned to an NL.'),
  );
  
  $form['nlpvoter']['saveVoterAC'] = array(
    '#type' => 'submit',
    '#name' => 'saveAC',
    '#value' => 'Save >>'
  ); 
  
   
  //
  // - - - Display of available canvass response codes. - - - - - - - - -
  //
  
  $form['vbtbl'] = array(
    '#title' => 'Verification of canvass response codes',
    '#prefix' => " \n".'<div style="width:450px;">'." \n",
    '#suffix' => " \n".'</div>'." \n",
    '#type' => 'fieldset',
  );
  
  
  $form['vbtbl']['table_start'] = array(
    '#prefix' => " \n".'<style type="text/css"> textarea { resize: none;} </style>',
    '#markup' => " \n".'<!-- Canvass response code table -->'." \n".'<table border="1" style="font-size:x-small; padding:0px; '
    . 'border-color:#d3e7f4; border-width:1px; width:300px;" class="noborder">',
  );
  // Create the header.
  $bv_header_row = '<th style="width:140px;">Desired Responses</th>';
  $bv_header_row .= '<th style="width:160px;">Available Responses</th>';

  $form['vbtbl']['header_row'] = array(
    '#markup' => " \n".'<thead>'.
    " \n".'<tr>'.$bv_header_row.'</tr>'." \n".'</thead>',
  );
  // Start the body.
  $form['vbtbl']['body-start'] = array(
    '#markup' => " \n".'<tbody>',
  );
  
  $rowClassType = "even";
  $rowCount = 0;
  foreach ($apiExpectedResultCodes as $contentType => $responseArray) {
    $rowClassType = ($rowClassType=="even")?'odd':'even';
    $form['vbtbl']["row-$rowCount"] = array(
      '#markup' => " \n".'<tr class='.$rowClassType.'><td><b>'.$contentType.'</b></td><td></td></tr>',);
    $rowCount++;
    
    $responseNames = array_keys($responseArray['responses']);
    foreach ($responseNames as $responseName) {
      $rowClassType = ($rowClassType=="even")?'odd':'even';
      
      $responseExists = (isset($apiKnownResultCodes[$contentType]['responses'][$responseName]))?$responseName:'-';
      
      $form['vbtbl']["row-$rowCount"] = array(
        '#markup' => " \n".'<tr class='.$rowClassType.'><td>&nbsp;&nbsp;'.$responseName.'</td><td> &nbsp;&nbsp;'.$responseExists.' </td></tr>',);
      
      $rowCount++;

    }
  }
  
  //if(!exist($apiKnownResultCodes['Walk']['responses']['Not a Dem'])) {
  //  $apiKnownResultCodes['Walk']['responses']['Not a Dem'] = 9999;
  //}
  
  
  $form['vbtbl']['table_end'] = array(
    '#markup' => " \n".'</tbody></table>'." \n".'<!-- End of Data Entry Table -->'." \n",
    );
  
  $form['vbtbl']['vbnote'] = array(
    '#markup' => "<p><b>Where the available response is missing, the response "
    . "will not be recorded in VoteBuilder.  Check with your VAN coordinator "
    . "if the response needs to be available to the NL and recorded.</b></p>",
    );
  
  
  
  //
  // - - - Candidate survey question display. - - - - - - - - - - - - - -
  //
  
  $form['candidates'] = array(
    '#title' => 'Display of the available candidate survey questions',
    '#prefix' => " \n".'<div style="width:850px;">'." \n",
    '#suffix' => " \n".'</div>'." \n",
    '#type' => 'fieldset',
  );
  
  $form['candidates']['note'] = array(
    '#markup' => "<p><b>The following is the list of candidate survey questions "
      . "visible to the API. Configuratioin of the candidate survey "
      . "questions is done from the admin page.   But, only these are available"
      . "for use.</b></p>",
  );
  
  foreach ($questionsInfoObj->result as $surveyQuestion) {
    
    if($surveyQuestion['type'] == 'Candidate') {
      $qid = $surveyQuestion['qid'];
      $candidateInfo = '<p><b>name: </b>'.$surveyQuestion['name']
        . '<b> cycle: </b>'.$surveyQuestion['cycle']
        . '<b> type: </b>'.$surveyQuestion['type']
        . '<b> scriptQuestion: </b>'.$surveyQuestion['scriptQuestion'].'</p>';

      $form['candidates'][$qid] = array(
        '#markup' => $candidateInfo,
      );
 
    }

  }
  
  
  /**
  $form['surveyq']['candidateChoice'] = array(
    '#type' => 'checkboxes',
    '#title' => t('Candiate ID Choice'),
    //'#default_value' => isset($node->active) ? $node->active : 1,
    '#options' => $candidateList,
    '#description' => t('Choose one of more candidates for this cycle.'),
  );
  
   * 
   */
  
  
   $form['verifyvb_back'] = array(
    '#type' => 'submit',
    '#name' => 'vbback',
    '#value' => '<< Back'
  );
  $form['verifyvb_submit'] = array(
    '#type' => 'submit',
    '#name' => 'verifyvb',
    '#value' => 'Continue >>'
  );    
  return $form;
}
