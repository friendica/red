<feed xml:lang="en-US" xmlns="http://www.w3.org/2005/Atom" xmlns:thr="http://purl.org/syndication/thread/1.0" xmlns:georss="http://www.georss.org/georss" xmlns:activity="http://activitystrea.ms/spec/1.0/" xmlns:media="http://purl.org/syndication/atommedia" xmlns:poco="http://portablecontacts.net/spec/1.0" xmlns:ostatus="http://ostatus.org/schema/1.0" xmlns:statusnet="http://status.net/schema/api/1/">
 <generator uri="http://status.net" version="0.9.7">StatusNet</generator>
 <id>tag:friendika:PublicTimeline</id>
 <title>Network on Friendika</title>
 <subtitle>Your network updates on Friendika</subtitle>
 <logo>$rss.logo</logo>
 <updated>$rss.updated</updated>
 <link type="text/html" rel="alternate" href="$rss.alternate"/>
 <link type="application/atom+xml" rel="self" href="$rss.self"/>
  	{{ for $statuses as $status }}
	<entry>
	 <activity:object-type>$status.objecttype</activity:object-type>
	 <id>$status.id</id>
	 <title>$status.text</title>
	 <content type="html">$status.html</content>
	 <link rel="alternate" type="text/html" href="$status.url"/>
	 <activity:verb>$status.verb</activity:verb>
	 <published>$status.published</published>
	 <updated>$status.updated</updated>

	 <link rel="ostatus:conversation" href="$status.url"/>
	 <!--
	 <source>
	  <id>http://identi.ca/api/statuses/user_timeline/397830.atom</id>
	  <title>Sin Mobopolitan</title>
	  <link rel="alternate" type="text/html" href="http://identi.ca/mobopolitan"/>
	  <link rel="self" type="application/atom+xml" href="http://identi.ca/api/statuses/user_timeline/397830.atom"/>
	  <link rel="license" href="http://creativecommons.org/licenses/by/3.0/"/>
	  <icon>http://avatar.identi.ca/397830-96-20110312195623.jpeg</icon>
	  <updated>2011-04-21T18:39:32+00:00</updated>
	 </source>
	 -->
	 <link rel="self" type="application/atom+xml" href="$status.self"/>
	 <link rel="edit" type="application/atom+xml" href="$status.edit"/>
	 <statusnet:notice_info local_id="$status.id" source="$status.source" favorite="false" repeated="false">
	 </statusnet:notice_info>
	</entry>    
    {{ endfor }}
</feed>
