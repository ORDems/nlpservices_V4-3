<?php
/**
 * Name: voterdb_nladmin.php    V4.2   7/24/18
*/
require_once "voterdb_group.php";
require_once "voterdb_banner.php";

define('HINTS',
   'p.narrow {margin:6px 0px; padding:0px; font-size:medium;} 
    #hint1 { position: relative; }
    #hint1 a span { display: none; color: #0033ff; }
    #hint1 a:hover span { display: block; position: absolute; width: 300px;
          background-color: #ffffff; left: 200px; top: -50px; color: #0033ff; 
          padding: 5px; border: 2px solid #0033ff; border-radius:5px;}
  ');

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterbd_nlpadmin
 * 
 * Create a list of hyper links to the various functions of NLP services.  
 *
 * @return type
 */
function voterbd_nlpadmin() {
  $dn_state=array();
  if(!voterdb_get_group($dn_state)) {
    $output = "<p>Opps!</p>";
    return array('#markup' => $output);
    }
  $dn_style = HINTS;
  drupal_add_css($dn_style, array('type' => 'inline')); 
  $dn_county = $dn_state['voterdb']['county'];
  $output = voterdb_build_banner ($dn_county);
  $dn_grp_id = 'County='.$dn_county;
  global $base_url;
  $dn_path = $base_url ."/";
  // Start the table.
  $output .= "\n".'<div style="width:850px;">';
  $output .= "\n".'<table style="width:800px;"><body><tr><td style="width:380px;   vertical-align: top;">';
  // Admin Turfs section.
  $output .= "\n".'<div style="width:400px;"> <fieldset><legend><span style="font-size:large; color:#af2108; font_weight:bold;">Admin Turfs</span></legend>';
  $output .= "\n".'<p class="narrow" id="hint1"><a href="'.$dn_path.'nlpturfcheckin?'.$dn_grp_id.'">Check in a turf<span>You will need both the exported turf and the PDF files</span></a></a></p>';
  $output .= "\n".'<p class="narrow"><a href="'.$dn_path.'nlpturfdelete?'.$dn_grp_id.'">Delete a turf</a></p>';
  $output .= "\n".'<p class="narrow" id="hint1"><a href="'.$dn_path.'nlpturfdeliver?'.$dn_grp_id.'">Send email with turf to NL <span>Be sure you have identified a coordinator and uploaded the NLP instructions.</span> </a></p>';
  $output .= "\n".'</fieldset></div>';
  // Admin NL's section.
  $output .= "\n".'<div style="width:400px;"> <fieldset><legend><span style="font-size:large; color:#af2108; font_weight:bold;">Admin NLs</span></legend>';
  $output .= "\n".'<p class="narrow"><a href="'.$dn_path.'nlpupload?'.$dn_grp_id.'">Import the list of active NLs</a></p>';
  $output .= "\n".'<p class="narrow"><a href="'.$dn_path.'nlpdisplay?'.$dn_grp_id.'">Display the Active NL Management Page</a></p>';
  $output .= "\n".'<p class="narrow"><a href="'.$dn_path.'nlpcanvassresults?'.$dn_grp_id.'">NL Login</a></p>';
  $output .= "\n".'<p class="narrow" id="hint1"><a href="'.$dn_path.'nlplegislativefixes?'.$dn_grp_id.'">Create substitute HD/Pct for NL <span>This function will repair the HD and Pct numbers when they are missing from the MyCampaign export.</span> </a></p>';
  $output .= "\n".'<p class="narrow" ><a href="'.$dn_path.'nlpcoordinators?'.$dn_grp_id.'">Identify the district coordinators</a></p>';
  $output .= "\n".'<p class="narrow" ><a href="'.$dn_path.'nlpblocked?'.$dn_grp_id.'">List of NL emails that are undeliverable</a></p>';
  $output .= "\n".'<p class="narrow" id="hint1"><a href="'.$dn_path.'nlp-documents">NLP Documents <span>The latest documents for coordinators and admins can be found here.</span> </a></p>';
  $output .= "\n".'<p class="narrow" id="hint1"><a href="'.$dn_path.'nlpfixreport?'.$dn_grp_id.'">Fix NLS reports <span>This function allows you to set an NL report as inactive so it does not show in any reports.</span> </a></p>';
  $output .= "\n".'<p class="narrow" id="hint1"><a href="'.$dn_path.'nlpexportnlsstatus?'.$dn_grp_id.'">Export NL status report <span>This report gives the status of the NL activity for this cycle.</span> </a></p>';
  $output .= "\n".'</fieldset></div>';
  
  $output .= "\n".'</td><td style="width:380px;   vertical-align: top;">';
  
  // Admin election cycle section.
  $output .= "\n".'<div style="width:350px;"> <fieldset><legend><span style="font-size:large; color:#af2108; font_weight:bold;">Admin Election Cycle Start</span></legend>';
  //$output .= "\n".'<p class="narrow";><a href="'.$dn_path.'nlpgoals?'.$dn_grp_id.'">Set NL Volunteer recruitment goals</a></p>';
  $output .= "\n".'<p class="narrow";><a href="'.$dn_path.'nlpcandidates?'.$dn_grp_id.'">Set candidate names for this cycle</a></p>';
  $output .= "\n".'<p class="narrow";><a href="'.$dn_path.'nlpinstructions?'.$dn_grp_id.'">Upload the NLP instructions</a></p>';
  //$output .= "\n".'<p class="narrow";><a href="'.$dn_path.'nlpsurvey?'.$dn_grp_id.'">Create the survey question</a></p>';
  $output .= "\n".'<p class="narrow";><a href="'.$dn_path.'nlpballotsreceived?'.$dn_grp_id.'">Upload the Ballot Received status</a></p>';
  $output .= "\n".'<p class="narrow";><a href="'.$dn_path.'nlpballotcounts?'.$dn_grp_id.'">Upload the Crosstab counts</a></p>';
  $output .= "\n".'<p class="narrow";><a href="'.$dn_path.'nlprestorenlsreports?'.$dn_grp_id.'">Restore NLS reports</a></p>';
  $output .= "\n".'</fieldset></div>';
  // Admin end of cycle.
  $output .= "\n".'<div style="width:350px;"> <fieldset><legend><span style="font-size:large; color:#af2108; font_weight:bold;">Admin End of Election Cycle</span></legend>';
  $output .= "\n".'<p class="narrow";><a href="'.$dn_path.'nlpdisplayresults?'.$dn_grp_id.'">Display a summary of results</a></p>';
  $output .= "\n".'<p class="narrow";><a href="'.$dn_path.'nlpexportblob?'.$dn_grp_id.'">Export NL email blob</a></p>';
  $output .= "\n".'<p class="narrow";><a href="'.$dn_path.'nlpexportturfstatus?'.$dn_grp_id.'">Export canvassing status by turf</a></p>';
  $output .= "\n".'<p class="narrow";><a href="'.$dn_path.'nlpexportnlsreports?'.$dn_grp_id.'">Export NLS reports</a></p>';
  $output .= "\n".'</fieldset></div>';

  $output .= "\n</td></tr></body></table></div>";
  return array('#markup' => $output);
}