Tags and Mentions
=================


* [Home](help)


Like many other modern social networks, Friendika uses a special notation inside messages to indicate "tags" or contextual links to other entities.

**Mentions**

People are tagged by preceding their name with the @ character.

The following are various ways of indicating a person:

* @mike - indicates a known contact in your social circle whose nickname is "mike"
* @mike_macgirvin - indicates a known contact in your social circle whose  full name is "Mike Macgirvin". Note that spaces cannot be used inside tags.
* @mike@macgirvin.com - indicates the Identity Address of a person on a different network, or one that is *not* in your social circle. This can only be an email-style locator, not a web URL. 

Unless their system blocks unsolicited "mentions", the person tagged will likely receive a "Mention" post/activity or become a direct participant in the conversation in the case of public posts. Please note that Friendika often blocks incoming "mentions" from other networks and especially from people with no relationship to you. This is a spam prevention measure. 

Friendika makes no distinction between people and groups for the purpose of tagging. (Some other networks use !group to indicate a group.)

**Topical Tags**

Topical tags are indicated by preceding the tag name with the  # character. This will create a link in the post to a generalised site search for the term provided. For example, #cars will provide a search link for all posts mentioning 'cars' on your site. Topical tags are generally a minimum of three characters in length.  Shorter search terms are not likely to yield any search results, although this depends on the database configuration. The same rules apply as with names that spaces within tags are represented by the underscore character. It is therefore not possible to create a tag whose target contains an underscore.  

Tag searches may also use "boolean" logic. 

* \#bike - creates a search for "bike"
* \#bike_red - creates a search for posts that contain either the word "bike" OR the word "red".
* \#+bike_+red - creates a search for posts that contain both the word "bike" AND the word "red"  
* \#+bike_-blue - creates a search for posts that contain the word "bike" but do *not* contain the word "blue"


 

