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

# 2.3.2

Fehlerbehebungen

* Transaktionstatus-Übertragung des txstatus "paid"

Wartung

* getestet mit Shopware 6.4.4.0

Hinweis

* Wir werden die Kompatibilität zu 6.2.* in zukünftigen Versionen einstellen

# 2.4.0

Neue Funktionen

* Neue Zahlart: Apple Pay
* Weiterleitung des Transaktionsstatus an Drittsysteme ermöglichen

Fehlerbehebungen

* Verschiedene Fehler in mehreren Sprachen behoben
* Fehler bei der Vorkasse behoben

Wartung

* Kompatibel mit 0€ Bestellungen
* getestet mit 6.4.1

# 2.4.1

Neue Funktionen

* PAYONE Zahlungsarten für 0€ Bestellungen gesperrt
* Apple-pay hinzugefügt
* Zahlartenbeschreibung hinzugefügt

Fehlerbehebungen

* Fehler beim Laden der Konfiguration behoben
* Storefront Anfragen korrigiert
* Fehlende Services behoben
* Fehlender Parameter bei der Vorkasse hinzugefügt

Wartung

* Abwärtskompatibilität beheben
* Kartentyp Discover entfernt
* Abhängigkeit zur GitHub-Pipeline hinzufügen
* getestet mit 6.4.5.0

# 3.0.0

Fehlerbehebungen

* Löschung von Kunden ist jetzt möglich
* Gutschrift nur bei noch nicht gutgeschriebenen Artikeln möglich
* Fehlende Abhängigkeiten hinzugefügt für die Installation via Store

Wartung

* Kompatibilität zu 6.4.7.0 hergestellt
* Unterstützung für 6.2 entfernt

# 3.1.0

Neue Funktionen
 
* Neue Zahlungsart: Rechnung
* Checkbox für Kreditkartenzahlungen hinzugefügt, um Zahlungsdaten zu speichern

Fehlerbehebungen
 
* Der Capturemode Parameter wurde entfernt bei abgeschlossenen Zahlungen 
* den ZeroAmountCartValidator geupdatet
* Data Protection Check wird immer aktiviert

Wartung

* Artikel ohne Steuern werden beim Capture berücksichtigt
* Versandinformationen wurden bei Unzer hinzugefügt

Getestet mit:
Shopware 6.4.10.0

# 3.2.0

Neue Funktionen
 
* Neue Zahlungsart: Bancontact
* Bankgruppen Typen für iDEAL hinzugefügt
* Regelmäßiges automatisiertes reinigen der redirect Tabelle
* Zahlungsziel auf den Standardrechnungen für den Rechnungskauf hinzugefügt
 
Fehlerbehebung
 
* Versandkosten zu einzelnen Artikeln hinzugefügt
* Fehler in Regel zur Entfernung des gesicherten Rechnungskaufs behoben
 
Wartung

# 4.1.0
 
* Umbenennung der Zahlungsarten
* PAYONE Logo ausgetauscht
* Getestet mit 6.4.12

# 3.3.0

Neue Funktionen

* Neue Zahlungsart: Ratepay Rechnungskauf
* Neue Zahlungsart: Ratepay Lastschrift
* Neue Zahlungsart: Ratepay Ratenkauf

Wartung

* Sales Landingpage ins Backend integriert
* Getestet mit 6.4.14 

# 4.0.0

Neue Funktionen

* Unterstützung für Shopware 6.3 aufgehoben
* Allgemeine Code Optimierungen durchgeführt

* Wichtige Änderung: Die Transaktionsdaten von PAYONE Zahlungen 
wurden bisher immer in den Zusatzfeldern der Bestellungen gespeichert. 
Da die Zusatzfelder als JSON in der Datenbank gespeichert werden, 
war das Durchsuchen der Transaktionsdaten bei großen Datenmengen nicht 
sehr performant. Deshalb wurde für die Transaktionsdaten eine Entity 
Extension eingerichtet, sodass die Daten in einer extra Datenbanktabelle 
gespeichert werden, die deutlich performanter durchsucht werden kann. 
Beim Plugin Update werden die alten Zusatzfelder in die Entity Extension 
migriert und danach werden die Zusatzfelder gelöscht. Sollten Sie in Ihrem 
eigenen Code oder zum Beispiel bei der Synchronisation zu externen Systemen 
unsere Zusatzfelder verwendet haben, müssen Sie das auf die neue Entity 
Extension anpassen.

Fehlerbehebung

* Löschung gespeicherter Kreditkarten entfernt

Wartung

* BIC aus der Lastschrift entfernt
* Getestet mit 6.4.16

### Lesen der Transaktionsdaten ###
```        
$criteria = (new Criteria())
->addAssociation(PayonePaymentOrderTransactionExtension::NAME)
->addFilter(new EqualsFilter(PayonePaymentOrderTransactionExtension::NAME . '.transactionId', $payoneTransactionId));

/** @var null|OrderTransactionEntity $transaction */
$transaction = $this->transactionRepository->search($criteria, $context)->first();

/** @var PayonePaymentOrderTransactionDataEntity $payoneTransactionData */
$payoneTransactionData = $transaction->getExtension(PayonePaymentOrderTransactionExtension::NAME);
   ```

### Aktualisieren der Transaktionsdaten ###

```
$this->transactionRepository->upsert([[
   'id'                                         => $transaction->getId(),
   PayonePaymentOrderTransactionExtension::NAME => [
        'id' => $payoneTransactionData->getId(),
        'sequenceNumber' => 1,
        'transactionState' => 'appointed'
   ],
]], $context);
 ```

# 4.1.0

Neue Funktionen
 
* Neue Zahlungsart: Klarna Rechnung
* Neue Zahlungsart: Klarna Sofort
* Neue Zahlungsart: Klarna Ratenkauf
* Neue Zahlungsart: P24
* Der Kreditkarten - Kartentyp wird nun im Adminbereich bei den Bestelldetails angezeigt
 
Fehlerbehebung
 
* Fehler in der Weiterleitung behoben, wenn Multi-Saleschannels genutzt werden - Vielen Dank an @patchee500
* Fehler bei Unzer B2B behoben
* Fehler beim Refund mit falscher tx_id behoben
 
Wartung
 
* Getestet mit: 6.4.17.1

# 4.2.0

* Neue Zahlungsart: PAYONE WeChat Pay
* Neue Zahlungsart: PAYONE Postfinance Card
* Neue Zahlungsart: PAYONE Postfinance E-Finance
* Neue Zahlungsart: PAYONE AliPay
* Opt-in für automatischen Capture

Fehlerbehebung

* Fehler mit der Bestellnummer bei paydirekt behoben
* Fehler beim Capture bei iDEAL behoben
* Fehler bei Datentyp Migration behoben
* Fehler bei der Unterstützung von Rabattcodes behoben

Wartung

* Zahlungsartenfilter Technologie verbessert
* Geburtstagsfeld aus der Zahlungsart offene Rechnung entfernt
* iDEAL Bankliste geupdated
* getestet mit 6.4.20
