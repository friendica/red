Red Developer Guide
===================

**Here is how you can join us.**

First, get yourself a working git package on the system where you will be
doing development.

Create your own github account.

You may fork/clone the Red repository from [https://github.com/friendica/red.git](https://github.com/friendica/red.git).

Follow the instructions provided here: [http://help.github.com/fork-a-repo/](http://help.github.com/fork-a-repo/)
to create and use your own tracking fork on github

Then go to your github page and create a "Pull request" when you are ready
to notify us to merge your work.

**Translations**

Our translations are managed through Transifex. If you wish to help out translating the Red Matrix to another language, sign up on transifex.com, visit [https://www.transifex.com/projects/p/red-matrix/](https://www.transifex.com/projects/p/red-matrix/) and request to join one of the existing language teams or create a new one. Notify one of the core developers when you have a translation update which requires merging, or ask about merging it yourself if you're comfortable with git and PHP. We have a string file called 'messages.po' which is gettext compliant and a handful of email templates, and from there we automatically generate the application's language files.   

[Translations - More Info](help/Translations)

**Important**

Please pull in any changes from the project repository and merge them with your work **before** issuing a pull request. We reserve the right to reject any patch which results in a large number of merge conflicts. This is especially true in the case of language translations - where we may not be able to understand the subtle differences between conflicting versions.

Also - **test your changes**. Don't assume that a simple fix won't break something else. If possible get an experienced Red developer to review the code. 

Further documentation can be found at the Github wiki pages at: [https://github.com/friendica/red/wiki](https://github.com/friendica/red/wiki).

**Licensing**

All code contributed to the project falls under the MIT license, unless otherwise specified. We will accept third-party code which falls under MIT, BSD and LGPL, but copyleft licensing (GPL, and AGPL) is only permitted in addons. It must be possible to completely remove the GPL (copyleft) code from the main project without breaking anything.

**Coding Style** 

In the interests of consistency we adopt the following code styling. We may accept patches using other styles, but where possible please try to provide a consistent code style. We aren't going to argue or debate the merits of this style, and it is irrelevant what project 'xyz' uses. This is not project 'xyz'. This is a baseline to try and keep the code readable now and in the future. 

* All comments should be in English.

* We use doxygen to generate documentation. This hasn't been consistently applied, but learning it and using it are highly encouraged.

* Indentation is accomplished primarily with tabs using a tab-width of 4.

* String concatenation and operators should be separated by whitespace. e.g. "$foo = $bar . 'abc';" instead of "$foo=$bar.'abc';"

* Generally speaking, we use single quotes for string variables and double quotes for SQL statements. "Here documents" should be avoided. Sometimes using double quoted strings with variable replacement is the most efficient means of creating the string. In most cases, you should be using single quotes.

* Use whitespace liberally to enhance readability. When creating arrays with many elements, we will often set one key/value pair per line, indented from the parent line appropriately. Lining up the assignment operators takes a bit more work, but also increases readability.

* Generally speaking, opening braces go on the same line as the thing which opens the brace. They are the last character on the line. Closing braces are on a line by themselves. 


