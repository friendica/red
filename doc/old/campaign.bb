[b]Initial Indiegg pitch[/b]

[b][color= grey][size=20]What have we done, and what we hope to achieve[/size][/color][/b]

[b][color= grey][size=18]Single-click sign on, nomadic identity, censorship-resistance, privacy, self-hosting[/size][/color][/b]

We started the Red Matrix project by asking ourselves a few questions: 

- Imagine if it was possible to just access the content of different web sites, without the need to enter usernames and passwords for every site.  Such a feature would permit Single-Click user identification: the ability to access sites simply by clicking on links to remote sites. 
Authentication just happens automagically behind the scenes. Forget about remembering multiple user names with multiple passwords when accessing different sites online. 

We liked this idea and went ahead with coding it immediately. Today, single-click sign is in alpha state.  It needs more love, which means a solid three months of full-time development efforts.

- Think of your Facebook, Twitter, WordPress, or any other website where you currently have an account. Now imagine being able to clone your account, to make an exact duplicate of it (with all of your friends, posts and settings), then export your cloned account into another server that is part of this communication network.  After you're done, both of your accounts are synced from the time they were cloned.  It doesn't matter where you log in (at your original location, or where you imported your clone). You see the same content, the same friends, posts, and account settings.  
At that point, it is more appropriate to call your account an identity that is nomadic (it is not tied to one home, unless you choose to do so!).  
It's 2013, our online presence no longer has to be tied to a single server, domain name or IP address.  We should be able to clone and import our identities to other servers.  In such a network, it should only matter who you are, not where you are.  

We're very intrigued by the possibilities nomadic identities open up for freedom, censorship-resistance, and identity resilience.  Consider the following scenarios:

  -- Should a repressive government or corporation decide to delete your account, your cloned identity lives on, because it is located on another server, across the world, which is part of the same communication network.  You can't be silenced!  

  -- What if there is a server meltdown, and your identity goes off line.  No problem, you log into your clone and all is good.

  -- Your server administrator can no longer afford to keep paying to support a free service (a labor love and principle, which all of us have participating in as system administrators of Friendica sites!). She notifies you that you must clone your account before the shutoff date.  Rather than loose all your friends, and start from scratch by creating a new identity somewhere, you clone and move to another server.  
We feel this is especially helpful for the free web, where administrators of FOSS community sites are often faced with difficult financial decisions.  Since many of them rely on donations, sometimes servers have to be taken offline, when costs become prohibitive for the brave DIY souls running those server.  Nomadic identities should relieve some of the pressures associated with such situations.

At the same time, we are also thinking of solutions that would make it possible for people running Red hubs to be financially sustainable.  To that end, we're starting to implement service classes in our code, which would allow administrators to structure paid levels of service, if they choose to do so.

Today, nomadic identity is currently in alpha state.  It also needs more love, which means a solid three months of full-time development efforts.

- Imagine a social network that is censorship-resistant, and privacy-respecting by design.  It is not controlled by one mega-corporation, and where users cannot be easily censored by oppressive governments.  So, in addition to nomadic identities, we are talking about decentralization, open source, freely software, that can run on any hardware that supports a database and a modern web browser.  And we mean &quot;any hardware&quot;, from a self-hosted $35 Raspberry Pi, to the very latest Intel Xeon and AMD Bulldozer-powered server behemoths.

We've realized that privacy requires full control over content.  We should be able to delete, backup and download all of our content, as well as associated account/identity information.  To this end, we have already implemented the initial version of account export and backup.

Concerned about pages and pages of posts from months and years past?  The solution should be simple: visit your settings page, specify that all content older than 7 days, with the exception of starred posts, should be automatically deleted.  Done, the clutter is gone! (Consider also the privacy and anti-mass surveillance implications of this feature.  PRISM disclosures have hinted that three-letter spying agencies around the world are recording all internet traffic and storing it for a few days at a time.  We feel that automatic post expiration becomes a rather useful feature in this context, and implementing it is one of our near future priorities.)  

[b][color= grey][size=18]The Affinity Slider and Access Control Lists[/size][/color][/b]

- What if the permissions and access control lists that help secure modern operating systems were extended into a communication network that lived on the internet? This means somebody could log into this network from their home site, and with the simple click of a few buttons dynamically sort who can have access to their online content on a very fine level: from restricting others from seeing your latest blog post, to sharing your bookmarks with the world.

We've coded the initial version of such a new feature. It is called the &quot;Affinity Slider&quot;, and in our very-alpha user interface it looks like this.  
[img]https://friendicared.net/photo/b07b0262e3146325508b81a9d1ae4a1e-0.png[/img]

{INSERT SCREENSHOT OF A MATRIX PAGE}

Think of it as an easy way to filter content that you see, based on the degree of &quot;closeness&quot; to you.  Move the slider to Friends, and only content coming from contacts you've tagged as friends is displayed on your home page.  Uncluttering thousands of contacts, friends, RSS feeds, and other content should be a basic feature of modern communication on the web, but not at the expense of ease of use.  

In addition to the Affinity Slider, we also have the ACL (Access Control List). Say you want to share something with only 5 of your contacts (a blog, two friends from college, and two forums).  You click on the padlock, choose the recipients, and that's it.  Only those identities will recieve their posts.  Furthermore, the post will be encrypted via PKI (pulic key encryption) to help maintain privacy.  In the age of PRISM, we don't know all the details on what's safe out there, but we still think that privacy by design should be automatically present, invisible to the user, and easy to use.
Attaching permissions to any data that lives on this network, potentially solves a great many headaches, while achieving simplicity in communication.

Think of it this way: the internet is nothing, but a bunch of permissions and a folder of data.  You, the user controls the permissions and thus the data that is relevant to you.

[b][color= grey][size=20]The Matrix is Born![/size][/color][/b]

After asking and striving to answer a number of such questions, we realized that we were imagining a general purpose communication network with a number of unique, and potentially game-changing, features.  We called it the Red Matrix and started thinking of it as an over-lay on top of the internet as it exists today; an operating system re-invented as a communication network, with its own permissions, access control lists, protocol, connectors to others services, and open-ended possibilities via its API.  The sum of the matrix is greater than it's parts.  We're not building website, but a way for websites to link together and grow into something that is unique and ever-changing, with autonomy and privacy.

It's a lot of work, for anyone.  So far, we've got a team of a handful of volunteers, code geeks, brave early adopters, system administrators and other good people, willing to give the project a shot.  We're motivated by our commitment to a free web, where privacy is built-in, and corporations don't have a stranglehold on our daily communication. 

We need your help to finish it and release it to the world!

[b][color= grey][size=20]What have we written so far[/size][/color][/b]

As of the today, the Red Matrix is in developer preview (alpha) state.  It is not ready for everyday use, but some of the initial set of core features are implemented (again, in alpha state).  These include: 

- Zot, the protocol powering the matrix
- Single-signon logins.
- Nomadic identities
- Basic content manipulation: creation, deletion, rudimentary handling of photos, and media files
- A bare-bones outline of the API and user documentation.


[b][color= grey][size=20]Our TO-DO List[/size][/color][/b]

However, in addition to finishing and polishing the above, there are a number of features that have to implemented to make the Red Matrix ready for daily use.  If we meet our fundraising goal, we hope to dive into the following road map, by order of priority: 

- A professionally designed user interface (UI), interface that is adaptive to any user level, from end users who want to use the Matrix as a social network, to tinkerers who will put together a customized blog using Comanche, to hackers who will develop and extend the matrix using a built-in code editor, that hooks to the API and the git. 

- Comanche, our new markup language, similar to BBCode, with which to create elaborate and complex web pages by assembling them from a series of components - some of which are pre-built and others which can be defined on the fly.  You can read more about it on our github wiki: https://github.com/friendica/red/wiki/Comanche

- A unique help system that lives in the matrix, but is not based on the principles of a search engine.  We have some interesting ideas about decentralizing help documentation, without going down the road of distributed search engines.  Here's a hint:  We shouldn't be searching at all, we should just be filtering what's already there in new, and cunning ways.

- An appropriate logo, along with professionally done documentation system, both for our API, as well as users.

- WordPress-like single button software upgrades

- A built-in development environment, using an integrated web-based code editor such as Ace9

[b][color= grey][size=20]What will the money be used for[/size][/color][/b]

If we raise our targeted amount of funds, we plan to use it as follows: 

1) Fund 6 months {OR WHATEVER} of full time work for our current core  developers, Mike, Thomas, and Tobias {ANYONE ELSE?]

2) Pay a professional web developer to design an kick ass reference theme, along with a project logo.  

3) {WHAT ELSE?}

[b][color= grey][size=20]Deadlines[/size][/color][/b]

[b]March, 2014: Red Matrix Beta with the following features[/b]

- {LIST FEATURES}

[b][color= grey][size=20]Who We Are[/size][/color][/b]

Mike: {FILL IN BIO, reference Friendica, etc.}

Thomas: {bio blurb}

Tobias: {bio blurb}

Arto: {documentation, etc.}

{WHO ELSE?  WE NEED A TEAM, AT LEAST 3-4 PEOPLE}

[b][color= grey][size=20]What Do I Get as a Supporter?[/size][/color][/b]

Our ability to reach 1.0 stable release depends on your generosity and support. We appreciate your help, regardless of the amount!  Here's what we're thinking as far as different contribution levels go:

[b]$1: {CATCHY TAGLINE}[/b]

We'll list your name on our initial supporters list, a Hall of Fame of the matrix!

[b]$5:[/b]

[b]$10: [/b]

[b]$16: [/b]

You get one of your Red Matrix t-shirts, as well as our undying gratitude.  

[b]$32:  [/b]

[b]$64 [/b]

[b]128 [/b]

[b]$256: [/b]

[b]$512: [/b]

[b]$1024 [/b]

[b]$2048[/b]

Each contributor at this level gets their own Red Matrix virtual private server, installed, hosted and supported by us for a period of 1 year. 

[b][color= grey][size=20]Why are we so excited about the Red Matrix?[/size][/color][/b]

{SOMETHING ABOUT THE POTENTIAL IMPACT OF RED, ITS INNOVATIONS, ETC&gt;

[b][color= grey][size=20]Other Ways to Help[/size][/color][/b]

We're a handful of volunteers, and we understand that not everyone can contribute by donating money.  There are many other ways you can in getting the Matrix to version 1.0!

First, you can checkout our source code on github: https://github.com/friendica/red  

Maybe you can dive in and help us out with some development.

Second, you can install the current developer preview on a server and start  compiling bug reports.

Third, register at one of the public alpha Red hubs, and get a feel for what Red is trying to do!

Perhaps you're good at writing and documenting stuff.  Grab an account at one of the public alphas and give us a hand.

[b][color= grey][size=20]Frequently Asked Questions[/size][/color][/b]

[b]1. Is Red a social network?[/b]

The Red Matrix is not a social network. We're thinking of it as a general purpose communication network, with sharing, and public/private communications built into the matrix.

[b]2. What is the difference between Red and Friendica?[/b]

What's the difference between a passport, and a postcard?

Friendica is really, really good at sending postcards. It can do all sorts of things with postcards.  It can send them to your friends.  It can send them to people you don't know.  It can put them in an envelope and send them privately.  It can run them through a photocopier and plaster them all over the internet.  It can even take postcards in one language and convert them to many others so your friends who speak a different language can read them.

What Friendica can't do, is wave a postcard at somebody and expect them to believe that holding this postcard prove you are who you say you are.  Sure, if you've been sending somebody postcards, they might accept that it is you in the picture, but somebody who has never heard of you will not accept ownership of a postcard as proof of identity.

The Red Matrix offers a passport.

You can still use it to send postcards. At the same time, when you wave your passport at somebody, they do accept it as proof of identity.  No longer do you need to register at every single site you use.  You already have an account - it's just not necessarily at our site - so we'll ask to see your passport instead. 

Once you've proven your identity, a Red hub lets you use our services, as though you'd registered with directly, and we'd verified your credentials as would have happened in the olden days.  These resources can, of course, be anything at all.

[b]2. Why did you choose PHP, instead of Ruby or Python?[/b]

The reference implementation is in PHP.  We chose PHP, because it is available everywhere, and is easily configurable.  We understand the debates between proponents and opponents of PHP, Ruby and Python.  Nothing prevents implementations of Zot and the matrix in those languages. In fact, people on the matrix have already started developing a version of Red in Python [SOURCE?], and there is talk about future implementations in C (aiming for blazing native performance) and Java.  It's free and open source, so we feel it's only a matter of time, once Red is initially completed.

[b]4. Other than PHP, what other technology does Red use?[/b]

We use MySQL as our database (this include any forks such as, MariaDB or Percona), and any modern webserver (Apache, nginx, etc.).

[b]5. How is the Affinity Slider different from Mozilla's Persona?[/b]
{COMPLETE}

[b]6. Does the Red Matrix use encryption?  Details please![/b]

Yes, we do our best to use free and open source encryption libraries to help achieve privacy from general, mass surveillance.

Communication between web browsers and Red hubs is encrypted using SSL certificates.

Private communication on the matrix  is protected by AES symmetric encryption, which is itself protected by RSA PKI (public key encryption). By default, we use AES-256-CBC, and our RSA keys are set to 4096-bits.

For more info on our initial implementation of encrypted communication, check out our source code at Github: https://github.com/friendica/red/blob/master/include/crypto.php

[b]7. What do you mean by decentralization? [/b]


[b]8. Can I build my own website with in the Red Matrix?[/b]

Yes.  The short explanation: We've got this spiffy idea we're calling &quot;Comanche&quot;, which will allow non-programmers to build complete custom websites, and any such website will be able to connect to any other website or channel in the matrix.  The goal of Comanche is to hide the technical complexities of communicating in the matrix, while encouraging people to use their creativity and put together their own unique presence on the matrix.

The longer explanation: Comanche is a markup language, similar to bbcode, with which to create elaborate and complex web pages by assembling them from a series of components - some of which are pre-built and others which can be defined on the fly. Comanche uses a Page Description Language file (&quot;.pdl&quot;, pronounced &quot;puddle&quot;) to create these pages. Bbcode is not a requirement; an XML PDL file could also be used. The tag delimiters would be different. Usage is the same.

Additional information is available on our Github project wiki: https://github.com/friendica/red/wiki/Comanche

Comanche is another one of our priorities for the next six months.

[b]9. Where can I see some technical description of Zot?[/b]

Our github wiki contains a number of high-level and technical descriptions of Zot, Comanche, and Red in general: https://github.com/friendica/red/wiki

[b]10. What happens if you raise more than {TARGETED NUMBER}?[/b]

Raising more than our initial goal of funds, will speed up our development efforts.  More developers will be able to take time off from other jobs, and concentrate efforts on finishing Red.

[b]11 Can I make a contribution via Bitcoin?[/b]

{YES/NO}

[b]12. I have additional Questions[/]

Awesome. We'd be more than happy to chat. You can find us {HERE}