==========
b8: readme
==========

:Author: Tobias Leupold
:Homepage: http://nasauber.de/
:Contact: tobias.leupold@web.de
:Date: |date|

.. contents:: Table of Contents

Description of b8
=================

What is b8?
-----------

b8 is a spam filter implemented in `PHP <http://www.php.net/>`__. It is intended to keep your weblog or guestbook spam-free. The filter can be used anywhere in your PHP code and tells you whether a text is spam or not, using statistical text analysis. See `How does it work?`_ for details about this. To be able to do this, b8 first has to learn some spam and some ham example texts to decide what's good and what's not. If it makes mistakes classifying unknown texts, they can be corrected and b8 learns from the corrections, getting better with each learned text.

At the moment of this writing, b8 has classified 14411 guestbook entries and weblog comments on my homepage since december 2006. 131 were ham. 39 spam texts (0.27 %) have been rated as ham (false negatives), with not even one false positive (ham message classified as spam). This results in a sensitivity of 99.73 % (the probability that a spam text will actually be rated as spam) and a specifity of 100 % (the probability that a ham text will actually be rated as ham) for me. I hope, you'll get the same good results :-)

Basically, b8 is a statistical ("Bayesian"[#]_) spam filter like `Bogofilter <http://bogofilter.sourceforge.net/>`__ or `SpamBayes <http://spambayes.sourceforge.net/>`__, but it is not intended to classify e-mails. When I started to write b8, I didn't find a good PHP spam filter (or any spam filter that wasn't just some example code how one *could* implement a Bayesian spam filter in PHP) that was intended to filter weblog or guestbook entries. That's why I had to write my own ;-) |br|
Caused by it's purpose, the way b8 works is slightly different from most of the Bayesian email spam filters out there. See `What's different?`_ if you're interested in the details.

.. [#] A mathematician told me that the math in b8 actually does not use Bayes' theorem but some derived algorithms that are just related to it. So … let's simply believe that and stop claiming b8 was a *Bayesian* spam filter ;-)

How does it work?
-----------------

b8  basically uses the math and technique described in Paul Graham's article "A Plan For Spam" [#planforspam]_ to distinguish ham and spam. The improvements proposed in Graham's article "Better Bayesian Filtering" [#betterbayesian]_ and Gary Robinson's article "Spam Detection" [#spamdetection]_ have also been considered. See also the article "A Statistical Approach to the Spam Problem" [#statisticalapproach]_.

b8 cuts the text to classify to pieces, extracting stuff like e-mail addresses, links and HTML tags. For each such token, it calculates a single probability for a text containing it being spam, based on what the filter has learned so far. When the token was not seen before, b8 tries to find similar ones using the "degeneration" described in [#betterbayesian]_ and uses the most relevant value found. If really nothing is found, b8 assumes a default rating for this token for the further calculations. |br|
Then, b8 takes the most relevant values (which have a rating far from 0.5, which would mean we don't know what it is) and calculates the probability that the whole text is spam by the inverse chi-square function described in [#spamdetection]_.
There are some parameters that can be set which influence the filter's behaviour (see below).

In short words: you give b8 a text and it returns a value between 0 and 1, saying it's ham when it's near 0 and saying it's spam when it's near 1.

What do I need for it?
----------------------

Not much! You just need PHP 5 on the server where b8 will be used (b8 version 0.5 finally dropped PHP 4 compatibility – thankfully ;-) and a proper storage possibility for the wordlists. I strongly recommend using `Berkeley DB <http://www.oracle.com/database/berkeley-db/index.html>`_. See below how you can check if you can use it and why you should use it. If the server's PHP wasn't compiled with Berkeley DB support, a `MySQL <http://mysql.com/>`_ table can be used alternatively.

What's different?
-----------------

b8 is designed to classify weblog or guestbook entries, not e-mails. For this reason, it uses a slightly different technique than most of the other statistical spam filters out there use.

My experience was that spam entries on my weblog or guestbook were often quite short, sometimes just something like "123abc" as text and a link to a suspect homepage. Some spam bots don't even made a difference between e. g. the "name" and "text" fields and posted their text as email address, for example. Considering this, b8 just takes one string to classify, making no difference between "headers" and "text". |br|
The other thing is that most statistical spam filters count one token one time, no matter how often it appears in the text (as Graham describes it in [#planforspam]_). b8 does count how often a token was seen and learns or considers this. Additionally, the number of learned ham and spam texts are saved and used as the calculation base for the single probabilities. Why this? Because a text containing one link (no matter where it points to, just indicated by a "\h\t\t\p\:\/\/" or a "www.") might not be spam, but a text containing 20 links might be.

This means that b8 might be good for classifying weblog or guestbook entries (I really think it is ;-) – but very likely, it will work quite poor when being used for something else (like classifying e-mails). But as said above, for this task, there are a lot of very good filters out there to choose from.

Update from prior versions
==========================

If this is a new b8 installation, read on at the `Installation`_ section!

Update from bayes-php version 0.2.1 or earlier
----------------------------------------------

Please first follow the database update instructions of the bayes-php-0.3 release if you update from a version prior to bayes-php-0.3 and then read the following paragraph about updating from a version <0.3.3.

Update from bayes-php version 0.3 or later
------------------------------------------

**You use Berkeley DB?**
	Everything's fine, you can simply continue using your database.

**You use MySQL?**
	The ``CREATE`` statement of b8's wordlist has changed. The best is probably to create a dump via your favorite administration tool or script, create the new table and re-insert all data. The layout is still the same: there's one "token" column and one "data" column. Having done that, you can keep using your data.

**You use SQLite?**
	Sorry, at the moment, there's no SQLite backend for b8. But we're working on it :-)

The configuration model of b8 has changed.  Please read through the `Configuration`_ section and update your configuration accordingly.

b8's lexer has been partially re-written. It should now be able to handle all kind of non-latin-1 input, like cyrillic, chinese or japanese texts. Caused by this fact, much more tokens will be recognized when classifying such texts. Therefore, you could get different results in b8's ratings, even if the same database is used and although the math is still the same.

b8 0.5 introduced two constants that can be used in the ``learn()`` and ``unlearn()`` functions: ``b8::HAM`` and ``b8::SPAM``. The literal values "ham" and "spam" can still be used anyway.

Installation
============

Installing b8 on your server is quite easy. You just have to provide the needed files. To do this, you could just upload the whole ``b8`` subdirectory to the base directory of your homepage. It contains the filter itself and all needed backend classes. The other directories (``doc``, ``example`` and ``install``) are not used by b8.

That's it ;-)

Configuration
=============

The configuration is passed as arrays when instantiating a new b8 object. Two arrays can be passed to b8, one containing b8's base configuration and some settings for the lexer (which should be common for all lexer classes, in case some other lexer than the default one will be written one day) and one for the storage backend. |br|
You can have a look at ``example/index.php`` to see how this can be done. `Using b8 in your scripts`_ also shows example code showing how b8 can be included in a PHP script.

Not all values have to be set. When some values are missing, the default ones will be used. If you do use the default settings, you don't have to pass them to b8.

b8's base configuration
-----------------------

All these values can be set in the "config_b8" array (the first parameter) passed to b8. The name of the array doesn't matter (of course), it just has to be the first argument.

These are some basic settings telling b8 which backend classes to use:

	**storage**
		This defines which storage backend will be used to save b8's wordlist. Currently, two backends are available: `Berkeley DB <http://www.oracle.com/database/berkeley-db/index.html>`_ (``dba``) and `MySQL <http://mysql.com/>`_ (``mysql``). At the moment, b8 does not support `SQLite <http://sqlite.org/>`_ (as the previous version did), but it will be (hopefully) re-added in one of the next releases. The default is ``dba`` (string).

		*Berkeley DB*
			This is the preferred storage backend. It was the original backend for the filter and remains the most performant. b8's storage model is optimized for this database, as it is really fast and fits perfectly to what the filter needs to do the job. All content is saved in a single file, you don't need special user rights or a database server. |br|
			If you don't know whether your server's PHP can use a Berkeley DB, simply run the script ``install/setup_berkeleydb.php``. If it shows a Berkeley DB handler, please use this backend.

		*MySQL*
			As some webspace hosters don't allow using a Berkeley DB (but please be sure to check if you can use it!), but most do provide a MySQL server, using a MySQL table for the wordlist is provided as an alternative storage method. As said above, b8 was always intended to use a Berkeley DB. It doesn't use or need SQL to query the database. So, very likely, this will work less performant, produce a lot of unnecessary overhead and waste computing power. But it will do fine anyway!

		See `Configuration of the storage backend`_ for the settings of the chosen backend.

	**degenerator**
		The degenerator class to be used. See `How does it work?`_ and [#betterbayesian]_ if you're interested in what "degeneration" is. Defaults to ``default`` (string). At the moment, only one degenerator exists, so you probably don't want to change this unless you have written your own degenerator.

	**lexer**
		The lexer class to be used. Defaults to ``default`` (string). At the moment, only one lexer exists, so you probably don't want to change this unless you have written your own lexer.

		The behaviour of the lexer can be additionally configured with the following variables:

			**min_size**
				The minimal length for a token to be considered when calculating the rating of a text. Defaults to ``3`` (integer).

			**max_size**
				The maximal length for a token to be considered when calculating the rating of a text. Defaults to ``30`` (integer).

			**allow_numbers**
				Should pure numbers also be considered? Defaults to ``FALSE`` (boolean).

The following settings influence the mathematical internals of b8. If you want to experiment, feel free to play around with them; but be warned: wrong settings of these values will result in poor performance or could even "short-circuit" the filter. |br|
Leave these values as they are unless you know what you are doing!

The "Statistical discussion about b8" [#b8statistic]_ shows why the default values are the default ones.

	**use_relevant**
		This tells b8 how many tokens should be used when calculating the spamminess of a text. The default setting is ``15`` (integer). This seems to be a quite reasonable value. When using to many tokens, the filter will fail on texts filled with useless stuff or with passages from a newspaper, etc. not being very spammish. |br|
		The tokens counted multiple times (see above) are added in addition to this value. They don't replace other ratings.

	**min_dev**
		This defines a minimum deviation from 0.5 that a token's rating must have to be considered when calculating the spamminess. Tokens with a rating closer to 0.5 than this value will simply be skipped. |br|
		If you don't want to use this feature, set this to ``0``. Defaults to ``0.2`` (float). Read [#b8statistic]_ before increasing this.

	**rob_x**
		This is Gary Robinson's *x* constant (cf. [#spamdetection]_). A completely unknown token will be rated with the value of ``rob_x``. The default ``0.5`` (float) seems to be quite reasonable, as we can't say if a token that also can't be rated by degeneration is good or bad. |br|
		If you receive much more spam than ham or vice versa, you could change this setting accordingly.

	**rob_s**
		This is Gary Robinson's *s* constant. This is essentially the probability that the *rob_x* value is correct for a completely unknown token. It will also shift the probability of rarely seen tokens towards this value. The default is ``0.3`` (float) |br|
		See [#spamdetection]_ for a closer description of the *s* constant and read [#b8statistic]_ for specific information about this constant in b8's algorithms.

Configuration of the storage backend
------------------------------------

All the following values can be set in the "config_database" array (the second parameter) passed to b8. The name of the array doesn't matter (of course), it just has to be the second argument.

Settings for the Berkeley DB (DBA) backend
``````````````````````````````````````````
**database**
	The filename of the database file, relative to the location of ``b8.php``. Defaults to ``wordlist.db`` (string).

**handler**
	The DBA handler to use (cf. `the PHP documentation <http://php.net/manual/en/dba.requirements.php>`_ and `Setting up a new Berkeley DB`_). Defaults to ``db4`` (string).

Settings for the MySQL backend
``````````````````````````````

**database**
	The database containing b8's wordlist table. Defaults to ``b8_wordlist`` (string).

**table_name**
	The table containing b8's wordlist. Defaults to ``b8_wordlist`` (string).

**host**
	The host of the MySQL server. Defaults to ``localhost`` (string).

**user**
	The user name used to open the database connection. Defaults to ``FALSE`` (boolean).

**pass**
	The password required to open the database connection. Defaults to ``FALSE`` (boolean).

**connection**
	An existing MySQL link-resource that can be used by b8. Defaults to ``NULL`` (NULL).

Using b8
========

Now, that everything is configured, you can start to use b8. A sample script that shows what can be done with the filter exists in ``example/index.php``. The best thing for testing how all this works is to use this script before using b8 in your own scripts.

Before you can start, you have to setup a database so that b8 can store a wordlist.

Setting up a new database
-------------------------

Setting up a new Berkeley DB
````````````````````````````

I wrote a script to setup a new Berkeley DB for b8. It is located in ``install/setup_berkeleydb.php``. Just run this script on your server and be sure that the directory containing it has the proper access rights set so that the server's HTTP server user or PHP user can create a new file in it (probably ``0777``). The script is quite self-explaining, just run it.

Of course, you can also create a Berkeley DB by hand. In this case, you just have to insert three keys:

::

	bayes*dbversion  => 2
	bayes*texts.ham  => 0
	bayes*texts.spam => 0

Be sure to set the right DBA handler in the storage backend configuration if it's not ``db4``.

Setting up a new MySQL table
````````````````````````````

The SQL file ``install/setup_mysql.sql`` contains both the create statement for the wordlist table of b8 and the ``INSERT`` statements for adding the necessary internal variables.

Simply change the table name according to your needs (or leave it as it is ;-) and run the SQL to setup a b8 wordlist MySQL table.

Using b8 in your scripts
------------------------

Just have a look at the example script located in ``example/index.php`` to see how you can include b8 in your scripts. Essentially, this strips down to:

::

	# Include the b8 code
	require "{$_SERVER['DOCUMENT_ROOT']}/b8/b8.php";

	# Do some configuration

	$config_b8 = array(
		'some_key' => 'some_value',
		'foo' => 'bar'
	);

	$config_database = array(
		'some_key' => 'some_value',
		'foo' => 'bar'
	);

	# Create a new b8 instance
	$b8 = new b8($config_b8, $config_database);

b8 provides three functions in an object oriented way (called e. g. via ``$b8->classify($text)``):

**learn($text, $category)**
	This saves the reference text ``$text`` (string) in the category ``$category`` (b8 constant). |br|
	b8 0.5 introduced two constants that can be used as ``$category``: ``b8::HAM`` and ``b8::SPAM``. To be downward compatible with older versions of b8, the literal values "ham" and "spam" (case-sensitive strings) can still be used here.

**unlearn($text, $category)**
	This function just exists to delete a text from a category in which is has been stored accidentally before. It deletes the reference text ``$text`` (string) from the category ``$category`` (either the constants ``b8::HAM`` or ``b8::SPAM`` or the literal case-sensitive strings "ham" or "spam" – cf. above). |br|
	**Don't delete a spam text from ham after saving it in spam or vice versa, as long you don't have stored it accidentally in the wrong category before!** This will not improve performance, quite the opposite: it will actually break the filter after a time, as the counter for saved ham or spam texts will reach 0, although you have ham or spam tokens stored: the filter will try to remove texts from the ham or spam data which have never been stored there, decrease the counter for tokens which are found just skip the non-existing words.

**classify($text)**
	This function takes the text ``$text`` (string), calculates it's probability for being spam it and returns a value between 0 and 1 (float). |br|
	A value close to 0 says the text is more likely ham and a value close to 1 says the text is more likely spam. What to do with this value is *your* business ;-) See also `Tips on operation`_ below.

Tips on operation
=================

Before b8 can decide whether a text is spam or ham, you have to tell it what you consider as spam or ham. At least one learned spam or one learned ham text is needed to calculate anything. To get good ratings, you need both learned ham and learned spam texts, the more the better. |br|
What's considered as "ham" or "spam" can be very different, depending on the operation site. On my homepage, practically each and every text posted in English or using cyrillic letters is spam. On an English or Russian homepage, this will be not the case. So I think it's not really meaningful to provide some "spam data" to start. Just train b8 with "your" spam and ham.

For the practical use, I advise to give the filter all data availible. E. g. name, email address, homepage, IP address und of course the text itself should be stored in a variable (e. g. separated with an ``\n`` or just a space or tab after each block) and then be classified. The learning should also be done with all data availible. |br|
Saving the IP address is probably only meaningful for spam entries, because spammers often use the same IP address multiple times. In principle, you can leave out the IP of ham entries.

You can use b8 e. g. in a guestbook script and let it classify the text before saving it. Everyone has to decide which rating is necessary to classify a text as "spam", but a rating of >= 0.8 seems to be reasonable for me. If one expects the spam to be in another language that the ham entries or the spams are very short normally, one could also think about a limit of 0.7. |br|
The email filters out there mostly use > 0.9 or even > 0.99; but keep in mind that they have way more data to analyze in most of the cases. A guestbook entry may be quite short, especially when it's spam.

In my opinion, a autolearn function is very handy. I save spam messages with a rating higher than 0.7 but less than 0.9 automatically as spam. I don't do this with ham messages in an automated way to prevent the filter from saving a false negative as ham and then classifying and learning all the spam as ham when I'm on holidays ;-)

Closing
=======

So … that's it. Thanks for using b8! If you find a bug or have an idea how to make b8 better, let me know. I'm also always looking forward to get e-mails from people using b8 on their homepages :-)

References
==========

.. [#planforspam] Paul Graham, *A Plan For Spam* (http://paulgraham.com/spam.html)
.. [#betterbayesian] Paul Graham, *Better Bayesian Filtering* (http://paulgraham.com/better.html)
.. [#spamdetection] Gary Robinson, *Spam Detection* (http://radio.weblogs.com/0101454/stories/2002/09/16/spamDetection.html)
.. [#statisticalapproach] *A Statistical Approach to the Spam Problem* (http://linuxjournal.com/article/6467)
.. [#b8statistic] Tobias Leupold, *Statistical discussion about b8* (http://nasauber.de/opensource/b8/discussion/)

Appendix
========

FAQ
---

What about more than two categories?
````````````````````````````````````

I wrote b8 with the `KISS principle <http://en.wikipedia.org/wiki/KISS_principle>`__ in mind. For the "end-user", we have a class with almost no setup to do that can do three things: classify a text, learn a text and un-learn a text. Normally, there's no need to un-learn a text, so essentially, there are only two functions we need. |br|
This simplicity is only possible because b8 only knows two categories (normally "Ham" and "Spam" or some other category pair) and tells you, in one float number between 0 and 1, if a given texts rather fits in the first or the second category. If we would support multiple categories, more work would have to be done and things would become more complicated. One would have to setup the categories, have another database layout (perhaps making it mandatory to have SQL) and one float number would not be sufficient to describe b8's output, so more code would be needed – even outside of b8.

All the code, the database layout and particularly the math is intended to do exactly one thing: distinguish between two categories. I think it would be a lot of work to change b8 so that it would support more than two categories. Probably, this is possible to do, but don't ask me in which way we would have to change the math to get multiple-category support – I'm a dentist, not a mathematician ;-) |br|
Apart from this I do believe that most people using b8 don't want or need multiple categories. They just want to know if a text is spam or not, don't they? I do, at least ;-)

But let's think about the multiple-category thing. How would we calculate a rating for more than two categories? If we had a third one, let's call it "`Treet <http://en.wikipedia.org/wiki/Treet>`__", how would we calculate a rating? We could calculate three different ratings. One for "Ham", one for "Spam" and one for "Treet" and choose the highest one to tell the user what category fits best for the text. This could be done by using a small wrapper script using three instances of b8 as-is and three different databases, each containing texts being "Ham", "Spam", "Treet" and the respective counterparts. |br|
But here's the problem: if we have "Ham" and "Spam", "Spam" is the counterpart of "Ham". But what's the counterpart of "Spam" if we have more than one additional category? Where do the "Non-Ham", "Non-Spam" and "Non-Treet" texts come from?

Another approach, a direct calculation of more than two probabilities (the "Ham" probability is simply 1 minus the "Spam" probability, so we actually get two probabilities with the return value of b8) out of one database would require big changes in b8's structure and math.

There's a project called `PHPNaiveBayesianFilter <http://xhtml.net/scripts/PHPNaiveBayesianFilter>`__ which supports multiple categories by default. The author calls his software "Version 1.0", but I think this is the very first release, not a stable or mature one. The most recent change of that release dates back to 2003 according to the "changed" date of the files inside the zip archive, so probably, this project is dead or has never been alive and under active development at all. |br|
Actually, I played around with that code but the results weren't really good, so I decided to write my own spam filter from scratch back in early 2006 ;-)

All in all, there seems to be no easy way to implement multiple (meaning more than two) categories using b8's current code base and probably, b8 will never support more than two categories. Perhaps, a fork or a complete re-write would  be better than implementing such a feature. Anyway, I don't close my mind to multiple categories in b8. Feel free to tell me how multiple categories could be implementented in b8 or how a multiple-category version using the same code base (sharing a common abstract class?) could be written.

What about a list with words to ignore?
```````````````````````````````````````

Some people suggested to introduce a list with words that b8 will simply ignore. Like "and", "or", "the", and so on. I don't think this is very meaningful.

First, it would just work for the particular language that has been stored in the list. Speaking of my homepage, most of my spam is English, almost all my ham is German. So I would have to maintain a list with the probably less interesting words for at least two languages. Additionally, I get spam in Chinese, Japanese and Cyrillic writing or something else I can't read as well. What word should be ignored in those texts?  |br|
Second, why should we ever exclude words? Who tells us those words are *actually* meaningless? If a word appears both in ham and spam, it's rating will be near 0.5 and so, it won't be used for the final calculation if a appropriate minimum deviation was set. So b8 will exclude it anyway without any blacklist. And think of this: if we excluded a word of which we only *think* it doesn't mean anything but it actually does appear more often in ham or spam, the results will get even worse.

So why should we care about things we do not have to care about? ;-)


Why is it called "b8"?
``````````````````````

The initial name for the filter was (damn creative!) "bayes-php". There were two main reasons for searching another name: 1. "bayes-php" sucks. 2. the `PHP License <http://php.net/license/3_01.txt>`_ says the PHP guys do not like when the name of a script written in PHP contains the word "PHP". Read the `License FAQ <http://www.php.net/license/index.php#faq-lic>`_ for a reasonable argumentation about this.

Luckily, `Tobias Lang <http://langt.net/>`_ proposed the new name "b8". And these are the reasons why I chose this name:

- "bayes-php" is a "b" followed by 8 letters.
- "b8" is short and handy. Additionally, there was no program with the name "b8" or "bate"
- The English verb "to bate" means "to decrease" – and that's what b8 does: it decreases the number of spam entries in your weblog or guestbook!
- "b8" just sounds way cooler than "bayes-php" ;-)

About the database
------------------

The database layout
```````````````````

The database layout is quite simple. It's just key:value for everything stored. There are three "internal" variables stored as normal tokens (but all containing a ``*`` which is always used as a split character by the lexer, so we can't get collisions):

**bayes*dbversion**
	This indicates the database's "version". The first versions of b8 did not set this. Version "2" indicates that we have a database created by a b8 version already storing `the "lastseen" parameter`_.

**bayes*texts.ham**
	The number of ham texts learned.

**bayes*texts.spam**
	The number of spam texts learned.

Each "normal" token is stored with it's literal name as the key and it's data as the value. The data consists of the count of the token in all ham and spam texts and the date when the token was used the last time, all in one string and separated by spaces. So we have the following scheme:

::

	"token" => "count_ham count_spam lastseen"

The "lastseen" parameter
````````````````````````

Somebody looking at the code might be wondering why b8 stores this "lastseen" parameter. This value is not used for any calculation at the moment. Initially, it was intended to keep the database maintainable in a way that "old" data could be removed. When e. g. a token only appeared once in ham or spam and has not been seen for a year, one could simply delete it from the database. |br|
I actually never used this feature (does anybody?). So probably, some changes will be done to this one day. Perhaps, I find a way to include this data in the spamminess calculation in a meaningful way, or at least for some statistics. One could also make this optional to keep the calculation effort small if this is needed.

Feel free to send me any suggestions about this!

.. |br| raw:: html

   <br />

.. section-numbering::

.. |date| date::
