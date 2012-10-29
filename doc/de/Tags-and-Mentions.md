Tags und Erwähnungen
=================

* [Zur Startseite der Hilfe](help)

Wie viele andere soziale Netzwerke benutzt auch Friendica eine spezielle Schreibweise in seinen Nachrichten, um Tags oder kontextbezogene Links zu anderen Beiträgen hervorzuheben.

**Erwähnungen**

Personen werden "getagged", indem du das "@"-Zeichen vor den Namen schreibst. 

Im Folgenden findest du verschiedene Möglichkeiten, um eine Person zu erwähnen:

* @mike - deutet auf eine Person hin, die im Netzwerk den Namen "mike" nutzt
* @mike_macgirvin - deutet auf eine Person hin, die sich im Netzwerk "Mike Macgirvin" nennt. Beachte, dass Leerzeichen in Tags nicht genutzt werden können.
* @mike+151 - diese Schreibweise deutet auf eine Person hin, die "mike" heißt und deren Kontakt-Identitäts-Nummer 151 ist. Bei der Eingabe erscheint direkt ein Auswahlmenü, sodass du diese Nummer nicht selbst kennen musst. 
* @mike@macgirvin.com - diese Schreibweise deutet auf die Profiladresse eines Nutzers in einem anderen Netzwerk oder auf jemanden, der *nicht* in deiner Kontaktliste ist. Diese Schreibweise wird "Fernerwähnung" (remote mention)genannt und kann nur im Email-Stil geschrieben werden, nicht als Internetadresse/URL.


Wenn das System ungewollte Erwähnungen nicht blockiert, erhält diese Person eine Mitteilung oder nimmt direkt an der Diskussion teil, wenn es sich um einen öffentlichen Beitrag handelt. Bitte beachte, dass Friendica eingehende "Erwähnungs"-Nachrichten von Personen blockt, die du nicht zu deinem Profil hinzugefügt hast. Diese Maßnahme dient dazu, Spam zu vermeiden.

"Fernerwähnungen" werden durch das OStatus-Protokoll übermittelt. Dieses Protokoll wird von Friendica, StatusNet und anderen Systemen genutzt, ist allerdings derzeit nicht in Diaspora eingebaut. 

Friendica unterscheidet bei Tags nicht zwischen Personen und Gruppen (einige andere Netzwerke nutzen "!gruppe", um solche zu markieren).


**Thematische Tags**

Thematische Tags werden durch eine "#" gekennzeichnet. Dieses Zeichen erstellen einen Link zur allgemeinen Seitensuche mit dem ausgewählten Begriff. So wird z.B. #Autos zu einer Suche führen, die alle Beiträge deiner Seite umfasst, die dieses Wort erwähnen. Thematische Tags haben generell eine Mindestlänge von 3 Stellen. Kürzere Suchbegriffe finden meist keine Suchergebnisse, wobei dieses abhängig von der Datenbankeinstellung ist. Tags mit einem Leerzeichen werden, wie es auch bei Namen der Fall ist, durch einen Unterstrich gekennzeichnet. Es ist hingegen nicht möglich, Tags zu erstellen, deren gesuchtes Wort einen Unterstrich enthält. 

Thematische Tags werden auch dann nicht verlinkt, wenn sie nur aus Nummern bestehen, wie z.B. #1. Wenn du einen numerischen Tag nutzen willst, füge bitte einen Beschreibungstext hinzu wie z.B. #2012_Wahl.
