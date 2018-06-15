Setting up a new site:   Rev 4.0 (26 December 2017)

Start by installing and enabling the SMTP and the Bounce module.   The SMTP
module must be configured to permit the VoterDB module to send emails.   You 
will have to get the SMTP information from the server vendor.   Then install 
and enable the bounce module.  To use this module you will have to set up two 
emails.  One should be for sending emails and can be named 
notifications@nlpservices.org.  The email name is configurable and includes
the actual name of the site.   Also, you will need another email with the name 
bounce@nlpservices.org.  This email will be used to catch the bounce of 
undeliverable emails of Neighborhood Leaders.

The setup process creates two basic pages that use PHP code.  To allow PHP code, 
enable the PHP Filter module in the core.  From the admin menu, click 
Configuration and then click Text formats.   Click Add text format.  Name the 
new format "PHP Code" and select PHP evaluator and click Save configuration.

Then install and enable the VoterDB module.   Enabling the module will create
the permissions you will need later to set up users.

Setting up the database:

The VoterDB module uses a separate database from that used by Drupal.  You have 
to create this database and declare it to Drupal.  Find the setting.php file, 
typically located in /sites/default.   Edit this file to add the information 
about the new database.   Note that you will have to change the write 
permissions for both the file and the directory to alter settings.php.  Be sure 
to restore the permissions when you are done changing the file.  Look for the 
default database.  The variable will look like this:

$databases['default']['default'] = array(
  'driver' => 'mysql',
  'database' => 'nlpservi_drup411',
  'username' => 'nlpservi_drup411',
  'password' => 'xxxx',   // the xxxx will be the actual password.
  'host' => 'localhost',
  'prefix' => '8rk_',
  'port' => '',
 );

Add a new database for nlp_voterdb:

$databases['nlp_voterdb']['default'] = array(
  'driver' => 'mysql',
  'database' => 'nlpservi_voterdb',
  'username' => 'nlpservi_nldata',
  'password' => 'SomethingDifficult',
  'host' => 'localhost',
  'prefix' => 'nlp_',
  'port' => '',
 );

The database name, user name and password will be as set by the person creating
the database on the server.   This only declares the database to Drupal so
various functions work correctly.  Note: the prefix nlpservi in this example is
installation dependent.  Be sure to use the one provided for your installation.

Configuring Drupal Theme

NLP Services was developed and tested with the Garland theme.  There should be
no specific dependency on theme but the Garland standard options produce
pleasing NLP displays.

The NLP Services pages all assume they have the full width to display
information.  If the site has any blocks on either the right or left side
bars, suppress display of these sidebar blocks.  From the Admin menu, navigate
to Structure, Blocks, find the sidebar blocks that are active.  Click on 
configure for each and set the "Show block on specific pages" to except and
enter nlp*.

From the menu bar, click Appearance, then Settings for the Garland theme.
Upload the logo image.  The name is NLP-LOGO.png and you can find it in
the voterdb folder.   Also, upload the icon image.  This is called 
NLP-FAVICON.ico which is also in the voterdb folder.   Download both image 
files to your local system so you can use the Garland setting page to set them.

From the menu bar, click Configuration and then click the Site Information
link.   In Site Details set the Site name to NLP Services and the E-mail
address to notifications@nlpservices.org.

Configuring User Account Settings

The people with access to this site will be coordinators for county parties. 
The accounts will have four additional required fields added to the 
account record.   These fields are FirstName, LastName, Phone and County.  The
spelling and case are important as they are used in generating automatic
emails.   

From the Admin menu, click People and then Account Settings.  From that page
click the Manage Fields tab.   Click Add New Field to add each of the fields
for FirstName, LastName, Phone and County.   From the command line, select 
"Configuration", then chose "Account Settings" from the People section.  Then 
click the "Manage Fields" tab.  Choose "Add new field" and create FirstName, 
LastName, Phone and County.  Note the machine names will be field_firstname, 
field_lastname, field_phone and field_county.  The machine names must be these 
values.  Create the FirstName, LastName, and Phone fields with: type text, 
required, and no default.  The County field is configured as list(text) and the 
list is a complete list of the county names.   If the county names have more 
than one word, the spaces must be replaced with an underscore(_). (If the
underscore is missing, it will be added during the upload.)

Creating NLP Coordinators role

Any new NLP user must be assigned to this permission.   Click on the command 
"People", select the tab "Permissions" and click the link "Roles".  Add new 
role for NLP coordinator.  For the new NLP coordinator role click 
"edit permissions".  Slide down to the VoterDB module and select the 
"Access VoterDB tools" permission.  (If you select the 
"Administer VoterDB access" permission, you may be creating a vulnerability as 
this permission will allow the user to destroy the database.  Consider using 
this permission only for the admin.)  

Creating NLP Admin

Some of the NLP functions can alter or destroy an existing database and should
not be used during an election.  These functions are accessible only with the
permission "Administer VoterDB access".   At least one person responsible for
the administration of NLP Services should have this permission assigned to 
them.

Create the role for NLP Admin.  Provide both NLP permissions for this role.  
And, add the dashboard and user admin permissions so the user can create 
additional users.

Creating the database tables

Log in as the NLP Admin (Any authenticated user with the NLP Services admin 
permission.  Using the browser, enter www.nlpservices.org/nlpsetup.  This 
will create, or recreate, all the tables.   It does delete all existing tables
and any content they had.   

The nlpsetup function will require a comma separated file with one record for
each county in the state.  The first field is the county name followed by the
list of state house districts in the county.  The record for a county will 
look as follows:  Washington,24,26,31,32.   The file 
voterdb_oregon_county_names.txt is provided as an example.

Set 403 page 

Creating the tables will also create several basic pages.  One of these pages 
will be to catch errors when a user in authenticated.   From the menu bar, click
Configuration then click the Site Information link.  Change the Default 403 page
to login_error.  This will work only if you have run nlpsetup first.

Configure the election cycle

When logged in as the NLP Admin, enter www.nlpservices.org/nlpconfig in 
the browser address line.   Set the election Cycle ID, the password and other 
information for the NLs to get their turf.  The config function is 
non-distructive and can be run multiple times.

The password for NLs is a single value for every NL.  It's purpose is to keep
Internet bots from accessing the turfs.  The value is not case sensitive and
should be kept simple to help NLs complete the task of reporting results.  A
second password is provided to bridge the period from one election cycle to 
another where some NLs have a new turf walksheet and other still have to old
one.

An election cycle has an identifier in the form of yyyy-mm-t, where t is
G, P, S or U.  These signify General, Primary, Special and Undefined.  
Typically, U is for a ballot measure.  The election is also defined by the 
date, the date when early ballots are sent, and the date when NLs should be
making voter contact and reporting results.  The purpose of these dates is to
get the NL participation rate above 80%.

VoteBuilder is constantly updated to include the latest voting history. 
The column header for the latest voting history is unique and must be declared
for NLP Services.   The optional header is provided for the transition 
where and update is eminent and turfs are being cut.

The state name and the email address used to send notifications are also set
here.  Typically, they do not change for a cycle.