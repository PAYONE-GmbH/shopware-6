# 1.0.0
- Erste Version der PAYONE Payment Integration für Shopware 6.1

# 1.0.1
Fehlerbehebung

* Korrigierte Kodierung der Antwortparameter während PayPal ECS
* Fehlende CVC-Längenkonfigurationen für weniger verwendete Kartentypen hinzugefügt
* Ein Fehler wurde behoben, durch den benutzerdefinierte Felder in dem Bestellabschluss nicht angezeigt wurden, wenn nicht die standardmäßige Shop-Sprachen verwendet wurde. Wir unterstützen derzeit DE und EN und planen, dies zu erweitern.

Wartung

* Best Practices für die Überprüfung des Shopware-Codes eingebaut

# 1.0.2
Erweiterung

* Möglichkeit zum Teil-Einzug und Teil-Rückerstattung integriert

# 1.1.0

Neue Funktionen

* Teilweise Einzüge und Rückerstattungen sind jetzt möglich!
* UI-Verbesserungen in den Einstellungen (diese sind jetzt zusammenklappbar)
* Sie können jetzt die Autorisierungsmethode für jede Zahlungsmethode wählen!
* Neue Zahlungsmethode: iDeal
* Neue Zahlungsmethode: EPS

Fehlerbehebungen

* korrigierte PayPal ECS-Schaltfläche
* Übersetzungsfehler beim Checkout behoben
* Besseres Feedback bei der Überprüfung von API-Anmeldeinformationen ohne aktive PAYONE-Zahlungsmethoden
* ein Fehler behoben, der bei der Migration von 1.0.0 auf 1.0.1 auftreten konnte

Bekannte Inkompatibilitäten

* Backurlhandling in Shopware 6.2 ist derzeit fehlerhaft. Wenn ein Kunde zu seiner bevorzugten Zahlungsmethode umgeleitet wird, sich aber entscheidet, zu stornieren und eine andere Zahlungsmethode zu wählen, stehen keine PAYONE-Zahlungsmethoden zur Verfügung. Wir arbeiten an einer Lösung, um eine korrekte Handhabung dieses Anwendungsfalles zu ermöglichen.

# 2.0.0

Neue Funktionen
 
* Neue Zahlungsmethode: Vorauszahlung
* Neue Zahlungsmethode: Paydirekt
* Unterstützung des Storno-Zahlungsflusses von Shopware 6.2
 
Fehlerbehebung(en)
 
* ein Fehler behoben, durch den bestehende Einstellungen wie die Zuweisung von Zahlungsmethoden nach einem Plugin-Update verloren gehen konnten
* falsches Vertriebskanal-Routing von PayPal-Express-Zahlungen korrigiert (thx @boxblinkracer)
* verschiedene kleinere Korrekturen
 
Wartung

* Kompatibilität für Shopware 6.2.x+
* getestet mit Shopware 6.3.0.2
* Wir mussten die Unterstützung für Shopware <6.2.0 einstellen.