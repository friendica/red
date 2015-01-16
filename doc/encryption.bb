[size=large]Builtin Automatic Encryption[/size]

Full disclosure: The encryption Red Matrix uses per default is not absolutely waterproof. There [i]are[/i] known procedures to circumvent it. [i]But[/i] this takes a lot of effort and needs to be done individually for each channel. And to make this clear: Other services store your messages in plaintext, therefore we regard this approach as a [i]significant[/i] improvement for your privacy. Plus you are always free to use further encryption and password protection if you so desire.

To explain this in more detail: 

- each channel has its key pair
- every non-public post is automatically encrypted
- optional password protect content via crypto-javascript browser-to-browser encryption (needs to be enabled in settings) Full disclosure: A rogue hub admin could injects malicious javascript-code (e.g. keylogging-abilities) into the code. Encrypt our stuff out of band with GPG, become a hub administrator yourself or use other means of communication if this worries you.

So what is the scope of security? Full disclosure: This might be great, but it is not perfect.
- every non-public post is automatically encrypted but persons who have access to the site's database and files [i]may[/i] be able to decrypt everything by using these keys which obviously need to be stored on the server. To be clear: The encrypion keys are different for every channel and it is [i]quite an effort[/i] to do this. And again: Other services store your messages in plain text unencrypted. So this [i]is[/i] quite a significant win for your privacy.

We believe that the NSA-level dragnet plaintext extracting mass surveillance is probably not possible due to the design of the zot protocol. Dedicated attacks including hacking into one hub to obtain the server logs and database only partly reveal what is going on between people communication between different hubs. We believe that this makes it much more expensive for state-level attackers to access your content in Red Matrix.

We gladly accept help improving the security of the system and auditing it as well.
