Friendica BBCode tags reference
========================

* [Home](help)

Inline
-----


<pre>[b]bold[/b]</pre> : <strong>bold</strong>

<pre>[i]italic[/i]</pre> : <em>italic</em>

<pre>[u]underlined[/u]</pre> : <u>underlined</u>

<pre>[s]strike[/s]</pre> : <strike>strike</strike>

<pre>[color=red]red[/color]</pre> : <span style="color:  red;">red</span>

<pre>[url=http://www.friendica.com]Friendica[/url]</pre> : <a href="http://www.friendica.com" target="external-link">Friendica</a>

<pre>[img]http://friendica.com/sites/default/files/friendika-32.png[/img]</pre> : <img src="http://friendica.com/sites/default/files/friendika-32.png" alt="Immagine/foto">

<pre>[size=xx-small]small text[/size]</pre> : <span style="font-size: xx-small;">small text</span>

<pre>[size=xx-large]big text[/size]</pre> : <span style="font-size: xx-large;">big text</span>

<pre>[size=20]exact size[/size] (size can be any number, in pixel)</pre> :  <span style="font-size: 20px;">exact size</span>







Block
-----

<pre>[code]code[/code]</pre>

<code>code</code>

<p style="clear:both;">&nbsp;</p>

<pre>[quote]quote[/quote]</pre>

<blockquote>quote</blockquote>

<p style="clear:both;">&nbsp;</p>

<pre>[quote=Author]Author? Me? No, no, no...[/quote]</pre>

<strong class="author">Author wrote:</strong><blockquote>Author? Me? No, no, no...</blockquote>

<p style="clear:both;">&nbsp;</p>

<pre>[center]centered text[/center]</pre>

<div style="text-align:center;">centered text</div>

<p style="clear:both;">&nbsp;</p>

<pre>You should not read any further if you want to be surprised.[spoiler]There is a happy end.[/spoiler]</pre>

You should not read any further if you want to be surprised.<br />*click to open/close*

(The text between thhe opening and the closing of the spoiler tag will be visible once the link is clicked. So *"There is a happy end."* wont be visible until the spoiler is uncovered.)

<p style="clear:both;">&nbsp;</p>

**Table**
<pre>[table border=1]
 [tr] 
   [th]Tables now[/th]
 [/tr]
 [tr]
   [td]Have headers[/td]
 [/tr]
[/table]</pre>

<table border="1"><tbody><tr><th>Tables now</th></tr><tr><td>Have headers</td></tr></tbody></table>

<p style="clear:both;">&nbsp;</p>

**List**

<pre>[list]
 [*] First list element
 [*] Second list element
[/list]</pre>
<ul class="listbullet" style="list-style-type: circle;">
<li> First list element<br>
</li>
<li> Second list element</li>
</ul>

[list] is equivalent to [ul] (unordered list). 

[ol] can be used instead of [list] to show an ordered list:

<pre>[ol]
 [*] First list element
 [*] Second list element
[/ol]</pre>
<ul class="listdecimal" style="list-style-type: decimal;"><li> First list element<br></li><li> Second list element</li></ul>

For more options on ordered lists, you can define the style of numeration on [list] argument:
<pre>[list=1]</pre> : decimal

<pre>[list=i]</pre> : lover case roman

<pre>[list=I]</pre> : upper case roman

<pre>[list=a]</pre> : lover case alphabetic

<pre>[list=A] </pre> : upper case alphabetic




Embed
------

You can embed video, audio and more in a message.

<pre>[video]url[/video]</pre>
<pre>[audio]url[/audio]</pre>

Where *url* can be an url to youtube, vimeo, soundcloud, or other sites wich supports oembed or opengraph specifications.
*url* can be also full url to an ogg  file. HTML5 tag will be used to show it.

<pre>[url]*url*[/url]</pre>

If *url* supports oembed or opengraph specifications the embedded object will be shown (eg, documents from scribd).
Page title with a link to *url* will be shown.

Map
---

<pre>[map]address[/map]</pre>
<pre>[map=lat,long]</pre>

You can embed maps from coordinates or addresses. 
This require "openstreetmap" addon version 1.3 or newer.

-----------------------------------------------------------

Abstract for longer posts
-------------------------

If you want to spread your post to several third party networks you can have the problem that these networks have (for example) a length limitation. (Like on Twitter)

Friendica is using a semi intelligent mechanism to generate a fitting abstract. But it can be interesting to define an own abstract that will only be displayed on the external network. This is done with the [abstract]-element. Example:

<pre>[abstract]Totally interesting! A must-see! Please click the link![/abstract]
I want to tell you a really boring story that you really never wanted 
to hear.</pre>

Twitter would display the text "Totally interesting! A must-see! Please click the link!". On Friendica you would only see the text after "I want to tell you a really ..."

It is even possible to define abstracts for separate networks:

<pre>
[abstract]Hi friends Here are my newest pictures![abstract]
[abstract=twit]Hi my dear Twitter followers. Do you want to see my new 
pictures?[abstract]
[abstract=apdn]Helly my dear followers on ADN. I made sone new pictures 
that I wanted to share with you.[abstract]
Today I was in the woods and took some real cool pictures ...
</pre>

For Twitter and App.net the system will use the defined abstracts. For other networks (e.g. when you are using the "statusnet" connector that is used to post to GNU Social) the general abstract element will be used.

If you use (for example) the "buffer" connector to post to Facebook or Google+ you can use this element to define an abstract for a longer blogpost that you don't want to post completely to these networks.

Networks like Facebook or Google+ aren't length limited. For this reason the [abstract] element isn't used. Instead you have to name the explicit network:

<pre>
[abstract]These days I had a strange encounter ...[abstract]
[abstract=goog]Helly my dear Google+ followers. You have to read my 
newest blog post![abstract]
[abstract=face]Hello my Facebook friends. These days happened something 
really cool.[abstract]
While taking pictures in the woods I had a really strange encounter ... </pre>

The [abstract] element isn't working with the native OStatus connection or with connectors where we post the HTML. (Like Tumblr, Wordpress or Pump.io)

Special
-------

If you need to put literal bbcode in a message, [noparse], [nobb] or [pre] are used to escape bbcode:

<pre>[noparse][b]bold[/b][/noparse]</pre> : [b]bold[/b]
