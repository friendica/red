<feed xml:lang="en-US" xmlns="http://www.w3.org/2005/Atom" xmlns:thr="http://purl.org/syndication/thread/1.0" xmlns:georss="http://www.georss.org/georss" xmlns:activity="http://activitystrea.ms/spec/1.0/" xmlns:media="http://purl.org/syndication/atommedia" xmlns:poco="http://portablecontacts.net/spec/1.0" xmlns:ostatus="http://ostatus.org/schema/1.0" xmlns:statusnet="http://status.net/schema/api/1/">
 <generator uri="http://status.net" version="0.9.7">StatusNet</generator>
 <id>tag:friendika:PublicTimeline</id>
 <title>Network on Friendika</title>
 <subtitle>Your network updates on Friendika</subtitle>
 <logo>$rss.logo</logo>
 <updated>$rss.updated</updated>
 <link type="text/html" rel="alternate" href="$rss.alternate"/>
 <link type="application/atom+xml" rel="self" href="$rss.self"/>
 
 
 <author>
	<activity:object-type>http://activitystrea.ms/schema/1.0/person</activity:object-type>
	<uri>$user.url</uri>
	<name>$user.name</name>
	<link rel="alternate" type="text/html" href="$user.url"/>
	<link rel="avatar" type="image/jpeg" media:width="106" media:height="106" href="$user.profile_image_url"/>
	<link rel="avatar" type="image/jpeg" media:width="96" media:height="96" href="$user.profile_image_url"/>
	<link rel="avatar" type="image/jpeg" media:width="48" media:height="48" href="$user.profile_image_url"/>
	<link rel="avatar" type="image/jpeg" media:width="24" media:height="24" href="$user.profile_image_url"/>
	<georss:point></georss:point>
	<poco:preferredUsername>$user.screen_name</poco:preferredUsername>
	<poco:displayName>$user.name</poco:displayName>
	<poco:urls>
		<poco:type>homepage</poco:type>
		<poco:value>$user.url</poco:value>
		<poco:primary>true</poco:primary>
	</poco:urls>
	<statusnet:profile_info local_id="$user.id"></statusnet:profile_info>
 </author>

 <!--Deprecation warning: activity:subject is present only for backward compatibility. It will be removed in the next version of StatusNet.-->
 <activity:subject>
	<activity:object-type>http://activitystrea.ms/schema/1.0/person</activity:object-type>
	<id>$user.url</id>
	<title>$user.name</title>
	<link rel="alternate" type="text/html" href="$user.url"/>
	<link rel="avatar" type="image/jpeg" media:width="106" media:height="106" href="$user.profile_image_url"/>
	<link rel="avatar" type="image/jpeg" media:width="96" media:height="96" href="$user.profile_image_url"/>
	<link rel="avatar" type="image/jpeg" media:width="48" media:height="48" href="$user.profile_image_url"/>
	<link rel="avatar" type="image/jpeg" media:width="24" media:height="24" href="$user.profile_image_url"/>
	<poco:preferredUsername>$user.screen_name</poco:preferredUsername>
	<poco:displayName>$user.name</poco:displayName>
	<poco:urls>
		<poco:type>homepage</poco:type>
		<poco:value>$user.url</poco:value>
		<poco:primary>true</poco:primary>
	</poco:urls>
	<statusnet:profile_info local_id="$user.id"></statusnet:profile_info>
 </activity:subject>
 
 
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

		<author>
			<activity:object-type>http://activitystrea.ms/schema/1.0/person</activity:object-type>
			<uri>$status.user.url</uri>
			<name>$status.user.name</name>
			<link rel="alternate" type="text/html" href="$status.user.url"/>
			<link rel="avatar" type="image/jpeg" media:width="106" media:height="106" href="$status.user.profile_image_url"/>
			<link rel="avatar" type="image/jpeg" media:width="96" media:height="96" href="$status.user.profile_image_url"/>
			<link rel="avatar" type="image/jpeg" media:width="48" media:height="48" href="$status.user.profile_image_url"/>
			<link rel="avatar" type="image/jpeg" media:width="24" media:height="24" href="$status.user.profile_image_url"/>
			<georss:point/>
			<poco:preferredUsername>$status.user.screen_name</poco:preferredUsername>
			<poco:displayName>$status.user.name</poco:displayName>
			<poco:address/>
			<poco:urls>
				<poco:type>homepage</poco:type>
				<poco:value>$status.user.url</poco:value>
				<poco:primary>true</poco:primary>
			</poco:urls>
			<!-- <statusnet:profile_info local_id="123710" following="true" blocking="false"></statusnet:profile_info> -->			
		</author>
		<!--Deprecation warning: activity:actor is present only for backward compatibility. It will be removed in the next version of StatusNet.-->
		<activity:actor>
			<activity:object-type>http://activitystrea.ms/schema/1.0/person</activity:object-type>
			<id>$status.user.url</id>
			<title>$status.user.name</title>
			<link rel="alternate" type="text/html" href="$status.user.url"/>
			<link rel="avatar" type="image/jpeg" media:width="106" media:height="106" href="$status.user.profile_image_url"/>
			<link rel="avatar" type="image/jpeg" media:width="96" media:height="96" href="$status.user.profile_image_url"/>
			<link rel="avatar" type="image/jpeg" media:width="48" media:height="48" href="$status.user.profile_image_url"/>
			<link rel="avatar" type="image/jpeg" media:width="24" media:height="24" href="$status.user.profile_image_url"/>
			<georss:point/>
			<poco:preferredUsername>$status.user.screen_name</poco:preferredUsername>
			<poco:displayName>$status.user.name</poco:displayName>
			<poco:address />
			<poco:urls>
				<poco:type>homepage</poco:type>
				<poco:value>$status.user.url</poco:value>
				<poco:primary>true</poco:primary>
			</poco:urls>
			<!-- <statusnet:profile_info local_id="123710" following="true" blocking="false"></statusnet:profile_info> -->
		</activity:actor>
		<link rel="ostatus:conversation" href="$status.conversation"/> 

	</entry>    
    {{ endfor }}
</feed>
