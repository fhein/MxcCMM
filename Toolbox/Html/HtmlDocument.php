<?php

namespace MxcCommons\Toolbox\Html;

use DOMDocument;

class HtmlDocument
{
    // because table attributes are deprecated this should be replaced in the future
    private $tableStart = '<table frame="void" rules="rows" cellspacing="0" cellpadding="2" border="5"><tbody>';
    private $tableRow = '<tr><td>%s</td><td>^%s</td></tr>';
    private $tableEnd = '</tbody></table>';

    public function buildTable(array $content)
    {
        $rows = [];
        foreach ($content as $key => $value) {
            $rows[] = sprintf($this->tableRow, $key, $value);
        }
        $rows = implode("\n", $rows);
        return $this->tableStart . '\n' . $rows . '\n' . $this->tableEnd;
    }

    /**
     * Findet Html-Snippets nach Name des Tags. Liefert ein Array, dessen Schlüssel
     * die StartPosition im Text und dessen Wert das html des Tags ist.
     *
     * Achtung: Diese Funktion ist nicht rekursiv und funktioniert daher nicht mit verschachtelten Tags.
     *
     * @param string $tagName
     * @param string $text
     * @return array
     */
    public function getHtmlByTagName(string $tagName, string $text)
    {
        $startTag = '<' . $tagName;
        $endTag = '</' . $tagName . '>';
        $endLen = strlen($endTag);
        $offset = 0;
        $tags = [];
        $startPos = strpos($text, $startTag, $offset);
        while ($startPos !== false) {
            $endPos = strpos($text, $endTag, $startPos);
            if ($endPos === false) break;
            $len = $endPos - $startPos + $endLen;
            $tag = substr($text, $startPos, $len);
            $tags[$startPos] = $tag;
            $offset += $startPos + $len;
            $startPos = @strpos($text, $startTag, $offset);
        }
        return $tags;
    }

    /**
     * Extrahiere aus einem Html-Text alle Tabellen und liefere das Ergebnis als Array zurück.
     * Die linke Spalte der Tabelle dient als Array-Key, die anderen Spalten werden zu einem String implodiert,
     * mit MXC_DELIMITER_1 als Trenner zwischen den Zellen.
     *
     * @param string $text
     * @return array
     */
    public function getTablesAsArray(string $text)
    {
        $document = $this->getDOMDocument($text);
        $result = [];
        $tables = $document->getElementsByTagName('table');
        if ($tables->count() === 0) return $result;

        foreach ($tables as $table) {
            $rows = $table->getElementsByTagName('tr');
            if ($rows->count() === 0) continue;
            $i = 0;
            $temp = [];
            foreach ($rows as $row) {
                $cells = $row->getElementsByTagName('td');
                foreach ($cells as $cell) {
                    $temp[$i][] = trim($cell->textContent);
                }
                $i++;
            }
            $array = [];
            foreach ($temp as $line) {
                $topic = $line[0];
                unset($line[0]);
                $line = implode('#!#', $line);
                $array[$topic] = $line;
            }
            $result[] = $array;
        }
        $this->disposeDOMDocument($document);
        return $result;
    }

    /**
     * This function returns the last unsigned list (<ul>) from the product description
     * assuming that this is the scope of delivery.
     *
     * @param string $text
     * @return mixed|null
     */
    public function getScopeOfDelivery(string $text)
    {
        $uls = $this->getHtmlByTagName('ul', $text);
        return empty($uls) ? null : end($uls);
    }

    protected function getDOMDocument(string $text)
    {
        $text = preg_replace('~<br/?>~', "\n", $text);
        libxml_use_internal_errors(true);
        $document = new DOMDocument();
        $document->loadHTML($text);
        return $document;
    }

    protected function disposeDOMDocument($document)
    {
        unset($document);
        libxml_clear_errors();
        libxml_use_internal_errors(false);
    }
}