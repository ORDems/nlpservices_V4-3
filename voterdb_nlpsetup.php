<?php
/*
 * Name: voterdb_nlpsetup.php   V4.3 8/10/18
 * 
 * Creates the MySQL tables used by the module.  And, the table for the 
 * house district numbers is populated.  And, two basic pages are created
 * for use to display the call list and the mail list for an NL.
 */

require_once "voterdb_class_paths.php";
require_once "voterdb_class_counties.php";
require_once "voterdb_debug.php";

use Drupal\voterdb\NlpPaths;
use Drupal\voterdb\NlpCounties;


/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_hd_build
 * 
 * Build the list of county names and establish the index for each.
 * Populate the HD table with the house district numbers for each participating 
 * county.  The HD table must already be created.
 *
 */
function voterdb_hd_build($hb_county_defs) {
  $countiesObj = new NlpCounties();
  foreach ($hb_county_defs as $hb_county_name=>$hd_hd_def) { 
    foreach ($hd_hd_def as $hb_hd_num) {
      $countiesObj->createHdName($hb_county_name,$hb_hd_num);
    }
  }
  return;
}

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_create_node
 * 
 * Create a standard Drupal page for display of some content that the user
 * may want to print.  And create a page for the login challenge.
 * 
 * @param type $cn_name
 * @param type $cn_title
 * @param type $cn_title - type of code, php or html
 */
function voterdb_create_node($cn_name,$cn_title,$cn_type,$cn_promote) {
  // Check if the Drupal page exists.
  $cn_url = drupal_lookup_path('source', $cn_name, NULL);
  $cn_nid = 0;
  if ($cn_url) {
    $cn_nid_array = explode('/', $cn_url);
    $cn_nid = $cn_nid_array[1];
  }
  // Read the PHP code for the body.
  $cn_body_text = "";
  $cn_module_path = drupal_get_path('module','voterdb');
  $cn_suffix = ($cn_type=='php')?'php':'txt';
  $cn_call_file_name = $cn_module_path."/voterdb_".$cn_name.'.'.$cn_suffix;

  $cn_call_file_fh = fopen($cn_call_file_name,"r");
  do {
    $cn_php_line = fgets($cn_call_file_fh);
    if (!$cn_php_line) {break;}
    $cn_body_text .= $cn_php_line;
  } while (TRUE);
  fclose($cn_call_file_fh);
  if ($cn_nid == 0) {
    // Call List page does not exist, create one
    $cn_node = new stdClass();
    $cn_node->type = "page";
    node_object_prepare($cn_node);
    $cn_format = ($cn_type=='php')?'php_code':'full_html';
    $cn_node->title    = $cn_title;
    $cn_node->promote = ($cn_promote)?NODE_PROMOTED:NODE_NOT_PROMOTED;
    $cn_node->language = LANGUAGE_NONE;
    $cn_node->body[$cn_node->language][0]['value']   = $cn_body_text;
    $cn_node->body[$cn_node->language][0]['format']  = $cn_format;
    $cn_node->path = array('alias' => $cn_name);
    node_save($cn_node);
    $cn_nid = $cn_node->nid;
  } else {
    // The Call List page already exists, update it
    $cn_node = node_load($cn_nid,NULL,NULL);
    $cn_node->body[$cn_node->language][0]['value']   = $cn_body_text;
    node_save($cn_node);
  }
}


/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_prepare_county
 *
 * Build a database of NLs. Read the file exported from MyCampaign and 
 * process the submitted fields.
 * 
 * @param type 
 * @return boolean - TRUE if no errors in upload.
 */
function voterdb_prepare_county($pc_file_name){
  $pc_county_fh = fopen($pc_file_name, "r");
  if ($pc_county_fh == FALSE) {
    voterdb_debug_msg("Failed to open the county name File",'');
    return FALSE;
  }
  $pc_counties = array();
  do {
    $pc_county_raw = fgets($pc_county_fh);
    if (!$pc_county_raw) {break;}  // Break out of DO loop at end of file.
    // Remove any stuff that might be a security risk.
    $pc_county_sanitized = sanitize_string($pc_county_raw);
    $pc_county_record = trim(preg_replace('/\s+/',' ', $pc_county_sanitized));
    $pc_county_info = explode(",", $pc_county_record);
    $pc_count = count($pc_county_info);
    if($pc_count < 2) {
      voterdb_debug_msg('county', $pc_county_raw);
      drupal_set_message('There must be at least one HD.','error');
      return FALSE;
    }
    // replace the blank with an underscore and remove any periods.
    $pc_rcounty = $pc_county_info[0];
    $pc_county = str_replace(array(' ','.'), array('_',''), $pc_rcounty);
    //$pc_county = str_replace(' ', '_', $pc_rcounty);
    $pc_hds = array();
    for ($pc_i=1;$pc_i<$pc_count;$pc_i++) {
      $pc_hd = $pc_county_info[$pc_i];
      $pc_hds[] = $pc_hd;
    }
    sort($pc_hds);
    $pc_counties[$pc_county] = $pc_hds;
  } while (TRUE);  
  ksort($pc_counties);
  fclose($pc_county_fh);
  return $pc_counties; 
}

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_county_enum
 * 
 * Create an enumeration type for MySQL from the associative array
 * of county names.
 * 
 * @param type $ce_county_defs
 * @return string
 */
function voterdb_county_enum($ce_county_defs) {
  //$ce_enum = "ENUM(";
  $ce_enum = "";
  $ce_counties = array_keys($ce_county_defs);
  $ce_first = TRUE;
  foreach ($ce_counties as $ce_county) {
    if ($ce_first) {
      $ce_first = FALSE;
    } else {
      $ce_enum .= ",";
    }
    $ce_enum .= "'".$ce_county."'";
  }
  //$ce_enum .= ")";
  return $ce_enum;
}

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_setup_form
 *
 * Warn the user that this function will rebuild the voterdb database
 * and lose all existing information.
 *
 * @param type $form_id
 * @param type $form_state
 * @return string
 */
function voterdb_setup_form($form_id, &$form_state) {
  $form = array();
  $form['partipation'] = array (
    '#type' => 'markup',
    '#markup' => '<b>** WARNING **</b> This procedure rebuilds the database
      for the NLP program.
      All data associated with a prior election will be lost.  If you want
      to retain any data, please use the <b>"Export NL status report "</b> page to
      export and save the NL entered canvas results.'
  );
  $form['deletedatabase'] = array(
    '#type' => 'checkbox',
    '#title' =>
        t('By selecting this option you agree to rebuild the database.'),
    '#required' => TRUE,
  );
  // File with county names and HDs contained in county.
  $form['countynames'] = array(
    '#type' => 'file',
    '#title' => t('CSV with county names and list of HDs in the county.'),
    '#size' => 75,
    //'#required' => TRUE,
  );
  $form['submit'] = array(
    '#type' => 'submit',
    '#value' => 'Next >>'
  );
  return $form;
}

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_setup_form_validate
 *
 * Verif that the use supplied a CSV file with the county names and associated
 * house districts.
 *
 * @param type $form
 * @param type $form_state
 * @return boolean
 */
function voterdb_setup_form_validate($form, &$form_state) {
  $sv_name = $_FILES['files']['name']['countynames'];
  $sv_tmp_fn = $_FILES['files']['tmp_name']['countynames'];
  if (empty($sv_tmp_fn)) {
    form_set_error('countynames', 'A file is required.');
  }
    $sv_cname_txt = strtolower($sv_name);
    $sv_cname_txt_array = explode('.', $sv_cname_txt);
    $sv_ctype_txt = end($sv_cname_txt_array);
    if (!($sv_ctype_txt == 'txt' OR $sv_ctype_txt == 'csv' )) {
      form_set_error('countynames', 'The county name must be a txt or csv file.');
      return;
    }
  $sv_counties = voterdb_prepare_county($sv_tmp_fn);
  if(!$sv_counties) {
    form_set_error('countynames', 'Fix the county name file.');
    return;
  }
  $form_state['voterdb']['counties'] = $sv_counties;
  
  return;
}

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_setup_form_submit
 *
 * Delete any existing tables and build new ones.   All the field entries
 * will be built with appropriate attributes.  Then build the table with the
 * list of house districts for each supported county.
 *
 * @param type $form
 * @param type $form_state
 */
function voterdb_setup_form_submit($form, &$form_state) {
  $fs_counties = $form_state['voterdb']['counties'];
  $output = '<p><span style="font-size:24px; color:#0033ff;
             font-family:trebuchet ms,helvetica,sans-serif;"> Setup</span></p>';
  //$fs_enum = voterdb_county_enum($fs_counties);
  $counties = voterdb_county_enum($fs_counties);
  //Build the voterdb database tables.
  //$counties = "'Baker','Benton','Clackamas','Clatsop','Columbia','Coos','Crook','Curry','Deschutes','Douglas','Gilliam','Grant','Harney','Hood_River','Jackson','Jefferson','Josephine','Klamath','Lake','Lane','Lincoln','Linn','Malheur','Marion','Morrow','Multnomah','Polk','Sherman','Tillamook','Umatilla','Union','Wallowa','Wasco','Washington','Wheeler','Yamhill'";
  $schema = array();
  require_once "voterdb_schema.php";

  foreach ($schema as $name => $table) {
    voterdb_debug_msg($name.' created', '');
    db_set_active('nlp_voterdb');
    db_drop_table($name);
    db_create_table($name,$table);
    db_set_active('default');
  }
  // Build the tables in the Drupal database.
  //voterdb_build_tables($fs_enum,DB_DRUPAL_TBLS_ARRAY,DB_DRUPAL_FIELDS_ARRAY,'default');
  // Initial values for static fields.
  voterdb_hd_build($fs_counties);
  // Create the nodes we use for navigation and display.
  
  $pathsObj = new NlpPaths();
  
  voterdb_create_node($pathsObj::CALLLIST_PAGE,'GOTV Call List','php',FALSE);
  voterdb_create_node($pathsObj::MAILLIST_PAGE,'Post Card Mailing List','php',FALSE);
  voterdb_create_node($pathsObj::ERROR_PAGE,'NLP Login','txt',FALSE);
  //voterdb_create_front_page($pathsObj::FRONT_PAGE, $fs_counties);
  voterdb_create_node($pathsObj::FRONT_PAGE,'Neighborhood Leader Login','txt',TRUE);
  return $output;
}