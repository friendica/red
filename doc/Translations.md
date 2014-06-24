Translating the Red Matrix
==========================

Translation Process
-------------------

The strings used in the UI of Red is translated at [Transifex][1] and then
included in the git repository at github. If you want to help with translation
for any language, be it correcting terms or translating Red to a
currently not supported language, please register an account at transifex.com
and contact the Red translation team there.

Translating Red is simple. Just use the online tool at transifex. If you
don't want to deal with git & co. that is fine, we check the status of the
translations regularly and import them into the source tree at github so that
others can use them.

We do not include every translation from transifex in the source tree to avoid
a scattered and disturbed overall experience. As an uneducated guess we have a
lower limit of 50% translated strings before we include the language. This
limit is judging only by the amount of translated strings under the assumption
that the most prominent strings for the UI will be translated first by a
translation team. If you feel your translation useable before this limit,
please contact us and we will probably include your teams work in the source
tree.

If you want to get your work into the source tree yourself, feel free to do so
and contact us with and question that arises. The process is simple and
Red ships with all the tools necessary.

The location of the translated files in the source tree is
    /view/LNG-CODE/
where LNG-CODE is the language code used, e.g. de for German or fr for French.
For the email templates (the *.tpl files) just place them into the directory
and you are done. The translated strings come as a "messages.po" file from
transifex which needs to be translated into the PHP file Red uses.  To do
so, place the file in the directory mentioned above and use the "po2php"
utility from the util directory of your Red installation.

Assuming you want to convert the German localization which is placed in
view/de/messages.po you would do the following.

1. Navigate at the command prompt to the base directory of your
   Red installation

2. Execute the po2php script, which will place the translation
   in the strings.php file that is used by Red.

       $> php util/po2php.php view/de/messages.po

   The output of the script will be placed at view/de/strings.php where
   froemdoca os expecting it, so you can test your translation mmediately.
                                  
3. Visit your Red page to check if it still works in the language you
   just translated. If not try to find the error, most likely PHP will give
   you a hint in the log/warnings.about the error.
                                        
   For debugging you can also try to "run" the file with PHP. This should
   not give any output if the file is ok but might give a hint for
   searching the bug in the file.

       $> php view/de/strings.php

4. commit the two files with a meaningful commit message to your git
   repository, push it to your fork of the Red repository at github and
   issue a pull request for that commit.

Utilities
---------

Additional to the po2php script there are some more utilities for translation
in the "util" directory of the Red source tree.  If you only want to
translate Red into another language you wont need any of these tools most
likely but it gives you an idea how the translation process of Red
works.

For further information see the utils/README file.

Known Problems
--------------

* Red uses the language setting of the visitors browser to determain the
  language for the UI. Most of the time this works, but there are some known
  quirks.
* the early translations are based on the friendica translations, if you 
  some rough translations please let us know or fix them at Transifex.

Links
------
[1]:   http://www.transifex.com/projects/p/red-matrix/

