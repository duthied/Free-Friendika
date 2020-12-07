Häufig gestellte Fragen - FAQ
==============

* [Zur Startseite der Hilfe](help)

Nutzer

* **[Warum erhalte ich Warnungen über fehlende Zertifikate?](help/FAQ#ssl)**
* **[Wie kann ich Bilder, Dateien, Links, Video und Audio in Beiträge einfügen?](help/FAQ#upload)**
* **[Ist es möglich, bei mehreren Profilen verschiedene Avatare (Nutzerbilder) zu haben?](help/FAQ#avatars)**
* **[Wie kann ich Friendica in einer bestimmten Sprache ansehen?](help/FAQ#language)**
* **[Was ist der Unterschied zwischen blockierten|ignorierten|archivierten|versteckten Kontakten?](help/FAQ#contacts)**
* **[Was passiert, wenn ein Account gelöscht ist? Ist dieser richtig gelöscht?](help/FAQ#removed)**
* **[Kann ich einem Hashtag folgen?](help/FAQ#hashtag)**
* **[Wie kann ich einen RSS-Feed meiner Netzwerkseite (Stream) erstellen?](help/FAQ#rss)**
* **[Gibt es Clients für Friendica?](help/FAQ#clients)**
* **[Wo finde ich Hilfe?](help/FAQ#help)**

Admins

* **[Kann ich mehrere Domains mit den selben Dateien aufsetzen?](help/FAQ#multiple)**
* **[Wo kann ich den Quellcode von Friendica, Addons und Themes finden?](help/FAQ#sources)**
* **[Ich habe meine E-Mail Adresse geändern und jetzt ist das Admin Panel verschwunden?](help/FAQ#adminaccount1)**
* **[Kann es mehr als einen Admin auf einer Friendica Instanz geben?](help/FAQ#adminaccount2)**
* **[Die Datenbank Struktur schein nicht aktuell zu sein. Was kann ich tun?](help/FAQ#dbupdate)**

Nutzer
--------

<a name="ssl"></a>
### Warum erhalte ich Warnungen über fehlende Zertifikate?

Manchmal erhältst Du eine Browser-Warnung über fehlende Zertifikate.
Diese Warnungen können drei Gründe haben:

1. der Server, mit dem Du verbunden bist, nutzt kein SSL;

2. der Server hat ein selbst-signiertes Zertifikat (nicht empfohlen)

3. das Zertifikat ist nicht mehr gültig.

*(SSL (Secure Socket Layer) ist eine Technologie, die Daten auf ihrem Weg zwischen zwei Computern verschlüsselt.)*

Wenn Du noch kein SSL-Zertifikat hast, dann gibt es drei Wege, eines zu erhalten: kauf Dir eines, hole Dir ein kostenloses (z.B. bei StartSSL, WoSign, hoffentlich bald auch letsencrypt) oder kreiere Dein eigenes (nicht empfohlen).
[Weitere Informationen über die Einrichtung von SSL und warum es schlecht ist, selbst-signierte Zertifikate zu nutzen, findest Du hier.](help/SSL)

Sei Dir bewusst, dass Browser-Warnungen über Sicherheitslücken etwas sind, wodurch neue Nutzer schnell das Vertrauen in das gesamte Friendica-Projekt verlieren können.
Aus diesem Grund wird Friendica Red nur SSL-Zertifikate eines anerkannten Anbieters (CA, certificate authority) akzeptieren und nicht zu Seiten verbinden, die kein SSL nutzen.
Unabhängig von den negativen Aspekten von SSL handelt es sich hierbei um eine notwendige Lösung, solange keine etablierte Alternative existiert.

Abgesehen davon kann es ohne SSL auch Probleme mit der Verbindung zu Diaspora geben, da einige Diaspora-Pods eine zertifizierte Verbindung benötigen.

Wenn Du Friendica nur für eine bestimmte Gruppe von Leuten auf einem einzelnen Server nutzt, bei dem keine Verbindung zum restlichen Netzwerk besteht, dann benötigst Du kein SSL.
Ebenso benötigst Du SSL nicht, wenn Du ausschließlich öffentliche Beiträge auf Deiner Seite veröffentlichst bzw. empfängst.

Wenn Du zum jetzigen Zeitpunkt noch keinen Server aufgesetzt hast, ist es sinnvoll, die verschiedenen Anbieter in Bezug auf SSL zu vergleichen.
Einige erlauben die Nutzung von freien Zertifikaten oder lassen Dich ihre eigenen Zertifikate mitnutzen.
Andere erlauben nur kostenpflichtige Zertifikate als eigenes Angebot bzw. von anderen Anbietern.

<a name="upload"></a>
### Wie kann ich Bilder, Dateien, Links, Video und Audio in Beiträge einfügen?

Bilder können direkt im [Beitragseditor](help/Text_editor) vom Computer hochgeladen werden.
Eine Übersicht aller Bilder, die auf Deinem Server liegen, findest Du unter <i>deineSeite.de/photos/profilname</i>.
Dort kannst Du auch direkt Bilder hochladen und festlegen, ob Deine Kontakte eine Nachricht über das neue Bild bekommen.

Alle Arten von Dateien können grundsätzlich als Anhang in Friendica hochgeladen werden.
Dafür verwendest Du das Büroklammersymbol im Editor.
Sie sind dann direkt an den Beitrag geknüpft, können von den Betrachtern heruntergeladen werden, aber werden nicht als Vorschau angezeigt.
Deshalb eignet sich diese Methode vor allem für Office-Dateien oder gepackte Dateien wie ZIPs, aber weniger für Multimediadateien.
Wer hingegen Dateien über Dropbox, über eine auf dem eigenen Server installierte Owncloud oder über einen anderen [Filehoster](http://en.wikipedia.org/wiki/Comparison_of_file_hosting_services) einfügen will, verwendet den Link-Button.

Wenn Du mit dem Link-Button (Ketten-Symbol) URLs zu anderen Seiten einfügst, versucht Friendica eine kurze Zusammenfassung als Vorschau abzurufen.
Manchmal klappts das nicht ... dann verlinke den Beitrag einfach per [url=http://example.com]<i>freigewählter Name</i>[/url] im Editor.

Video- und Audiodateien können zwar in Beiträge eingebunden werden, allerdings geht das nicht über einen direkten Upload im Editor wie bei Fotos.
Du hast zwei Möglichkeiten:

1. Du kannst bei dem Video- oder Audiobutton die URL von einem Hoster eingeben (Youtube, Vimeo, Soundcloud und alle anderen mit oembed/opengraph-Unterstützung). Bei Videos zeigt Friendica dann ein Vorschaubild in Deinem Beitrag an, nach einem Klick öffnet sich ein eingebetter Player. Bei Soundcloud wird der Player direkt eingebunden.
2. Wenn Du Zugang zu einem eigenen Server hast, kannst Deine Multimediadatei per FTP dort hochladen und beim Video-/Audiobutton diese URL angeben. Dann wird das Video oder die Audiodatei direkt mit einem Player in Deinem Beitrag angezeigt.
Friendica verwendet zur Einbettung HTML5. Das bedeutet, dass je nach Browser und Betriebssystem andere Formate unterstützt werden, darunter WebM, MP4, MP3 und Ogg. Eine Tabelle findest Du bei Wikipedia ([Video](http://en.wikipedia.org/wiki/HTML5_video), [Audio](http://en.wikipedia.org/wiki/HTML5_audio)).

Zum Konvertieren von Videos in das lizenfreie Videoformat WebM gibt es unter Windows das kostenlose Programm [Xmedia-Recode](http://www.xmedia-recode.de/).

<a name="avatars"></a>
### Ist es möglich, bei mehreren Profilen verschiedene Avatare (Nutzerbilder) zu haben?

Ja.
Auf Deiner ["Profile verwalten/editieren"-Seite](../profiles) wählst Du zunächst das gewünschte Profil aus.
Anschließend siehst Du eine Seite mit allen Infos zu diesem Profil.
Klicke nun oben auf den Link "Profilbild ändern" und lade im nächsten Fenster ein Bild von Deinem PC hoch.
Um Deine privaten Daten zu schützen, wird in Beiträgen nur das Bild aus Deinem öffentlichen Profil angezeigt.

<a name="language"></a>
### Wie kann ich Friendica in einer bestimmten Sprache ansehen?

Die Sprache des Friendica Interfaces kann durch den `lang` Parameter un der URL beeinflusst werden.
Das Argument des Parameters ist ein  [ISO 639-1](https://en.wikipedia.org/wiki/List_of_ISO_639-1_codes)  Code.
Zwischen der URL und dem Parameter muss ein Fragezeichen als Trennzeichen verwendet werden.

Ein Beispiel:

     https://social.example.com/profile/example 

auf Deutsch:

     https://social.example.com/profile/example?lang=de.

<a name="contacts"></a>
### Was ist der Unterschied zwischen blockierten|ignorierten|archivierten|versteckten Kontakten?

Wir verhindern direkte Kommunikation mit blockierten Kontakten.
Sie gehören nicht zu den Empfängern beim Versand von Beiträgen und deren Beiträge werden auch nicht importiert.
Trotzdem werden deren Unterhaltungen mit Deinen Freunden in Deinem Stream sichtbar sein.
Wenn Du einen Kontakt komplett löschst, können sie Dir eine neue Freundschaftsanfrage schicken.
Blockierte Kontakte können das nicht machen.
Sie können nicht mit Dir direkt kommunizieren, nur über Freunde.

Ignorierte Kontakte können weiterhin Beiträge und private Nachrichten von Dir erhalten.
Deren Beiträge und private Nachrichten werden allerdings nicht importiert.
Wie bei blockierten Beiträgen siehst Du auch hier weiterhin die Kommentare dieser Person zu anderen Beiträgen Deiner Freunde.

[Ein Erweiterung namens "blockem" kann installiert werden, um alle Beiträge einer bestimmten Person in Deinem Stream zu verstecken bzw. zu verkürzen.
Dabei werden auch Kommentare dieser Person in Beiträgen Deiner Freunde blockiert.]

Ein archivierter Kontakt bedeutet, dass Kommunikation nicht möglich ist und auch nicht versucht wird (das ist z.B. sinnvoll, wenn eine Person zu einem neuen Server gewechselt ist und das alte Profil gelöscht hat).
Anders als beim Blockieren werden existierende Beiträge, die vor der Archivierung erstellt wurden, weiterhin angezeigt.

Ein versteckter Kontakt wird in keiner "Freundeliste" erscheinen (außer für dich).
Trotzdem wird ein versteckter Kontakt normal in Unterhaltungen angezeigt - was für andere Kontakte ein Hinweis sein kann, dass diese Person als versteckter Kontakt in Deiner Liste ist.

<a name="removed"></a>
### Was passiert, wenn ein Account gelöscht ist? Ist dieser richtig gelöscht?

Wenn Du Deinen Account löschst, wird sofort der gesamte Inhalt auf Deinem Server gelöscht und ein Löschbefehl an alle Deine Kontakte verschickt.
Dadurch wirst Du ebenfalls aus dem globalen Verzeichnis gelöscht.
Dieses Vorgehen setzt voraus, dass Dein Profil für 24 Stunden weiterhin "teilweise" verfügbar sein wird, um eine Verbindung zu allen Deinen Kontakten ermöglicht.
Wir können also Dein Profil blockieren und es so erscheinen lassen, als wären alle Daten sofort gelöscht, allerdings warten wir 24 Stunden (bzw. bis alle Deine Kontakte informiert wurden), bevor wir die Daten auch physikalisch löschen.

<a name="hashtag"></a>
### Kann ich einem Hashtag folgen?

Ja.
Füge die Tags zu Deinen gespeicherten Suchen hinzu, sie werden automatisch auf der Netzwerk-Seite auftauchen.
Bitte beachte, dass Deine Antworten auf solche Posts aus technischen Gründen nicht unter dem "Persönlich"-Reiter auf der Netzwerk-Seite und der gesamte Thread nicht per API zu sehen sind.

<a name="rss"></a>
### Wie kann ich einen RSS-Feed meiner Netzwerkseite (Stream) erstellen?

Wenn Du die Beiträge Deines Accounts mit RSS teilen willst, dann kannst Du einen der folgenden Links nutzen:

#### RSS-Feed Deiner Beiträge

	deineSeite.de/feed/[profilname]/posts

Beispiel: Friendica Support

	https://forum.friendi.ca/feed/helpers/posts

#### RSS-Feed all deiner Beiträge und Antworten

    deineSeite.de/dfrn_poll/feed/[profilname]/comments

Beispiel: Friendica Support

    https://forum.friendi.ca/feeds/helpers/comments

#### RSS-Feed all deiner Aktivitäten

    deineSeite.de/feed/[profilname]/

<a name="clients">
### Gibt es Clients für Friendica?

Friendica verwendet eine [Twitter/GNU Social](help/api) kompatible API.
Das bedeutet, dass du jeden Twitter/GNU Social Client verwenden kannst in dem du den API Pfad entsprechend änderst.

Hier ist eine Liste von Clients bei denen dies möglich ist, bzw. die speziell für Friendica entwickelt werden:

* Android
  * [Friendiqa](https://git.friendi.ca/lubuwest/Friendiqa) (Gibt es im Google Playstore oder als [binary Repository](https://freunde.ma-nic.de/display/3e98eba8185a13c5bdbf3d1539646854) für F-Droid)
  * [Fedilab](https://gitlab.com/tom79/mastalab) (Gibt es im F-Droid und dem Google Play Store)
  * [DiCa](https://dica.mixi.cool/) (Gibt es bei Google Play)
  * AndStatus
  * Twidere
  * Mustard and Mustard-Mod
* SailfishOS
  * [Friendly](https://openrepos.net/content/fabrixxm/friendly#comment-form)
* Linux
  * Hotot
  * Choqok
* MacOS X
  * Hotot
* Windows
  * [Friendica Mobile](https://www.microsoft.com/de-DE/store/p/friendica-mobile/9nblggh0fhmn?rtc=1) für Windows 10
  * Hotot

<a name="help"></a>
### Wo finde ich Hilfe?

Wenn Du Probleme mit Deiner Friendica-Seite hast, dann kannst Du die Community in der [Friendica-Support-Gruppe](https://forum.friendi.ca/profile/helpers) fragen oder Dir das [deutsche Wiki](https://friendica-wiki.de/) anschauen.
Wenn Du Deinen Account nicht nutzen kannst, kannst Du entweder einen [Testaccount](https://tryfriendica.de) bzw. einen Account auf einer öffentlichen Seite ([Liste](https://dir.friendica.social/servers)) nutzen.

Wenn du dir keinen weiteren Friendica Account einrichten willst, kannst du auch gerne über einen der folgenden alternativen Kanäle Hilfe suchen:

  * [Friendica Support Forum](https://forum.friendi.ca/~helpers)
  * [Mailing List Archive](http://mailman.friendi.ca/mailman/listinfo/support-friendi.ca) you can subscribe to the list by sending an email to ``support-request(at)friendi.ca?subject=subscribe``
  * XMPP/Jabber MUC: support(at)forum.friendi.ca
  * IRC: #friendica at irc.freenode.net
  * Matrix: #friendi.ca or #friendica at matrix.org.

Admin
--------

<a name="multiple"></a>
### Kann ich mehrere Domains mit den selben Dateien aufsetzen?

Ja, das ist möglich.
Es ist allerdings nicht möglich, eine Datenbank durch zwei Domains zu nutzen.
Solange Du Deine config/local.config.php allerdings so einrichtest, dass das System nicht versucht, eine Installation durchzuführen, kannst Du die richtige Config-Datei in include/$hostname/config/local.config.php hinterlegen.
Alle Cache-Aspekte und der Zugriffsschutz können pro Instanz konfiguriert werden.

<a name="sources"></a>
### Wo kann ich den Quellcode von Friendica, Addons und Themes finden?

Du kannst den Friendica-Quellcode [hier](https://github.com/friendica/friendica) finden.
Dort findest Du immer die aktuellste stabile Version von Friendica.
Der Quellcode von Friendica Red ist [hier](https://github.com/friendica/red) zu finden.

Addons findest Du auf [dieser Seite](https://github.com/friendica/friendica-addons).

Wenn Du neue Themen suchst, findest Du sie auf [Friendica-Themes.com](http://friendica-themes.com/).

<a name="adminaccount1"></a>
### Ich habe meine E-Mail Adresse geändern und jetzt ist das Admin Panel verschwunden?

Bitte aktualisiere deine E-Mail Adresse in der <tt>config/local.config.php</tt> Datei.

<a name="adminaccount2"></a>
### Kann es mehr als einen Admin auf einer Friendica Instanz geben?

Ja.
Du kannst in der <tt>config/local.config.php</tt> Datei mehrere E-Mail Adressen auflisten.
Die aufgelisteten Adressen werden mit Kommata von einander getrennt.

<a name="dbupdate">
### Die Datenbank Struktur schein nicht aktuell zu sein. Was kann ich tun?

Rufe bitte im Admin Panel den Punkt [DB Updates](/admin/dbsync/) auf und folge dem Link *Datenbank Struktur überprüfen*.
Damit wird ein Hintergrundprozess gestartet der die Struktur deiner Datenbank überprüft und gegebenenfalls aktualisiert.

Du kannst das Struktur Updatee auch manuell auf der Kommandoeingabe ausführen.
Starte dazu bitte vom Grundverzeichnis deiner Friendica Instanz folgendes Kommand:

    bin/console dbstructure update

sollten bei der Ausführung Fehler auftreten, kontaktiere bitte das [Support Forum](https://forum.friendi.ca/profile/helpers).
