<?php

$file = $argv[1] ?? 'build/logs/clover.xml';
$minimum = (float)($argv[2] ?? 100);

if (!is_file($file)) {
    fwrite(STDERR, "Coverage file not found: {$file}\n");
    exit(1);
}

$xml = simplexml_load_file($file);
$metrics = $xml->project->metrics ?? null;
$covered = (int)($metrics['coveredstatements'] ?? 0);
$total = (int)($metrics['statements'] ?? 0);
$percent = $total > 0 ? ($covered / $total) * 100 : 0;

printf("Statement coverage: %.2f%% (%d/%d)\n", $percent, $covered, $total);

if ($percent + 0.0001 < $minimum) {
    fwrite(STDERR, "Coverage is below {$minimum}%.\n");
    exit(1);
}
