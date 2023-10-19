# hcpp-wp-global
A plugin for Hestia Control Panel (via [hestiacp-pluginable](https://github.com/virtuosoft-dev/hestiacp-pluginable)) that installs WordPress "global" plugins; these are available to all WordPress installations under the given HestiaCP user. The folder located at /home/&lt;username&gt;/web/wp-global can contain *compatible plugins that are available to all WordPress installations under the given user; regardless of domain. Such plugins were popular under (now discontinued) ServerPress' DesktopServer. Mostly for design-time use, these plugins allow WordPress developers to drop in various plugins to aid in development or common site functionality. Such plugins are included when this HestiaCP Pluginable plugin is installed, they inclue:

* [Admin Color Bar](https://github.com/ServerPress/admin-color-bar) - Prevent accidents! Changes the WordPress admin bar to another color and adds a message, i.e. 'DEVELOPMENT WEBSITE' to make it clear you are working on a development site (versus a live website).
* [AirPlane Mode](https://github.com/norcross/airplane-mode) - Disables external data calls and loading for working on a purely local (i.e. no internet connection) WordPress site.
* [Login Bypass](https://github.com/virtuosoft-dev/login-bypass) - A hard fork of ServerPress' original Bypass Login plugin and designed to work only on [CodeGarden](https://code.gdn/pws) development sites. Allows you to login to WordPress as another user or admin without a password or changing your client's user table.

&nbsp;
## Installation
HCPP-WP-Global requires an Ubuntu or Debian based installation of [Hestia Control Panel](https://hestiacp.com) in addition to an installation of [HestiaCP-Pluginable](https://github.com/virtuosoft-dev/hestiacp-pluginable) to function; please ensure that you have first installed pluginable on your Hestia Control Panel before proceeding. Switch to a root user and simply clone this project to the /usr/local/hestia/plugins folder. It should appear as a subfolder with the name `wp-global`, i.e. `/usr/local/hestia/plugins/wp-global`.

First, switch to root user:
```
sudo -s
```

Then simply clone the repo to your plugins folder, with the name `wp-global`:

```
cd /usr/local/hestia/plugins
git clone https://github.com/virtuosoft-dev/hcpp-wp-global wp-global
```

Note: It is important that the destination plugin folder name is `wp-global`.

Be sure to logout and login again to your Hestia Control Panel as the admin user or, as admin, visit Server (gear icon) -> Configure -> Plugins -> Save; the plugin will immediately start installing the Mosquitto server and depedencies in the background. 

<!--<br><img src='images/wp-global.jpg' width='50%'><br>
<sub>Figure 1 - WP Global plugin install notification</sub>-->

A notification will appear under the admin user account indicating *"WP Global plugin has finished installing"* when complete. This may take awhile before the options appear in Hestia. You can force manual installation via:

```
cd /usr/local/hestia/plugins/wp-global
./install
touch "/usr/local/hestia/data/hcpp/installed/wp-global"
```

&nbsp;
## Using WP Global
Simply locate the wp-global folder in your userfolder. In [CodeGarden PWS](https://code.gdn/pws), simply use the `Files` option to open your website files; the wp-global folder is a top-level folder adjacent to all your website domains (i.e. example.dev.cc, etc). Place *compatible plugins in this folder to have them automatically load in all your adjacent WordPress website domains. To disable a plugin without deletion; simply rename the given plugin folder with a `.disabled` extension. For example, the [AirPlane Mode](https://github.com/norcross/airplane-mode) plugin that is pre-installed with HCPP-WP-Global is disabled by default (named airplane-mode.disabled). 

**Note:** *Compatibility; not all WordPress plugins are compatible with the wp-global folder. Plugin auto-updates are not supported. 

## Support the creator
You can help this author's open source development endeavors by donating any amount to Stephen J. Carnam @ Virtuosoft. Your donation, no matter how large or small helps pay for essential time and resources to create MIT and GPL licensed projects that you and the world can benefit from. Click the link below to donate today :)
<div>
         

[<kbd> <br> Donate to this Project <br> </kbd>][KBD]


</div>


<!---------------------------------------------------------------------------->

[KBD]: https://virtuosoft.com/donate

https://virtuosoft.com/donate
