<feed xml:lang="en-US" xmlns:georss="http://www.georss.org/georss" xmlns="http://www.w3.org/2005/Atom" xmlns:twitter="http://api.twitter.com">
  <title>Friendika</title>
  <id>tag:friendika:Status</id>
  <link type="text/html" rel="alternate" href="$rss.alternate"/>
  <link type="application/atom+xml" rel="self" href="$rss.self"/>
  <updated>$rss.updated</updated>
  <subtitle>Friendika timeline</subtitle>
  	{{ for $statuses as $status }}
    <entry>
      <title>$status.text</title>
      <content type="html">$status.text</content>
      <id>$status.id</id>
      <published>$status.created_at</published>
      <updated>$status.created_at</updated>
      <link type="text/html" rel="alternate" href="$status.url"/>
      <author>
        <name>$status.user.name</name>
        <uri>$status.user.url</uri>
      </author>
      <twitter:source>$status.source</twitter:source>
    </entry>
    {{ endfor }}
</feed>