# CX Disqus Comments

Disqus (http://disqus.com) allows users to comment on your entries everywhere. At it’s simplest form, you can simply add javascript to your pages, but this doesn’t deal with all the existing comments on your website, and isn’t very search engine friendly (since comments are displayed using javascript after page load).

CX Disqus Comments helps solve this problem by exporting your existing comments to Disqus, then syncing new Disqus comments back to ExpressionEngine’s internal database. This is especially great if you feel uneasy about a third party being responsible for the safe keeping of all those user comments!

## Important!

**Disqus have disabled their original authentication method (which this module was based on) for new accounts. While this module still works perfectly for existing accounts, it is no longer possible to use it with new Disqus accounts.**

**We have released this module as open source so that others may either update it with OAuth support, or use it as a basis for their own modules.**

## Installation

Follow these steps carefully. Setting up an API key to remotely access Disqus is not as easy as one would hope, and it’s important that you grant your API key the appropriate permissions, otherwise comment import/export won’t work!

**Make sure you have the standard EE Comments module installed and enabled before you install CX Disqus Comments.**

1. Upload the entire `cx_disqus` folder to `system/expressionengine/third_party` on your server.
2. Under Add-ons > Modules, find “CX Disqus Comments” and click Install
3. If you haven’t already, create a Disqus account, and add a new forum (you should create one forum to use for your entire website). You can create/view your Disqus forums at [http://disqus.com/dashboard/](http://disqus.com/dashboard/)). You will need to enter the forum shortname in the CX Disqus settings page.
4. Go to [http://disqus.com/api/applications/](http://disqus.com/api/applications/), and click “Register new application”. Give your application a name (can be the same as your forum).
5. Once your Disqus API application has been created, scroll down to the bottom of the settings and check the box next to your forum name. This will give your application write access to export existing EE comments.
6. Also at the bottom of the API application settings page, make sure that under “Authentication”, “Inherit permissions from (yourusername)” is checked, rather than OAuth. CX Disqus does not support OAuth authentication at this stage.
7. Copy the “Secret Key” from your Disqus API application to the CX DIsqus settings page. You’re now ready to add CX DIsqus to your templates!

## Comments Tag

    {exp:channel:entries channel="blog"}
        {title}
        {exp:cx_disqus:comments entry_id="{entry_id}"}
    {/exp:channel:entries}

Add Disqus comments to any channel entry. Unlike the standard EE comments form, this should be placed inside a channel entries loop (where you already know the entry ID). This will output a list of comments inside a `<noscript>` tag (search engine friendly), which will be dynamically replaced by Disqus on page load. You can also use it as a tag pair, if you want to template the fallback version of your comments page (this will not affect the display of the dynamic Disqus comments). All of the standard comment variables are available inside the tag pair.

This tag also automatically imports recent comments from Disqus (at most every 10 minutes by default). You can customise this behaviour with the `sync=””` parameter.

### Comments Tag Parameters

* `entry_id=”{entry_id}”`- Specify the entry the comments relate to (required).
* `title=”Comments”` - Override the Disqus thread title. Defaults to the ExpressionEngine entry title.
* `sync=”600”` - Specify the number of seconds between sync attempts. Defaults to 600 (10 minutes). Set to 0 to force sync on every page load (ONLY EVER do this during development!).
* `developer=”yes”` - Allows Disqus to be used on offline/local websites (without this, Disqus will throw an API error).

### Comments Tag Variables

You can also use `{exp:cx_disqus:comments}` as a tag pair, if you wish to customise the fallback version of your comments page. All of the standard comment variables are available inside the tag pair. Remember this will only affect the fallback, `<noscript>` version of your comments.

* {name}
* {email}
* {url}
* {location}
* {comment}

## Changelog

### CX Disqus Comments 1.2.1
*Released January 26, 2013*

* Disqus is loaded via HTTPS to avoid issues on secure websites
* First open source release

### CX Disqus Comments 1.2.0
*Released February 10, 2012*

* MSM support
* Updated help text to reflect new required Disqus API settings

### CX Disqus Comments 1.1.0
*Released August 23, 2011*

* Disqus comment sync now happens in a background browser request, to prevent page load issues if Disqus is unavailable
* Comment email and URL are trimmed of extra whitespace, to prevent occasional errors when exporting certain comments
* NSM Add-on updater support

### CX Disqus Comments 1.0.4
*Released June 23, 2011*

* Trim comment author name to 30 characters during export to prevent a Disqus API error
* More helpful error messages during comment export

### CX Disqus Comments 1.0.3
*Released June 20, 2011*

* Test Disqus forum name & API secret key when visiting CP settings page

### CX Disqus Comments 1.0.2
*Released June 10, 2011*

* Update channel entry comment_total and recent_comment_date variables when syncing comments from Disqus

### CX Disqus Comments 1.0.1
*Released June 9, 2011*

* Fixed an issue preventing Disqus from loading on pages where the entry title contained quote marks

### CX Disqus Comments 1.0.0
*Released June 8, 2011*

* Initial release
