# Nextcloud Circles

[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/nextcloud/circles/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/nextcloud/circles/?branch=master)
[![Build Status](https://drone.nextcloud.com/api/badges/nextcloud/circles/status.svg)](https://drone.nextcloud.com/nextcloud/circles)

**Bring cloud-users closer together.**

![](https://raw.githubusercontent.com/nextcloud/circles/master/screenshots/1.png)

Circles allows your users to create their own groups of users/colleagues/friends. 
Those groups of users (or circles) can then be used by any other app for sharing purpose 
(files, social feed, status update, messaging, ...) through the Circles API

Differents type of circles can be created:


- A **Personal Circle** is a list of users known only to yourself. 
Use this if you want to send messsages or share things repeatedly to the same group of people. 
Only you will know the members list of a personal circle.
- A **Public Circle** is an open group visible to anyone in the cloud, and everyone will be able to join it. 
- An **Hidden Circle** is an open group that can be protected by a password. 
Select this circle to create a public community that will not be displayed to everyone like the Public Circle.
- A **Private Circle** require an invitation or a confirmation from an admin, This way you can create a team or a group of people.
his is the best circle if you are looking for privacy when sharing your files or else.

***
# Compatibility

This app is **not** compatible with the basic version of nextcloud. 

***
# API

```php
CIRCLES_PERSONAL is 1 or 'personal';
CIRCLES_HIDDEN is 2 or 'hidden';
CIRCLES_PRIVATE is 4 or 'private';
CIRCLES_PUBLIC is 8 or 'public';
```
***



### Javascript - list of API calls:

How to include the Circles.js in your templates:
>      <?php script('circles', 'circles'); ?>



**Create a Circle**
>     OCA.Circles.api.createCircle(type, name, callback);
```javascript
OCA.Circles.api.createCircle('public', 'test-public', creationDone);
function creationDone(result)
{
	console.log('status: ' + JSON.stringify(result));
}     
```


**Listing Circles**
>     OCA.Circles.api.listCircle(type, callback);
```javascript
OCA.Circles.api.listCircles('all', listingDone);
function listingDone(result)
{
	console.log('status: ' + JSON.stringify(result));
}     
```



**Searching Circles**
>     OCA.Circles.api.listCircle(type, callback);
```javascript
OCA.Circles.api.searchCircles('all', 'test', listingDone);
function listingDone(result)
{
	console.log('status: ' + JSON.stringify(result));
}     
```



**Details of a Circle**
>     OCA.Circles.api.detailsCircle(circle_id, callback);
```javascript
OCA.Circles.api.detailsCircle(42, detailsCircleResult);
function detailsCircleResult(result)
{
	console.log('status: ' + JSON.stringify(result));
}     
```





### PHP - list of API calls

**Create a Circle**
>     $result = OCA\Circles\Api\Circles::createCircle($type, $name);



**Listing Circles/Searching Circles**
>     $result = OCA\Circles\Api\Circles::listCircles($type, [$name]);



**Details of a Circle**
>     $result = OCA\Circles\Api\Circles::detailsCircle($circle_id);


# Credits

App Icon by [http://www.flaticon.com/authors/madebyoliver](Madebyoliver) under Creative Commons BY 3.0
