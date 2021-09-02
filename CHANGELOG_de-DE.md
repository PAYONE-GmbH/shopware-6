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

# 2.1.0

Neue Funktionen

* Neue Zahlungsmethode: PAYONE sichere Rechnung
* Neue Zahlungsmethode: Trustly
* Neue zahlungsspezifische Einstellung zur Übergabe der Shopware-Bestellnummer im Feld `narrative_text` für (Vor-)Autorisierungsanfragen

Fehlerbehebung(en)

* Überarbeitung der txstatus-Logik, so dass benutzerdefinierte Felder mit den Backend-Optionen übereinstimmen

# 2.2.0

Neue Funktionen
 
* Kompatibilität mit Shopware 6.4.x
 
Fehlerbehebungen
 
* API-Test für paydirekt behoben
* Lieferadresse bei Paypal-Zahlungen immer angeben
* gefixte Labels für PAYONE Statusmapping (endlich!) 
 
Wartung
 
* getestet mit Shop-Version 6.4.1.0
* bessere Übersetzungen der Fehlermeldungen


# 2.3.0

Neue Funktionen
 
* neue PAYONE Berechtigungsverwaltung
* Status Mapping pro Zahlungsmethode möglich
 
Fehlerbehebungen
 
* Fix für die Freischaltung der Schaltfläche "Jetzt kaufen"
* PayPal Express: Telefonnummer ist kein Pflichtfeld mehr
 
Wartung
 
* getestet mit Shopware 6.4.3.1
* massive Überarbeitungen in der Pluginstruktur
* Elasticsearch Kompatibilität hergestellt

# 2.3.1

Fehlerbehebungen

* Abwärtskompatiblität zu Version <6.4.0