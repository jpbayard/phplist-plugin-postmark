# Postmark Plugin #

## Description ##

The plugin allows you get readable DMARC reports through the free Postmark API. 
The plugin adds a menu item under "Statistics" where you can generate some reports.
Those reports are (kind of ) similar to the weekly reports of postmark. 

### Set the plugin directory ###
The default plugin directory is `plugins` within the admin directory.
The plugin dir has to be in the admin folder but can be named another way.

The PostMarkPlugin dir must be accessible from outside due to ajax call to the page postmark.php.
Or you can change the path of this call in main.php and copy this page to a public folder.

### Install manually ###
Download the plugin zip file from <https://github.com/jpbayard/phplist-plugin-postmark/archive/master.zip>

Expand the zip file, then copy the contents of the plugins directory to your phplist plugins directory.
This should contain

* the file PostMarkPlugin.php
* the directory PostMarkPlugin

###Settings###
In the settings, setup you private key for Postmark API.
Default timeout for the request is 30 seconds.

##Usage##
For guidance on usage, go to the postmark statistics page

##Support##

Questions and problems can be reported in the phplist user forum topic.

## Donation ##
This plugin is free but if you install and find it useful then a donation to support further development is greatly appreciated.

## Version history ##

    version     Description
    2015-03-10  Release to GitHub
	2015-08-31  Beter management of ipv6 ip ( separation in the report like in the mails ) & results now give the exacts same % as postmark mails.
