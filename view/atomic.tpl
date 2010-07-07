<feed xmlns="http://www.w3.org/2005/Atom"
      xmlns:foaf="http://xmlns.com/foaf/0.1" >
  <id>$feed_id</id>
  <title>$feed_title</title>
  <updated>$feed_updated</updated>

  <author>
    <name>$name</name>
    <foaf:homepage rdf:resource="$profile_page" />
    <foaf:img rdf:resource="$thumb" />  
  </author>


  <entry>
     <id>$item_id</id>
     <title>$title</title>
     <link href="$link" />
     <updated>$updated</updated>
     <summary>$summary</summary>
     <content type="text/plain" ></content>
  </entry>
</feed>