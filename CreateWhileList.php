
<?php

/*
   Script to create VOIP.MS whitelist CallerID Filters via the VOIP.MS SOAP API.
   Script is designed to create one CallerID Filter for each line in a
   text file formatted in the same format used by VOIP.MS when one exports
   ones phone book from VOIP.MS, namely CSV format, where column one is ignored by
   this script, column two, is the caller name surrounded by double quotes,
   and column three is the phone number in NXXNXXXXXX format.

   For this whitelist, I've just routed each number in the list to the main
   account, so they will just ring through without being affected by lower
   priority filters (such as a catch all filter with the wildcard number "*"
   that could route non-whitelisted numbers to an IVR). However, it is easy
   to modify the whitelist routing parameter values in this script to something
   different.

   Note that information, downloads, examples and signup for VOIP.MS API start at:
   https://voip.ms/m/api.php

   Note also that you MUST first enable the API for your account AND establish an API login
   and password, and then provide "class.voipms.php" (added to your include path) with
   this login and password via the variables $api_username and $api_password
   respectively. Personally, I just edited these two values directly into
   class.voipms.php for my simplistic use of this script.
*/

/*
   The below include file must be copied from the voip.ms API downloads into your include path.
   Since PHP is a server-side facility, this script will work only on a web SERVER, and not
   directly from inside a browser CLIENT.

   Also note that the SOAP and OpenSSL extensions must also be enabled on your web server via
   the correct php.ini file, where said enabling can be verified with phpinfo().

   As usual with this sort of thing, it behooves you to have "display_errors = On" as well
   as "log_errors = On".
*/


require_once ( "class.voipms.php" );


// Disable log file by commenting out the 2nd  line below and uncommenting the line right below:

// $LogHandle = "";

$LogHandle = fopen ( "whitelist_" . date ( "Y-m-d_H-i-s" ) . ".log", "w" );	// Log file



// Variables that the user can alter. ($routing MUST be altered; the others are optional)

$PhoneBook        = "VOIPMSphonebook.csv";		// Text file with Phone Book entries to whitelist

$routing          = "account:123456";			// Routing parameter: (where to route whitelisted call)

$did	          = "all";				// DIDs affected by filter

$fail_unreachable = "";					// Optional routing when DID is unreachable

$fail_busy        = "";					// Optional routing when DID is busy

$fail_noanswer    = "";					// Optional routing when DID doesn't answer



// Init script variables and Objects


$voipms 	  = new VoIPms();			// Make a new VoIPms object instance

$row    	  = 0;					// Counter for lines in $PhoneBook

$bad    	  = 0;					// Counter for number of Filter creations that failed

$good   	  = 0;					// Counter for number of Filter creations that succeeded



if ( ( $PhoneBookHandle = fopen ( $PhoneBook, "r" ) ) !== FALSE )		// Open Phone Book file
{
    while ( ( $data = fgetcsv ( $PhoneBookHandle, 1000, "," ) ) !== FALSE )	// Read next line from Phone  Book file
    {
        /* Documentation for setCallerIDFiltering from VOIP.MS:

              Parameters

              filter                  =>            ID for a specific Caller ID Filtering
                                                    (Example: 18915 / Leave empty to create a new one)
              callerid                => [Required] Caller ID that triggers the Filter
                                                    (i = Non USA, 0 = Anonymous, NPANXXXXXX)
              did                     => [Required] DIDs affected by the filter (all, NPANXXXXXX)
              routing                 => [Required] Route the call follows when filter is triggered
              failover_unreachable    =>            Route the call follows when unreachable
              failover_busy           =>            Route the call follows when busy
              failover_noanswer       =>            Route the call follows when noanswer
              note                    =>            Note for the Caller ID Filtering

              Output

              Array
              (
                  [status] => success
                  [filtering] => 18915
              )

           Documentation for most of these parameter values is essentially non-existent. The best way to find
           valid values is to manually create a desired entry via the voip.ms CallerID filtering web page
           and then delete it. When you delete it, a window will be displayed that shows the parameter
           values, which you can subsequently paste into setCallerIDFiltering if you wish to modify that command.
        */


        $response = $voipms->setCallerIDFiltering ( "", $data[2], $did, $routing, $fail_unreachable, $fail_busy, $fail_noanswer, $data[1] );

        $row++;

        if( $response['status'] != 'success' )		// Filter failed to create
        {
           $msg = "Line " . $row . ": " . "Error: \"" . $response['status'] . "\" failed for " . $data[1] . " at number " .  $data[2] .  " (usually means disallowed duplicated phone number).\n";

           $bad++;
        }

        else						// Filter created successfully
        {
           $msg = "Line " . $row . ": Filter" . $response['filtering'] . " created for " . $data[1] . " at number " .  $data[2] .  ".\r\n";

           $good++;
        }

        echo $msg, "<br>";

        if ( $LogHandle !== "" ) fwrite ( $LogHandle, $msg );
     }

     $msg = $row . " lines processed from " . $PhoneBook . ". Successfully created " . $good . " Filters; " . $bad . " Filters failed to create.\r\n";

     echo "<br>", $msg;


     if ( $LogHandle !== "" ) fwrite ( $LogHandle, "\r\n" . $msg );

     if ( $LogHandle !== "" ) fclose ( $LogHandle );


     fclose ( $PhoneBookHandle );

}

?>