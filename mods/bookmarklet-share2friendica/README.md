# Bookmarklet-share2friendica

JavaScript bookmarklet to share websites with your friendica account

## Getting Started

### Installing

Open the file bookmarklet-share2friendica.js and change 'YourFriendicaDomain.tld" with your friendica domain

If you friendica is at https://myfriend.myfami.ly/ , the original ...
```javascript
javascript:(function(){f='https://YourFriendicaDomain.tld/bookmarklet/?url='+encodeURIC....
```
... has to be changed to ...

```javascript
javascript:(function(){f='https://myfriend.myfami.ly/bookmarklet/?url='+encodeURIC....
```

*Please copy the whole script, not only the part mentioned here!*

Then create a new bookmark, give it a name like "share2Friendica" and paste the script in the address field. Save it. Now you can click on that bookmarklet every time you want to share a website, you are currently reading. A new small window will open where title is prefilled and the website you want to share is put as attachment in the body of the new post.

## Additional notes if it doesn't work

* Make sure the site you want to share is allowed to run javascript. (enable it in your script blocker)
* Check the apostrophes that are used. Sometimes it is changed by the copy and paste process depending on the editor you are using, or if you copy it from a website. Correct it and it will work again.



## Authors

* **diaspora** - *Initial work* - [Share all teh internetz!](https://share.diasporafoundation.org/about.html)
* **hoergen** - *Adaptation to Friendica (2017)* - [hoergen.org](https://hoergen.org)

## License

This project is licensed under the same license like friendica

## Acknowledgments

* Hat tip to anyone who's code was used
* Hat tip to everyone who does everyday a little something ot make this world better
* Had tip but spent it


