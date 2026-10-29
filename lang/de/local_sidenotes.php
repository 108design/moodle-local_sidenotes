<?php
/**
 * This file is part of Moodle - https://moodle.org/
 *
 * Moodle is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Moodle is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Moodle. If not, see <https://www.gnu.org/licenses/>.
 *
 */
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * German language strings for the personal Side Notes fork.
 *
 * @package     local_sidenotes
 * @copyright   2026 Andreas Giesen
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['bulk:select'] = 'Notizen auswählen';
$string['bulk:selectnote'] = 'Notiz auswählen: {$a}';
$string['bulk:count'] = '{$a} ausgewählt';
$string['bulk:visible'] = 'Sichtbare auswählen';
$string['bulk:filtered'] = 'Alle gefilterten Ergebnisse auswählen ({$a})';
$string['bulk:clear'] = 'Auswahl aufheben';
$string['bulk:action'] = 'Aktion wählen';
$string['bulk:apply'] = 'Anwenden';
$string['bulk:addtags'] = 'Tag hinzufügen';
$string['bulk:removetags'] = 'Tag entfernen';
$string['bulk:tag'] = 'Tagname';
$string['bulk:globalon'] = 'Global in meinem Bereich anzeigen';
$string['bulk:globaloff'] = 'Nicht mehr global anzeigen';
$string['bulk:hint'] = 'Shift-Klick wählt einen Bereich. Filteränderungen heben die Auswahl auf; Blättern behält sie.';
$string['bulk:confirm'] = 'Diese Aktion auf {$a} ausgewählte Notizen anwenden? Ungespeicherter Text in ausgewählten Editoren wird vorher gespeichert.';
$string['bulk:deleteconfirm'] = '{$a} ausgewählte archivierte Notizen einschließlich ihrer Bilder endgültig löschen? Dies kann nicht rückgängig gemacht werden.';
$string['bulk:done'] = '{$a} Notizen aktualisiert.';
$string['bulk:changed'] = 'Die Auswahl hat sich verändert. Diese Sammelaktion hat nichts geändert; bitte neu laden und erneut auswählen.';
$string['bulk:limit'] = 'Bitte höchstens 500 Notizen auswählen. Für größere Sammlungen die Filter eingrenzen.';
$string['bulk:taglimit'] = 'Eine ausgewählte Notiz würde mehr als 20 Tags erhalten. Keine Tags wurden geändert.';
$string['bulk:exportsaved'] = 'Bitte Änderungen in ausgewählten Editoren vor dem Export speichern. Exportiert werden gespeicherte Notizen.';
$string['sidebar:loading'] = 'Notizen werden geladen …';
$string['sidebar:loaderror'] = 'Notizen konnten nicht geladen werden. Erneut versuchen';
$string['sidebar:pagecount'] = 'Notizen auf dieser Seite: {$a}';
$string['shortcut:enabled'] = 'Neue Notiz mit Alt+Q';
$string['privacy:metadata:shortcut'] = 'Persönliche Einstellung für das Tastenkürzel zum Anlegen einer Notiz.';

$string['allcourses'] = 'Alle Kurse';
$string['allpages'] = 'Alle Seiten';
$string['alltags'] = 'Alle Tags';
$string['sidenotes:use'] = 'Private Side Notes-Notizen verwenden';
$string['exportmd'] = 'Als Markdown exportieren';
$string['exportpdf'] = 'Als PDF exportieren';
$string['filterbycourse'] = 'Nach Kurs filtern';
$string['filterbytag'] = 'Nach Tag filtern';
$string['markdown:preview'] = 'Formatierte Vorschau';
$string['note:add'] = 'Neue Notiz';
$string['note:delete'] = 'Notiz löschen';
$string['note:delete_confirm'] = 'Diese Notiz löschen?';
$string['archive:title'] = 'Archiv';
$string['archive:active'] = 'Notizen';
$string['archive:views'] = 'Notizenansicht';
$string['archive:complete'] = 'Als erledigt archivieren';
$string['archive:restore'] = 'Aus dem Archiv wiederherstellen';
$string['archive:clear'] = 'Archiv leeren';
$string['archive:delete'] = 'Endgültig löschen';
$string['archive:deleteconfirm'] = 'Diese Notiz einschließlich ihrer Bilder endgültig löschen? Das kann nicht rückgängig gemacht werden.';
$string['archive:clearconfirm'] = 'Alle {$a} archivierten Notizen einschließlich ihrer Bilder endgültig löschen? Das kann nicht rückgängig gemacht werden. Kurs-, Tag- und Suchfilter werden dabei nicht berücksichtigt.';
$string['archive:empty'] = 'Dein Archiv ist leer.';
$string['archive:hint'] = 'Erledigte Notizen bleiben hier erhalten. Entferne das Häkchen im Header, um eine Notiz wiederherzustellen.';
$string['archive:changed'] = 'Das Archiv wurde inzwischen geändert. Bitte prüfe die aktuelle Ansicht und bestätige das Löschen erneut.';
$string['archive:archived'] = 'Notiz archiviert.';
$string['archive:restored'] = 'Notiz wiederhergestellt.';
$string['archive:cleared'] = 'Archiv geleert.';
$string['archive:draftblocked'] = 'Diese Notiz wurde archiviert. Dein lokaler Textentwurf bleibt erhalten; bitte kopiere ihn vor dem Schließen.';
$string['tags:filteradd'] = 'Tag hinzufügen …';
$string['tags:selectedfilters'] = 'Ausgewählte Tags – alle müssen zutreffen';
$string['tags:removefilter'] = 'Tagfilter entfernen: {$a}';
$string['privacy:metadata:local_sidenotes_notes:archived'] = 'Ob die Notiz als erledigt archiviert wurde.';
$string['privacy:metadata:local_sidenotes_notes:timearchived'] = 'Zeit der Archivierung, oder null nach Wiederherstellung.';
$string['note:empty'] = 'Noch keine Notizen vorhanden.';
$string['note:error'] = 'Die Notiz konnte nicht gespeichert werden';
$string['note:globalbadge'] = 'Global';
$string['note:isglobal'] = 'Auf jeder Seite anzeigen';
$string['note:location'] = 'Seite';
$string['note:placeholder'] = 'Private Notiz schreiben …';
$string['note:saved'] = 'Automatisch gespeichert';
$string['note:saving'] = 'Wird gespeichert …';
$string['note:updated'] = 'Aktualisiert';
$string['note:viewintext'] = 'Auf der Seite anzeigen';
$string['notescenter'] = 'Notizübersicht';
$string['perpage'] = 'Notizen pro Seite';
$string['perpage_desc'] = 'Maximale Zahl der Notizen pro Seite in der Notizübersicht.';
$string['pluginname'] = 'Side Notes';
$string['import:title'] = 'Aus QuickNote importieren';
$string['import:offer'] = '{$a} Deiner QuickNote-Notizen können nach Side Notes übernommen werden.';
$string['import:explanation'] = 'Kopiere Deine eigenen QuickNote-Notizen nach Side Notes. Text, Zitate, Seiten-/Kursbezug und Zeitstempel bleiben erhalten; die Originalnotizen bleiben unverändert. Ursprünglicher Klartext bleibt Klartext. Markdown, Tags und Screenshots des erweiterten Forks werden ebenfalls übernommen. Bereits importierte Notizen werden übersprungen, auch wenn Du die Kopie gelöscht hast. Spätere Änderungen am Original überschreiben Side Notes nicht. Rechte und Plugin-Einstellungen werden nicht importiert. Notizen aus nicht verfügbaren Kursen bleiben schreibgeschützt.';
$string['import:pending'] = 'Neue importierbare Notizen: {$a}';
$string['import:confirm'] = 'Meine Notizen jetzt importieren';
$string['import:complete'] = '{$a} Notizen nach Side Notes importiert.';
$string['privacy:metadata:imports'] = 'Private QuickNote-Importzuordnungen: Besitzer, Kurs, ursprüngliche Notiz-ID und Erstellungszeit, Zielnotiz-ID und Importzeit. Nach dem Löschen einzelner Notizen zum Schutz vor erneutem Import aufbewahrt.';
$string['privacy:metadata:local_sidenotes_notes'] = 'Private, von Nutzern erstellte Schnellnotizen.';
$string['privacy:metadata:local_sidenotes_notes:content'] = 'Textinhalt der Notiz.';
$string['privacy:metadata:local_sidenotes_notes:contentformat'] = 'Moodle-Textformat des Notizinhalts.';
$string['privacy:metadata:local_sidenotes_notes:courseid'] = 'Kurs, in dem die Notiz erstellt wurde.';
$string['privacy:metadata:local_sidenotes_notes:isglobal'] = 'Ob die Notiz auf jeder Seite erscheint.';
$string['privacy:metadata:local_sidenotes_notes:pagehash'] = 'Nicht umkehrbare Kennung der Ursprungsseite.';
$string['privacy:metadata:local_sidenotes_notes:pagetitle'] = 'Titel der Ursprungsseite.';
$string['privacy:metadata:local_sidenotes_notes:quote'] = 'Markiertes Zitat, auf das sich die Notiz bezieht.';
$string['privacy:metadata:local_sidenotes_notes:quoteurl'] = 'URL des zitierten Abschnitts.';
$string['privacy:metadata:local_sidenotes_notes:timecreated'] = 'Erstellungszeit der Notiz.';
$string['privacy:metadata:local_sidenotes_notes:timemodified'] = 'Zeit der letzten Änderung.';
$string['privacy:metadata:local_sidenotes_notes:url'] = 'Ursprungsseite der Notiz.';
$string['privacy:metadata:local_sidenotes_notes:userid'] = 'Nutzer, der die Notiz erstellt hat.';
$string['privacy:metadata:files'] = 'Screenshots, die in private Side Notes-Notizen eingefügt wurden.';
$string['privacy:metadata:tags'] = 'Private Kategorisierungs-Tags von Side Notes-Notizen.';
$string['search'] = 'Suchen';
$string['search:clear'] = 'Suche leeren';
$string['search:noresultstext'] = 'Keine passenden Notizen gefunden.';
$string['search:placeholder'] = 'Meine Notizen durchsuchen …';
$string['select:highlightlabel'] = 'Markierung als Notiz speichern';
$string['screenshot:delete'] = 'Screenshot löschen';
$string['screenshot:attachment'] = 'Screenshot';
$string['screenshot:pastehint'] = 'Screenshot hier mit Strg+V einfügen.';
$string['screenshot:uploading'] = 'Screenshot wird hochgeladen …';
$string['sidebar:close'] = 'Notizen schließen';
$string['sidebar:title'] = 'Meine Notizen';
$string['sidebar:toggle'] = 'Notizen öffnen';
$string['tagarea_local_sidenotes_notes'] = 'Side Notes-Notizen';
$string['tagcollection_sidenotes_private'] = 'Private Side Notes-Tags';
$string['tags'] = 'Tags';
$string['tags:placeholder'] = 'Tags durch Kommas trennen';
$string['unknownpage'] = 'Moodle-Seite';
$string['viewnotescenter'] = 'Notizübersicht öffnen';
$string['sidenotes:usecourse'] = 'Eigene Side Notes-Notizen in zugänglichen Kursen verwenden';
$string['sidenotes:managecourse'] = 'Side Notes-Verfügbarkeit im Kurs einstellen';
$string['settings:courseenabled'] = 'Kursnotizen aktivieren';
$string['settings:courseenabled_desc'] = 'Private Notizen für Studierende und Lehrende in zugänglichen Kursen. Kursverantwortliche können Side Notes im Kurs oder einzelnen Aktivitäten deaktivieren. Notizen bleiben erhalten.';
$string['settings:coursedefault'] = 'In Kursen standardmäßig aktiv';
$string['settings:coursedefault_desc'] = 'Standard für Kurse ohne eigene Side Notes-Einstellung.';
$string['settings:sitewideenabled'] = 'Seitenweite Nutzung zusätzlich aktivieren';
$string['settings:sitewideenabled_desc'] = 'Notizen auch auf Start-, Verwaltungs- und anderen Moodle-Seiten. Nur für Administratoren und explizit mit dem Systemrecht „Side Notes verwenden“ ausgestattete Nutzer. Kein Zugriff auf fremde Notizen.';
$string['course:settings'] = 'Side Notes im Kurs';
$string['course:enabled'] = 'Kursnotizen erlauben';
$string['course:inherit'] = 'Website-Standard verwenden';
$string['course:excluded'] = 'In diesen Aktivitäten deaktivieren';
$string['course:help'] = 'Betrifft nur den Kursbetrieb. Berechtigte Nutzer der zusätzlichen seitenweiten Funktion behalten Zugriff. Vorhandene private Notizen werden nicht gelöscht.';
$string['access:denied'] = 'Side Notes ist für Dich in diesem Bereich nicht verfügbar.';
$string['error:busy'] = 'Die Notiz wird gerade gespeichert. Bitte versuche es erneut.';
$string['error:conflict'] = 'Der Text wurde inzwischen anderweitig geändert. Deine Eingabe bleibt erhalten. Bitte kopiere sie vor dem Neuladen und gleiche die Änderungen ab.';
$string['note:unbound'] = 'Ohne Seitenbindung';
$string['note:globalcourses'] = 'In allen meinen Kursen anzeigen';
$string['note:edit'] = 'Text bearbeiten';
$string['note:readonly'] = 'Dieser Ursprungsbereich ist momentan nicht für die Bearbeitung freigegeben.';
$string['center:add'] = 'Neue Notiz';
$string['center:addhint'] = 'Neue Notizen bleiben in der Übersicht, bis Du sie bewusst global schaltest.';
$string['center:editorhint'] = 'Markdown-Kürzel möglich · Strg+Enter speichert · Screenshots mit Strg+V';
$string['center:pendingtext'] = 'Änderung gespeichert · Textentwurf noch ungespeichert';
$string['center:immediatehint'] = 'Tags, globale Sichtbarkeit und Bilder werden sofort gespeichert; Abbrechen betrifft nur den Text.';
$string['center:discard'] = 'Die ungespeicherten Textänderungen verwerfen?';
$string['tags:add'] = 'Tag hinzufügen';
$string['tags:remove'] = 'Tag entfernen: {$a}';
$string['screenshot:deleteconfirm'] = 'Diesen Screenshot dauerhaft aus der Notiz löschen?';
$string['sidebar:globalnotes'] = 'Globale Notizen';
$string['sidebar:globalcount'] = '{$a->visible} von {$a->total}';
$string['sidebar:pageempty'] = 'Noch keine Notizen für diese Seite.';
$string['tags:manage'] = 'Tags verwalten';
$string['tags:managerhint'] = 'Nur Deine Tags: Umbenennen aktualisiert Deine Notizen; ein vorhandener Name führt die Zuordnungen zusammen. Löschen entfernt den Tag aus Deinen Notizen, nicht die Notizen selbst. Andere Nutzer bleiben unberührt.';
$string['tags:notecount'] = '{$a} Notizen';
$string['tags:name'] = 'Tagname';
$string['tags:colour'] = 'Grundfarbe';
$string['tags:automatic'] = 'Automatische Farbe';
$string['tags:delete'] = 'Tag aus meinen Notizen entfernen';
$string['tags:deleteconfirm'] = 'Diesen Tag aus allen Deinen Notizen entfernen? Die Notizen bleiben erhalten.';
$string['tags:empty'] = 'Noch keine Tags. Du kannst sie direkt an einer Notiz hinzufügen.';
$string['tags:emptyname'] = 'Bitte einen Tagnamen eingeben.';
$string['tags:readonly'] = 'Dieser Tag wird auch in einer archivierten oder derzeit nicht bearbeitbaren Notiz verwendet. Du kannst seine Farbe ändern, aber erst nach Wiederherstellung oder Freigabe aller betroffenen Notizen umbenennen oder entfernen.';
$string['privacy:metadata:tagcolours'] = 'Eigene Grundfarben für private Side Notes-Tags, gespeichert als Moodle-Nutzereinstellungen.';
$string['editor:heading'] = 'Überschrift';
$string['editor:paragraph'] = 'Normaler Text';
$string['editor:bold'] = 'Fett';
$string['editor:italic'] = 'Kursiv';
$string['editor:bullet'] = 'Aufzählung';
$string['editor:ordered'] = 'Nummerierte Liste';
$string['editor:task'] = 'Checkliste';
$string['editor:source'] = 'Markdown-Rohtext';
$string['editor:visual'] = 'Visuell bearbeiten';
$string['editor:hint'] = 'Markdown-Kürzel funktionieren auch hier. Strg+V fügt Screenshots ein.';
$string['editor:unsupported'] = 'Dieser Inhalt bleibt zur Sicherheit im Markdown-Rohtextmodus, damit keine unbekannten Auszeichnungen verloren gehen.';
$string['quote:more'] = 'Mehr';
$string['quote:less'] = 'Weniger';
$string['tags:filtercount'] = '{$a} ausgewählt';
$string['search:removefilter'] = 'Filter entfernen: {$a}';

$string['image:close'] = 'Bildansicht schließen';
$string['image:previous'] = 'Vorheriges Bild';
$string['image:next'] = 'Nächstes Bild';
$string['image:zoom'] = 'Bild vergrößern';
$string['image:error'] = 'Das Bild konnte nicht geladen werden.';
$string['image:gallery'] = 'Screenshots der Notiz';
$string['image:thumbnail'] = 'Bild {$a} anzeigen';

$string['overviewshortcut'] = 'Notizübersicht öffnen';

// Contextual administration help.
$string['adminhelp'] = 'Hilfe zur Verwaltung';
$string['adminhelpmore'] = 'Mehr dazu im Hilfepanel';
$string['adminhelptopics'] = 'Hilfethemen';
$string['adminhelp_overview'] = 'Notizen bereitstellen';
$string['adminhelp_overview_article'] = '<p><strong>Kursnotizen aktivieren</strong> stellt persönliche Notizen in zugänglichen Kursen bereit. Kursverantwortliche können die Notizleiste für ihren Kurs oder einzelne Aktivitäten ausschalten. Vorhandene Notizen bleiben beim Ausschalten erhalten.</p><p><strong>In Kursen standardmäßig aktiv</strong> gilt für Kurse ohne eigene Side Notes-Einstellung, auch für bestehende Kurse. Hat ein Kurs eine ausdrückliche Einstellung, bleibt diese maßgeblich.</p><p><strong>Seitenweite Nutzung zusätzlich aktivieren</strong> erlaubt Notizen außerhalb von Kursen für Administratoren und Personen, die bereits das Systemrecht zur Verwendung von Side Notes haben. Der Schalter vergibt dieses Recht nicht und eröffnet keinen Zugriff auf fremde Notizen.</p>';
$string['adminhelp_lists'] = 'Übersichten und Seitengröße';
$string['adminhelp_lists_article'] = '<p><strong>Notizen pro Seite</strong> legt fest, wie viele Einträge auf einer Seite der Notizübersicht erscheinen. Wähle 12, 24 oder 48, oder <strong>Alle</strong> für die vollständige Liste.</p><p>Die Seitengröße begrenzt nicht die Zahl der gespeicherten Notizen und löscht oder archiviert nichts. Bei vielen Notizen kann <strong>Alle</strong> eine lange Seite erzeugen.</p>';

$string['taskdeletecoursenotes'] = 'Notizen eines gelöschten Kurses bereinigen';

$string['overview'] = $string['adminhelp_overview'];
$string['lists'] = $string['adminhelp_lists'];
$string['overview_help'] = 'Kursnotizen und seitenweite Nutzung sind getrennte Angebote. Eigene Kurseinstellungen haben Vorrang vor der Kursvorgabe; beim Ausschalten bleiben Notizen erhalten.';
$string['lists_help'] = 'Diese Einstellung bestimmt die Anzahl auf einer Seite der Notizübersicht. Sie begrenzt nicht die gespeicherten Notizen.';

// Administrator source-link relocation.
$string['sourcemove'] = 'Quellverweise nach einem Website-Umzug anpassen';
$string['sourcemoveold'] = 'Bisherige Moodle-Basis-URL';
$string['sourcemovetarget'] = 'Aktuelle Moodle-Basis-URL';
$string['sourcemoveold_help'] = 'Gib die bisherige Moodle-Adresse einschließlich eines Installationsunterordners an, ohne Abfrageparameter oder Fragment. Nur Verweise innerhalb genau dieser Installation werden zugeordnet.';
$string['sourcemoveintro'] = 'Nach einem Umzug von Moodle auf eine andere Domain oder in einen anderen Unterordner kannst Du die gespeicherten Quell- und Zitatverweise in Side Notes prüfen und anpassen. Das Ziel ist diese Moodle-Installation.';
$string['sourcemovepreview'] = 'Vorschau erstellen';
$string['sourcemovecontinue'] = 'Verarbeitung fortsetzen';
$string['sourcemovepause'] = 'Pausieren';
$string['sourcemoveresume'] = 'Fortsetzen';
$string['sourcemovestop'] = 'Beenden';
$string['sourcemoveapply'] = 'Zuordnung aus der Vorschau übernehmen';
$string['sourcemoveinvalidbase'] = 'Gib eine absolute Moodle-Basis-URL mit http:// oder https:// an, ohne Zugangsdaten, Abfrageparameter oder Fragment.';
$string['sourcemovebusy'] = 'Eine Anpassung der Quellverweise läuft bereits. Setze sie fort oder beende sie, bevor Du eine neue Vorschau erstellst.';
$string['sourcemovetargetchanged'] = 'Die aktuelle Moodle-Adresse hat sich geändert. Beende diesen Vorgang und erstelle eine neue Vorschau.';
$string['sourcemoveerror'] = 'Die letzte Aktion konnte nicht abgeschlossen werden. Prüfe die aktuelle Moodle-Adresse und setze die Verarbeitung ab dem gespeicherten Stand fort, oder beende den Vorgang und erstelle eine neue Vorschau.';
$string['sourcemovestatus_preview'] = 'Vorschau wird erstellt';
$string['sourcemovestatus_ready'] = 'Vorschau ist bereit';
$string['sourcemovestatus_apply'] = 'Quellverweise werden angepasst';
$string['sourcemovestatus_paused'] = 'Pausiert';
$string['sourcemovestatus_failed'] = 'Verarbeitung unterbrochen';
$string['sourcemovestatus_done'] = 'Anpassung abgeschlossen';
$string['sourcemovestatus_stopped'] = 'Beendet';
$string['sourcemovephase_preview'] = 'Vorschau';
$string['sourcemovephase_apply'] = 'Anpassung';
$string['sourcemoveprogress'] = '{$a->scanned} von {$a->total} Notizen geprüft';
$string['sourcemovechanged'] = 'Notizen mit Änderungen';
$string['sourcemovesources'] = 'Quellverweise';
$string['sourcemovequotes'] = 'Zitatverweise';
$string['sourcemovehashes'] = 'Seitenzuordnungen';
$string['sourcemoveskipped'] = 'Übersprungene ungültige Datensätze';
$string['sourcemoveready'] = 'Die Vorschau hat keine Notizen verändert. Mit „Zuordnung aus der Vorschau übernehmen“ bestätigst Du die oben angezeigte bisherige und aktuelle Basis-URL. Nach der Vorschau neu angelegte Notizen bleiben ausgenommen; die Zahlen können sich ändern, wenn Nutzer zwischenzeitlich Notizen bearbeiten oder löschen.';
$string['sourcemoverunning'] = 'Die Verarbeitung läuft automatisch weiter, solange diese Seite sichtbar ist. Du kannst pausieren, die Seite schließen und später zurückkehren oder ohne JavaScript „Verarbeitung fortsetzen“ verwenden.';
$string['sourcemovestophelp'] = '„Pausieren“ und „Beenden“ erhalten alle abgeschlossenen Verarbeitungsschritte. „Beenden“ schließt diesen Vorgang ab und macht bereits übernommene Änderungen nicht rückgängig. Notiztext, Eigentümer, Titel, Archivstatus, Tags, Anhänge und Zeitstempel bleiben erhalten.';
$string['adminhelp_sourcemove'] = 'Quellverweise nach einem Website-Umzug';
$string['adminhelp_sourcemove_article'] = '<p>Öffne nach einem Umzug von Moodle auf eine andere Domain oder in einen anderen Unterordner <strong>Quellverweise nach einem Website-Umzug anpassen</strong>. Gib die <strong>Bisherige Moodle-Basis-URL</strong> einschließlich des alten Unterordners an. Die <strong>Aktuelle Moodle-Basis-URL</strong> stammt aus dieser Installation und kann hier nicht geändert werden.</p><p><strong>Vorschau erstellen</strong> prüft vorhandene Notizen in Portionen von höchstens 200. Du siehst die Anzahl der Quellverweise, Zitatverweise und Seitenzuordnungen, ohne private Notizinhalte einzusehen oder Notizen zu verändern. Verweise auf andere Websites oder außerhalb des bisherigen Installationspfads bleiben erhalten.</p><p><strong>Zuordnung aus der Vorschau übernehmen</strong> bestätigt beide Adressen und startet die Anpassung. Zitatfragmente bleiben erhalten. Notiztext, Eigentümer, vollständige Titel, Archivstatus, Tags, Anhänge und Zeitstempel bleiben unverändert. Bestätigte alte Verweise bleiben während der Verarbeitung lesbar.</p><p>Lass die Seite für den automatischen Fortschritt sichtbar. <strong>Pausieren</strong> und <strong>Fortsetzen</strong> verwenden den gespeicherten Stand. Wenn Du die Seite schließt, enden weitere Anfragen; kehre zum Fortsetzen zurück. <strong>Beenden</strong> schließt den Vorgang ab, ohne abgeschlossene Schritte rückgängig zu machen. Nach der Vorschau neu angelegte Notizen bleiben ausgenommen; erstelle bei Bedarf eine weitere Vorschau. Neue Notizen müssen weiterhin die aktuelle Moodle-Adresse verwenden.</p>';
$string['sourcemove_help'] = 'Prüfe die genaue Zuordnung der bisherigen zur aktuellen Installation in der Vorschau. Die Verarbeitung ist begrenzt und fortsetzbar; beim Beenden bleiben abgeschlossene Schritte erhalten.';
