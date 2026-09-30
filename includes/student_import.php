<?php

function student_import_normalize_header($value) {
    $value = trim(mb_strtolower((string) $value, 'UTF-8'));
    $value = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
    return preg_replace('/[^a-z0-9]+/', '', strtolower($value));
}

function student_import_header_key($value) {
    $header = student_import_normalize_header($value);
    $aliases = [
        'full_name' => ['nama', 'name', 'fullname', 'studentname'],
        'ic_number' => ['nokp', 'ic', 'icno', 'icnumber', 'identitycardnumber'],
        'matric_no' => ['nopend', 'matricno', 'matricnumber', 'studentid', 'registrationnumber'],
        'session' => ['sesisemasa', 'session', 'academicsession', 'sesi'],
        'department' => ['jabatan', 'department', 'dept']
    ];

    foreach ($aliases as $key => $names) {
        if (in_array($header, $names, true)) {
            return $key;
        }
    }

    return null;
}

function student_import_cell_column($reference) {
    preg_match('/^[A-Z]+/', strtoupper($reference), $matches);
    $column = 0;
    foreach (str_split($matches[0] ?? '') as $letter) {
        $column = ($column * 26) + ord($letter) - 64;
    }
    return $column - 1;
}

function student_import_read_xlsx($path) {
    $archive = new ZipArchive();
    if ($archive->open($path) !== true) {
        throw new RuntimeException('The XLSX file could not be opened.');
    }

    $workbook_xml = $archive->getFromName('xl/workbook.xml');
    $relationships_xml = $archive->getFromName('xl/_rels/workbook.xml.rels');
    if ($workbook_xml === false || $relationships_xml === false) {
        $archive->close();
        throw new RuntimeException('The XLSX workbook structure is invalid.');
    }

    $workbook = new DOMDocument();
    $relationships = new DOMDocument();
    if (!$workbook->loadXML($workbook_xml, LIBXML_NONET | LIBXML_NOBLANKS) || !$relationships->loadXML($relationships_xml, LIBXML_NONET | LIBXML_NOBLANKS)) {
        $archive->close();
        throw new RuntimeException('The XLSX workbook metadata is invalid.');
    }

    $sheet = (new DOMXPath($workbook))->query('//*[local-name()="sheet"]')->item(0);
    if (!$sheet) {
        $archive->close();
        throw new RuntimeException('The XLSX workbook has no worksheets.');
    }

    $relationship_id = $sheet->getAttributeNS('http://schemas.openxmlformats.org/officeDocument/2006/relationships', 'id');
    $relationship_nodes = (new DOMXPath($relationships))->query('//*[local-name()="Relationship"]');
    $sheet_target = '';
    foreach ($relationship_nodes as $relationship) {
        if ($relationship->getAttribute('Id') === $relationship_id) {
            $sheet_target = $relationship->getAttribute('Target');
            break;
        }
    }

    $sheet_path = str_starts_with($sheet_target, '/') ? ltrim($sheet_target, '/') : 'xl/' . $sheet_target;
    $sheet_xml = $sheet_target !== '' ? $archive->getFromName($sheet_path) : false;
    if ($sheet_xml === false) {
        $archive->close();
        throw new RuntimeException('The first XLSX worksheet could not be read.');
    }

    $shared_strings = [];
    $shared_xml = $archive->getFromName('xl/sharedStrings.xml');
    if ($shared_xml !== false) {
        $shared_document = new DOMDocument();
        if ($shared_document->loadXML($shared_xml, LIBXML_NONET | LIBXML_NOBLANKS)) {
            foreach ((new DOMXPath($shared_document))->query('//*[local-name()="si"]') as $item) {
                $text = '';
                foreach ((new DOMXPath($item->ownerDocument))->query('.//*[local-name()="t"]', $item) as $text_node) {
                    $text .= $text_node->textContent;
                }
                $shared_strings[] = $text;
            }
        }
    }

    $worksheet = new DOMDocument();
    if (!$worksheet->loadXML($sheet_xml, LIBXML_NONET | LIBXML_NOBLANKS)) {
        $archive->close();
        throw new RuntimeException('The first XLSX worksheet is invalid.');
    }
    $archive->close();

    $rows = [];
    foreach ((new DOMXPath($worksheet))->query('//*[local-name()="sheetData"]/*[local-name()="row"]') as $row_node) {
        $cells = [];
        foreach ((new DOMXPath($worksheet))->query('./*[local-name()="c"]', $row_node) as $cell) {
            $column = student_import_cell_column($cell->getAttribute('r'));
            $value_node = (new DOMXPath($worksheet))->query('./*[local-name()="v"]', $cell)->item(0);
            $value = $value_node ? $value_node->textContent : '';
            if ($cell->getAttribute('t') === 's') {
                $value = $shared_strings[(int) $value] ?? '';
            } elseif ($cell->getAttribute('t') === 'inlineStr') {
                $value = '';
                foreach ((new DOMXPath($worksheet))->query('.//*[local-name()="t"]', $cell) as $text_node) {
                    $value .= $text_node->textContent;
                }
            }
            $cells[$column] = $value;
        }
        $rows[] = ['number' => (int) $row_node->getAttribute('r'), 'cells' => $cells];
    }

    return $rows;
}

function student_import_read_csv($path) {
    $handle = fopen($path, 'rb');
    if (!$handle) {
        throw new RuntimeException('The CSV file could not be opened.');
    }

    $first_line = fgets($handle);
    if ($first_line === false) {
        fclose($handle);
        throw new RuntimeException('The CSV file is empty.');
    }
    $first_line = preg_replace('/^\xEF\xBB\xBF/', '', $first_line);
    $delimiter = ',';
    foreach ([',', ';', "\t"] as $candidate) {
        if (substr_count($first_line, $candidate) > substr_count($first_line, $delimiter)) {
            $delimiter = $candidate;
        }
    }

    rewind($handle);
    $rows = [];
    $line_number = 0;
    while (($cells = fgetcsv($handle, 0, $delimiter)) !== false) {
        $line_number++;
        if ($line_number === 1 && isset($cells[0])) {
            $cells[0] = preg_replace('/^\xEF\xBB\xBF/', '', $cells[0]);
        }
        $rows[] = ['number' => $line_number, 'cells' => $cells];
    }
    fclose($handle);
    return $rows;
}

function student_import_parse_file($path, $extension) {
    $rows = $extension === 'xlsx' ? student_import_read_xlsx($path) : student_import_read_csv($path);
    $header_map = null;
    $header_row_number = null;
    $required_headers = ['full_name', 'ic_number', 'matric_no', 'session', 'department'];

    foreach ($rows as $row) {
        if ($row['number'] > 25) {
            break;
        }
        $candidate = [];
        foreach ($row['cells'] as $index => $value) {
            $key = student_import_header_key($value);
            if ($key !== null) {
                $candidate[$key] = $index;
            }
        }
        if (count(array_intersect($required_headers, array_keys($candidate))) === count($required_headers)) {
            $header_map = $candidate;
            $header_row_number = $row['number'];
            break;
        }
    }

    if ($header_map === null) {
        throw new RuntimeException('Could not find columns for Name, IC No, Matric No, Session and Department in the first 25 rows.');
    }

    $records = [];
    foreach ($rows as $row) {
        if ($row['number'] <= $header_row_number) {
            continue;
        }
        $values = [];
        foreach ($header_map as $key => $column) {
            $values[$key] = trim((string) ($row['cells'][$column] ?? ''));
        }
        if (implode('', $values) === '') {
            continue;
        }
        $records[] = ['row' => $row['number'], 'values' => $values];
    }

    if (count($records) > 5000) {
        throw new RuntimeException('The file contains more than 5,000 student rows. Please split it into smaller files.');
    }

    return $records;
}