<?xml version="1.0" encoding="utf-8" ?>
<feed xmlns="http://www.w3.org/2005/Atom"
      xmlns:thr="http://purl.org/syndication/thread/1.0"
      xmlns:at="http://purl.org/atompub/tombstones/1.0"
      xmlns:media="http://purl.org/syndication/atommedia"
      xmlns:dfrn="http://purl.org/macgirvin/dfrn/1.0" 
      xmlns:zot="http://purl.org/zot"
      xmlns:as="http://activitystrea.ms/spec/1.0/"
      xmlns:georss="http://www.georss.org/georss" 
      xmlns:poco="http://portablecontacts.net/spec/1.0" 
      xmlns:ostatus="http://ostatus.org/schema/1.0" 
	  xmlns:statusnet="http://status.net/schema/api/1/" > 

  <id>{{$feed_id}}</id>
  <title>{{$feed_title}}</title>
  <generator uri="http://getzot.com" version="{{$version}}">{{$red}}</generator>
  <link rel="license" href="http://creativecommons.org/licenses/by/3.0/" />
{{if $hub}}
  {{$hub}}
{{/if}}
{{if $salmon}}
  {{$salmon}}
{{/if}}

  <updated>{{$feed_updated}}</updated>

  <zot:owner>
    <name>{{$name}}</name>
    <uri>{{$profile_page}}</uri>
    <link rel="photo"  type="{{$mimephoto}}" media:width="175" media:height="175" href="{{$photo}}" />
    <link rel="avatar" type="{{$mimephoto}}" media:width="175" media:height="175" href="{{$photo}}" />
  </zot:owner>
