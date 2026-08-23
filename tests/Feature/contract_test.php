<?php

declare(strict_types=1);

it('passes the SDK conformance suite', function (): void {
    $output = shell_exec('php ' . escapeshellarg(__DIR__ . '/../unit.php'));
    expect($output)->toContain('PASS');
});
