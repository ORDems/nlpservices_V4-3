<?php
/*
 * Name: voterdb_nls_results.php   V4.0  12/27/17
 */
require_once "voterdb_constants_rr_tbl.php";
require_once "voterdb_constants_nls_tbl.php";
require_once "voterdb_group.php";
require_once "voterdb_banner.php";
require_once "voterdb_path.php";

define('NR_NLS_RESULTS','nls_results'); // Name of the result file.

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_nls_results
 * 
 * Export the NL reports for a county and put them in a tab delimited file 
 * suitable for download.  The file can be used for archive of an election 
 * cycle, or
 * for import to the VAN.
 * 
 * The fields in the voterdb results table are selected and written to a file.
 * The VANID is moved to the first field in the file to make import to the VAN
 * easier.   The last field is called EOR and will contain the EOR text to 
 * meet the VAN requirements that the last field always has information.  The
 * nickname and last name of the NL is included to make the export file 
 * a little easier to read.
 *
 * @return $output - display with link to file for download.
 */
function voterdb_nls_results() {
  $form_state = array();
  if(!voterdb_get_group($form_state)) {return;}
  $nr_county = $form_state['voterdb']['county'];
  $nr_all = isset($form_state['voterdb']['ALL']);
  $nr_banner = voterdb_build_banner ($nr_county);
  $output = $nr_banner;
  $nr_path = voterdb_get_path('',$nr_county);
  $nr_file_name = $nr_path.NR_NLS_RESULTS.'.txt';
  $nr_cycle = variable_get('voterdb_ecycle', 'xxxx-mm-G');
  $nr_file_fh = fopen($nr_file_name,"w");
  // Write the header as the first record in this tab delimited file
  $nr_record = array(
        NC_VANID,      NC_CYCLE, NC_COUNTY, NC_MCID, 
        NC_CDATE,      NC_TYPE,  NC_VALUE,   NC_TEXT,    
        NH_NICKNAME, NH_LNAME);
  $nr_string = implode("\t", $nr_record)."\tEOR\n";
  fwrite($nr_file_fh,$nr_string);
  // get the reported results for this county.
  db_set_active('nlp_voterdb');
  try {
    $nr_query = db_select(DB_NLPRESULTS_TBL, 'r');
    $nr_query->join(DB_NLS_TBL, 'n', 'r.'.NC_MCID.' = n.'.NH_MCID );
    $nr_query->addField('r', NC_VANID);
    $nr_query->addField('r', NC_CYCLE);
    $nr_query->addField('r', NC_COUNTY);
    $nr_query->addField('r', NC_MCID);
    $nr_query->addField('r', NC_CDATE);
    $nr_query->addField('r', NC_TYPE);
    $nr_query->addField('r', NC_VALUE);
    $nr_query->addField('r', NC_TEXT);
    $nr_query->addField('n', NH_NICKNAME);
    $nr_query->addField('n', NH_LNAME);
    if(!$nr_all) {
      $nr_query->condition('r.'.NC_COUNTY,$nr_county);
      $nr_query->condition('r.'.NC_CYCLE,$nr_cycle);
    }
    $nr_query->orderBy('r.'.NC_COUNTY);
    $nr_query->orderBy('r.'.NC_CYCLE);
    $nr_result = $nr_query->execute();
  }
  catch (Exception $e) {
    db_set_active('default');
    voterdb_debug_msg('e', $e , __FILE__, __LINE__);
    return FALSE;
  }
  db_set_active('default');
  // Copy the results to a tab delimited file
  do {
    $nr_record_row1 = $nr_result->fetchAssoc();
    $nr_record_row = str_replace(array("\n","\r"), '', $nr_record_row1);
    if (!$nr_record_row) {break;}
    $nr_string = implode("\t", $nr_record_row);

    $nr_string .= "\tEOR\n";
    fwrite($nr_file_fh,$nr_string);
  } while (TRUE);
  fclose($nr_file_fh);
  $output .= "<p><a href=".$nr_file_name.">Right click here to download the NLs reports</a> <i>(Use the Save link as... option)</i></p>";
  return $output;
}