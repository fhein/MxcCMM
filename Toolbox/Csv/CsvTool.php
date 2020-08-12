<?php

namespace MxcCommons\Toolbox\Csv;

class CsvTool
{
    public function export(string $fileName, array $array, string $delimiter = ';') {
        $content = $this->arrayToCsv($array, $delimiter);
        file_put_contents($fileName, $content);
    }

    public function import(string $fileName, string $delimiter = ';') : array
    {
        $content = file_get_contents($fileName);
        return $this->csvToArray($content, $delimiter);
    }

    /**
     * Convert csv content to an associative array
     * The first line of the csv file should contain column headers
     * which are used as array keys.
     *
     * @param string $content
     * @param string $delimiter
     *
     * @return array
     */
    public function csvToArray(string $content, string $delimiter)
    {
        $entities = str_getcsv(html_entity_decode($content), "\n");

        $headers = null;
        foreach ($entities as &$entity) {
            $entity = str_getcsv($entity, $delimiter);
            if (! $headers) {
                $headers = $entity;
                continue;
            }
            $entity = array_combine($headers, $entity);
        }
        // remove header entity
        array_shift($entities);
        return $entities;
    }

    public function arrayToCsv(array $array, string $delimiter)
    {
        if (count($array) == 0) {
            return null;
        }
        ob_start();
        $df = fopen("php://output", 'w');
        fputcsv($df, array_keys(reset($array)), $delimiter);
        foreach ($array as $row) {
            fputcsv($df, $row, $delimiter);
        }
        fclose($df);
        return ob_get_clean();
    }
}