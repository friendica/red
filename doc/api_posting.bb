[b][size=xx-large]Posting to the Matrix via the API[/size][/b]

The API allows you to post to the red# by HTTP POST request. Below you see an example using the command line tool cURL:

[code]curl -ssl -u [color=blue]$E-Mail[/color]:[color=blue]$Password[/color] -d "[color=blue]$Parameters[/color]" [url][observer=1][observer.baseurl][/observer][observer=0]example.com[/observer]/api/statuses/update
[/url][/code]
[table][tr][td]$E-Mail:[/td][td]The E-Mail Adress you use to login[/td][/tr]
[tr][td]$Password:[/td][td]The Password you use to login[/td][/tr]
[tr][td]$Parameters:[/td][td]That's the interesting part, here you insert the content you want to send using the following parameters:[/td][/tr][/table]

[ul]
[*]title: the title of the posting
[*]channel: the channel you want to post to 
[*]category: a comma-seperated list of categories for the posting
[*]status: the content of the posting, formatted with BBCode
                                OR
[*]htmlstatus:the content of the posting, formatted in HTML.
[/ul]


Instead of calling [observer=1][observer.baseurl][/observer][observer=0]example.com[/observer]/api/statuses/update which returns a json (you could also add .json on the end to clarify) output, you can use [observer.baseurl]/api/statuses/update.xml to get an xml formatted return.

Instead of Basic HTTP Authentification you could also use oAuth.
