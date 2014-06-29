Creating a Derived Theme
========================

**Lesson 1**

A derived theme takes most of the settings from its "parent" theme and lets you change a few things to your liking without creating an entire theme package. 


To create a derived theme, first choose a name. For our example we'll call our theme 'mytheme'. Hopefully you'll be a bit more creative. But throughout this document, wherever you see 'mytheme', replace that with the name you chose.

**Directory Structure**

First you need to create a theme directory structure. We'll keep it simple. We need a php directory and a css directory. Here are the Unix/Linux commands to do this. Assume that 'mywebsite' is your top level Red Matrix folder. 


    cd mywebsite
    mkdir view/theme/mytheme
    mkdir view/theme/mytheme/css
    mkdir view/theme/mytheme/php


Great. Now we need a couple of files. The first one is your theme info file, which describes the theme.

It will be called view/theme/mytheme/php/theme.php (clever name huh?)

Inside it, put the following information - edit as needed

    <?php

    /**
     *   * Name: Mytheme
     *   * Description: Sample Derived theme
     *   * Version: 1.0
     *   * Author: Your Name
     *   * Compat: Red [*]
     *
     */

    function mytheme_init(&$a) {

        $a->theme_info['extends'] = 'redbasic';


    }


Remember to rename the mytheme_init function with your theme name. In this case we will be extending the theme 'redbasic'. 


Now create another file. We call this a PCSS file, but it's really a PHP file.

The file is called view/theme/mytheme/php/style.php

In it, put the following:

    <?php

    require_once('view/theme/redbasic/php/style.php');

    echo @file_get_contents('view/theme/mytheme/css/style.css');



That's it. This tells the software to read the PCSS information for the redbasic theme first, and then read our CSS file which will just consist of changes we want to make from our parent theme (redbasic). 


Now create the actual CSS file for your theme.  Put it in view/theme/mytheme/css/style.css (where we just told the software to look for it). For our example, we'll just change the body background color so you can see that it works. You can use any CSS you'd like. 


    body {
        background-color: #DDD;
    }


You've just successfully created a derived theme. This needs to be enabled in the admin "themes" panel, and then anybody on the site can use it by selecting it in Settings->Display Settings as their default theme.  


 