<?php
/*
 * Name: voterdb_van_hdr.php      V3.1    10/15/17
 */

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_export_required
 *
 * Verify that all the required columns are present in an export from the
 * VAN or MyCampaign.  Issue an error message with the missing column named.
 *
 * @param type $er_fields
 * @param type $er_req
 * @param type $er_emsg
 * @return boolean
 */
function voterdb_export_required($er_fields,$er_req,$er_emsg) {
  $er_error = FALSE;
  $er_field_cnt = count($er_fields);
  $er_field = 1;
  do {
    if ($er_req[$er_field] AND !$er_fields[$er_field]) {
      $er_error = TRUE;
      $er_message = 'Export option '.$er_emsg[$er_field].' is missing';
      drupal_set_message($er_message, 'error');
    }
  } while (++$er_field < $er_field_cnt);
  return $er_error;
}
/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_decode_header
 *
 * Determine which columns hold the fields we want.  The export can have
 * many choices in which columns so this code deals with the extra columns
 * that may be present.
 *
 * @param type $vh_column_header - Header record from the NL export
 * @param type $vh_hdr_fields
 * @return array  array of indexes to the columns with the fields we want
 */
function voterdb_decode_header($vh_column_header,$vh_hdr_fields) {
  for ($index = 0; $index < count($vh_hdr_fields); $index++) {
    $vh_field_pos[$index] = 0;
  }
  // For each of the fields we need, find which column they are in for this export.
  $vh_field_cnt = 0;
  do {
    $vh_col_cnt = 0;
    do {
      if ($vh_hdr_fields[$vh_field_cnt] == $vh_column_header[$vh_col_cnt]) {
        $vh_field_pos[$vh_field_cnt] = $vh_col_cnt;
      }
    } while (++$vh_col_cnt < count($vh_column_header));
  } while (++$vh_field_cnt < count($vh_hdr_fields));
  return $vh_field_pos;
}