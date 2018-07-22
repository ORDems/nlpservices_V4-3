  <?php
/*
 * Name: voterdb_export_blob.php   V4.2 7/16/18
 *
 */
//require_once "voterdb_constants_rr_tbl.php";
//require_once "voterdb_constants_log_tbl.php";
//require_once "voterdb_constants_nls_tbl.php";
require_once "voterdb_constants_voter_tbl.php";
//require_once "voterdb_constants_mb_tbl.php";
require_once "voterdb_group.php";
//require_once "voterdb_path.php";
require_once "voterdb_debug.php";
require_once "voterdb_banner.php";
require_once "voterdb_class_button.php";
require_once "voterdb_class_nls.php";

use Drupal\voterdb\NlpButton;
use Drupal\voterdb\GetBrowser;
use Drupal\voterdb\NlpNls;

define('DD_BLOB_FILE','email-blob');


/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_get_participating_counties  
 * 
 * Get a list of county names for which there are turfs assigned.  This is
 * the list of active counties for this cycle.
 * 
 * @return array - array of county names.
 */
function voterdb_get_participating_counties() {
  // Get the list of county names with turfs.
  db_set_active('nlp_voterdb');
  try {
    $pc_query = db_select(DB_NLPVOTER_GRP_TBL, 'r');
    $pc_query->addField('r', NV_COUNTY) ; 
    $pc_query->distinct();
    $pc_query->orderBy(NV_COUNTY);
    $pc_result = $pc_query->execute();
  }
  catch (Exception $e) {
    db_set_active('default');
    voterdb_debug_msg('e', $e->getMessage() );
    return NULL;
  }
  $pc_county_list = $pc_result->fetchAll(PDO::FETCH_ASSOC);
  db_set_active('default');
  $pc_names = array();
  foreach ($pc_county_list as $pc_name) {
    $pc_names[] = $pc_name[NV_COUNTY];
  } 
  return $pc_names;
}

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_create_blob
 * 
 * Create a blob for each HD in each requested county.  The emails are 
 * formated for gmail and separated by commas.
 * 
 * @param type $an_all - TRUE if all participating counties wanted.
 * @param type $an_county
 * @param type $dd_blob_uri - URI for the blob file.
 * @return type
 */
function voterdb_create_blob($an_all,$an_county,$dd_blob_uri) {
  // Open the results file.
  $an_blob_fh = fopen($dd_blob_uri,"w");
  // Check if the export is for all conunties or just one.
  if ($an_all) {
    $an_grp_array = voterdb_get_participating_counties();
  } else {
    $an_grp_array = array($an_county);  // Just one group.
  }
  $nlObj = new NlpNls();
  // Create a report for each requested county.
  $an_hd = '';
  $an_current_hd = '';
  $an_email_blob = '';
  $an_starttime = voterdb_timer('start',0);
  set_time_limit(60);
  foreach ($an_grp_array as $an_county) {
    $an_email_c = 'County: '.$an_county."\n";  // start blob with county name.
    fwrite($an_blob_fh,$an_email_c);
    // Get the needed info about each NL.
    
    $an_nl_list = $nlObj->getCountyNls($an_county);
    
    

    // For each NL, add the email to.
    $an_delimit = FALSE;
    foreach ($an_nl_list as $an_nl) {
      //voterdb_debug_msg('NL', $an_nl);
      $an_hd = $an_nl['hd'];
      if($an_hd != $an_current_hd) {
        $an_current_hd = $an_hd;
        if($an_email_blob != '') {
          // Wrie the blob for the current house district.
          fwrite($an_blob_fh,$an_email_blob."\n");
          $an_email_blob = '';
          $an_delimit = FALSE;
        }
        // State a new house district.
        fwrite($an_blob_fh,"HD: ".$an_hd."\n");
      }
      $an_email = $an_nl['email'];
      // restore the apostrophies.
      $an_nickname =  str_replace("&#039;", "'", $an_nl['nickname']); 
      $an_lname =  str_replace("&#039;", "'", $an_nl['lastName']);
      // If there is an email for this NL, add an entry to the blob.
      if($an_email != '') {
        if($an_delimit) {
          $an_email_blob .= ', ';
        } else {
          $an_delimit = TRUE;
        }
        $an_email_blob .= $an_nickname.' '.$an_lname. '<'.$an_email.'>';
      }  
    } 
    // Write the blob for the last HD.
    if($an_email_blob != '') {
      fwrite($an_blob_fh,$an_email_blob."\n");
      $an_email_blob = '';
      $an_delimit = FALSE;
    }
  }
  $an_totaltime = voterdb_timer('end',$an_starttime);
  $msg = 'The email blob were created in ' .round($an_totaltime,2). ' seconds.';
  drupal_set_message($msg,'status');
  fclose($an_blob_fh);
  return;
}

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_export_blob
 *
 * Create a blob of email addresses arranged by HD for 
 * use as the address for an email to communicate with NLs.  This helps a 
 * recruiter recruit volunteers for an election.
 *  
 * @return string - HTML for display with the links to the files.
 */
function voterdb_export_blob() {
  $dd_button_obj = new NlpButton();
  $dd_button_obj->setStyle();
  //$dn_style = HINTS;
  //drupal_add_css($dn_style, array('type' => 'inline')); 
  $form_state = array();
  if(!voterdb_get_group($form_state)) {return "";}
  $dd_county = $form_state['voterdb']['county'];
  $dd_all = (isset($form_state['voterdb']['ALL']))? TRUE : FALSE;
  $dd_banner = voterdb_build_banner ($dd_county);
  $output = $dd_banner;
  // Create a temp file for the blob.
  // The temp file will be deleted by Drupal after 6 hours.
  $dd_temp_dir = 'public://temp';
  // Use a date in the name to make the file unique., just in case two people 
  // are doing an export at the same time.
  $dd_cdate = date('Y-m-d-H-i-s',time());
  // Create the blob file.
  $dd_blob_uri = $dd_temp_dir.'/'.DD_BLOB_FILE.'-'.$dd_county.'-'.$dd_cdate.'.txt';
  $dd_blob_object = file_save_data('', $dd_blob_uri, FILE_EXISTS_REPLACE);
  $dd_blob_object->status = 0;
  file_save($dd_blob_object);
  // Now fill the file with information.
  //$msg = 'huh2.';
  voterdb_create_blob($dd_all,$dd_county,$dd_blob_uri);
  // Provide the external link to the user so the file can be downloaded.
  $dd_browser_obj = new GetBrowser();
  $dd_browser = $dd_browser_obj->getBrowser();
  $dd_browser_hint = $dd_browser['hint'];
  $dd_blob_url = file_create_url($dd_blob_uri);
  $output .= "\n".'<div style="width:450px;"> <fieldset><legend><span style="font-size:large; color:#af2108; font_weight:bold;">Email blob file</span></legend>';
  $output .= "<p>The email blob is a list of NLs for use as the address for an email using gmail.</p>";
  $output .= '<p id="hint1"> <a href="'.$dd_blob_url.'">Right-click to download NL email blob. <span>Remember to right-click the link and then select "'.$dd_browser_hint.'".</span> </a></p>';
  
  $output .= "\n".'</fieldset></div>';
  $output .= '<p><a href="nlpadmin?County='.$dd_county.'" class="button ">Return to Admin page >></a></p>';
  
  return $output;
}
