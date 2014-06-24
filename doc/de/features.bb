[size=24][b]Features der Red-Matrix[/b][/size]

[url=[baseurl]/help]Zurück zur Hilfe-Startseite[/url]

Die Red-Matrix ist ein Allzweck-Kommunikationsnetzwerk mit einigen einzigartigen Features. Sie wurde für eine große Bandbreite von Nutzern entwickelt, von Nutzern sozialer Netzwerke über technisch nicht interessierte Blogger bis hin zu PHP-Experten und erfahrenen Systemadministratoren.

Diese Seite listet einige der Kern-Features von Red auf, die in der offiziellen Distribution enthalten sind. Wie immer bei freier Open-Source-Software sind den Möglichkeiten keine Grenzen gesetzt. Beliebige Erweiterungen, Addons, Themes und Konfigurationen sind möglich.

[b][size=18]Entwickelt für Privatsphäre und Freiheit[/size][/b]

Eines der Design-Ziele von Red ist einfache Kommunikations über das Web, ohne die Privatsphäre zu vernachlässigen, wenn die Nutzer das Wünschen. Um dieses Ziel zu erreichen, verfügt Red über einige Features, die beliebige Stufen des Privatsphäre-Schutzes ermöglichen:

[b]Beziehungs-Tool[/b]

Wenn Du in der Red-Matrix einen Kontakt hinzufügst (und das Beziehungs-Tool aktiviert hast), hast Du die Möglichkeit, einen „Grad der Freundschaft“ zu bestimmen. Bespiel: Wenn Du ein Blog eines Bekannten hinzufügst, könntest Du ihm den Freundschaftsgrad „Bekannte“ (Acquaintances) geben.

[img]https://friendicared.net/photo/b07b0262e3146325508b81a9d1ae4a1e-0.png[/img]

Wenn Du aber den privaten Kanal eines Freundes hinzufügst, wäre der Freundschaftsgrad „Freunde“ vermutlich passender.

Wenn Du allen Kontakten solche Freundschaftsgrade zugeordnet hast, kannst Du mit dem Beziehungs-Tool, das (sofern aktiviert) oben auf Deiner Matrix-Seite erscheint, bestimmen, welche Inhalte Du sehen willst. Indem Du die Schieberegler so einstellst, dass der linke auf „Ich“ und der rechte auf „Freunde“ steht, kannst Du dafür sorgen, dass nur Inhalte von Kontakten angezeigt werden, deren Freundschaftsgrad sich irgendwo im Bereich zwischen „Ich“, „Beste Freunde“ und „Freunde“ bewegt. Alle anderen Kontakte, zum Beispiel solche mit einem Freundschaftsgrad in der Nähe von „Bekannte“, werden nicht angezeigt.

Das Beziehungs-Tool erlaubt blitzschnelles Filtern von großen Mengen Inhalt, gruppiert nach Freundschaftsgrad.

[b]Zugriffsrechte[/b]

Wenn Du Inhalte mit anderen teilst, hast Du die Option, den Zugriff darauf einzuschränken. Wenn Du auf das Schloss unterhalb des Beitrags-Editors klickst, kannst Du auswählen, wer diesen Beitrag sehen darf, indem Du einfach auf die Namen klickst.

Diese Nachricht kann dann nur vom Absender und den eingestellten Empfängern betrachtet werden. Mit anderen Worten, sie erscheint nicht öffentlich auf einer Pinnwand.

[b]Verschlüsselung privater Nachrichten[/b]

Öffentliche Nachrichten werden in der Red-Matrix nicht verschlüsselt, bevor sie Deinen Server verlassen, und sie werden auch im Klartext in der Datenbank gespeichert.

Nachrichten mit eingeschränktem Empfängerkreis werden jedoch mit einem symmetrischen 256-bit-AES-CBC-Schlüssel verschlüsselt, der seinerseits mit Public-Key-Kryptografie auf Basis von 4096-bittigen RSA-Schlüsseln geschützt (nochmal verschlüsselt) wird, die mit dem sendenden Kanal verbunden sind.

Jeder Red-Kanal hat seinen eigenes 4096-bit-RSA-Schlüsselpaar, das erzeugt wird, wenn der Kanal erstellt wird.

[b]TLS/SSL[/b]

Red-Server, die TLS/SSL benutzen, verschlüsseln ihre Kommunikation vom Server zum Nutzer mit SSL. Nach den aktuellen Enthüllungen über das Umgehen von Verschlüsselung durch NSA, GHCQ und andere Dienste, sollte man jedoch nicht mehr savon ausgehen, dass diese Verbindungen nicht mitgelesen werden können.

[b]Kanal-Einstellungen[/b]

Für jeden Kanal kannst Du detaillierte Zugriffseinstellungen für verschiedenste Aspekte der Kommunikation festlegen. Unter den „Sicherheits- und Privatsphäre-Einstellungen“ kann für jeden Punkt auf der linken Seite eine von 5-6 möglichen Optionen aus dem Menü gewählt werden.

[img]https://friendicared.net/photo/0f5be8da282858edd645b0a1a6626491.png[/img]

Die Optionen sind:
[ul][*]Niemand außer Du selbst
[*]Nur die, denen Du es explizit erlaubst
[*]All Deine Kontakte
[*]Jeder auf diesem Server
[*]Alle Red-Nutzer
[*]Jeder im Internet[/ul]

[b]Klone[/b]

Konten in der Red-Matrix werden auch als [i]nomadische Identitäten[/i] bezeichnet (eine ausführliche Erklärung dazu gibt es unter [url=[baseurl]/help/what_is_zot]What is Zot?[/url]). Nomadisch, weil bei anderen Diensten die Identität eines Nutzers an den Server oder die Plattform gebunden ist, auf der er ursprünglich erstellt wurde. Ein Facebook- oder Gmail-Konto ist and diese Dienste gekettet. Er funktioniert nicht ohne Facebook.com bzw. Gmail.com.

Bei Red ist das anders. Sagen wir, Du hast eine Red-Indentität namens tina@red.com. Die kannst Du auf einen anderen Server klonen, mit dem gleichen oder einem anderen Namen, zum Beispiel lebtEwig@matrixserver.info.

Beide Kanäle sind jetzt miteinander synchronisiert, das heißt, dass alle Kontakte und Einstellungen auf dem Klon immer die gleichen sind wie auf dem ursprünglichen Kanal. Es ist egal, ob Du eine Nachricht von dort aus oder vom Klon aus schickst. Alle Nachrichten sind in beiden Klonen vorhanden.

Das ist ein ziemlich revolutionäres Feature, wenn man sich einige Szenarien dazu ansieht:

[ul][*]Was passiert, wenn ein Server, auf dem sich Deine Identität befindet, plötzlich offline ist? Ohne Klone ist der Nutzer nicht in der Lage zu kommunzieren, bis der Server wieder online ist. Mit Klonen loggst Du Dich einfach bei Deinem geklonten Kanal ein und lebst glücklich bis an Dein Ende.
[*]Der Administrator Deines Red-Servers kann es sich nicht länger leisten, seinen für alle kostenlosen Server zu bezahlen. Er gibt bekannt, dass der Server in zwei Wochen vom Netz gehen wird. Zeit genug, um Deine Red-Kanäle auf andere Server zu klonen und somit Verbindungen und Freunde zu behalten.
[*]Was, wenn Dein Kanal staatlicher Zensur unterliegt? Dein Server-Admin wird gezwungen, Dein Konto und alle damit verbundenen Kanäle und Daten zu löschen. Durch Klone bietet die Red-Matrix Zensur-Resistenz. Wenn Du willst, kannst Du hunderte von Klonen haben, alle mit unterschiedlichen Namen und auf unterschiedlichen Servern überall im Internet.[/ul]

Red bietet interessante, neue Möglichkeiten in Bezug auf die Privatsphäre. Mehr dazu bald unter „Tipps und Tricks zur privaten Kommunikation“.

Klone unterliegen einigen Restriktionen. Eine vollständige Erklärung zum Klonen von Identitäten gibt es bald unter „Klone“.


[b]Kanal-Backups[/b]

In Red gibt es ein einfaches Ein-Klick-Backup, mit dem Du ein komplettes Backup Deiner Kanal-Einstellungen und Verbindungen herunterladen kannst.

Solche Backups sind ein Weg, um Klone zu erstellen, und können genutzt werden, um einen Kanal wiederherzustellen.

[b]Löschen von Konten[/b]

Konten und Kanäle können sofort gelöscht werden, indem Du einfach auf einen Link klickst. Das wars. Alle damit verbundenen Inhalte werden sofort aus der Matrix gelöscht (inklusiver aller Beiträge und sonstiger Inhalte, die von dem gelöschten Konto/Kanal erzeugt wurden).

[b][size=18]Erstellen von Inhalten[/size][/b]

[b]Beiträge schreiben[/b]

Red unterstützt diverse verschiedene Wege, um Inhalte zu erstellen, von einem grafischen Text-Editor über verschieden Markup-Sprachen bis hin zu reinem HTML.

Mit Red geliefert wird TinyMCE, ein WYSIWYG-Editor, der in den Einstellungen eingeschaltet werden kann. Ist er nicht aktiviert, können normale Beiträge mit BBCode (wie in Foren) formatiert werden.

Webseiten können außerdem in HTML, Markdown und Plain Text erstellt werden.

[b]Inhalte löschen[/b]

Alle Inhalte in der Matrix bleiben unter der Kontroller des Nutzers (bzw. Kanals), der sie ursprünglich erstellt hat. Alle Beiträge können jederzeit gelöscht werden, egal, ob sie auf dem Heimat-Server des Nutzers oder auf einem anderen Server erstellt wurden, an dem der Nutzer via Zot angemeldet war.

[b]Medien[/b]

Genau wie jedes andere Blog-System, soziale Netzwerk oder Mikro-Blogging-Dienst unterstützt Red das Hochladen von Dateien, das Einbetten von Bildern und Videos und das Verlinken von Seiten.

[b]Vorschau[/b] 

Vor dem Absenden kann eine Vorschau von Beiträgen betrachtet werden.

[url=[baseurl]/help]Zurück zur Hilfe-Startseite[/url]
