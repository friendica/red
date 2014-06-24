[b]Git For Non-Developers[/b]

So you're handling a translation, or you're contributing to a theme, and every time you make a pull request you have to talk to one of the developers before your changes can be merged in?

Chances are, you just haven't found a quick how-to explaining how to keep things in sync on your end.  It's really very easy.

After you've created a fork of the repo (just click &quot;fork&quot; at github), you need to clone your own copy.

For the sake of examples, we'll assume you're working on a theme called redexample (which does not exist).

[code]git clone https://github.com/username/red.git[/code]

Once you've done that, cd into the directory, and add an upstream.

[code]
cd red
git remote add upstream https://github.com/friendica/red
[/code]

From now on, you can pull upstream changes with the command
[code]git fetch upstream[/code]

Before your changes can be merged automatically, you will often need to merge upstream changes.

[code]
git merge upstream/master
[/code]

You should always merge upstream before pushing any changes, and [i]must[/i] merge upstream with any pull requests to make them automatically mergeable.

99% of the time, this will all go well.  The only time it won't is if somebody else has been editing the same files as you - and often, only if they have been editing the same lines of the same files.  If that happens, that would be a good time to request help until you get the hang of handling your own merge conflicts.

Then you just need to add your changes [code]git add view/theme/redexample/[/code]

This will add all the files in view/theme/redexample and any subdirectories.  If your particular files are mixed throughout the code, you should add one at a time.  Try not to do git add -a, as this will add everything, including temporary files (we mostly, but not always catch those with a .gitignore) and any local changes you have, but did not intend to commit.

Once you have added all the files you have changed, you need to commit them.  [code]git commit[/code]

This will open up an editor where you can describe the changes you have made.  Save this file, and exit the editor.

Finally, push the changes to your own git
[code]git push[/code]

And that's it!

Return to the [url=[baseurl]/help/main]Main documentation page[/url]