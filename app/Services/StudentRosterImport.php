<?php

namespace App\Services;

use DOMDocument;
use DOMXPath;
use RuntimeException;
use ZipArchive;

class StudentRosterImport
{
    private const ALIASES = [
        'full_name' => ['nama', 'name', 'fullname', 'studentname'],
        'ic_number' => ['nokp', 'ic', 'icno', 'icnumber', 'identitycardnumber'],
        'matric_no' => ['nopend', 'matricno', 'matricnumber', 'studentid', 'registrationnumber'],
        'session' => ['sesisemasa', 'session', 'academicsession', 'sesi'],
        'department' => ['jabatan', 'department', 'dept'],
    ];

    public function parse(string $path, string $extension): array
    {
        $rows = $extension === 'xlsx' ? $this->readXlsx($path) : $this->readCsv($path);
        $header = null;
        $headerRow = 0;
        foreach ($rows as $row) {
            if ($row['number'] > 25) break;
            $candidate = [];
            foreach ($row['cells'] as $index => $value) {
                $key = $this->headerKey((string) $value);
                if ($key) $candidate[$key] = $index;
            }
            if (count($candidate) === count(self::ALIASES)) {
                $header = $candidate;
                $headerRow = $row['number'];
                break;
            }
        }
        if (!$header) throw new RuntimeException('Required student roster columns are missing.');

        $records = [];
        foreach ($rows as $row) {
            if ($row['number'] <= $headerRow) continue;
            $values = [];
            foreach ($header as $key => $column) $values[$key] = trim((string) ($row['cells'][$column] ?? ''));
            if (implode('', $values) !== '') $records[] = ['row' => $row['number'], 'values' => $values];
        }
        if (count($records) > 5000) throw new RuntimeException('Roster contains more than 5,000 rows.');
        return $records;
    }

    private function headerKey(string $value): ?string
    {
        $normalized = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', mb_strtolower(trim($value), 'UTF-8')) ?: $value;
        $normalized = preg_replace('/[^a-z0-9]+/', '', strtolower($normalized));
        foreach (self::ALIASES as $key => $aliases) if (in_array($normalized, $aliases, true)) return $key;
        return null;
    }

    private function readCsv(string $path): array
    {
        $handle = fopen($path, 'rb');
        if (!$handle) throw new RuntimeException('CSV could not be opened.');
        $first = preg_replace('/^\xEF\xBB\xBF/', '', (string) fgets($handle));
        $delimiter = ',';
        foreach ([',', ';', "\t"] as $candidate) if (substr_count($first, $candidate) > substr_count($first, $delimiter)) $delimiter = $candidate;
        rewind($handle);
        $rows = [];
        $number = 0;
        while (($cells = fgetcsv($handle, 0, $delimiter)) !== false) {
            $number++;
            if ($number === 1 && isset($cells[0])) $cells[0] = preg_replace('/^\xEF\xBB\xBF/', '', $cells[0]);
            $rows[] = ['number' => $number, 'cells' => $cells];
        }
        fclose($handle);
        return $rows;
    }

    private function readXlsx(string $path): array
    {
        $zip = new ZipArchive();
        if ($zip->open($path) !== true) throw new RuntimeException('XLSX could not be opened.');
        try {
            $workbook = $this->xml($zip->getFromName('xl/workbook.xml'));
            $relations = $this->xml($zip->getFromName('xl/_rels/workbook.xml.rels'));
            $sheet = (new DOMXPath($workbook))->query('//*[local-name()="sheet"]')->item(0);
            if (!$sheet) throw new RuntimeException('XLSX has no worksheet.');
            $relId = $sheet->getAttributeNS('http://schemas.openxmlformats.org/officeDocument/2006/relationships', 'id');
            $target = '';
            foreach ((new DOMXPath($relations))->query('//*[local-name()="Relationship"]') as $relation) {
                if ($relation->getAttribute('Id') === $relId) { $target = $relation->getAttribute('Target'); break; }
            }
            $sheetPath = str_starts_with($target, '/') ? ltrim($target, '/') : 'xl/'.ltrim($target, '/');
            $worksheet = $this->xml($zip->getFromName($sheetPath));
            $shared = [];
            $sharedXml = $zip->getFromName('xl/sharedStrings.xml');
            if ($sharedXml !== false) {
                $sharedDoc = $this->xml($sharedXml);
                foreach ((new DOMXPath($sharedDoc))->query('//*[local-name()="si"]') as $item) $shared[] = $this->textNodes($item);
            }
            $rows = [];
            foreach ((new DOMXPath($worksheet))->query('//*[local-name()="sheetData"]/*[local-name()="row"]') as $row) {
                $cells = [];
                foreach ((new DOMXPath($worksheet))->query('./*[local-name()="c"]', $row) as $cell) {
                    preg_match('/^[A-Z]+/', strtoupper($cell->getAttribute('r')), $letters);
                    $column = 0;
                    foreach (str_split($letters[0] ?? '') as $letter) $column = $column * 26 + ord($letter) - 64;
                    $value = (new DOMXPath($worksheet))->query('./*[local-name()="v"]', $cell)->item(0)?->textContent ?? '';
                    if ($cell->getAttribute('t') === 's') $value = $shared[(int) $value] ?? '';
                    elseif ($cell->getAttribute('t') === 'inlineStr') $value = $this->textNodes($cell);
                    $cells[$column - 1] = $value;
                }
                $rows[] = ['number' => (int) $row->getAttribute('r'), 'cells' => $cells];
            }
            return $rows;
        } finally {
            $zip->close();
        }
    }

    private function xml(string|false $source): DOMDocument
    {
        if ($source === false) throw new RuntimeException('XLSX workbook metadata is missing.');
        $document = new DOMDocument();
        if (!$document->loadXML($source, LIBXML_NONET | LIBXML_NOBLANKS)) throw new RuntimeException('XLSX contains invalid XML.');
        return $document;
    }

    private function textNodes(\DOMNode $node): string
    {
        $text = '';
        foreach ((new DOMXPath($node->ownerDocument))->query('.//*[local-name()="t"]', $node) as $textNode) $text .= $textNode->textContent;
        return $text;
    }
}
