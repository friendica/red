[b]Red Developer Guide[/b]

We're pretty relaxed when it comes to developers. We don't have a lot of rules. Some of us are over-worked and if you want to help we're happy to let you help. That said, attention to a few guidelines will make the process smoother and make it easier to work together. We have developers from across the globe with different abilities and different cultural backgrounds and different levels of patience. Our primary rule is to respect others. Sometimes this is hard and sometimes we have very different opinions of how things should work, but if everybody makes an effort, we'll get along just fine.  

[b]Here is how you can join us.[/b]

First, get yourself a working git package on the system where you will be
doing development.

Create your own github account.

You may fork/clone the Red repository from [url=https://github.com/friendica/red.git]https://github.com/friendica/red.git[/url]

Follow the instructions provided here: [url=http://help.github.com/fork-a-repo/]http://help.github.com/fork-a-repo/[/url]
to create and use your own tracking fork on github

Then go to your github page and create a &quot;Pull request&quot; when you are ready
to notify us to merge your work.

[b]Translations[/b]

Our translations are managed through Transifex. If you wish to help out translating the Red Matrix to another language, sign up on transifex.com, visit [url=https://www.transifex.com/projects/p/red-matrix/]https://www.transifex.com/projects/p/red-matrix/[/url] and request to join one of the existing language teams or create a new one. Notify one of the core developers when you have a translation update which requires merging, or ask about merging it yourself if you're comfortable with git and PHP. We have a string file called 'messages.po' which is gettext compliant and a handful of email templates, and from there we automatically generate the application's language files.   

[zrl=[baseurl]/help/Translations]Translations - More Info[/zrl]

[b]Important[/b]

Please pull in any changes from the project repository and merge them with your work **before** issuing a pull request. We reserve the right to reject any patch which results in a large number of merge conflicts. This is especially true in the case of language translations - where we may not be able to understand the subtle differences between conflicting versions.

Also - **test your changes**. Don't assume that a simple fix won't break something else. If possible get an experienced Red developer to review the code. 

Further documentation can be found at the Github wiki pages at: [url=https://github.com/friendica/red/wiki]https://github.com/friendica/red/wiki[/url]

[b]Licensing[/b]

All code contributed to the project falls under the MIT license, unless otherwise specified. We will accept third-party code which falls under MIT, BSD and LGPL, but copyleft licensing (GPL, and AGPL) is only permitted in addons. It must be possible to completely remove the GPL (copyleft) code from the main project without breaking anything.

[b]Concensus Building[/b]

Code changes which fix an obvious bug are pretty straight-forward. For instance if you click "Save" and the thing you're trying to save isn't saved, it's fairly obvious what the intended behaviour should be. Often when developing feature requests, it may affect large numbers of community members and it's possible that other members of the community won't agree with the need for the feature, or with your proposed implementation. They may not see something as a bug or a desirable feature.

We encourage concensus building within the community when it comes to any feature which might be considered controversial or where there isn't unanimous decision that the proposed feature is the correct way to accomplish the task. The first place to pitch your ideas is to [url=https://zothub.com/channel/one]Channel One[/url]. Others may have some input or be able to point out facets of your concept which might be problematic in our environment. But also, you may encounter opposition to your plan. This doesn't mean you should stop and/or ignore the feature. Listen to the concerns of others and try and work through any implementation issues. 

There are places where opposition cannot be resolved. In these cases, please consider making your feature [b]optional[/b] or non-default behaviour that must be specifically enabled. This technique can often be used when a feature has significant but less than unanimous support. Those who desire the feature can turn it on and those who don't want it - will leave it turned off.

If a feature uses other networks or websites and or is only seen as desirable by a small minority of the community, consider making the functionality available via an addon or plugin. Once again, those who don't desire the feature won't need to install it. Plugins are relatively easy to create and "hooks" can be easily added or modified if the current hooks do not do what is needed to allow your plugin to work.
     

[b]Coding Style[/b]

In the interests of consistency we adopt the following code styling. We may accept patches using other styles, but where possible please try to provide a consistent code style. We aren't going to argue or debate the merits of this style, and it is irrelevant what project 'xyz' uses. This is not project 'xyz'. This is a baseline to try and keep the code readable now and in the future. 

[li] All comments should be in English.[/li]

[li] We use doxygen to generate documentation. This hasn't been consistently applied, but learning it and using it are highly encouraged.[/li]

[li] Indentation is accomplished primarily with tabs using a tab-width of 4.[/li]

[li] String concatenation and operators should be separated by whitespace. e.g. &quot;$foo = $bar . 'abc';&quot; instead of &quot;$foo=$bar.'abc';&quot;[/li]

[li] Generally speaking, we use single quotes for string variables and double quotes for SQL statements. &quot;Here documents&quot; should be avoided. Sometimes using double quoted strings with variable replacement is the most efficient means of creating the string. In most cases, you should be using single quotes.[/li]

[li] Use whitespace liberally to enhance readability. When creating arrays with many elements, we will often set one key/value pair per line, indented from the parent line appropriately. Lining up the assignment operators takes a bit more work, but also increases readability.[/li]

[li] Generally speaking, opening braces go on the same line as the thing which opens the brace. They are the last character on the line. Closing braces are on a line by themselves. [/li]

#include doc/macros/main_footer.bb;
