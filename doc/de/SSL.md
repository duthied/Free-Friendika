Friendica mit SSL nutzen
=====================================

* [Zur Startseite der Hilfe](help)

## Disclaimer

**Dieses Dokument wurde im November 2016 aktualisiert.
SSL-Verschlüsselung ist sicherheitskritisch.
Das bedeutet, dass sich die empfohlenen Einstellungen schnell verändern.
Halte deine Installation auf dem aktuellen Stand und verlasse dich nicht darauf, dass dieses Dokument genau so schnell aktualisiert wird, wie sich Technologien verändern!**

## Einleitung

Wenn du deine eigene Friendica-Seite betreibst, willst du vielleicht SSL (https) nutzen, um die Kommunikation zwischen den Servern und zwischen dir und deinem Server zu verschlüsseln.

Dafür gibt es grundsätzlich zwei Arten von SSL-Zertifikaten: Selbst-signierte Zertifikate und Zertifikate, die von einer Zertifizierungsstelle (CA) unterschrieben sind.
Technisch gesehen sorgen beide für valide Verschlüsselung.
Mit selbst-signierten Zertifikaten gibt es jedoch ein Problem:
Sie sind weder in Browsern noch auf anderen Servern installiert.
Deshalb führen sie zu Warnungen über "nicht vertrauenswürdige Zertifikate".
Das ist verwirrend und stört sehr.

Aus diesem Grund empfehlen wir, dass du dir ein von einer CA unterschriebenes Zertifikat besorgst.
Normalerweise kosten sie Geld - und sind nur für eine begrenzte Zeit gültig (z.B. ein Jahr oder zwei).

Es gibt aber Möglichkeiten, ein vertrauenswürdiges Zertifikat umsonst zu bekommen.

## Wähle deinen Domainnamen

Dein SSL-Zertifikat wird für eine bestimmte Domain gültig sein oder sogar nur für eine Subdomain.
Entscheide dich endgültig für einen Domainnamen, *bevor* du ein Zertifikat bestellst.
Wenn du das Zertifikat hast, brauchst du ein neues, wenn du den Domainnamen ändern möchtest.

## Gehosteter Webspace

Wenn deine Friendica-Instanz auf einem gehosteten Webspace läuft, solltest du dich bei deinem Hosting-Provider informieren.
Dort bekommst du Instruktionen, wie es dort läuft.
Du kannst bei deinem Provider immer ein kostenpflichtiges Zertifikat bestellen.
Sie installieren es für dich oder haben in der Weboberfläche eine einfache Upload-Möglichkeit für Zertifikat und Schlüssel.

Um Geld zu sparen, kann es sich lohnen, dort auch nachzufragen, ob sie ein anderes Zertifikat, das du selbst beschaffst, für dich installieren würden.
Wenn ja, dann lies weiter.


## Let's encrypt

Wenn du einen eigenen Server betreibst und den Nameserver kontrollierst, könnte auch die Initiative "Let's encrypt" interessant für dich werden.
Sie bietet nicht nur freie SSL Zertifikate sondern auch einen automatisierten Prozess zum Erneuern der Zertifikate.
Um letsencrypt Zertifikate verwenden zu können, musst du dir einen Client auf deinem Server installieren.
Eine Anleitung zum offiziellen Client findet du [hier](https://certbot.eff.org/).
Falls du dir andere Clients anschauen willst, kannst du einen Blick in diese [Liste von alternativen letsencrypt Clients](https://letsencrypt.org/docs/client-options/).

## Webserver-Einstellungen

Im [Wiki von Mozilla](https://wiki.mozilla.org/Security/Server_Side_TLS) gibt es Anleitungen für die Konfiguration sicherer Webserver.
Dort findest du Empfehlungen, die auf [verschiedene Webserver](https://wiki.mozilla.org/Security/Server_Side_TLS#Recommended_Server_Configurations) zugeschnitten sind.

## Teste deine SSL-Einstellungen

Wenn du fertig bist, kannst du auf der Testseite [SSL-Labs](https://www.ssllabs.com/ssltest/) prüfen lassen, ob Du alles richtig gemacht hast.

## Friendica Konfigurieren

Wenn du deine Friendica Instanz über https erreichen kannst solltest du ein paar Einstellungen vornehmen um sicher zu stellen, dass deine Nutzer ausschließlich über https zugreifen können.

### Webserver-Umleitungen

Dies ist der einfachste Weg den Zugriff für die ganze Webseite abzusichern.
Jedes Mal wenn ein Nutzer Friendica aufruft wird er permanent vom Webserver auf die abgesicherte Seite umgeleitet.

Wenn du den Apache Webserver verwendest, aktiviere die Module rewrite und ssl (bei einem Shared-Hosting Prider sollte dies bereits der Fall sein):

        sudo a2enmod rewrite ssl

und füge die folgenden Zeilen zur .htaccess Datei im Wurzelverzeichnis deiner Friendica Instanz hinzu:

        RewriteEngine On
        RewriteCond %{SERVER_PORT} 80
        RewriteRule ^(.*)$ https://your.friendica.domain/$1 [R=301,L]

(Dank an [url=https://github.com/AlfredSK]AlfredSK[/url]).

Bei nginx solltest du deinen Server folgendermaßen konfigurieren ([documentation](https://www.nginx.com/blog/creating-nginx-rewrite-rules/)):

        server {
             listen 80;
             server_name your.friendica.domain;
             return 301 https://$server_name$request_uri;
        }

### SSL Einstellungen

Im Admin-Panel gibt es drei Einstellungen, die SSL betreffen:

1. **Regeln für SSL Links**: Diese Einstellung betrifft wie Friendica interne Links erzeugt. Wenn deine SSL Installation erfolgreich war, empfehlen wir die Einstellung "SSL für alle Links erzwingen".
2. **Erzwinge SSL**: Mit dieser Einstellung werden alle externen Links auf HTTPS gesetzt. Dies kann bei Mixed-Content Problemen helfen, allerdings unterstützen noch nicht alle Webseiten HTTPS. Benutzung auf eigene Gefahr.
3. **SSL überprüfen**: Wenn diese Einstellung aktiv ist, wird Friendica nicht mehr mit Instanzen interagieren, die über ein selbst signiertes Zertifikat verfügen. Da selbst signierte Zertifikate ein Hinweis auf Man-in-the.Middle Angriffe sein können, empfehlen wir dies zu tun.
