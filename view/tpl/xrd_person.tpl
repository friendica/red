<?xml version="1.0" encoding="UTF-8"?>
<XRD xmlns="http://docs.oasis-open.org/ns/xri/xrd-1.0">
 
    <Subject>{{$accturi}}</Subject>
 
    <Link rel="http://schemas.google.com/g/2010#updates-from" 
          type="application/atom+xml" 
          href="{{$atom}}" />
    <Link rel="http://webfinger.net/rel/profile-page"
          type="text/html"
          href="{{$profile_url}}" />
    <Link rel="http://portablecontacts.net/spec/1.0"
          href="{{$poco_url}}" />
    <Link rel="http://webfinger.net/rel/avatar"
          type="image/jpeg"
          href="{{$photo}}" />
 
</XRD>
