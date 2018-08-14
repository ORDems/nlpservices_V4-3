<?php

  $instructionsType = "enum('canvass','postcard','absentee')";
  $nlContactMethod = "enum('canvass','minivan','phone','mail')";
  $nlSignupProgress = "enum('-','Asked','Yes','No','Quit')";
  $nlsStatusType = "enum('Asked','Declined','Signed up','Checked in turf','Delivered turf','Reported results','Quit')";
  $coordinatorScopeType = "enum('County','HD','Pct')";
  

  $schema['activist_codes'] = array( 
    'description' => 'The table for remembering the current activist codes.', 
    'fields' => array( 
      'FunctionName' => array( 'type' => 'varchar', 'length' => 22,  'not null' => TRUE, ),
      'Name' => array( 'type' => 'varchar', 'length' => 22, 'not null' => TRUE, ),
      'Type' => array( 'type' => 'varchar', 'length' => 22, 'not null' => TRUE, ),
      'Description' => array( 'type' => 'varchar', 'length' => 256, 'not null' => TRUE, ),
      'ActivistCodeId' => array( 'type' => 'int', 'unsigned' => TRUE, 'not null' => TRUE, ),
    ),
    'primary key' => array( 'FunctionName', ),
  );
  
  
  $schema['ballotcount'] = array( 
    'description' => 'The table of crosstabs and counts from VoteBuilde.', 
    'fields' => array( 
      'County' => array( 'mysql_type' => 'enum('.$counties.')', 'not null' => TRUE, ),
      'Party' => array( 'type' => 'varchar', 'length' => 64, ),
      'RegVoters' => array( 'type' => 'int', 'unsigned' => TRUE, ),
      'RegVoted' => array( 'type' => 'int', 'unsigned' => TRUE, ),
    ),
    'unique keys' => array( 'BC_Index' => array( 'County', 'Party', ),),
  );

  $schema['candidates'] = array( 
    'description' => 'The table to keep track of candidates.', 
    'fields' => array( 
      'Qid' => array( 'type' => 'int', 'size' => 'normal', 'unsigned' => TRUE, 'not null' => TRUE, ),
      'Name' => array( 'type' => 'varchar', 'length' => 32, 'not null' => TRUE, ),
      'Weight' => array( 'type' => 'int', 'size' => 'tiny', 'unsigned' => TRUE, 'not null' => TRUE, ),
      'Scope' => array( 'type' => 'varchar', 'length' => 8, 'not null' => TRUE, ),
      'County' => array( 'mysql_type' => 'enum('.$counties.')', 'length' => 32, 'not null' => TRUE, ),
      'CD' => array( 'type' => 'int', 'size' => 'tiny', 'unsigned' => TRUE, ),
      'HD' => array( 'type' => 'int', 'size' => 'tiny', 'unsigned' => TRUE, ),
      'Pcts' => array( 'type' => 'varchar', 'length' => 240, ),
    ),
    'primary key' => array( 'Qid', ),
  );
  
  
  $schema['contact_types'] = array( 
    'description' => 'Canvass response code types available in VoteBuilder.', 
    'fields' => array( 
      'Name' => array( 'type' => 'varchar', 'length' => 16, 'not null' => TRUE, ),
      'Code' => array( 'type' => 'int', 'unsigned' => TRUE, 'not null' => TRUE, ),
    ),
    'primary key' => array( 'Name', ),
  );

  
  $schema['coordinator'] = array( 
    'description' => 'The table to keep track of coordinators.', 
    'fields' => array( 
      'CIndex' => array( 'type' => 'serial', 'size' => 'normal', 'unsigned' => TRUE, 'not null' => TRUE, ),
      'County' => array( 'mysql_type' => 'enum('.$counties.')', 'not null' => TRUE, ),
      'FirstName' => array( 'type' => 'varchar', 'length' => 32, 'not null' => TRUE, ),
      'LastName' => array( 'type' => 'varchar', 'length' => 32, 'not null' => TRUE, ),
      'Email' => array( 'type' => 'varchar', 'length' => 60, 'not null' => TRUE, ),
      'Phone' => array( 'type' => 'varchar', 'length' => 20, 'not null' => TRUE, ),
      'Scope' => array( 'mysql_type' => $coordinatorScopeType , 'length' => 8, 'not null' => TRUE, ),
      'HD' => array( 'type' => 'int', 'size' => 'tiny', 'unsigned' => TRUE, 'not null' => TRUE, ),
      'Partial' => array( 'type' => 'int', 'size' => 'tiny', 'unsigned' => TRUE, 'not null' => TRUE, ),
    ),
    'primary key' => array( 'CIndex', ),
  );
  
  
  $schema['date_br'] = array( 
    'description' => 'The date strings used in matchbacks.', 
    'fields' => array( 
      'BRIndex' => array( 'type' => 'int', 'size' => 'tiny', 'unsigned' => TRUE, ),
      'BRDate' => array( 'mysql_type' => 'date', 'length' => 16, 'not null' => TRUE, ),
    ),
    'primary key' => array( 'BRIndex', ),
  );
  
  
  $schema['hd_def'] = array( 
    'description' => 'Identifies the house districts in each county.', 
    'fields' => array(
      'County' => array( 'mysql_type' => 'enum('.$counties.')', 'not null' => TRUE, ),
      'Number' => array( 'type' => 'int', 'size' => 'tiny', 'unsigned' => TRUE, 'not null' => TRUE, ),
    ),
    'unique keys' => array( 'HD_Index' => array( 'County', 'Number', ),),
  );
  
  
  $schema['instructions'] = array( 
    'description' => 'Keeps track of the instructions for the counties.', 
    'fields' => array(
      'County' => array( 'mysql_type' => 'enum('.$counties.')', 'not null' => TRUE, ),
      'Type' => array( 'mysql_type' => $instructionsType , 'length' => 12, 'not null' => TRUE, ),
      'FileName' => array( 'type' => 'varchar', 'length' => 160, 'not null' => TRUE, ),
      'Title' => array( 'type' => 'varchar', 'length' => 20, ),
      'Blurb' => array( 'type' => 'varchar', 'length' => 256, ),
    ),
    'unique keys' => array( 'OIndex' => array( 'County', 'Type', ),),
  );
  
  
  $schema['leg_district'] = array( 
    'description' => 'repairs for damaged legislative districts.',
    'fields' => array(
      'County' => array( 'mysql_type' => 'enum('.$counties.')', 'not null' => TRUE, ),
      'MCID' => array( 'type' => 'int', 'size' => 'normal', 'unsigned' => TRUE, 'not null' => TRUE, ),
      'FName' => array( 'type' => 'varchar', 'length' => 32, 'not null' => TRUE, ),
      'LName' => array( 'type' => 'varchar', 'length' => 32, 'not null' => TRUE, ),
      'HD' => array( 'type' => 'int', 'size' => 'tiny', 'not null' => TRUE, ),
      'Pct' => array( 'type' => 'varchar', 'length' => 32, 'not null' => TRUE, ),
    ),
    'primary key' => array( 'MCID', ),
  );
  

  $schema['magic_word'] = array( 
    'description' => 'Magic word chosen by NL.', 
    'fields' => array(
      'MCID' => array( 'type' => 'int', 'size' => 'normal', 'unsigned' => TRUE, 'not null' => TRUE, ),
      'MagicWord' => array( 'type' => 'varchar', 'length' => 128, 'not null' => TRUE, ),
    ),
    'primary key' => array( 'MCID', ),
  );

  
  $schema['magic_words'] = array( 
    'description' => 'List of 6-letter scrabble words.', 
    'fields' => array(
      'MWKey' => array( 'type' => 'int', 'size' => 'normal', 'unsigned' => TRUE, 'not null' => TRUE, ),
      'Word' => array( 'type' => 'varchar', 'length' => 8, 'not null' => TRUE, ),
    ),
    'primary key' => array( 'MWKey', ),
  );
  
  
  $schema['matchback'] = array( 
    'description' => 'Records the date ballots are recieved.', 
    'fields' => array(
      'VANID' => array( 'type' => 'int', 'size' => 'normal', 'unsigned' => TRUE, 'not null' => TRUE, ),
      'DateIndex' => array( 'type' => 'int', 'size' => 'medium', 'unsigned' => TRUE, 'not null' => TRUE, ),
      'County' => array( 'mysql_type' => 'enum('.$counties.')', 'not null' => TRUE, ),
    ),
    'primary key' => array( 'VANID', ),
  );
  
  
  $schema['nls'] = array( 
    'description' => 'Contact information about an NL.',
    'fields' => array(
      'MCID' => array( 'type' => 'int', 'size' => 'normal', 'unsigned' => TRUE, 'not null' => TRUE, ),
      'LastName' => array( 'type' => 'varchar', 'length' => 32, 'not null' => TRUE, ),
      'FirstName' => array( 'type' => 'varchar', 'length' => 32, 'not null' => TRUE, ),
      'Nickname' => array( 'type' => 'varchar', 'length' => 32,  ),
      'County' => array( 'mysql_type' => 'enum('.$counties.')', 'not null' => TRUE, ),
      'HD' => array( 'type' => 'int', 'size' => 'tiny', ),
      'Pct' => array( 'type' => 'varchar', 'length' => 32, ),
      'Address' => array( 'type' => 'varchar', 'length' => 128, ),
      'Email' => array( 'type' => 'varchar', 'length' => 60, ),
      'Phone' => array( 'type' => 'varchar', 'length' => 32, ),
      'HomePhone' => array( 'type' => 'varchar', 'length' => 32, ),
      'CellPhone' => array( 'type' => 'varchar', 'length' => 32, ),
    ),
    'primary key' => array( 'MCID', ),
  );
  
  $schema['nls_grp'] = array( 
    'description' => 'County that is using this NL.',
    'fields' => array(
      'MCID' => array( 'type' => 'int', 'size' => 'normal', 'unsigned' => TRUE, 'not null' => TRUE, ),
      'County' => array( 'type' => 'varchar', 'length' => 32, 'not null' => TRUE, ),
    ),
    'unique keys' => array( 'NL_Index' => array( 'County', 'MCID', ),),
  );
  
  
  $schema['nls_status'] = array( 
    'description' => 'Status of this NL.',
    'fields' => array(  
      'MCID' => array( 'type' => 'int', 'size' => 'normal', 'unsigned' => TRUE, 'not null' => TRUE, ),
      'County' => array( 'mysql_type' => 'enum('.$counties.')', 'not null' => TRUE, ),
      'Login_Date' => array( 'mysql_type' => 'date', 'length' => 32, ),
      'Contact' => array( 'mysql_type' => $nlContactMethod , 'length' => 8, ),
      'NLSignup' => array( 'type' => 'char', 'length' => 1, ),
      'Turfcut' => array( 'type' => 'char', 'length' => 1, ),
      'TurfDelivered' => array( 'type' => 'char', 'length' => 1, ),
      'ResultsReported' => array( 'type' => 'char', 'length' => 1, ),
      'Asked' => array( 'mysql_type' => $nlSignupProgress , 'length' => 8, ),
      'Notes' => array( 'type' => 'varchar', 'length' => 81, ),
      'UserName' => array( 'type' => 'varchar', 'length' => 32, ),
    ),
    'unique keys' => array( 'NS_Index' => array( 'County', 'MCID', ),),
  );
  
  
  $schema['nls_status_history'] = array( 
    'description' => 'Status of this NL.',
    'fields' => array(
      'HIndex' => array( 'type' => 'serial', 'size' => 'normal', 'unsigned' => TRUE, 'not null' => TRUE, ),
      'Date' => array( 'mysql_type' => 'date', 'length' => 32, ),
      'MCID' => array( 'type' => 'int', 'size' => 'normal', 'unsigned' => TRUE, 'not null' => TRUE, ),
      'County' => array( 'mysql_type' => 'enum('.$counties.')', 'not null' => TRUE, ),  
      'Cycle' => array( 'type' => 'varchar', 'length' => 10, ),
      'Status' => array( 'mysql_type' => $nlsStatusType , 'length' => 16, ),
      'NLfname' => array( 'type' => 'varchar', 'length' => 32, ),
      'NLlname' => array( 'type' => 'varchar', 'length' => 32, ),
    ),
    'indexes' => array( 
      'MCID' => array( 'MCID', ),
    ),
    'primary key' => array( 'HIndex', ),
  );
  

  
  $schema['pct_coordinator'] = array( 
    'description' => 'Maps Pct number to coordinator.', 
    'fields' => array(
      'CIndex' => array( 'type' => 'serial', 'size' => 'normal', 'unsigned' => TRUE, 'not null' => TRUE, ),
      'Pct' => array( 'type' => 'varchar', 'length' => 32, 'not null' => TRUE, ),
    ),
    'indexes' => array( 
      'GType' => array( 'CIndex', 'Pct',),
      'Pct' => array( 'Pct', ),
    ),
  );
  
  
  $schema['response_codes'] = array( 
    'description' => 'Response codes available in VoteBuilder.', 
    'fields' => array(
      'ContactType' => array( 'type' => 'varchar', 'length' => 16, 'not null' => TRUE, ),
      'Name' => array( 'type' => 'varchar', 'length' => 16, ),
      'Code' => array( 'type' => 'int', 'size' => 'normal', 'unsigned' => TRUE, 'not null' => TRUE, ),
    ),
    'indexes' => array( 
      'Response' => array( 'ContactType', 'Name',),
    ),
  );
  
  
  $schema['results'] = array( 
    'description' => 'Voter contact reports by NL.', 
    'fields' => array( 
      'Rindex' => array( 'type' => 'serial', 'size' => 'normal', 'unsigned' => TRUE, 'not null' => TRUE, ),
      'Recorded' => array( 'mysql_type' => 'date', 'length' => 16, ),
      'Cycle' => array( 'type' => 'varchar', 'length' => 10, 'not null' => TRUE, ),
      'County' => array( 'mysql_type' => 'enum('.$counties.')','not null' => TRUE, ),
      'Active' => array( 'type' => 'int', 'size' => 'tiny', 'unsigned' => TRUE, ),
      'VANID' => array( 'type' => 'int', 'size' => 'normal', 'unsigned' => TRUE, 'not null' => TRUE, ),
      'MCID' => array( 'type' => 'int', 'size' => 'normal', 'unsigned' => TRUE, 'not null' => TRUE, ),
      'Cdate' => array( 'mysql_type' => 'date', 'length' => 16, 'not null' => TRUE, ),
      'Type' => array( 'type' => 'varchar', 'length' => 16, ),
      'Value' => array( 'type' => 'varchar', 'length' => 22, ),
      'Text' => array( 'type' => 'varchar', 'length' => 190, ),
      'Qid' => array( 'type' => 'int', 'size' => 'normal', 'unsigned' => TRUE, ),
      'Rid' => array( 'type' => 'int', 'size' => 'normal', 'unsigned' => TRUE, ),
    ),
    'indexes' => array( 
      'VANID' => array( 'VANID', ),
    ),
    'primary key' => array( 'Rindex', ),
  );
  
  
  $schema['survey_questions'] = array( 
    'description' => 'Survey questions available in VoteBuilder.', 
    'fields' => array( 
      'Qid' => array( 'type' => 'int', 'size' => 'normal', 'unsigned' => TRUE, 'not null' => TRUE, ),
      'QuestionName' => array( 'type' => 'varchar', 'length' => 32, 'not null' => TRUE, ),
      'QuestionType' => array( 'type' => 'varchar', 'length' => 32, 'not null' => TRUE, ),
      'Cycle' => array( 'type' => 'varchar', 'length' => 8, 'not null' => TRUE, ),
      'ScriptQuestion' => array( 'type' => 'varchar', 'length' => 256, ),
    ),
    'primary key' => array( 'Qid', ),
  );
  
  
  $schema['survey_responses'] = array( 
    'description' => 'Survey questions available in VoteBuilder.', 
    'fields' => array( 
      'Rid' => array( 'type' => 'int', 'size' => 'normal', 'unsigned' => TRUE, 'not null' => TRUE, ),
      'ResponseName' => array( 'type' => 'varchar', 'length' => 32, 'not null' => TRUE, ),
      'Qid' => array( 'type' => 'int', 'size' => 'normal', 'unsigned' => TRUE, 'not null' => TRUE, ),
      'QuestionName' => array( 'type' => 'varchar', 'length' => 32, 'not null' => TRUE, ),
    ),
    'primary key' => array( 'Rid', ),
  );
  
  $schema['track'] = array( 
    'description' => 'Track interesting activity in NLP Services for debugging.', 
    'fields' => array( 
      'Indx' => array( 'type' => 'serial', 'size' => 'normal', 'unsigned' => TRUE, 'not null' => TRUE, ),
      'County' => array( 'mysql_type' => 'enum('.$counties.')', 'not null' => TRUE, ),
      'Type' => array( 'type' => 'varchar', 'length' => 10, 'not null' => TRUE, ),
      'Date' => array( 'mysql_type' => 'datetime', 'length' => 32, ),
      'User' => array( 'type' => 'varchar', 'length' => 62, ),
      'IP' => array( 'type' => 'varchar', 'length' => 45, ),
      'Status' => array( 'type' => 'varchar', 'length' => 32, ),
      'Info' => array( 'type' => 'varchar', 'length' => 256, ),
    ),
    'primary key' => array( 'Indx', ),
  );
  
  
  $schema['turf'] = array( 
    'description' => 'Turf description.', 
    'fields' => array( 
      'TurfIndex' => array( 'type' => 'serial', 'size' => 'normal', 'unsigned' => TRUE, 'not null' => TRUE, ),
      'County' => array( 'mysql_type' => 'enum('.$counties.')', 'not null' => TRUE, ),
      'MCID' => array( 'type' => 'int', 'size' => 'normal', 'unsigned' => TRUE, 'not null' => TRUE, ),
      'NLlname' => array( 'type' => 'varchar', 'length' => 32, 'not null' => TRUE, ),
      'NLfname' => array( 'type' => 'varchar', 'length' => 32, 'not null' => TRUE, ),
      'Delivered' => array( 'mysql_type' => 'date', ),
      'LastAccess' => array( 'mysql_type' => 'date',  ),
      'ReminderNeeded' => array( 'type' => 'char', 'length' => 1, ),
      'TurfName' => array( 'type' => 'varchar', 'length' => 160, ),
      'TurfPDF' => array( 'type' => 'varchar', 'length' => 160, ),
      'TurfMail' => array( 'type' => 'varchar', 'length' => 30, ),
      'TurfCall' => array( 'type' => 'varchar', 'length' => 30, ),
      'TurfPCT' => array( 'type' => 'varchar', 'length' => 32, ),
      'TurfPDF' => array( 'type' => 'varchar', 'length' => 160, ),
      'TurfHD' => array( 'type' => 'int', 'size' => 'tiny', ),
      'CommitDate' => array( 'mysql_type' => 'date', ),
      'ElectionName' => array( 'type' => 'varchar', 'length' => 120, ),
    ),
    'primary key' => array( 'TurfIndex', ),
  );
  
  
  $schema['van_api'] = array( 
    'description' => 'VoterBuilder authorizations for API.', 
    'fields' => array( 
      'Committee' => array( 'type' => 'varchar', 'length' => 64, 'not null' => TRUE, ),
      'URL' => array( 'type' => 'varchar', 'length' => 128, 'not null' => TRUE, ),
      'User' => array( 'type' => 'varchar', 'length' => 32, 'not null' => TRUE, ),
      'apiKey' => array( 'type' => 'varchar', 'length' => 128, 'not null' => TRUE, ),
    ),
    'primary key' => array( 'Committee', ),
  );
  
  
  $schema['voter'] = array( 
    'description' => 'Contact information about a voter.',
    'fields' => array(
      'VANID' => array( 'type' => 'int', 'size' => 'normal', 'unsigned' => TRUE, 'not null' => TRUE, ),
      'LastName' => array( 'type' => 'varchar', 'length' => 32, 'not null' => TRUE, ),
      'FirstName' => array( 'type' => 'varchar', 'length' => 32, 'not null' => TRUE, ),
      'Nickname' => array( 'type' => 'varchar', 'length' => 32,  ),
      'Age' => array( 'type' => 'char', 'length' => 3,  ),
      'Sex' => array( 'type' => 'char', 'length' => 1,  ),
      'StreetNo' => array( 'type' => 'varchar', 'length' => 16, ),
      'StreetPrefix' => array( 'type' => 'varchar', 'length' => 4, ),
      'StreetName' => array( 'type' => 'varchar', 'length' => 32, ),
      'StreetType' => array( 'type' => 'varchar', 'length' => 10, ),
      'AptType' => array( 'type' => 'varchar', 'length' => 10, ),
      'AptNo' => array( 'type' => 'varchar', 'length' => 10, ),
      'City' => array( 'type' => 'varchar', 'length' => 20, ), 
      'County' => array( 'mysql_type' => 'enum('.$counties.')', 'not null' => TRUE, ),
      'CD' => array( 'type' => 'int', 'size' => 'tiny', ),
      'HD' => array( 'type' => 'int', 'size' => 'tiny', ),
      'Pct' => array( 'type' => 'varchar', 'length' => 32, ),
      'HomePhone' => array( 'type' => 'varchar', 'length' => 32, ),
      'CellPhone' => array( 'type' => 'varchar', 'length' => 32, ),
      'mAddress' => array( 'type' => 'varchar', 'length' => 60, ),
      'mCity' => array( 'type' => 'varchar', 'length' => 20, ),
      'mState' => array( 'type' => 'char', 'length' => 3, ),
      'mZip' => array( 'type' => 'char', 'length' => 6, ),
      'Voting' => array( 'type' => 'varchar', 'length' => 40, ),
      'DateReg' => array( 'mysql_type' => 'date', ),
      'DORCurrent' => array( 'mysql_type' => 'date', ),
      'Party' => array( 'type' => 'char', 'length' => 2, ),
    ),
    'primary key' => array( 'VANID', ),
  );
  
  
  $schema['voter_grp'] = array( 
    'description' => 'Voters assigned to a turf.', 
    'fields' => array( 
      'indx' => array( 'type' => 'serial', 'size' => 'normal', 'unsigned' => TRUE, 'not null' => TRUE, ),
      'VANID' => array( 'type' => 'int', 'size' => 'normal', 'unsigned' => TRUE, 'not null' => TRUE, ),
      'County' => array( 'mysql_type' => 'enum('.$counties.')', 'not null' => TRUE, ),
      'MCID' => array( 'type' => 'int', 'size' => 'normal', 'unsigned' => TRUE, 'not null' => TRUE, ),
      'NLTurfIndex' => array( 'type' => 'int', 'size' => 'normal', 'unsigned' => TRUE, 'not null' => TRUE, ),
      'Status' => array( 'type' => 'char', 'length' => 1, ),
    ),
    'primary key' => array( 'indx', ),
  );
  
  
  $schema['voter_status'] = array( 
    'description' => 'Voters assigned to a turf.', 
    'fields' => array( 
      'VANID' => array( 'type' => 'int', 'size' => 'normal', 'unsigned' => TRUE, 'not null' => TRUE, ),
      'DORCurrent' => array( 'mysql_type' => 'date', ),
      'Moved' => array( 'type' => 'int', 'size' => 'tiny', 'unsigned' => TRUE, ),
      'Deceased' => array( 'type' => 'int', 'size' => 'tiny', 'unsigned' => TRUE, ),
      'Hostile' => array( 'type' => 'int', 'size' => 'tiny', 'unsigned' => TRUE, ),
      'NLPVoter' => array( 'type' => 'int', 'size' => 'tiny', 'unsigned' => TRUE, ),
    ),
    'primary key' => array( 'VANID', ),
  );
  
  