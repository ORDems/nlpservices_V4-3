<?php
/*
 * Name: voterdb_export_nls_status_history.php   V4.0 12/17/17
 */
require_once "voterdb_constants_log_tbl.php";
require_once "voterdb_constants_nls_tbl.php";
require_once "voterdb_constants_rr_tbl.php";
require_once "voterdb_group.php";
require_once "voterdb_banner.php";
require_once "voterdb_debug.php";

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_update_rpt_history
 * 
 * Update the NLs status history for the given current cycle.   The history 
 * table entries for the cycle are removed (in case someone quit) and rebuilt
 * from the report table.
 * 
 * @param type $ur_cycle - the current cycle of reporting.
 *  
 * @return nothing
 */
function voterdb_update_rpt_history($ur_cycle) {
  // Identify all the NLs that have participated for this cycle.
  db_set_active('nlp_voterdb');
  try {
    $ut_query = db_select(DB_NLSSTATUS_TBL, 's');
    $ut_query->addField('s', NN_MCID);
    $ut_query->addField('s', NN_NLSIGNUP);
    $ut_query->addField('s', NN_TURFDELIVERED);
    $ut_query->addField('s', NN_RESULTSREPORTED);
    $ut_query->condition(NN_NLSIGNUP, 'Y');
    $ut_tresult = $ut_query->execute();
  }
  catch (Exception $e) {
    db_set_active('default');
    voterdb_debug_msg('e', $e->getMessage() );
    return;
  }
  db_set_active('default');
  // Remove the history for this cycle.
  try {
    db_set_active('nlp_voterdb');
    db_delete(DB_NLSSTATUS_HISTORY_TBL)
    ->condition(NY_CYCLE, $ur_cycle)
    ->execute();
  }
  catch (Exception $e) {
    db_set_active('default');
    voterdb_debug_msg('e', $e->getMessage() );
    return;
  }
  db_set_active('default');
  // For each NL that participated in this cycle, update the status history table.
  do {
    $ut_nlstat = $ut_tresult->fetchAssoc();
    if(!$ut_nlstat) {break;}
    // Get the name of this NL.
    db_set_active('nlp_voterdb');
    try {
    $ut_nquery = db_select(DB_NLS_TBL, 'n');
    $ut_nquery->addField('n', NH_SALUTATION);
    $ut_nquery->addField('n', NH_LNAME);
    $ut_nquery->addField('n', NH_COUNTY);
    $ut_nquery->condition(NH_MCID, $ut_nlstat[NN_MCID]);
    $ut_nresult = $ut_nquery->execute();
    }
    catch (Exception $e) {
      db_set_active('default');
      voterdb_debug_msg('e', $e->getMessage() );
      return;
    }
    $ut_nl = $ut_nresult->fetchAssoc();
    // Update the history table if the NL is still in the database.
    if(!empty($ut_nl)) {
      $ut_status = NULL;
      if ($ut_nlstat[NN_RESULTSREPORTED] == 'Y') {
        $ut_status = NY_REPORTEDRESULTS;
      } elseif ($ut_nlstat[NN_TURFDELIVERED] == 'Y') {
        $ut_status = NY_GIVENTURF;
      } elseif ($ut_nlstat[NN_NLSIGNUP] == 'Y') {
        $ut_status = NY_SIGNEDUP;
      }
      try {
        db_set_active('nlp_voterdb');
        db_insert(DB_NLSSTATUS_HISTORY_TBL)
          ->fields(array(
            NY_NLFNAME => $ut_nl[NH_SALUTATION],
            NY_NLLNAME => $ut_nl[NH_LNAME]  ,
            NY_COUNTY => $ut_nl[NH_COUNTY]  ,
            NY_STATUS => $ut_status,
            NY_CYCLE => $ur_cycle,
            NY_MCID => $ut_nlstat[NN_MCID]))
          ->execute();
        db_set_active('default');
      }
        catch (Exception $e) {
        db_set_active('default');
        voterdb_debug_msg('e', $e->getMessage() );
        return;
      }
    }
  } while (TRUE);
}

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_get_history
 * 
 * 
 * 
 * @param type $gh_cycle
 * @param type $gh_previous_cycle
 * @return string - path to the file with the report.
 */
function voterdb_get_history($gh_cycle,$gh_previous_cycle) {
  // The entire history table.  
  db_set_active('nlp_voterdb');
  try {
    $ut_query = db_select(DB_NLSSTATUS_HISTORY_TBL, 'h');
    $ut_query->fields('h');
    $ut_tresult = $ut_query->execute();
  }
  catch (Exception $e) {
    db_set_active('default');
    voterdb_debug_msg('e', $e->getMessage() );
    return;
  }
  db_set_active('default');
  // Pick out the records for the current and the previous cycle.
  $ut_history = array();
  do {
    $ut_nl = $ut_tresult->fetchAssoc();
    if(!$ut_nl) {break;} 
    // Record is for one of the two cycles we are comparing.  Build a history record.
    if ($ut_nl[NY_CYCLE] == $gh_previous_cycle OR $ut_nl[NY_CYCLE] == $gh_cycle ) {
      // If a history record already exists for this NL, use it.
      if(isset($ut_history[$ut_nl[NY_MCID]])) {
        if ($ut_nl[NY_CYCLE] == $gh_previous_cycle) {
          $ut_history[$ut_nl[NY_MCID]][$gh_previous_cycle] = $ut_nl[NY_STATUS];
        } else {
          $ut_history[$ut_nl[NY_MCID]][$gh_cycle] = $ut_nl[NY_STATUS];
        }
      // Otherwise create one.
      } else {
        if ($ut_nl[NY_CYCLE] == $gh_previous_cycle) {
          $ut_nl[$gh_previous_cycle] = $ut_nl[NY_STATUS];
          $ut_nl[$gh_cycle] = "";
        } else {
          $ut_nl[$gh_previous_cycle] = "";
          $ut_nl[$gh_cycle] = $ut_nl[NY_STATUS];
        }
        $ut_history[$ut_nl[NY_MCID]]=$ut_nl;
      }
    }
  } while (TRUE);
  // Create a file to save the report.
  $cd_voterdb_dir = drupal_get_path('module','voterdb');
  $cd_voterdb_dir .= '/voterdb_files/';
  $cd_file_name = $cd_voterdb_dir.'growth_report.txt';
  $pc_file_fh = fopen($cd_file_name,"w");
  // Write the header as the first record in this tab delimited file
  $pc_record = array("County", "MCID", "FName", "LName",
    "G2016", "P2018");
  $pc_string = implode("\t", $pc_record)."\tEOR\n";
  fwrite($pc_file_fh,$pc_string);
  // Write the history records to the file.
  foreach ($ut_history as $mcid => $pc_record) {
    $pc_record_row[NY_COUNTY] = $pc_record[NY_COUNTY];
    $pc_record_row[NY_MCID] = $mcid;
    $pc_record_row[NY_NLFNAME] = $pc_record[NY_NLFNAME];
    $pc_record_row[NY_NLLNAME] = $pc_record[NY_NLLNAME];
    $pc_record_row["G2016"] = $pc_record["G2016"];
    $pc_record_row["P2018"] = $pc_record["P2018"];
    $pc_string = implode("\t", $pc_record_row);
    $pc_string .= "\tEOR\n";
    fwrite($pc_file_fh,$pc_string);
  }
  fclose($pc_file_fh);
  return $cd_file_name;
}

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_export_nls_status_history
 * 
 * Create a file with the NL status changes for this cycle.
 * 
 * @return displayable HTML.
 */
function voterdb_export_nls_status_history() {
  $output = 'NL history report<br>';
  $gc_cycle = variable_get('voterdb_ecycle',"2016-11-G");
  voterdb_update_rpt_history($gc_cycle);
  $gc_previous_cycle = "G2016";
  $df_file_name =  voterdb_get_history($gc_cycle,$gc_previous_cycle);
  $output .= "<p><a href=".$df_file_name.">Right click here to download the NL history report.</a> "
    . "<i>(Use the Save link as... option)</i></p>";
  return array('#markup' => $output);
}