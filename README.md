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
### API

```php
const CIRCLES_PERSONAL = 1;
const CIRCLES_HIDDEN = 2;
const CIRCLES_PRIVATE = 4;
const CIRCLES_PUBLIC = 8;
```
***

How to include the Circles.js in your templates:
>      <?php script('circles', 'circles'); ?>

and a list of the calls to the API from the Javascript with few examples:

**Create a Circle**
>     OCA.Circles.api.createCircle(name, type, callback);
```javascript
OCA.Circles.api.createCircle('test-public', 8, creationDone);
function creationDone(result)
{
	console.log('status: ' + JSON.stringify(result));
}     
```

### Credits

App Icon by [http://www.flaticon.com/authors/madebyoliver](Madebyoliver) under Creative Commons BY 3.0


***
![example screenshot](example.png)

