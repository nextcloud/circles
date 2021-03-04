# Nextcloud Circles

**Bring cloud-users closer together.**

![](https://raw.githubusercontent.com/nextcloud/circles/master/screenshots/0.12.0.png)

Circles allows your users to create their own groups of users/colleagues/friends. 
Those groups of users (or circles) can then be used by any other app for sharing purpose 
(files, social feed, status update, messaging, …) through the Circles API

***
# API (PHP & Javascript)

[Please visit our wiki to read more about the API.](https://github.com/nextcloud/circles/wiki)

# Configuration

## Allow usage of Circles in non-SSL environments

In non-SSL environments (like on development setups) it is necessary to set two config flags for Circles:

`./occ config:app:set circles --value 1 allow_non_ssl_links` 

`./occ config:app:set circles --value 1 local_is_non_ssl`

# Credits

App Icon by [Madebyoliver](http://www.flaticon.com/authors/madebyoliver) under Creative Commons BY 3.0
