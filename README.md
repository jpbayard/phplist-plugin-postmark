# Postmark Plugin #

## Description ##

The plugin allows you get readable DMARC reports through the free Postmark API.
The plugin adds a menu item under "Statistics" where you can generate some reports.

## Installation ##

Just

### Dependencies ###

Requires php version 5.3 or later. 

Requires the Common Plugin to be installed. See <https://github.com/jpbayard/phplist-plugin-postmark>

### Set the plugin directory ###
The default plugin directory is `plugins` within the admin directory.

You can use a directory outside of the web root by changing the definition of `PLUGIN_ROOTDIR` in config.php.
The benefit of this is that plugins will not be affected when you upgrade phplist.

### Install through phplist ###
Install on the Plugins page (menu Config > Plugins) using the package URL 

* the file PostMarkPlugin.php
* the directory PostMarkPlugin

### Install manually ###
Download the plugin zip file from <https://github.com/jpbayard/phplist-plugin-postmark/archive/master.zip>

Expand the zip file, then copy the contents of the plugins directory to your phplist plugins directory.
This should contain

* the file PostMarkPlugin.php
* the directory PostMarkPlugin

###Settings###
In the settings, setup you private key for Postmark API

##Usage##

For guidance on usage just go to the postmark statistics page

##Support##

Questions and problems can be reported in the phplist user forum topic .

## Donation ##
This plugin is free but if you install and find it useful then a donation to support further development is greatly appreciated.

## Version history ##

    version     Description
    2015-03-09  Release to GitHub
