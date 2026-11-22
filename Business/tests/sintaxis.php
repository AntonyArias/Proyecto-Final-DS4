<?php
declare(strict_types=1);
$total = 0;
foreach (['src', 'public', 'tests'] as $directory) {
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator(dirname(__DIR__) . '/' . $directory)) as $file) {
        if (!$file->isFile() || $file->getExtension() !== 'php') continue;
        exec(PHP_BINARY . ' -l ' . escapeshellarg($file->getPathname()) . ' 2>&1', $output, $status);
        if ($status !== 0) { fwrite(STDERR, implode("\n", $output)); exit(1); }
        $output = []; $total++;
    }
}
echo "OK: sintaxis de $total archivos PHP.\n";
