Referenz der Friendica BBCode Tags
========================

* [Zur Startseite der Hilfe](help)

Inline Tags
-----


<pre>[b]fett[/b]</pre> : <strong>fett</strong>

<pre>[i]kursiv[/i]</pre> : <em>kursiv</em>

<pre>[u]unterstrichen[/u]</pre> : <u>unterstrichen</u>

<pre>[s]durchgestrichen[/s]</pre> : <strike>durchgestrichen</strike>

<pre>[color=red]rot[/color]</pre> : <span style="color:  red;">rot</span>

<pre>[url=http://www.friendica.com]Friendica[/url]</pre> : <a href="http://www.friendica.com" target="external-link">Friendica</a>

<pre>[img]http://friendica.com/sites/default/files/friendika-32.png[/img]</pre> : <img src="http://friendica.com/sites/default/files/friendika-32.png" alt="Immagine/foto">

<pre>[size=xx-small]kleiner Text[/size]</pre> : <span style="font-size: xx-small;">kleiner Text</span>

<pre>[size=xx-large]gro&szlig; Text[/size]</pre> : <span style="font-size: xx-large;">gro&szlig;er Text</span>

<pre>[size=20]exakte Textgr&ouml;&szlig;e[/size] (Textgr&ouml;&szlig;e kann jede Zahl sein, in Pixeln)</pre> :  <span style="font-size: 20px;">exakte Gr&ouml;&szlig;e</span>







Block Tags
-----

<pre>[code]Code[/code]</pre>

<code>Code</code>

<p style="clear:both;">&nbsp;</p>

<pre>[quote]Zitat[/quote]</pre>

<blockquote>Zitat</blockquote>

<p style="clear:both;">&nbsp;</p>

<pre>[quote=Autor]Der Autor? Ich? Nein, nein, nein...[/quote]</pre>

<strong class="author">Autor hat geschrieben:</strong><blockquote>Der Autor?  Ich? Nein, nein, nein...</blockquote>

<p style="clear:both;">&nbsp;</p>

<pre>[center]zentrierter Text[/center]</pre>

<div style="text-align:center;">zentrierter Text</div>

<p style="clear:both;">&nbsp;</p>

<pre>Wer überrascht werden möchte sollte nicht weiter lesen.[spoiler]Es gibt ein Happy End.[/spoiler]</pre>

Wer überrascht werden möchte sollte nicht weiter lesen.<br />*klicken zum öffnen/schließen*

(Der Text zweischen dem öffnenden und dem schließenden Teil des spoiler Tags wird nicht angezeigt, bis der Link angeklickt wurde. In dem Fall wird *"Es gibt ein Happy End."* also erst angezeigt, wenn der Spoiler verraten wird.)

<p style="clear:both;">&nbsp;</p>

**Tabelle**
<pre>[table border=1]
 [tr] 
   [th]Tabellenzeile[/th]
 [/tr]
 [tr]
   [td]haben &Uuml;berschriften[/td]
 [/tr]
[/table]</pre>

<table border="1"><tbody><tr><th>Tabellenzeile</th></tr><tr><td>haben &Uuml;berschriften</td></tr></tbody></table>

<p style="clear:both;">&nbsp;</p>

**Listen**

<pre>[list]
 [*] Erstes Listenelement
 [*] Zweites Listenelement
[/list]</pre>
<ul class="listbullet" style="list-style-type: circle;">
<li> Erstes Listenelement<br>
</li>
<li> Zweites Listenelement</li>
</ul>

[list] ist Equivalent zu [ul] (unsortierte Liste). 

[ol] kann anstelle von [list] verwendet werden um eine sortierte Liste zu erzeugen:

<pre>[ol]
 [*] Erstes Listenelement
 [*] Zweites Listenelement
[/ol]</pre>
<ul class="listdecimal" style="list-style-type: decimal;"><li>Erstes Listenelement<br></li><li> Zweites Listenelement</li></ul>

F&uuml;r weitere Optionen von sortierten Listen kann man den Stil der Numerierung der Liste definieren:
<pre>[list=1]</pre> : dezimal

<pre>[list=i]</pre> : r&ouml;misch, Kleinbuchstaben

<pre>[list=I]</pre> : r&ouml;misch, Gro&szlig;buchstaben

<pre>[list=a]</pre> : alphabetisch, Kleinbuchstaben

<pre>[list=A] </pre> : alphabethisch, Gro&szlig;buchstaben




Einbettung von Inhalten
------

Man kann viele Dinge, z.B. Video und Audio Dateine, in Nachrichten einbetten.

<pre>[video]url[/video]</pre>
<pre>[audio]url[/audio]</pre>

Wobei die *url* von youtube, vimeo, soundcloud oder einer anderen Seite stammen kann die die oembed oder opengraph Spezifikationen unterst&uuml;tzt.
Au&szlig;erdem kann *url* die genaue url zu einer ogg Datei sein, die dann per HTML5 eingebunden wird.

<pre>[url]*url*[/url]</pre>

Wenn *url* entweder oembed oder opengraph unterstützt wird das eingebettete Objekt (z.B. ein Dokument von scribd) eingebunden.
Der Titel der Seite mit einem Link zur *url* wird ebenfalls angezeigt.

Um eine Karte in einen Beitrag einzubinden, muss das *openstreetmap* Addon aktiviert werden. Ist dies der Fall, kann mit

<pre>[map]Broadway 26, New York[/map]</pre>

eine Karte von [OpenStreetmap](http://openstreetmap.org) eingebettet werden. Zur Identifikation des Ortes können entweder seine Koordinaten in der Form

<pre>[map=lat,long]</pre>

oder eine Adresse in obiger Form verwendet werden.

Zusammenfassung für längere Beiträge
------------------------------------

Wenn man seine Beiträge über mehrere Netzwerke verbreiten möchte, hat man häufig das Problem, dass diese Netzwerke z.B. eine Längenbeschränkung haben. 
(Z.B. Twitter).

Friendica benutzt zum Erzeugen eines Anreißtextes eine halbwegs intelligente Logik. 
Es kann aber dennoch von Interesse sein, eine eigene Zusammenfassung zu erstellen, die nur auf dem Fremdnetzwerk dargestellt wird. 
Dies geschieht mit dem [abstract]-Element. 
Beispiel:

<pre>[abstract]Total spannend! Unbedingt diesen Link anklicken![/abstract]
Hier erzähle ich euch eine total langweilige Geschichte, die ihr noch 
nie hören wolltet.</pre>

Auf Twitter würde das "Total spannend! Unbedingt diesen Link anklicken!" stehen, auf Friendica würde nur der Text nach "Hier erzähle ..." erscheinen.

Es ist sogar möglich, für einzelne Netzwerke eigene Zusammenfassungen zu erstellen:

<pre>
[abstract]Hallo Leute, hier meine neuesten Bilder![abstract]
[abstract=twit]Hallo Twitter-User, hier meine neuesten Bilder![abstract]
[abstract=apdn]Hallo App.net-User, hier meine neuesten Bilder![abstract]
Ich war heute wieder im Wald unterwegs und habe tolle Bilder geschossen ...
</pre>

Für Twitter und App.net nimmt das System die entsprechenden Texte. 
Bei anderen Netzwerken, bei denen der Inhalt gekürzt wird (z.B. beim "statusnet"-Connector, der für das Posten nach GNU Social verwendet wird) wird dann die Zusammenfassung unter [abstract] verwendet.

Wenn man z.B. den "buffer"-Connector verwendet, um nach Facebook oder Google+ zu posten, kann man dieses Element ebenfalls verwenden, wenn man z.B. einen längeren Blogbeitrag erstellt hat, aber ihn nicht komplett in diese Netzwerke posten möchte.

Netzwerke wie Facebook oder Google+ sind nicht in der Postinglänge beschränkt. 
Aus diesem Grund greift nicht die [abstract]-Zusammenfassung. Stattdessen muss man das Netzwerk explizit angeben:

<pre>
[abstract]Ich habe neulich wieder etwas erlebt, was ich euch mitteilen möchte.[abstract]
[abstract=goog]Hallo meine Google+-Kreislinge. Ich habe neulich wieder 
etwas erlebt, was ich euch mitteilen möchte.[abstract]
[abstract=face]Hallo Facebook-Freunde! Ich habe neulich wieder etwas 
erlebt, was ich euch mitteilen möchte.[abstract]
Beim Bildermachen im Wald habe ich neulich eine interessante Person 
getroffen ... </pre>

Das [abstract]-Element greift nicht bei der nativen OStatus-Verbindung oder bei Connectoren, die den HTML-Text posten wie z.B. die Connectoren zu Tumblr, Wordpress oder Pump.io.

Spezielle Tags
-------

Wenn Du &uuml;ber BBCode Tags in einer Nachricht schreiben m&ouml;chtest, kannst Du [noparse], [nobb] oder [pre] verwenden um den BBCode Tags vor der Evaluierung zu sch&uuml;tzen:

<pre>[noparse][b]fett[/b][/noparse]</pre> : [b]fett[/b]
