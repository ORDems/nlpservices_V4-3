<?php
/**
 * Name: voterdb_nls_upload.php    V4.2 6/5/18
 * 
 * This include file contains the code to upload a list of potential NLs from
 * MyCampaign into a MySQL database.  We use this data to verify spelling and
 * to manage contact information.
*/

require_once "voterdb_group.php";
require_once "voterdb_debug.php";
require_once "voterdb_banner.php";
require_once "voterdb_class_button.php";
require_once "voterdb_class_nls.php";
require_once "voterdb_class_legislative_fixes.php";

use Drupal\voterdb\NlpButton;
use Drupal\voterdb\NlpNls;
use Drupal\voterdb\NlpLegFix;


/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_prepare_NL_database
 *
 * Build a database of NLs. Read the file exported from MyCampaign and 
 * process the submitted fields.
 * 
 * @param type 
 * @return boolean - TRUE if no errors in upload.
 */
function voterdb_prepare_NL_database($form_state){
  $pn_file_name = $form_state['voterdb']['file'];
  $pn_field_pos = $form_state['voterdb']['pos'];
  $pn_county = $form_state['voterdb']['county'];

  // Get the HD/Pct fixes if any.
  $legFixObj = new NlpLegFix();
  $form_state['voterdb']['legFixObj'] = $legFixObj;
  $pn_fixes = $legFixObj->getLegFixes($pn_county);
  //$pn_fixes = voterdb_get_leg_fixes ($pn_county);
  // Open the MyCampaign export file of prospective nls.
  $pn_nl_fh = fopen($pn_file_name, "r");
  if ($pn_nl_fh == FALSE) {
    voterdb_debug_msg("Failed to open NLP Voters File",'');
    return FALSE;
  }
  // Discard the first record, it's the header and it was already checked.
  fgets($pn_nl_fh);
  // Delete the list of NLs in this group from the database.
  $nlObj = $form_state['voterdb']['nlObj'];
  $nlObj->deleteNlGrp($pn_county);
 // Read each record and enter each NL in the database.
  do {
    // Get the raw nl record in the upload file.
    $pn_nl_raw = fgets($pn_nl_fh);
    if (!$pn_nl_raw) {break;}  // Break out of DO loop at end of file.
    // Remove any stuff that might be a security risk.
    $pn_nl_info = array();
    $pn_nl_info_raw = explode("\t", $pn_nl_raw);
    foreach ($pn_nl_info_raw as $infoRaw) {
      $pn_nl_info[] = sanitize_string($infoRaw);
    }

    $pn_MCID = $pn_nl_info[$pn_field_pos['mcid']];
    if(empty($pn_MCID)) {continue;} // Skip an empty record.
    $pn_NLfname =  str_replace("'", "&#039;",$pn_nl_info[$pn_field_pos['firstName']]);
    $pn_NLlname = str_replace("'", "&#039;",$pn_nl_info[$pn_field_pos['lastName']]);
    $pn_NLnickname = $pn_nl_info[$pn_field_pos['nickname']];
    if(empty($pn_NLnickname)) {
      $pn_NLnickname = $pn_NLfname;
    }
    
    $pn_ncounty = $pn_nl_info[$pn_field_pos['county']];
    // Check for bad MyCampaign record.
    if (empty($pn_ncounty)) {
      $pn_ncounty = $pn_county;
    } else {
      // replace the blank with an underscore and remove any dots.
      $pn_ncounty = str_replace(array(' ','.'), array('_',''), $pn_county);
    }
    $pn_HDraw = $pn_nl_info[$pn_field_pos['hd']];
    $pn_NLHD = ltrim($pn_HDraw, "0");
    $pn_pct = $pn_nl_info[$pn_field_pos['pct']];
    // Protect the apostrophe if used in a last name like O'Brian.
    // Check if the HD or Pct is missing.
    if (empty($pn_pct) OR empty($pn_NLHD)) {
      // Check if we have a repair record.
      if (isset ($pn_fixes['mcid']))  {  
        // Use the fixes for HD and Pct.
        $pn_NLHD = $pn_fixes[$pn_MCID]['hd'];
        $pn_pct = $pn_fixes[$pn_MCID]['pct'];
        drupal_set_message("HD and Pct repaired for ".$pn_NLnickname." ". $pn_NLlname,"warning");
      } else {
        drupal_set_message("Opps, HD or Pct is missing for ".$pn_NLnickname." ".$pn_NLlname,"warning");
        $pn_NLHD = $pn_pct = 0;
      }
    }
        
    $nlRecord['mcid'] = $pn_MCID;
    $nlRecord['lastName'] = $pn_NLlname;
    $nlRecord['firstName'] = $pn_NLfname;
    $nlRecord['nickname'] = $pn_NLnickname;
    $nlRecord['hd'] = $pn_NLHD;
    $nlRecord['county'] = $pn_ncounty;
    $nlRecord['pct'] = $pn_pct;
    $nlRecord['address'] = $pn_nl_info[$pn_field_pos['address']].', '.$pn_nl_info[$pn_field_pos['city']];
    $nlRecord['email'] = $pn_nl_info[$pn_field_pos['email']];
    $nlRecord['phone'] = (empty($pn_nl_info[$pn_field_pos['phone']]))?NULL:$pn_nl_info[$pn_field_pos['phone']];
    $nlRecord['homePhone'] = empty($pn_nl_info[$pn_field_pos['homePhone']])?NULL:$pn_nl_info[$pn_field_pos['homePhone']];
    $nlRecord['cellPhone'] = empty($pn_nl_info[$pn_field_pos['cellPhone']])?NULL:$pn_nl_info[$pn_field_pos['cellPhone']];
    //voterdb_debug_msg('nlrecord', $nlRecord);
    $insertOk = $nlObj->createNl($nlRecord);
    //voterdb_debug_msg('insertok', $insertOk);
    // INSERT this NL into the list for this group.
    if($insertOk) {
      $nlObj->createNlGrp($pn_MCID,$pn_county);
      // Create a status record if on does not already exist.
      $pn_nl_status = $nlObj->getNlsStatus($pn_MCID,$pn_county);
      //$pn_nl_status = voterdb_nls_status('GET',$pn_MCID,$pn_county,NULL);
      if (!empty($pn_nl_status)) {
        $nlObj->setNlsStatus($pn_nl_status);
        //voterdb_nls_status('PUT',$pn_MCID,$pn_county,$pn_nl_status);
      }
    }
      
  } while (TRUE);  // Keep looping to read records until the break at EOF.
  db_set_active('default');
  fclose($pn_nl_fh);
  return TRUE;   // The NL database is complete.
}
/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_nlupload_form
 *
 * Create the form for uploading the list of potential NLs.   If the user
 * provides a valid export of a list of prospective NLs, the file is read
 * and the database of NLs is created.   Exporting the list from MyCampaign
 * is tedious and should be an automatic function using the long promised
 * API for VoteBuilder.
 * 
 * @param type $form
 * @param type $form_state
 * 
 * @return $form
 */
function voterdb_nlupload_form($form, &$form_state) {
  $ul_button_obj = new NlpButton();
  $ul_button_obj->setStyle();
  if(!voterdb_get_group($form_state)) {return;}
  $ul_county = $form_state['voterdb']['county'];
  // Create the form to display of all the NLs.
  $ul_banner = voterdb_build_banner ($ul_county);
  $form['note'] = array (
    '#type' => 'markup',
    '#markup' => $ul_banner
  ); 
  
  $nlObj = new NlpNls();
  $form_state['voterdb']['nlObj'] = $nlObj;
  
  // Description.
  $form['nldesc'] = array(
      '#type' => 'item',
      '#title' => t('Description of NL upload'),
      '#markup' => 'The NL list is an export from MyCampaign in tab delimited
        format.  The fields we want in the export are name, nickname, address,
        email, Preferred phone, home phone, cell phone, county,
        legislative district and Precinct.',
  );
  // Add a file upload file.
  $form['upload'] = array(
      '#type' => 'file',
      '#title' => t('Choose a file and then click Upload'),
      '#description' => t('Pick a file exported from MyCampaign in tab
        delimited format (.txt) to upload.')
  );
  // Add a submit button.
  $form['submit'] = array(
      '#type' => 'submit',
      '#value' => 'Upload',
  );
  
  $form['done'] = array(
    '#markup' => '<p><a href="nlpadmin?County='.$ul_county.'" class="button ">Return to Admin page >></a></p>',
  );
  return $form;
}
/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_nlupload_form_validate
 *
 * We validate that the submitted file appears to be a
 * valid export from MyCampaign with the columns we need. 
 *
 * @param type $form
 * @param type $form_state
 * @return boolean
 */
function voterdb_nlupload_form_validate($form, &$form_state) {
  // If a file was uploaded, then check that it is a MyCampaign export.
  if (isset($_FILES['files']) && is_uploaded_file($_FILES['files']['tmp_name']['upload'])) {
    $vv_tmp_name = $_FILES['files']['tmp_name']['upload']; // system temp name
    $vv_nls_fh = fopen($vv_tmp_name, "r");
    if ($vv_nls_fh == FALSE) {
      drupal_set_message("Failed to open NL File Upload",'error');
      form_set_error('upload', 'Fix the problem before resubmit.');
      return FALSE;
    }
    // Get the header record.
    $vv_nls_raw = fgets($vv_nls_fh);
    if (!$vv_nls_raw) {
      drupal_set_message('Failed to read MC NL File Header', 'error');
      form_set_error('upload', 'Fix the problem before resubmit.');
      return FALSE;
    }
    $vv_header_record = sanitize_string($vv_nls_raw);
    // Extract the column headers.
    $vv_column_header = explode("\t", $vv_header_record);
    
    $nlObj = $form_state['voterdb']['nlObj'];
    //voterdb_debug_msg('nlObj', $nlObj);
    $vv_hdr_decode = $nlObj->decodeNlHdr($vv_column_header);
    //voterdb_debug_msg('decoe', $vv_hdr_decode);
    if(!$vv_hdr_decode['ok']) {
      foreach ($vv_hdr_decode['err'] as $errMsg) {
        drupal_set_message($errMsg,'warning');
      }
      form_set_error('upload', 'Fix the problem before resubmit.');
      return FALSE;
    }

    fclose($vv_nls_fh);
    // Set files to form_state, to process when form is submitted.
    $form_state['voterdb']['file'] = $vv_tmp_name;
    $form_state['voterdb']['pos'] = $vv_hdr_decode['pos'];
  } else {
    // Set error.
    drupal_set_message('The file was not uploaded.', 'error');
    form_set_error('upload', 'Error uploading file.');
    return FALSE;
  }
  //drupal_set_message('The NL export file has been validated.', 'status');
}
/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_nlupload_form_submit
 *
 * The supplied file is used to create a MySQL database of potential NLs.  Each
 * record is read from the uploaded file, the column data extracted, and
 * a record inserted for each NL.  The database is use to simplify the search
 * for an NL and related information.
 * 
 * @param type $form
 * @param type $form_state
 */
function voterdb_nlupload_form_submit($form, &$form_state) {
  // Copy the MyCampaign export to the database.
  $vs_prep = voterdb_prepare_NL_database($form_state);
  if (!$vs_prep) {
    drupal_set_message('Opps, NL database build failed.','error');}
  else {
    drupal_set_message('The NL database has been created.','status');}
}