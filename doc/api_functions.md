Red Twitter API
===============

The "basic" Red web API is based on the Twitter API, as this provides instant compatibility with a huge number of third-party clients and applications without requiring any code changes on their part. It is also a super-set of the StatusNet version of the Twitter API, as this also has existing wide support.

Red has a lot more capability that isn't exposed in the Twitter interfaces or where we are forced to "dumb-down" the API functions to work with the primitive Twitter/StatusNet communications and privacy model. So we plan to extend the Twitter API in ways that will allow Red-specific clients to make full use of Red features without being crippled. 

A dedicated Red API is also being developed to work with native data structures and permissions and which do not require translating to different privacy and permission models and storage formats. This will be described in other documents. The prefix for all of the native endpoints is 'api/red'. 

Red provides multiple channels accesible via the same login account. With Red, any API function which requires authentication will accept a parameter &channel={channel_nickname} - and will select that channel and make it current before executing the API command. By default, the default channel associated with an account is selected. 
 
Red also provides an extended permission model. In the absence of any Red specific API calls to set permissions, they will be set to the default permissions settings which are associated with the current channel.  

Red will probably never be able to support the Twitter 'api/friendships' functions fully because Red is not a social network and has no concept of "friendships" - it only recognises permissions to do stuff (or not do stuff as the case may be).  

Legend: T= Twitter, S= StatusNet, F= Friendica, R= Red, ()=Not yet working, J= JSON only (XML formats deprecated)



Twitter API compatible functions:

*	api/account/verify_credentials		T,S,F,R
*	api/statuses/update					T,S,F,R
*	api/users/show						T,S,F,R
*	api/statuses/home_timeline			T,S,F,R
*	api/statuses/friends_timeline		T,S,F,R
*	api/statuses/public_timeline		T,S,F,R
*	api/statuses/show					T,S,F,R
*	api/statuses/retweet				T,S,F,R
*	api/statuses/destroy				T,S,F,(R)
*	api/statuses/mentions				T,S,F,(R)
*	api/statuses/replies				T,S,F,(R)
*	api/statuses/user_timeline			T,S,F,(R)
*	api/favorites						T,S,F,(R)
*	api/account/rate_limit_status		T,S,F,R
*	api/help/test						T,S,F,R
*	api/statuses/friends				T,S,F,R
*	api/statuses/followers				T,S,F,R
*	api/friends/ids						T,S,F,R
*	api/followers/ids					T,S,F,R
*	api/direct_messages/new				T,S,F,(R)
*	api/direct_messages/conversation	T,S,F,(R)
*	api/direct_messages/all				T,S,F,(R)
*	api/direct_messages/sent			T,S,F,(R)
*	api/direct_messages					T,S,F,(R)
*	api/oauth/request_token				T,S,F,R
*	api/oauth/access_token				T,S,F,R


Twitter API functions supported by StatusNet but not currently by Friendica or Red

*	api/favorites						T,S
*	api/favorites/create				T,S
*	api/favorites/destroy				T,S
*	api/statuses/retweets_of_me			T,S
*	api/friendships/create				T,S
*	api/friendships/destroy				T,S
*	api/friendships/exists				T,S
*	api/friendships/show				T,S
*	api/account/update_location			T,S
*	api/account/update_profile_background_image			T,S
*	api/account/update_profile_image			T,S
*	api/blocks/create					T,S
*	api/blocks/destroy					T,S

Twitter API functions not currently supported by StatusNet 

*	api/statuses/retweeted_to_me		T
*	api/statuses/retweeted_by_me		T
*	api/direct_messages/destroy			T
*	api/account/end_session				T,(R)
*	api/account/update_delivery_device	T
*	api/notifications/follow			T
*	api/notifications/leave				T
*	api/blocks/exists					T
*	api/blocks/blocking					T
*	api/lists							T


Statusnet compatible extensions to the Twitter API supported in both Friendica and Red

*	api/statusnet/version				S,F,R
*	api/statusnet/config				S,F,R

Friendica API extensions to the Twitter API supported in both Friendica and Red

*	api/statuses/mediap					F,R


Red specific API extensions to the Twitter API not supported in Friendica

*	api/account/logout					R
*	api/export/basic					R,J
*	api/friendica/config				R
*	api/red/config						R
*	api/friendica/version				R
*	api/red/version						R

*   api/red/channel/export/basic        R,J
*   api/red/channel/stream              R,J (currently post only)
*   api/red/albums                      R,J
*   api/red/photos                      R,J (option album=xxxx)


Red proposed API extensions to the Twitter API

*	api/statuses/edit					(R),J
*	api/statuses/permissions			(R),J
*	api/statuses/permissions/update		(R),J
*	api/statuses/ids					(R),J   # search for existing message_id before importing a foreign post
*	api/files/show						(R),J
*	api/files/destroy					(R),J
*	api/files/update					(R),J
*	api/files/permissions				(R),J
*	api/files/permissions/update		(R),J
*	api/pages/show						(R),J
*	api/pages/destroy					(R),J
*	api/pages/update					(R),J
*	api/pages/permissions				(R),J
*	api/pages/permissions/update		(R),J
*	api/events/show						(R),J
*	api/events/update					(R),J
*	api/events/permissions				(R),J
*	api/events/permissions/update		(R),J
*	api/events/destroy					(R),J
*	api/photos/show						(R),J
*	api/photos/update					(R),J
*	api/photos/permissions				(R),J
*	api/photos/permissions/update		(R),J
*	api/albums/destroy					(R),J
*	api/albums/show						(R),J
*	api/albums/update					(R),J
*	api/albums/permissions				(R),J
*	api/albums/permissions/update		(R),J
*	api/albums/destroy					(R),J
*	api/friends/permissions				(R),J


