# Circles

Circles allows your users to create their own groups of users/colleagues/friends. 
Those groups of users (or circles) can then be used by any other app for sharing purpose 
(files, social feed, status update, messaging, ...) 

Differents type of circles can be created:


- A Personal Circle is a list of users known only to yourself. 
Use this if you want to send messsage or share thing repeatedly to the same group of people.
- Hidden Circle is an open group that can be protected by a password. 
Select this circle to create a community not displayed as a Public Circle.
- A Private Circle require an invitation or a confirmation from an admin.
This is the best circle if you are looking for privacy when sharing your files or your ideas.
- A Public Circle is an open group visible to anyone that dare to join. 
Your circle will be visible to everyone and everyone will be able to join the circle.



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


***
![example screenshot](example.png)

