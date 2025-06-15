# HomeStats - Raumklima-Überwachungssystem

HomeStats ist ein umfassendes System zur Überwachung und Analyse der Raumluftqualität und des Raumklimas. Es sammelt Daten von verschiedenen Sensoren, speichert diese und stellt sie in einem benutzerfreundlichen Dashboard dar.

## Funktionen

- **Echtzeit-Überwachung** von Raumklima-Parametern:
  - Temperatur
  - Luftfeuchtigkeit
  - CO₂-Gehalt
  - Feinstaub (PM2.5)
  - Außentemperatur (über Wetter-APIs)

- **Datenvisualisierung**:
  - Aktuelle Werte mit farbcodierten Statusanzeigen
  - Historische Daten in Diagrammen
  - Gesamtbewertung der Luftqualität

- **Datenspeicherung**:
  - Kurzfristige Speicherung (24 Stunden) für detaillierte Analysen
  - Langfristige Speicherung (10 Wochen) für Trendanalysen

- **Responsive Design**:
  - Optimiert für Desktop und mobile Geräte
  - Vollbildmodus für Dashboards

## Technische Architektur

### Verwendete Technologien
- **Backend**: PHP mit Symfony Framework
- **Datenbank**: Doctrine ORM
- **Frontend**: Twig Templates, JavaScript mit Stimulus
- **APIs**: Integration mit Wetter-APIs (Open-Meteo, MET Norway)

### Komponenten
- **Sensoren**:
  1. LCD Display (zur Anzeige)
  2. Lichtsensor
  3. CO₂-Sensor
  4. Temperatursensor
  5. Feinstaubsensor (PM2.5)
  6. Luftfeuchtigkeitssensor
  7. Luftdrucksensor

- **Datenmodell**:
  - `Homelog`: Kurzfristige Daten (24 Stunden)
  - `PermanentData`: Langfristige Daten (10 Wochen)

- **Services**:
  - `HomeService`: Verarbeitung und Speicherung von Sensordaten
  - `StatsService`: Aufbereitung von Daten für Diagramme

## Installation

1. Repository klonen
2. Abhängigkeiten installieren: `composer install`
3. Datenbank konfigurieren in `.env`
4. Datenbank-Schema erstellen: `php bin/console doctrine:schema:create`
5. Webserver starten: `symfony server:start`

## Verwendung

### Weboberfläche
- **Startseite** (`/`): Zeigt das aktuelle Raumklima mit allen Sensorwerten
- **History** (`/history`): Zeigt historische Daten als Diagramme

### Datenerfassung
- Sensordaten können über den Endpunkt `/log_data` mit entsprechenden Parametern gesendet werden
- Unterstützte Parameter:
  - `temperature`: Temperatur in °C
  - `humidity`: Luftfeuchtigkeit in %
  - `co2`: CO₂-Konzentration in ppm
  - `co2temp`: Temperatur vom CO₂-Sensor
  - `dustDensity`: Feinstaubkonzentration

## Wartung

- Alte Daten werden automatisch bereinigt:
  - Homelog-Daten älter als 24 Stunden
  - PermanentData-Einträge älter als 10 Wochen
