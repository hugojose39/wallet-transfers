<?php

declare(strict_types=1);

$threshold = (int) ($argv[1] ?? 70);
$clover = $argv[2] ?? 'coverage/clover.xml';

if (! file_exists($clover)) {
    fwrite(STDERR, "Coverage file not found: {$clover}\n");
    exit(1);
}

$xml = simplexml_load_file($clover);
$metrics = $xml->project->metrics;
$total = (int) $metrics['statements'];
$covered = (int) $metrics['coveredstatements'];

if ($total === 0) {
    fwrite(STDERR, "No statements found in coverage report.\n");
    exit(1);
}

$pct = round($covered / $total * 100, 2);
echo PHP_EOL . "Coverage: {$pct}%" . PHP_EOL;

if ($pct < $threshold) {
    echo "FAIL: cobertura abaixo de {$threshold}% (atual: {$pct}%)" . PHP_EOL;
    exit(1);
}

echo "OK: cobertura atinge o minimo de {$threshold}%" . PHP_EOL;
