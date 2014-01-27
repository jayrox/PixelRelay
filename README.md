PixelRelay
==========
  
This is the web server side that handles uploads from the Android app PixelRelay
  
PixelRelay is an Android app that allows complete control of automatic image uploads  
to any supported web service.
  
This script is only for an example use case.  
It is simple by design and it's only purpose is to handle uploads.  
  
The idea is that you, the user, can control every aspect of the web service.  
You are free to use any web service framework, language, platform that you want.
  
The following params are sent from the app using POST:  
  
 version          -- PixelRelay app version  
 uploaded_file		-- The binary file uploaded  
 user_email		    -- The email address associated to the uploader's Google Play account  
 user_private_key	-- The private key used to authenticate the upload  
 file_host	    	-- The Image Host preference from the Android app  
 file_album		    -- The Photo Album preference  
 file_name	    	-- The name of the file within Android  
 file_mime	    	-- The mime type of the file  


==========
License
==========
Apache 2.0
