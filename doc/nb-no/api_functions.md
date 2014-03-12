Red Twitter API
===============

Det "grunnleggende" Red web API-et er basert på Twitter API-et, siden dette gir umiddelbar samhandling med et stort antall tredjepartsklienter og programmer uten å kreve noen kodeendring hos disse. Det er også et super-set av StatusNet-versjonen av Twitter API-et, siden den også har bred eksisterende støtte.

Red har flere muligheter som ikke vises gjennom Twitter-grensesnittene, der vi tvinges til å gjøre API-funksjoner "dummere" for å kunne arbeide med den primitive kommunikasjons- og personvernmodellen i Twitter/StatusNet. Vi planlegger å utvide Twitter-API-et slik at Red-spesifikke klienter kan ta i bruk alle funksjoner i Red uten begrensninger.

Et dedikert Red API som samvirker med egne datastrukturer og tillatelser er under utvikling, og dette krever ikke oversettelse til andre personvern- og tillatelsesmodeller og lagringsformater. Denne vil bli beskrevet i andre dokumenter. Prefikset for alle egne endepunkter er 'api/red'.

Red tilbyr tilgang til flere kanaler via samme innloggingskonto. Med Red vil enhver API-funksjon som krever autentisering akseptere et parameter - &channel={channel_nickname} - og vil velge den kanalen og gjøre den gjeldende før utføring av API-kommandoen. Som standard er det standardkanalen i kontoen som velges.

Red tilbyr også en utvidet tillatelsesmodell. Grunnet fraværet av Red-spesifikke API kall til å angi tillatelser, så vil disse innstillingene bli satt til standardtillatelsene assosiert med den gjeldende kanalen.

Red vil antakelig aldri helt kunne støtte Twitter sine 'api/friendships' funksjoner, fordi Red er ikke et sosialt nettverk og har ikke innebygget noe konsept om "vennskap" - den gjenkjenner tillatelser til å gjøre ting (eller ikke gjøre ting hvis det er det som trengs).

Tegnforklaring: T= Twitter, S= StatusNet, F= Friendica, R= Red, ()=Virker ikke ennå, J= kun JSON (XML-formater er avlegs)



Twitter API kompatible funksjoner:

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


Twitter API funksjoner støttet av StatusNet men for øyeblikket ikke av Friendica eller Red

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

Twitter API funksjoner som for øyeblikket ikke er støttet av StatusNet 

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


StatusNet kompatible utvidelser til Twitter API-et støttet av både Friendica og Red

*	api/statusnet/version				S,F,R
*	api/statusnet/config				S,F,R

Friendica API utvidelser til Twitter API-et støttet av både Friendica og Red

*	api/statuses/mediap					F,R


Red-spesifikke API utvidelser til Twitter API-et som ikke er støttet av Friendica

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


Foreslåtte Red API utvidelser til Twitter API-et

*	api/statuses/edit					(R),J
*	api/statuses/permissions			(R),J
*	api/statuses/permissions/update		(R),J
*	api/statuses/ids					(R),J   # søk  etter eksisterende message_id før importering av fremmed innlegg
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


