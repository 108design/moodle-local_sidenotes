<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * German language strings for the personal Side Notes fork.
 *
 * @package     local_sidenotes
 * @copyright   2026 Andreas Giesen
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// Contextual administration help.
// Administrator source-link relocation.

$string['access:denied'] = 'Side Notes ist für dich in diesem Bereich nicht verfügbar.';
$string['adminhelp_lists_article'] = '<p><strong>Notizen pro Seite</strong> legt fest, wie viele Einträge auf einer Seite der Notizübersicht erscheinen. Wähle 12, 24 oder 48, oder <strong>Alle</strong> für die vollständige Liste.</p><p>Die Seitengröße begrenzt nicht die Zahl der gespeicherten Notizen und löscht oder archiviert nichts. Bei vielen Notizen kann <strong>Alle</strong> eine lange Seite erzeugen.</p>';
$string['adminhelp_notes_article'] = '<p>Öffne die Notizleiste über den Stift am rechten Rand. Das Plus legt eine Notiz zur aktuellen Seite an, der Ordner öffnet die Notizübersicht. Globale Notizen stehen im Tab Kursweit oder Websiteweit.</p><p>Das Tag-Feld schlägt deine vorhandenen privaten Tags vor. Einen neuen Tag gibst du ein und fügst ihn mit dem Plus hinzu. Änderungen in der Seitenleiste werden automatisch gespeichert.</p><p>Mit dem Häkchen neben Löschen archivierst du eine erledigte Notiz. Die Seitenleiste speichert offenen Text vorher; Tags und Screenshots bleiben erhalten. Im Archiv der Notizübersicht kannst du die Notiz wiederherstellen. Schlägt das Speichern fehl, bleibt dein Entwurf erhalten.</p><p>In der Notizübersicht findest du Notizen über Kurs-, Tag- und Textfilter. Ausgewählte Tags müssen alle zutreffen. Mit dem Stift bearbeitest du Text, mit Änderungen speichern sicherst du ihn. Das Erledigt-Häkchen ist von der Auswahl für Sammelaktionen getrennt.</p><p>Notizen ohne Seitenbindung bleiben in der Übersicht. Globale Anzeige zeigt eine Notiz auf deinen erlaubten Seiten; andere Personen können sie nicht sehen. Das Archiv bewahrt erledigte Notizen auf. Endgültiges Löschen und Leeren des Archivs verlangen eine Bestätigung. Ausgewählte Notizen kannst du als Markdown oder PDF exportieren.</p><p>Behaltene Kursnotizen zeigen den ursprünglichen Kursnamen. Sie sind schreibgeschützt und können exportiert oder gelöscht werden. Das Behalten einer Notiz erteilt keine Kursrechte.</p><p>Diese Seite und Kursweit bzw. Websiteweit filtern deine eigenen Notizen. Die Suche bleibt beim Tabwechsel erhalten; die Zahlen zeigen Treffer je Bereich. Neue Notizen, Textauswahl und Alt+Q öffnen Diese Seite. Der Zähler an der Randlasche zählt weiterhin nur gespeicherte Seitennotizen. Beim Wechsel zur Hilfe oder zu einem Moodle-Drawer bleiben offene Entwürfe erhalten. Hilfe bleibt ein eigenes Panel.</p><p>In den Notizeinstellungen der Übersicht kannst du Neue Bilder vor dem Hochladen verkleinern auswählen. Die Einstellung ist zunächst ausgeschaltet und gilt für deine neuen Bilder im Seitenpanel und in der Übersicht.</p>';
$string['adminhelp_sourcemove_article'] = '<p>Öffne nach einem Umzug von Moodle auf eine andere Domain oder in einen anderen Unterordner <strong>Quellverweise nach einem Website-Umzug anpassen</strong>. Gib die <strong>Bisherige Moodle-Basis-URL</strong> einschließlich des alten Unterordners an. Die <strong>Aktuelle Moodle-Basis-URL</strong> stammt aus dieser Installation und kann hier nicht geändert werden.</p><p><strong>Vorschau erstellen</strong> prüft vorhandene Notizen in Portionen von höchstens 200. Du siehst die Anzahl der Quellverweise, Zitatverweise und Seitenzuordnungen, ohne private Notizinhalte einzusehen oder Notizen zu verändern. Verweise auf andere Websites oder außerhalb des bisherigen Installationspfads bleiben erhalten.</p><p><strong>Zuordnung aus der Vorschau übernehmen</strong> bestätigt beide Adressen und startet die Anpassung. Zitatfragmente bleiben erhalten. Notiztext, Eigentümer, vollständige Titel, Archivstatus, Tags, Anhänge und Zeitstempel bleiben unverändert. Bestätigte alte Verweise bleiben während der Verarbeitung lesbar.</p><p>Lass die Seite für den automatischen Fortschritt sichtbar. <strong>Pausieren</strong> und <strong>Fortsetzen</strong> verwenden den gespeicherten Stand. Wenn du die Seite schließt, enden weitere Anfragen; kehre zum Fortsetzen zurück. <strong>Beenden</strong> schließt den Vorgang ab, ohne abgeschlossene Schritte rückgängig zu machen. Nach der Vorschau neu angelegte Notizen bleiben ausgenommen; erstelle bei Bedarf eine weitere Vorschau. Neue Notizen müssen weiterhin die aktuelle Moodle-Adresse verwenden.</p>';
$string['archive:changed'] = 'Das Archiv wurde inzwischen geändert. Bitte prüfe die aktuelle Ansicht und bestätige das Löschen erneut.';
$string['archive:draftblocked'] = 'Diese Notiz wurde archiviert. Dein lokaler Textentwurf bleibt erhalten; bitte kopiere ihn vor dem Schließen.';
$string['archive:empty'] = 'Dein Archiv ist leer.';
$string['archive:hint'] = 'Erledigte Notizen bleiben hier erhalten. Entferne das Häkchen im Header, um eine Notiz wiederherzustellen.';
$string['center:addhint'] = 'Neue Notizen bleiben in der Übersicht, bis du sie bewusst global schaltest.';
$string['error:busy'] = 'Die Notiz wird gerade gespeichert. Bitte versuche es erneut.';
$string['error:conflict'] = 'Der Text wurde inzwischen anderweitig geändert. Deine Eingabe bleibt erhalten. Bitte kopiere sie vor dem Neuladen und gleiche die Änderungen ab.';
$string['import:explanation'] = 'Kopiere deine eigenen QuickNote-Notizen nach Side Notes. Text, Zitate, Seiten-/Kursbezug und Zeitstempel bleiben erhalten; die Originalnotizen bleiben unverändert. Ursprünglicher Klartext bleibt Klartext. Markdown, Tags und Screenshots des erweiterten Forks werden ebenfalls übernommen. Bereits importierte Notizen werden übersprungen, auch wenn du die Kopie gelöscht hast. Spätere Änderungen am Original überschreiben Side Notes nicht. Rechte und Plugin-Einstellungen werden nicht importiert. Notizen aus nicht verfügbaren Kursen bleiben schreibgeschützt.';
$string['import:offer'] = '{$a} Deiner QuickNote-Notizen können nach Side Notes übernommen werden.';
$string['sourcemove_help'] = 'Prüfe die genaue Zuordnung der bisherigen zur aktuellen Installation in der Vorschau. Die Verarbeitung ist begrenzt und fortsetzbar; beim Beenden bleiben abgeschlossene Schritte erhalten.';
$string['sourcemovebusy'] = 'Eine Anpassung der Quellverweise läuft bereits. Setze sie fort oder beende sie, bevor du eine neue Vorschau erstellst.';
$string['sourcemoveerror'] = 'Die letzte Aktion konnte nicht abgeschlossen werden. Prüfe die aktuelle Moodle-Adresse und setze die Verarbeitung ab dem gespeicherten Stand fort, oder beende den Vorgang und erstellst eine neue Vorschau.';
$string['sourcemoveintro'] = 'Nach einem Umzug von Moodle auf eine andere Domain oder in einen anderen Unterordner kannst du die gespeicherten Quell- und Zitatverweise in Side Notes prüfen und anpassen. Das Ziel ist diese Moodle-Installation.';
$string['sourcemoveinvalidbase'] = 'Gib eine absolute Moodle-Basis-URL mit http:// oder https:// an, ohne Zugangsdaten, Abfrageparameter oder Fragment.';
$string['sourcemoveold_help'] = 'Gib die bisherige Moodle-Adresse einschließlich eines Installationsunterordners an, ohne Abfrageparameter oder Fragment. Nur Verweise innerhalb genau dieser Installation werden zugeordnet.';
$string['sourcemoveready'] = 'Die Vorschau hat keine Notizen verändert. Mit „Zuordnung aus der Vorschau übernehmen“ bestätigst du die oben angezeigte bisherige und aktuelle Basis-URL. Nach der Vorschau neu angelegte Notizen bleiben ausgenommen; die Zahlen können sich ändern, wenn Nutzer/innen zwischenzeitlich Notizen bearbeiten oder löschen.';
$string['sourcemoverunning'] = 'Die Verarbeitung läuft automatisch weiter, solange diese Seite sichtbar ist. Du kannst pausieren, die Seite schließen und später zurückkehren oder ohne JavaScript „Verarbeitung fortsetzen“ verwenden.';
$string['sourcemovetargetchanged'] = 'Die aktuelle Moodle-Adresse hat sich geändert. Beende diesen Vorgang und erstelle eine neue Vorschau.';
$string['tags:deleteconfirm'] = 'Diesen Tag aus allen deinen Notizen entfernen? Die Notizen bleiben erhalten.';
$string['tags:empty'] = 'Noch keine Tags. Du kannst sie direkt an einer Notiz hinzufügen.';
$string['tags:managerhint'] = 'Nur deine Tags: Umbenennen aktualisiert deine Notizen; ein vorhandener Name führt die Zuordnungen zusammen. Löschen entfernt den Tag aus deinen Notizen, nicht die Notizen selbst. Andere Nutzer/innen bleiben unberührt.';
$string['tags:readonly'] = 'Dieser Tag wird auch in einer archivierten oder derzeit nicht bearbeitbaren Notiz verwendet. Du kannst seine Farbe ändern, aber erst nach Wiederherstellung oder Freigabe aller betroffenen Notizen umbenennen oder entfernen.';
$string['retention:sourceunavailable'] = 'Aus {$a} behalten. Die ursprüngliche Kursquelle ist nicht verfügbar. Du kannst diese persönliche Kopie lesen, exportieren oder löschen.';
$string['retention_help'] = 'Lege getrennt fest, was nach der letzten Einschreibung und nach einer Kurslöschung geschieht. Behaltene Notizen bleiben privat und sind in der persönlichen Übersicht schreibgeschützt.';
$string['retention:overviewhint'] = 'Deine behaltenen Kursnotizen sind privat und schreibgeschützt. Du kannst diese exportieren oder löschen.';
$string['sidebar:globalempty'] = 'Hier hast du noch keine Notizen. In der Übersicht wählst du, wo eine Notiz erscheint.';
$string['screenshot:quotaexceeded'] = 'Dein Bildspeicher für Side Notes ({$a} MiB) ist ausgeschöpft. Lösche Bilder oder bitte die Administration, die Grenze zu erhöhen.';
$string['note:retry'] = 'Dein Entwurf bleibt geöffnet. Prüfe die Verbindung und bearbeite die Notiz erneut, um das Speichern nochmals zu versuchen.';
$string['adminhelp_imagepreferences_article'] = '<p>Öffne die Notizeinstellungen in der Notizübersicht und wähle Neue Bilder vor dem Hochladen verkleinern. Die Einstellung ist zunächst ausgeschaltet und wird für dein Nutzerkonto gespeichert. Sie gilt für neue Bilder, die du im Seitenpanel oder in der Übersicht einfügst.</p><p>PNG-, JPEG- und WebP-Bilder können auf höchstens 2048 Pixel an der längsten Seite verkleinert werden. JPEG und WebP verwenden 85 % Qualität. Side Notes übernimmt das verkleinerte Bild nur, wenn es weniger Speicher benötigt. Prüfe die Lesbarkeit kleiner Schrift. GIFs und bereits gespeicherte Bilder bleiben unverändert.</p>';
