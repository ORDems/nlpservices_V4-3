<?php
/*
 * Name: voterdb_participation.php   V4.3 8/8/18
 */

require_once "voterdb_constants_voter_tbl.php";
require_once "voterdb_group.php";
require_once "voterdb_banner.php";
require_once "voterdb_debug.php";
require_once "voterdb_class_nls.php";
require_once "voterdb_class_nlreports_nlp.php";
require_once "voterdb_class_survey_question_nlp.php";
require_once "voterdb_class_survey_response_nlp.php";
require_once "voterdb_class_turfs.php";
require_once "voterdb_class_voters.php";

use Drupal\voterdb\NlpNls;
use Drupal\voterdb\NlpReports;
use Drupal\voterdb\NlpSurveyQuestion;
use Drupal\voterdb\NlpSurveyResponse;
use Drupal\voterdb\NlpTurfs;
use Drupal\voterdb\NlpVoters;
 
/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_percent
 * 
 * @param type $pe_base
 * @param type $pe_cnt
 * @return type
 */
function voterdb_percent($pe_base,$pe_cnt) {
  $pe_percent = ($pe_base > 0)?round($pe_cnt/$pe_base*100,1).'%':'0%';
  return $pe_percent;
}

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_participation_cnts
 * 
 * Fill a file with records that show the progress of each NL to contact
 * voters and gather responses to the survey question.
 * 
 * @param type $pc_file_uri - a temp file for the report.
 * @return boolean
 */
function voterdb_participation_cnts($pc_file_uri,&$pc_count_array) {
  $pc_file_fh = fopen($pc_file_uri,"w");
  if(!$pc_file_fh) {return FALSE;}
  

  // Write the header as the first record in this tab delimited file.
  $pc_record = array("County", "MCID", "FName", "LName","Rpt", "Attempts");
  // If we have a survey question, add the colmns for title and responses.
  
  $surveyResponseObj = new NlpSurveyResponse();
  $surveyQuestionObj = new NlpSurveyQuestion($surveyResponseObj);
  $surveyQuestion = $surveyQuestionObj->getSurveyQuestion();
  
  $nlObj = new NlpNls();
  $reportsObj = new NlpReports();
  $votersObj = new NlpVoters();
  
  //$sq_title = $surveyQuestion['questionName'];
  
  //$sq_response_list = variable_get('voterdb_survey_responses', '');
  //$sq_title = variable_get('voterdb_survey_title', '');
  $pc_record[] = "Survey Title";
  if(!empty($surveyQuestion)) {
    //$sq_title = $surveyQuestion['questionName'];
    //$sq_responses = explode(',',$sq_response_list);
    $pc_count_array['surveyQuestion'] = $surveyQuestion['questionName'];
    foreach ($surveyQuestion['responses'] as $rid => $sq_response) {
      $pc_record[] = $sq_response;
      $pc_count_array['surveyResponses'][$rid] = $sq_response;
    }
  }
  $pc_string = implode("\t", $pc_record)."\tEOR\n";
  fwrite($pc_file_fh,$pc_string);
  // Now fill the file.
  $pc_counties = $votersObj->getParticipatingCounties() ;
  
  //voterdb_debug_msg('counties', $pc_counties);
  $turfObj = new NlpTurfs();
  foreach ($pc_counties as $pc_county) {
    //$pc_counts = voterdb_get_report_counts($pc_county);
    //voterdb_debug_msg('county counts', $pc_counts);
    // List of NLs with turfs.
    $pc_count_array['county'][$pc_county]['attempts'] = 0;
    $pc_count_array['county'][$pc_county]['resultsReported'] = 0;
    $pc_count_array['county'][$pc_county]['loggedIn'] = 0;
    
    $pc_nls = $turfObj->getCountyNlsWithTurfs($pc_county);
    $pc_count_array['county'][$pc_county]['nls'] = count($pc_nls);
    //voterdb_debug_msg('nls', $pc_nls);
    foreach ($surveyQuestion['responses'] as $rid => $sq_response) {
      $pc_count_array['county'][$pc_county]['surveyResponses'][$rid] = 0;
    }
    
     
    // Copy the results to a tab delimited file.
    foreach ($pc_nls as $pc_mcid) {

      // Get the name and other information for the display.
      
      $pc_nl = $nlObj->getNlById($pc_mcid);
      
      $pc_nl_status = $nlObj->getNlsStatus($pc_mcid,$pc_county);
      
      if (!empty($pc_nl_status['resultsReported'])) {
        $pc_count_array['county'][$pc_county]['resultsReported']++;
      }
      
      if (!empty($pc_nl_status['loginDate'])) {
        $pc_count_array['county'][$pc_county]['loggedIn']++;
      }
      
      //$pc_nl = voterdb_nl_info($pc_mcid);
      
      // Construct the status record for the NL.
      $pc_nl_stat['County'] = $pc_nl['county'];
      $pc_nl_stat['MCID'] = $pc_mcid;
      $pc_nl_stat['fname'] = $pc_nl['firstName'];
      $pc_nl_stat['lname'] = $pc_nl['lastName'];
      $pc_nl_stat['resultsReported'] = $pc_nl_status['resultsReported'];
      
      //$reportsObj->getNlVoterContactAttempts($mcid);
      //$pc_atmps = (!empty($pc_counts[$pc_mcid]['attempts']))?$pc_counts[$pc_mcid]['attempts']:0;
      $pc_nl_stat['attempts'] = $reportsObj->getNlVoterContactAttempts($pc_mcid);
      $pc_count_array['county'][$pc_county]['attempts'] += $pc_nl_stat['attempts'];
      
      // If there is a survey question, include the title and the response columns.
      $pc_counts = NULL;
      if(!empty($surveyQuestion)) {
        $pc_nl_stat['title'] = $surveyQuestion['questionName'];
        $surveyCounts = $reportsObj->getSurveyResponsesCountsById($pc_mcid,$surveyQuestion['qid']);
        foreach ($surveyQuestion['responses'] as $rid => $sq_response) {
          $pc_nl_stat[$sq_response] = (empty($surveyCounts[$rid]))?0:$surveyCounts[$rid];
          $pc_count_array['county'][$pc_county]['surveyResponses'][$rid] += $pc_nl_stat[$sq_response];
        }
      }
      $pc_string = implode("\t", $pc_nl_stat);
      $pc_string .= "\tEOR\n";
      fwrite($pc_file_fh,$pc_string);
    }
  }
  fclose($pc_file_fh);
  return TRUE;
}

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_participation
 * 
 * Display the participation counts for the NL program.
 *
 * @return string - HTML for display.
 */
function voterdb_participation() {
  $gc_county = variable_get('voterdb_state', 'Select');
  $gc_banner1 = voterdb_build_banner ($gc_county);
  $gc_banner = str_replace("County", "State", $gc_banner1);
  $output = $gc_banner;
  // Set up the table style.
  drupal_add_css('
    table {border-collapse: collapse;}
    td {border: 1px solid black;
      text-align: right;}
    th{text-align: right;}', array('type' => 'inline'));
  // Count the NLs who have signed up, the voters assigned to NLs, and the
  // progress on contacting voters.
  // Export the progress report for each participating NL.
  $gc_temp_dir = 'public://temp';
  $gc_cdate = date('Y-m-d-H-i-s',time());
  $gc_participation_uri = $gc_temp_dir.'/participation_report_'.$gc_county.'_'.$gc_cdate.'.txt';
  $gc_participation_object = file_save_data('', $gc_participation_uri, FILE_EXISTS_REPLACE);
  $gc_participation_object->status = 0;
  file_save($gc_participation_object);
  
  $gc_count_array = array();
  if(!voterdb_participation_cnts($gc_participation_uri,$gc_count_array)) {return $output;}
  
  $nlObj = new NlpNls();
  $voterObj = new NlpNlpVoters();

  $gc_contactattempts = $gc_nlcnt = $gc_nlreporting = $gc_nllogincnt = 0;
  
  foreach (array_keys($gc_count_array['surveyResponses']) as $rid) {
    $gc_responseCounts[$rid] = 0;
  }
  
  foreach ($gc_count_array['county'] as $countyCounts) {
    $gc_nlcnt += $countyCounts['nls'];
    $gc_nlreporting += $countyCounts['resultsReported'];
    $gc_nllogincnt += $countyCounts['loggedIn'];
    //$gc_voter_count += $countyCounts['voters'];
    $gc_contactattempts += $countyCounts['attempts'];
    foreach ($countyCounts['surveyResponses'] as $rid => $responseCount) {
      $gc_responseCounts[$rid] += $responseCount;
    }
  }
  
  $gc_voter_count = $voterObj->getVoterCount(NULL);

  // Display the participation counts in a nice table.
  $output .= '<table style="white-space: nowrap; width:453px;">';
  $output .= '<thead><tr><th style="text-align: left; width:150px;">Count Type</th>
      <th style="width:100px;">Count</th></tr></thead><tbody>';
  $output .= '<tr><td style="text-align: left;">Number of NLs</td>
              <td>'.$gc_nlcnt.'</td></tr>';
  $output .= '<tr><td style="text-align: left;">NLs logged in</td><td>'.$gc_nllogincnt.'</td></tr>';
  $output .= '<tr><td style="text-align: left;">NLs reporting results</td><td>'.$gc_nlreporting.'</td></tr>';

  $output .= '<tr><td style="text-align: left;">Voters assigned to NLs</td><td>'.$gc_voter_count.'</td></tr>';
  $output .= '<tr><td style="text-align: left;">Reported voter contact attempts</td><td>'.$gc_contactattempts.'</td></tr>';

  if (!empty($gc_count_array['surveyQuestion'])) {
    $output .= '<tr><td style="text-align: left;">Survey: '.$gc_count_array['surveyQuestion'].'</td><td></td></tr>';
    
    $responses = $gc_count_array['surveyResponses'];

    foreach ($responses as $rid=>$responseName) {
      $count = 0;
      if(!empty($gc_responseCounts[$rid])) {
        $count = $gc_responseCounts[$rid];
      }
      $output .= '<tr><td style="text-align: left; font-style: italic;">&nbsp;&nbsp;'.$responseName.'</td><td>'.$count.'</td></tr>';
    }
  }
  $output .= '</tbody></table>';
  $output .= "<p>&nbsp;</p>";

  $gc_participation_url = file_create_url($gc_participation_uri);
  $output .= "<p><a href=".$gc_participation_url.">Right click here to download the participation report.</a> "
    . "<i>(Use the Save link as... option)</i></p>";
  return $output;
}
