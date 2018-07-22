<?php
/*
 * Name: voterdb_export_nlsreports.php   V4.2  7/16/18
 */

//require_once "voterdb_constants_rr_tbl.php";
//require_once "voterdb_constants_nls_tbl.php";
//require_once "voterdb_path.php";
require_once "voterdb_debug.php";
require_once "voterdb_group.php";
require_once "voterdb_banner.php";
require_once "voterdb_class_get_browser.php";
require_once "voterdb_class_button.php";
require_once "voterdb_class_nls.php";
require_once "voterdb_class_nlreports_nlp.php";
//require_once "voterdb_class_paths.php";

use Drupal\voterdb\NlpButton;
use Drupal\voterdb\GetBrowser;
use Drupal\voterdb\NlpNls;
use Drupal\voterdb\NlpReports;
//use Drupal\voterdb\NlpPaths;

define('NR_NLS_REPORTS','nlsreports'); // Name of the result file.

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_export_nlsreports
 * 
 * Export the NL reports for a county and put them in a tab delimited file 
 * suitable for download.  The file can be used for archive of an election 
 * cycle or for import to the VoteBuilder.
 * 
 * The fields in the voterdb results table are selected and written to a file.
 * The VANID is moved to the first field in the file to make import to the 
 * VoteBuilder easier.   The last field is called EOR and will contain the EOR 
 * text to meet the VoteBuilder requirements that the last field always has 
 * information. The nickname and last name of the NL is included to make the 
 * export file a little easier to read.
 *
 * @return $output - display with link to file for download.
 */
function voterdb_export_nlsreports() {
  // Use the public folder for saving temp files.
  $nr_temp_dir = 'public://temp';
  file_prepare_directory($nr_temp_dir, FILE_MODIFY_PERMISSIONS | FILE_CREATE_DIRECTORY);
  // Use a date in the name to make the file unique., just in case two people 
  // are doing an export at the same time.
  $nr_cdate = date('Y-m-d-H-i-s',time());
  //voterdb_debug_msg('cdate', $nr_cdate);
  // Open a temp file for receiving the records.
  $nr_fname = NR_NLS_REPORTS.'-'.$nr_cdate.'.txt';
  $nr_temp_uri = $nr_temp_dir.'/'.$nr_fname;
  
  // Create a managed file for temporary use.  Drupal will delete after 6 hours.
  $nr_file_object = file_save_data('', $nr_temp_uri, FILE_EXISTS_REPLACE);
  $nr_file_object->status = 0;
  file_save($nr_file_object);
  // Open the new remp file for writing by PHP functions.
  $nr_file_fh = fopen($nr_temp_uri,"w");
  //voterdb_debug_msg('fh', $nr_file_fh);
  // Get the column names for the export and add the NL name to ease editting.
  
  $nlpReportsObj = new NlpReports();
  $nr_col_names = $nlpReportsObj->getColumnNames();
  //voterdb_debug_msg('columns', $nr_col_names);
  //$nr_col_names = voterdb_col_names(DB_NLPRESULTS_TBL);
  $nr_col_names[] = 'Nickname';
  $nr_col_names[] = 'LastName';  
  // Write the header as the first record in this tab delimited file.
  $nr_string = implode("\t", $nr_col_names)."\tEOR\n";
  fwrite($nr_file_fh,$nr_string);
  fclose($nr_file_fh);
  
  $bn_num_rows = $nlpReportsObj->getReportCount();
  //voterdb_debug_msg('rows', $bn_num_rows);
  
 
  $nr_mpath = drupal_get_path('module','voterdb');
  $nr_args = array (
    'uri' => $nr_temp_uri,
    'col_names' => $nr_col_names,
    'num_rows' => $bn_num_rows,
  );
  $nr_batch = array(
    'operations' => array(
      array('voterdb_export_nlsreports_batch', array($nr_args))
      ),
    'file' => $nr_mpath.'/voterdb_export_nlsreports_batch.php',
    'finished' => 'voterdb_export_nlsreports_finished',
    'title' => t('Processing export reports.'), 
    'init_message' => t('Reports export is starting.'), 
    'progress_message' => t('Processed @percentage % of reports database.'), 
    'error_message' => t('Export reports has encountered an error.'),
  );
  batch_set($nr_batch);
  
  drupal_set_message('Batch started','status');

  return $nr_args;
}



/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_restore_nlsreports_form
 * 
 * Create the form for uploading voter contact, IDs and comments.
 * 
 * @param type $form_id
 * @param type $form_state
 * @return associative array - the form.
 */
function voterdb_export_nlsreports_form($form_id, &$form_state) {
  $hg_button_obj = new NlpButton();
  $hg_button_obj->setStyle();
  if(!isset($form_state['voterdb']['county'])) {
    if(!voterdb_get_group($form_state)) {return;}
  }
  $en_county = $form_state['voterdb']['county'];
  // Create the form to display of all the NLs.
  $en_banner = voterdb_build_banner ($en_county);
  $form['note'] = array (
    '#type' => 'markup',
    '#markup' => $en_banner
  ); 
  if (isset($form_state['voterdb']['reenter'])) {
    $en_args = $form_state['args'];
    $form['count'] = array(
      '#markup' => 'Record count: '.$en_args['num_rows'],
    );
    
    $en_browser_obj = new GetBrowser();
    $en_browser = $en_browser_obj->getBrowser();
    $en_browser_hint = $en_browser['hint'];
    
    $en_url = file_create_url($en_args['uri']);
    $form['file'] = array(
      '#markup' =>  '<p id="hint1">'
        . '<a id="hint2" href='.$en_url.'>Right-click here</a> to download the NLs reports. '
        . '<span style="color:red;"> Remember to right-click the link and then select "'.$en_browser_hint.'".</span> </p>',
    );
    
  } else {

    // A submit button.
    $form['uploadfile'] = array(
        '#type' => 'submit',
        '#id' => 'export-file',
        '#value' => 'Export the reports database >>',
    );
  }
  
  $form['done'] = array(
    '#markup' => '<p><a href="nlpadmin?County='.$en_county.'" class="button ">Return to Admin page >></a></p>',
  );
  return $form;
}

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_restore_nlsreports_form_submit
 * 
 * Process the submitted file to restore the historical NL reports.
 *
 * @param type $form
 * @param type $form_state
 */
function voterdb_export_nlsreports_form_submit($form,&$form_state) {
	
  if(($form_state['triggering_element']['#id'] == 'done') ) {
    $form_state['voterdb']['reenter'] = FALSE;
    $form_state['rebuild'] = FALSE;
  } else {
    $form_state['voterdb']['reenter'] = TRUE;
    $form_state['rebuild'] = TRUE;  
    $en_args = voterdb_export_nlsreports();
    $form_state['args'] = $en_args;
  }
}
