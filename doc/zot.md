Intro to Zot
============

Zot is a JSON-based web framework for implementing secure decentralised communications and services.

It differs from many other communication protocols by building communications on top of a decentralised identity and authentication framework. 

The authentication component is similar to OpenID conceptually but is insulated from DNS-based identities. Where possible remote authentication is silent and invisible. This provides a mechanism for internet scale distributed access control which is unobtrusive.

For example,

Jaquelina wishes to share photos with Roberto from her blog at "jaquelina.com.xyz", but to nobody else. Roberto maintains his own family website at "roberto.com.xyz". Zot allows Jaquelina to create an access list containing "Roberto" and allow Roberto unhindered access to the photos but without allowing Roberto's brother Marco to see the photos.

Roberto will only login once to his own website at roberto.com.xyz using his password. After this, no further passwords will be asked for. Marco may also have an account on roberto.com.xyz, but he is not allowed to see Jaquelina's photos.


Additionally, zot allows Roberto to use another site - gadfly.com.xyz, and after login to gadfly.com.xyz he can also access Jaquelina's private photos. Jaquelina does not have to do anything extra to allow this, as she has already given access rights of her private photos to Roberto - no matter what site he is logged into.  

Zot also allows basic messaging and communications with anybody else on the Zot network. 

In order to provide this functionality, zot creates a decentralised globally unique identifier for each node on the network. This global identifier is not linked inextricably to DNS, providing the requisite mobility. Many existing decentralised communications frameworks provide the communication aspect, but do not provide remote access control and authentication. Additionally most of these are based on 'webfinger' such that in our example, Roberto would only be recognised if he accessed Jaquelina's photos from roberto.com.xyz - but not from gadfly.com.xyz.


The primary issues zot addresses are 

* completely decentralised communications
* insulation from DNS based identity
* node mobility
* invisible or reduced "interaction" remote authentication
* high performance

We will rely on DNS-based user@host addresses as a "user-friendly" mechanism to let people know where you are, specifically on a named host with a given username, and communication will be carried out to DNS entities using TCP and the web.

But the underlying protocol will provide an abstraction layer on top of this, so that a communications node (e.g. "identity") can move to an alternate DNS location and (to the best of our ability) gracefully recover from site re-locations and/or maintain pre-existing communication relationships. A side effect of this requirement is the ability to communicate from alternate/multiple DNS locations and service providers and yet maintain a single online identity.

We will call this overlay network the "grid". Servers attached to this network are known as "hubs" and may support any number of individual identities. 

An identity does not necessarily correspond to a person. It is just something which requires the ability to communicate within the grid. 

The ability to recover will be accomplished by communication to the original location when creating a new or replacement identity, or as a fallback from a stored file describing the identity and their contacts in the case where the old location no longer responds. 

At least on the short term, the mobility of existing content is not a top priority. This may or may not take place at a later stage. The most important things we want to keep are your identity and your friends.  

Addresses which are shared amongst people are user@host, and which describe the **current** local account credentials for a given identity. These are DNS based addresses and used as a seed to locate a given identity within the grid. The machine communications will bind this address to a globally unique ID. A single globally unique ID may be attached or bound to any number of DNS locations. Once an identity has been mapped or bound to a DNS location, communications will consist of just knowing the globally unique address, and what DNS (url) is being used currently (in order to call back and verify/complete the current communication). These concepts will be specified in better detail. 

In order for an identity to persist across locations, one must be able to provide or recover 

* the globally unique ID for that identity
* the private key assigned to that identity
* (if the original server no longer exists) an address book of contacts for that identity.

This information will be exportable from the original server via API, and/or downloadable to disk or thumb-drive. 

We may also attempt to recover with even less information, but doing so is prone to account hijacking and will require that your contacts confirm the change. 

In order to implement high performance communications, the data transfer format for all aspects of zot is JSON. XML communications require way too much overhead.

Bi-directional encryption is based on RSA 4096-bit keys expressed in DER/ASN.1 format using the PKCS#8 encoding variant, with AES-256-CBC used for block encryption of variable length or large items.

Some aspects of well known "federation protocols" (webfinger, salmon, activitystreams, portablecontacts, etc.) may be used in zot, but we are not tied to them and will not be bound by them. The Red Matrix project is attempting some rather novel developments in decentralised communications and if there is any need to diverge from such "standard protocols" we will do so without question or hesitation. 

In order to create a globally unique ID, we will base it on a whirlpool hash of the identity URL of the origination node and a psuedo-random number, which should provide us with a 256 bit ID with an extremely low probability of collision (256 bits represents approximately 115 quattuorviginitillion or 1.16 X 10^77 unique numbers). This will be represented in communications as a base64url-encoded string. We will not depend on probabilities however and the ID must also be attached to a public key with public key cryptography used to provide an assurance of identity which has not been copied or somehow collided in whirlpool hash space.

Basing this ID on DNS provides a globally unique seed, which would not be the case if it was based completely on psuedo-random number generation.  

We will refer to the encoded globally unique uid string as zot_uid 

As there may be more than one DNS location attached/bound to a given zot_uid identity, delivery processes should deliver to all of them - as we do not know for sure which hub instance may be accessed at any given time. However we will designate one DNS location as "primary" and which will be the preferred location to view web profile information.

We must also provide the ability to change the primary to a new location. A look-up of information on the current primary location may provide a "forwarding pointer" which will tell us to update our records and move everything to the new location. There is also the possibility that the primary location is defunct and no longer responding. In that case, a location which has already been mapped to this zot_uid can seize control, and declare itself to be primary. In both cases the primary designation is automatically approved and moved. A request can also be made from a primary location which requests that another location be removed. 

In order to map a zot_uid to a second (or tertiary) location, we require a secure exchange which verifies that the new location is in possession of the private key for this zot_uid. Security of the private key is thus essential to avoid hijacking of identities.

Communications will then require

* zot_uid (string)
* uid_sig 
* callback (current location zot endpoint url)
* callback_sig
* spec (int)

passed with every communique. The spec is a revision number of the applicable zot spec so that communications can be maintained with hubs using older and perhaps incompatible protocol specifications. Communications are verified using a stored public key which was copied when the connection to this zot_uid was first established. 

Key revocation and replacement must take place from the primary hub. The exact form for this is still being worked out, but will likely be a notification to all other bound hubs to "phone home" (to the primary hub) and request a copy of the new key. This communique should be verified using a site or hub key; as the original identity key may have been compromised and cannot be trusted.   

In order to eliminate confusion, there should be exactly one canonical url for any hub, so that these can be indexed and referenced without ambiguity. 

So as to avoid ambiguity of scheme, it is strongly encouraged that all addresses to be https with a "browser valid" cert and a single valid host component (either www.domain.ext or domain.ext, but not both), which is used in all communications. Multiple URLs may be provided locally, but only one unique URL should be used for all zot communications within the grid.

Test installation which do not connect to the public grid may use non-SSL, but all traffic flowing over public networks should be safe from session-hijacking, preferably with a "browser recognised" cert.

Where possible, zot encourages the use of "batching" to minimise network traffic between two hubs. This means that site 'A' can send multiple messages to site 'B' in a single transaction, and also consolidate deliveries of identical messages to multiple recipients on the same hub.

Messages themselves may or may not be encrypted in transit, depending on the private nature of the messages. SSL (strongly encouraged) provides unconditional encryption of the data stream, however there is little point in encrypting public communications which have been designated as having unrestricted visibility. The encryption of data storage and so-called "end-to-end encryption" is outside the scope of zot. It is presumed that hub operators will take adequate safeguards to ensure the security of their data stores and these are functions of application and site integrity as opposed to protocol integrity. 


## Messages

Given the constraints listed previously, a zot communique should therefore be a json array of individual messages. These can be mixed and combined within the same transmission.

Each message then requires:

* type
* (optional) recipient list

Lack of a recipient list would indicate an unencrypted public or site level message. The recipient list would contain an array of zot_uid with an individual decryption key, and a common iv. The decryption key is encoded with the recipient identity's public key. The iv is encrypted with the sender's private key.

All messages should be digitally signed by the sender.    

The type can be one of (this list is extensible):

* post (or activity)
* mail
* identity
* authenticate

Identity messages have no recipients and notify the system social graph of an identity update, which could be a new or deleted identity, a new or deleted location, or a change in primary hub. The signature for these messages uses system keys as opposed to identity-specific keys.

Posts include many different types of activities, such as top-level posts, likes/dislikes, comments, tagging activities, etc. These types are indicated within the message sturcture.

authenticate messages result in mutual authentication and browser redirect to protected resources on the remote hub such as the ability to post wall-to-wall messages and view private photo albums and events, etc.

## Discovery

A well-known url is used to probe a hub for zot capabilities and identity lookups, including the discovery of public keys, profile locations, profile photos, and primary hub location. 

The location for this service is /.well-known/zot-info - and must be available at the root of the chosen domain.

To perform a lookup, a POST request is made to the discovery location with the following parameters:

Required:

address  => an address on the target system such as "john"

Optional

target     => the zot "guid" of the observer for evaluating permissions

target_sig => an RSA signature (base64url encoded) of the guid

key        => The public key needed to verify the signature

With no target provided, the permissions returned will be generic permissions 
for unknown or unauthenticated observers

Example of discovery packet for 'mike@zothub.com'

	{

    "success": true,
    "guid": "sebQ-IC4rmFn9d9iu17m4BXO-kHuNutWo2ySjeV2SIW1LzksUkss12xVo3m3fykYxN5HMcc7gUZVYv26asx-Pg",
    "guid_sig": "Llenlbl4zHo6-g4sa63MlQmTP5dRCrsPmXHHFmoCHG63BLq5CUZJRLS1vRrrr_MNxr7zob_Ykt_m5xPKe5H0_i4pDj-UdP8dPZqH2fqhhx00kuYL4YUMJ8gRr5eO17vsZQ3XxTcyKewtgeW0j7ytwMp6-hFVUx_Cq08MrXas429ZrjzaEwgTfxGnbgeQYQ0R5EXpHpEmoERnZx77VaEahftmdjAUx9R4YKAp13pGYadJOX5xnLfqofHQD8DyRHWeMJ4G1OfWPSOlXfRayrV_jhnFlZjMU7vOdQwHoCMoR5TFsRsHuzd-qepbvo3pzvQZRWnTNu6oPucgbf94p13QbalYRpBXKOxdTXJrGdESNhGvhtaZnpT9c1QVqC46jdfP0LOX2xrVdbvvG2JMWFv7XJUVjLSk_yjzY6or2VD4V6ztYcjpCi9d_WoNHruoxro_br1YO3KatySxJs-LQ7SOkQI60FpysfbphNyvYMkotwUFI59G08IGKTMu3-GPnV1wp7NOQD1yzJbGGEGSEEysmEP0SO9vnN45kp3MiqbffBGc1r4_YM4e7DPmqOGM94qksOcLOJk1HNESw2dQYWxWQTBXPfOJT6jW9_crGLMEOsZ3Jcss0XS9KzBUA2p_9osvvhUKuKXbNztqH0oZIWlg37FEVsDs_hUwUJpv2Ar09k4",
    "key": "-----BEGIN PUBLIC KEY-----\nMIICIjANBgkqhkiG9w0BAQEFAAOCAg8AMIICCgKCAgEA7QCwvuEIwCHjhjbpz3Oc\ntyei/Pz9nDksNbsc44Cm8jxYGMXsTPFXDZYCcCB5rcAhPPdZSlzaPkv4vPVcMIrw\n5cdX0tvbwa3rNTng6uFE7qkt15D3YCTkwF0Y9FVZiZ2Ko+G23QeBt9wqb9dlDN1d\nuPmu9BLYXIT/JXoBwf0vjIPFM9WBi5W/EHGaiuqw7lt0qI7zDGw77yO5yehKE4cu\n7dt3SakrXphL70LGiZh2XGoLg9Gmpz98t+gvPAUEotAJxIUqnoiTA8jlxoiQjeRK\nHlJkwMOGmRNPS33awPos0kcSxAywuBbh2X3aSqUMjcbE4cGJ++/13zoa6RUZRObC\nZnaLYJxqYBh13/N8SfH7d005hecDxWnoYXeYuuMeT3a2hV0J84ztkJX5OoxIwk7S\nWmvBq4+m66usn6LNL+p5IAcs93KbvOxxrjtQrzohBXc6+elfLVSQ1Rr9g5xbgpub\npSc+hvzbB6p0tleDRzwAy9X16NI4DYiTj4nkmVjigNo9v2VPnAle5zSam86eiYLO\nt2u9YRqysMLPKevNdj3CIvst+BaGGQONlQalRdIcq8Lin+BhuX+1TBgqyav4XD9K\nd+JHMb1aBk/rFLI9/f2S3BJ1XqpbjXz7AbYlaCwKiJ836+HS8PmLKxwVOnpLMbfH\nPYM8k83Lip4bEKIyAuf02qkCAwEAAQ==\n-----END PUBLIC KEY-----\n",
    "name": "Mike Macgirvin",
    "name_updated": "2012-12-06 04:59:13",
    "address": "mike@zothub.com",
    "photo_mimetype": "image/jpeg",
    "photo": "https://zothub.com/photo/profile/l/1",
    "photo_updated": "2012-12-06 05:06:11",
    "url": "https://zothub.com/channel/mike",
    "connections_url": "https://zothub.com/poco/mike",
    "target": "",
    "target_sig": "",
    "searchable": false,
    "permissions": {
        "view_stream": true,
        "view_profile": true,
        "view_photos": true,
        "view_contacts": true,
        "view_storage": true,
        "view_pages": true,
        "send_stream": false,
        "post_wall": false,
        "post_comments": false,
        "post_mail": false,
        "post_photos": false,
        "tag_deliver": false,
        "chat": false,
        "write_storage": false,
        "write_pages": false,
        "delegate": false
    },
    "profile": {
        "description": "Freedom Fighter",
        "birthday": "0000-05-14",
        "next_birthday": "2013-05-14 00:00:00",
        "gender": "Male",
        "marital": "It's complicated",
        "sexual": "Females",
        "locale": "",
        "region": "",
        "postcode": "",
        "country": "Australia"
    },
    "locations": [
        {
            "host": "zothub.com",
            "address": "mike@zothub.com",
            "primary": true,
            "url": "https://zothub.com",
            "url_sig": "eqkB_9Z8nduBYyyhaSQPPDN1AhSm5I4R0yfcFxPeFpuu17SYk7jKD7QzvmsyahM5Kq7vDW6VE8nx8kdFYpcNaurqw0_IKI2SWg15pGrhkZfrCnM-g6A6qbCv_gKCYqXvwpSMO8SMIO2mjQItbBrramSbWClUd2yO0ZAceq3Z_zhirCK1gNm6mGRJaDOCuuTQNb6D66TF80G8kGLklv0o8gBfxQTE12Gd0ThpUb5g6_1L3eDHcsArW_RWM2XnNPi_atGNyl9bS_eLI2TYq0fuxkEdcjjYx9Ka0-Ws-lXMGpTnynQNCaSFqy-Fe1aYF7X1JJVJIO01LX6cCs-kfSoz29ywnntj1I8ueYldLB6bUtu4t7eeo__4t2CUWd2PCZkY3PKcoOrrnm3TJP5_yVFV_VpjkcBCRj3skjoCwISPcGYrXDufJxfp6bayGKwgaCO6QoLPtqqjPGLFm-fbn8sVv3fYUDGilaR3sFNxdo9mQ3utxM291XE2Pd0jGgeUtpxZSRzBuhYeOybu9DPusID320QbgNcbEbEImO8DuGIxuVRartzEXQF4WSYRdraZzbOqCzmU0O55P836JAfrWjgxTQkXlYCic-DBk-iE75JeT72smCtZ4AOtoFWCjZAABCw42J7JELY9APixZXWriKtjy6JI0G9d3fs6r7SrXr1JMy0",
            "callback": "https://zothub.com/post",
            "sitekey": "-----BEGIN PUBLIC KEY-----\nMIICIjANBgkqhkiG9w0BAQEFAAOCAg8AMIICCgKCAgEA1IWXwd/BZuevq8jzNFoR\n3VkenduQH2RpR3Wy9n4+ZDpbrUKGJddUGm/zUeWEdKMVkgyllVA/xHdB7jdyKs1X\nuIet9mIdnzvhdLO/JFD5hgbNG2wpSBIUY6aSNeCFTzszqXmuSXMW5U0Ef5pCbzEA\nnhoCoGL1KAgPqyxnGKUlj7q2aDwC9IRNtAqNyFQL67oT91vOQxuMThjlDhbR/29Q\ncYR4i1RzyahgEPCnHCPkT2GbRrkAPjNZAdlnk9UesgP16o8QB3tE2j50TVrbVc/d\nYRbzC56QMPP9UgUsapNeSJBHji75Ip/E5Eg/kfJC/HEQgyCqjCGfb7XeUaeQ7lLO\nqc7CGuMP+Jqr/cE54/aSHg8boTwxkMp11Ykb+ng17fl57MHTM2RJ99qZ1KBkXezR\nuH1lyvjzeJPxEFr9rkUqc4GH74/AgfbgaFvQc8TS7ovEa5I/7Pg04m7vLSEYc6UF\nYJYxXKrzmZT2TDoKeJzaBBx5MFLhW19l68h9dQ8hJXIpTP0hJrpI+Sr6VUAEfFQC\ndIDRiFcgjz6j7T/x8anqh63/hpsyf2PMYph1+4/fxtSCWJdvf+9jCRM8F1IDfluX\n87gm+88KBNaklYpchmGIohbjivJyru41CsSLe0uinQFvA741W00w6JrcrOAX+hkS\nRQuK1dDVwGKoIY85KtTUiMcCAwEAAQ==\n-----END PUBLIC KEY-----\n"
        }
    ],
    "site": {
        "url": "https://zothub.com",
        "directory_mode": "primary",
        "directory_url": "https://zothub.com/dirsearch"
    }

	}



Discovery returns a JSON array with the following components:

'success' => ('1' or '')  Operation was successful if '1'. Otherwise an optional 'message' may be present indicating the source of error.

'guid' => the guid of the address on the target system

'guid_sig' => the base64url encoded RSA signature of the guid, signed with the private key associated with that guid.

'key' => the public key associated with that guid

'name' => channel name

'name_updated' => MySQL style timestamp '2012-01-01 00:00:00' when the name was last changed (UTC)

'address' => "webbie" or user@host address associated with this channel

'photo' => URL of a profile photo for this channel (ideally 175x175)

'photo_mimetype' => content-type of the profile photo

'photo_updated' => MySQL style timestamp when photo was last updated (UTC)  

'url' => location of channel homepage

'connections_url' => location of Portable Contacts (extended for zot) URL for this channel

'target' => if a permissions target was specified, it is mirrored

'target_sig' => if a permissions target was specified, the signature is mirrored.
    
'searchable' => ('1' or '') '1' indicates this entry can be searched in a directory

###Permissions


'permisssions' => extensible array of permissions appropriate to this target, values are '1' or ''

  Permissions may include:

* view_stream

* view_profile

* view_photos

* view_contacts

* view_storage

* view_pages

* send_stream

* post_wall

* post_comments

* post_mail

* post_photos

* tag_deliver

* chat

* write_storage

* write_pages

* delegate



###Profile


'profile' => array of important profile fields

* description

* birthday YYYY-MM-DD , all fields are optional, any field (such as year) may be zero

* next_birthday => MySQL datetime string representing the next upcoming birthday, converted from the channel's default timezone to UTC. 

* gender (free form)

* marital (marital status)

* sexual (preference)

* locale (city)

* region (state)

* postcode

* country


###Locations


'locations' => array of registered locations (DNS locations) this channel may be visible or may be posting from

Each location is an array of

'host' => DNS hostname, e.g. example.com

'address' => the webbie or user@host identifier associated with this location

'primary' => ('1' or '') whether or not this is the primary location for this channel where files and web pages are generally found

'url' => url of the root of this DNS location e.g. https://example.com

'url_sig' => base64url encoded RSA signature of the URL, signed with the channel private key

'callback' => zot communications endpoint on this site, usually https://example.com/post

'sitekey' => public key of this site/host


###Site


'site' => array providing the directory role of the site responding to this request

'url' => url of this site e.g. https://example.com

'directory_mode' => one of 'primary', 'secondary', 'normal', or 'standalone'

'directory_url' => if this is a directory server or standalone, the URL for the directory search module



Magic Auth
==========


So-called "magic auth" takes place by a special exchange. On the remote computer, a redirection is made to the zot endpoint with special GET parameters.

Endpoint: https://example.com/post/name

where 'name' is the left hand side of the channel webbie, for instance 'mike' where the webbie is 'mike@zothub.com'

Additionally four parameters are supplied:

* auth => the webbie of the person requesting access
* dest => the desired destination URL (urlencoded)
* sec  => a random string which is also stored locally for use during the verification phase. 
* version => the zot revision

When this packet is received, a zot message is initiated to the auth identity:


    {
      "type":"auth_check",
      "sender":{
        "guid":"kgVFf_1_SSbyqH-BNWjWuhAvJ2EhQBTUdw-Q1LwwssAntr8KTBgBSzNVzUm9_RwuDpxI6X8me_QQhZMf7RfjdA",
        "guid_sig":"PT9-TApzpm7QtMxC63MjtdK2nUyxNI0tUoWlOYTFGke3kNdtxSzSvDV4uzq_7SSBtlrNnVMAFx2_1FDgyKawmqVtRPmT7QSXrKOL2oPzL8Hu_nnVVTs_0YOLQJJ0GYACOOK-R5874WuXLEept5-KYg0uShifsvhHnxnPIlDM9lWuZ1hSJTrk3NN9Ds6AKpyNRqf3DUdz81-Xvs8I2kj6y5vfFtm-FPKAqu77XP05r74vGaWbqb1r8zpWC7zxXakVVOHHC4plG6rLINjQzvdSFKCQb5R_xtGsPPfvuE24bv4fvN4ZG2ILvb6X4Dly37WW_HXBqBnUs24mngoTxFaPgNmz1nDQNYQu91-ekX4-BNaovjDx4tP379qIG3-NygHTjFoOMDVUvs-pOPi1kfaoMjmYF2mdZAmVYS2nNLWxbeUymkHXF8lT_iVsJSzyaRFJS1Iqn7zbvwH1iUBjD_pB9EmtNmnUraKrCU9eHES27xTwD-yaaH_GHNc1XwXNbhWJaPFAm35U8ki1Le4WbUVRluFx0qwVqlEF3ieGO84PMidrp51FPm83B_oGt80xpvf6P8Ht5WvVpytjMU8UG7-js8hAzWQeYiK05YTXk-78xg0AO6NoNe_RSRk05zYpF6KlA2yQ_My79rZBv9GFt4kUfIxNjd9OiV1wXdidO7Iaq_Q",
        "url":"http:\/\/podunk.edu",
        "url_sig":"T8Bp7j5DHHhQDCFcAHXfuhUfGk2P3inPbImwaXXF1xJd3TGgluoXyyKDx6WDm07x0hqbupoAoZB1qBP3_WfvWiJVAK4N1FD77EOYttUEHZ7L43xy5PCpojJQmkppGbPJc2jnTIc_F1vvGvw5fv8gBWZvPqTdb6LWF6FLrzwesZpi7j2rsioZ3wyUkqb5TDZaNNeWQrIEYXrEnWkRI_qTSOzx0dRTsGO6SpU1fPWuOOYMZG8Nh18nay0kLpxReuHCiCdxjXRVvk5k9rkcMbDBJcBovhiSioPKv_yJxcZVBATw3z3TTE95kGi4wxCEenxwhSpvouwa5b0hT7NS4Ay70QaxoKiLb3ZjhZaUUn4igCyZM0h6fllR5I6J_sAQxiMYD0v5ouIlb0u8YVMni93j3zlqMWdDUZ4WgTI7NNbo8ug9NQDHd92TPmSE1TytPTgya3tsFMzwyq0LZ0b-g-zSXWIES__jKQ7vAtIs9EwlPxqJXEDDniZ2AJ6biXRYgE2Kd6W_nmI7w31igwQTms3ecXe5ENI3ckEPUAq__llNnND7mxp5ZrdXzd5HHU9slXwDShYcW3yDeQLEwAVomTGSFpBrCX8W77n9hF3JClkWaeS4QcZ3xUtsSS81yLrp__ifFfQqx9_Be89WVyIOoF4oydr08EkZ8zwlAsbZLG7eLXY"
      },
      "recipients":{
        {
        "guid":"ZHSqb3yGar3TYV_o9S-JkD-6o_V4DhUcxtv0VeyX8Kj_ENHPI_M3SyAUucU835-mIayGMmTpqJz3ujPkcavVhA",
        "guid_sig":"JsAAXigNghTkkbq8beGMJjj9LBKZn28hZ-pHSsoQuwYWvBJ2lSnfc4r9l--WgO6sucH-SR6RiBo78eWn1cZrh_cRMu3x3LLx4y-tjixg-oOOgtZakkBg4vmOhkKPkci0mFtzvUrpY4YHySqsWTuPwRx_vOlIYIGEY5bRXpnkNCoC8J4EJnRucDrgSARJvA8QQeQQL0H4mWEdGL7wlsZp_2VTC6nEMQ48Piu6Czu5ThvLggGPDbr7PEMUD2cZ0jE4SeaC040REYASq8IdXIEDMm6btSlGPuskNh3cD0AGzH2dMciFtWSjmMVuxBU59U1I6gHwcxYEV6BubWt_jQSfmA3EBiPhKLyu02cBMMiOvYIdJ3xmpGoMY1Cn__vhHnx_vEofFOIErb6nRzbD-pY49C28AOdBA5ffzLW3ss99d0A-6GxZmjsyYhgJu4tFUAa7JUl84tMbq28Tib0HW6qYo6QWw8K1HffxcTpHtwSL5Ifx0PAoGMJsGDZDD1y_r9a4vH5pjqmGrjL3RXJJUy-m4eLV5r7xMWXsxjqu3D8r04_dcw4hwwexpMT1Nwf8CTB0TKb8ElgeOpDFjYVgrqMYWP0XdhatcFtAJI7gsS-JtzsIwON9Kij66-VAkqy_z1IXI0ziyqV1yapSVYoUV1vMScRZ_nMqwiB5rEDx-XLfzko"
        }
      }
      "callback":"\/post",
      "version":1,
      "secret":"1eaa6613699be6ebb2adcefa5379c61a3678aa0df89025470fac871431b70467",
      "secret_sig":"eKV968b1sDkOVdSMi0tLRtOhQw4otA8yFKaVg6cA4I46_zlAQGbFptS-ODiZlSAqR7RiiZQv4E2uXCKar52dHo0vvNKbpOp_ezWYcwKRu1shvAlYytsflH5acnDWL-FKOOgz5zqLLZ6cKXFMoR1VJGG_Od-DKjSwajyV9uVzTry58Hz_w0W2pjxwQ-Xv11rab5R2O4kKSW77YzPG2R5E6Q7HN38FrLtyWD_ai3K9wlsFOpwdYC064dk66X7BZtcIbKtM6zKwMywcfJzvS5_0U5yc5GGbIY_lY6SViSfx9shOKyxkEKHfS29Ynk9ATYGnwO-jnlMqkJC7t149H-sI9hYWMkLuCzaeLP56k2B2TmtnYvE_vHNQjzVhTwuHCIRVr-p6nplQn_P3SkOpYqPi3k_tnnOLa2d3Wtga8ClEY90oLWFJC3j2UkBf_VEOBNcg-t5XO3T-j9O4Sbk96k1Qoalc-QlznbGx4bOVsGkRBBMiH4YUqiiWB_OkFHtdqv7dqGeC-u-B4u9IxzYst46vvmyA3O-Q4APSZ1RY8ITUH0jLTbh6EAV7Oki8pIbOg0t56p-8RlanOZqmFvR-grVSc7Ak1ZcD8NACmvidUpa1B7WEvRcOeffx9lype0bt5XenDnMyx6szevwxZIiM8qGM2lsSk4fu8HI9cW0mLywzZT0"
    }


auth_check messages MUST be encrypted with AES256CBC. This message is sent to the origination site, which checks the 'secret' to see if it is the same as the 'sec' which it passed originally. It also checks the secret_sig which is the secret signed by the destination channel's private key and base64url encoded. If everything checks out, a json packet is returned:

    { 
      "success":1, 
      "confirm":"q0Ysovd1uQRsur2xG9Tg6bC23ynzw0191SkVd7CJcYoaePy6e_v0vnmPg2xBUtIaHpx_aSuhgAkd3aVjPeaVBmts6aakT6a_yAEy7l2rBydntu2tvrHhoVqRNOmw0Q1tI6hwobk1BgK9Pm0lwOeAo8Q98BqIJxf47yO9pATa0wktOg6a7LMogC2zkkhwOV5oEqjJfeHeo27TiHr1e2WaphfCusjmk27V_FAYTzw05HvW4SPCx55EeeTJYIwDfQwjLfP4aKV-I8HQCINt-2yxJvzH7Izy9AW-7rYU0Il_gW5hrhIS5MTM12GBXLVs2Ij1CCLXIs4cO0x6e8KEIKwIjf7iAu60JPmnb_fx4QgBlF2HLw9vXMwZokor8yktESoGl1nvf5VV5GHWSIKAur3KPS2Tb0ekNh-tIk9u-xob4d9eIf6tge_d3aq1LcAtrDBDLk8AD0bho5zrVuTmZ9k-lBVPr_DRHSV_dlpu088j3ThaBsuV1olHK3vLFRhYCDIO0CqqK5IuhqtRNnRaqhlNN6fQUHpXk2SwHiJ2W36RCYMTnno6ezFk_tN-RA2ly-FomNZoC5FPA9gFwoJR7ZmVFDmUeK3bW-zYTA5vu15lpBPnt7Up_5rZKkr0WQVbhWJmylqOuwuNWbn3SrMQ8rYFZ23Tv300cOfKVgRBaePWQb4" 
    }

'confirm' in this case is the base64url encoded RSA signature of the concatenation of 'secret' with the base64url encoded whirlpool hash of the source guid and guid_sig; signed with the source channel private key. This prevents a man-in-the-middle from inserting a rogue success packet. Upon receipt and successful verification of this packet, the destination site will redirect to the original destination URL and indicate a successful remote login. 
   
#include doc/macros/main_footer.bb;
