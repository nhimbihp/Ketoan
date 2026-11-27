<?php

namespace App\Services\Einvoice;

use RuntimeException;
use ZipArchive;

/** Port of UnzipFile: take the .xml file(s) out of the zip returned by export-xml. */
class XmlExtractor
{
    /**
     * @return list<array{name: string, content: string}> renamed to "{baseName}.xml" ("{baseName}_2.xml"... if several)
     */
    public function extract(string $zipContent, string $baseName): array
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'hddt');
        file_put_contents($tempFile, $zipContent);

        $zip = new ZipArchive;
        if ($zip->open($tempFile) !== true) {
            @unlink($tempFile);
            throw new RuntimeException('File tải về không phải file zip hợp lệ.');
        }

        $files = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $entryName = $zip->getNameIndex($i);
            if (! str_ends_with(strtolower($entryName), '.xml')) {
                continue;
            }
            $suffix = $files ? '_'.(count($files) + 1) : '';
            $files[] = ['name' => $baseName.$suffix.'.xml', 'content' => $zip->getFromIndex($i)];
        }

        $zip->close();
        @unlink($tempFile);

        return $files;
    }
}
