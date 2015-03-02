This is the webserver at {{$sitename}};
   
A routine check indicates the SSL certificate for this website is
not valid. Your website cannot fully participate in the RedMatrix
until this is resolved. Please check your certificate and with your
certificate provider or service provider to ensure it is "browser valid" 
and installed correctly. Self-signed certificates are NOT SUPPORTED 
and NOT ALLOWED in the RedMatrix.

The check is performed by fetching a URL from your website with strict
SSL checking enabled, and if this fails, checking again with SSL 
checks disabled. It's possible a transient error could produce this
message, but if any recent configuration changes have been made,
or if you receive this message more than once, please check your 
certificate. 

The error message is '{{$error}}'.   

Apologies for the inconvenience, 
	your web server at {{$siteurl}}