# Themes

* [Home](help)

To change the look of friendica you have to touch the themes.
The current default theme is [Vier](https://github.com/friendica/friendica/tree/stable/view/theme/vier) but there are numerous others.
Have a look at [github.com/bkil/friendica-themes](https://github.com/bkil/friendica-themes) for an overview of the existing themes.
In case none of them suits your needs, there are several ways to change a theme.

So, how to work on the UI of friendica.

You can either directly edit an existing theme.
But you might loose your changes when the theme is updated by the friendica team.

If you are almost happy with an existing theme, the easiest way to cover your needs is to create a new theme, inheriting most of the properties of the parent theme and change just minor stuff.
The below for a more detailed description of theme heritage.

Some themes also allow users to select *variants* of the theme.
Those theme variants most often contain an additional [CSS](https://en.wikipedia.org/wiki/CSS) file to override some styling of the default theme values.
From the themes in the main repository *vier* and *vier* are using this methods for variations.
Quattro is using a slightly different approach.

Third you can start your theme from scratch.
Which is the most complex way to change friendicas look.
But it leaves you the most freedom.
So below for a *detailed* description and the meaning of some special files.

### Styling

If you want to change the styling of a theme, have a look at the themes CSS file.
In most cases, you can found these in

    /view/theme/**your-theme-name**/style.css

sometimes, there is also a file called style.php in the theme directory.
This is only needed if the theme allows the user to change certain things of the theme dynamically.
Say the font size or set a background image.

### Templates

If you want to change the structure of the theme, you need to change the templates used by the theme.
Friendica themes are using [SMARTY3](http://www.smarty.net/) for templating.
The default template can be found in

    /view/templates

if you want to override any template within your theme create your version of the template in

    /view/theme/**your-theme-name**/templates

any template that exists there will be used instead of the default one.

### JavaScript

The same rule applies to the JavaScript files found in

    /js

they will be overwritten by files in

    /view/theme/**your-theme-name**/js.

## Creating a Theme from Scratch

Keep patient.
Basically what you have to do is identify which template you have to change so it looks more like what you want.
Adopt the CSS of the theme accordingly.
And iterate the process until you have the theme the way you want it.

*Use the source Luke.* and don't hesitate to ask in @[developers](https://forum.friendi.ca/profile/developers) or @[helpers](https://forum.friendi.ca/profile/helpers).

## Special Files

### unsupported

If a file with this name (which might be empty) exists in the theme directory, the theme is marked as *unsupported*.
An unsupported theme may not be selected by a user in the settings.
Users who are already using it wont notice anything.

### README(.md)

The contents of this file, with or without the .md which indicates [Markdown](https://daringfireball.net/projects/markdown/) syntax, will be displayed at most repository hosting services and in the theme page within the admin panel of friendica.

This file should contain information you want to let others know about your theme.

### screenshot.[png|jpg]

If you want to have a preview image of your theme displayed in the settings you should take a screenshot and save it with this name.
Supported formats are PNG and JPEG.

### theme.php

This is the main definition file of the theme.
In the header of that file, some meta information is stored.
For example, have a look at the theme.php of the *vier* theme:

    <?php
    /**
     * [Licence]
     *
     * Name: Vier
     * Version: 1.2
     * Author: Fabio <http://kirgroup.com/profile/fabrixxm>
     * Author: Ike <http://pirati.ca/profile/heluecht>
     * Author: Beanow <https://fc.oscp.info/profile/beanow>
     * Maintainer: Ike <http://pirati.ca/profile/heluecht>
     * Description: "Vier" is a very compact and modern theme. It uses the font awesome font library: http://fortawesome.github.com/Font-Awesome/
     */

You see the definition of the theme's name, it's version and the initial author of the theme.
These three pieces of information should be listed.
If the original author is no longer working on the theme, but a maintainer has taken over, the maintainer should be listed as well.
The information from the theme header will be displayed in the admin panel.

The first thing in file is to import the `App` class from `\Friendica\` namespace.

    use Friendica\App;

This will make our job a little easier, as we don't have to specify the full name every time we need to use the `App` class.

The next crucial part of the theme.php file is a definition of an init function.
The name of the function is <theme-name>_init.
So in the case of vier it is

    function vier_init(App $a) {
		$a->theme_info = array();
		$a->set_template_engine('smarty3');
    }

Here we have set the basic theme information, in this case they are empty.
But the array needs to be set.
And we have set the template engine that should be used by friendica for this theme.
At the moment you should use the *smarty3* engine.
There once was a friendica specific templating engine as well but that is not used anymore.
If you like to use another templating engine, please implement it.

If you want to add something to the HTML header of the theme, one way to do so is by adding it to the theme.php file.
To do so, add something alike

    DI::page()['htmlhead'] .= <<< EOT
    /* stuff you want to add to the header */
    EOT;

So you can access the properties of this friendica session from the theme.php file as well.

### default.php

This file covers the structure of the underlying HTML layout.
The default file is in

    /view/default.php

if you want to change it, say adding a 4th column for banners of your favourite FLOSS projects, place a new default.php file in your theme directory.
As with the theme.php file, you can use the properties of the $a variable with holds the friendica application to decide what content is displayed.
