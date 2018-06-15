<?php
/*
 * Name:  voterdb_instructions_get.php               V4.1 2/14/18
 */

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_get_instructions
 * 
 * Get the file names for the canvass and postcard instructions.  If they
 * don't exist, give the text 'not uploaded yet'.
 * 
 * @param type $gi_county
 * @return associated array of file names for the canvass or postcard instructions.
 */
function voterdb_get_instructions($gi_county) {
  db_set_active('nlp_voterdb');
  $gi_tselect = "SELECT * FROM {".DB_INSTRUCTIONS_TBL."} WHERE  ".
    NI_COUNTY. " = :county ";
  $gi_targs = array(
    ':county' => $gi_county);
  $gi_result = db_query($gi_tselect,$gi_targs);
  $gi_instructs = $gi_result->fetchAll(PDO::FETCH_ASSOC);
  // Get the known instructions, either canvass, postcard or both.
  $gi_flist = array(NE_CANVASS,NE_POSTCARD,NE_ABSENTEE);
  foreach ($gi_flist as $gi_type) {
    $gi_options[$gi_type][NI_FILENAME] = NULL;
  }
  foreach ($gi_instructs as $ni_instruct) {
    $gi_type = $ni_instruct[NI_TYPE];
    $gi_options[$gi_type] = $ni_instruct;  // file name.
  } 
  db_set_active('default');
  return $gi_options;
}