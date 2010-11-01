	<entry>
		<author>
			<name>$name</name>
			<uri>$profile_page</uri>
			<link rel="photo"  type="image/jpeg" media:width="80" media:height="80" href="$thumb" />
			<link rel="avatar" type="image/jpeg" media:width="80" media:height="80" href="$thumb" />
		</author>

		<thr:in-reply-to ref="$parent_id" />
		<id>$item_id</id>
		<title>$title</title>
		<published>$published</published>
		<updated>$updated</updated>
		<content type="$type" >$content</content>
		<link rel="alternate" href="$alt" />
		<dfrn:comment-allow>$comment_allow</dfrn:comment-allow>
		<as:verb>$verb</as:verb>
		$actobj
		$mentioned
	</entry>

