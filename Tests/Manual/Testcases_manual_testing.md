## Testfälle für das manuelle Testen

### EXT:in2publish_core (bei deaktivierter EXT:in2publish)

### EXT:in2publish (Enterprise Edition)

#### W Workflows

 ![Workflow Module](./images/Workflows_1.png)

Voraussetzung: Workflows aktivieren in der `LocalConfiguration.yaml`

##### W1. Geänderte Seiteneigenschaften werden publiziert
  
  `Test Page 1.1` (readyToPublish) kann publiziert werden -> Seitentitel auf Foreign ändert sich auf `Test Page 1.1 edited`

##### W2. Geänderte Inhaltelemente werden publiziert

`Test Page 1.1.2` (readyToPublish) kann publiziert werden -> Content element Header ändert sich auf Foreign auf `Header 1.1.2 edited`

##### W3. Seiten und Inhalte von Seiten im Status `draft` werden nicht publiziert

`Test Page 1.1.1` (draft) kann nicht publiziert werden 

##### W4. Durch Änderung des Workflows von `draft` auf `readyToPublish` kann Seite publiziert werden

Nachdem `Test Page 1.1.1` (draft) auf readyToPublish gesetzt wurde, erscheint der Pfeil zum Publizieren

##### W5. *PublishAll* Button publiziert alle freigegebenen Seiten und Inhalte unterhalb der ausgewählten Seite im WorkflowModul

*PublishAll Button*: im Seitenbaum auf der Seite `Test Page 1`: betätigen: es werden alle Seiten und Inhalte von Seiten
 `readyToPublish` publiziert. Der Workflow State der publizierten Seiten ändert sich auf `published`.

#### Workflow State Assignment (WFSA)

![WFSA Workflow_Modul](./images/WFSA_1.png)

##### WFSA1. Auf Seiten mit eingeschränktem Zugriff dürfen nur berechtigte User den Workflow State zugewiesen bekommen

Bei der Seite `Test Workflow State Assignment/editors-restricted` (draft) dürfen User mit der Rolle `editor-restricted` 
beim Workflow State Assignment readyToPublish ausgewählt werden, keine User mit der Rolle `editor`

#### WC Workflows und CLC

Voraussetzung: CLC aktivieren in der `LocalConfiguration.yaml`

##### WC1. Es können im WorkflowModul nur Übersetzungen `readyToPublish` publiziert werden

`Test CLC Page 2 ` kann in der Default-Sprache (EN) und in der Übersetzung DE publiziert werden. JP ist in `draft`.

##### WC2. Es können im OverviewModul nur Übersetzungen `readyToPublish` publiziert werden

`Test CLC Page 2 ` kann in der Default-Sprache (EN) und in der Übersetzung DE publiziert werden (Pfeil). 
Die JP Übersetzung kann nicht publiziert werden.

##### WC3. *PublishAll* Button publiziert alle freigegebenen Übersetzungen unterhalb der ausgewählten Seite im WorkflowModul

*PublishAll Button*: im Seitenbaum auf der Seite `Test CLC Page 2`: betätigen: es wird die freigegebene 
 Default-Sprache (EN) und die Übersetzung DE publiziert. Die JP Übersetzung wird nicht publiziert.
Der Workflow State der publizierten Seiten (EN und DE) ändert sich auf `published`, JP bleibt auf `draft`.

### Overview Modul

 ![Overview Module](./images/Overview_1.png)

##### O1. *PublishPageAndSubpages* Button publiziert alle freigegebenen Seiten und Inhalte unterhalb der ausgewählten Seite im OverviewModul

`PublishPageAndSubpages`: im Seitenbaum auf der Seite `Test Page 1`: betätigen: es werden alle Seiten und Inhalte von Seiten
`readyToPublish` publiziert. Der Workflow State der publizierten Seiten ändert sich auf `published`.

### PublishFiles Modul

![Publish Files Module](./images/Files_1.png)

##### F1. Einzelne Dateien können publiziert werden
Im PublishFiles Modul können neue Dateien (z.B. glenn-carstens..) und geänderte Dateien (z.B. nate-johnston...) publiziert werden.

##### F2. Alle neuen/geänderten Dateien können über den *PublishAll* Button publiziert werden

Im PublishFiles Modul können über den Button *PublishAll* alle neuen/geänderten Dateien in diesem Ordner publiziert werden.

##### F3. Dateien im PublishFiles Modul können gefiltert werden

Die Dateien können nach Namen und Status gefiltert werden.