<?php

declare(strict_types=1);

namespace App;

final class ImapSyncFactory
{
    public function create(string $srcHost, int $srcPort, string $srcUser, string $srcPass, bool $srcSsl,string $dstHost, int $dstPort, string $dstUser, string $dstPass, bool $dstSsl, bool $dryRun, bool $delete2, bool $noSslCheck,string $exclude, string $folder): array
    {
        $cmd = [
            'imapsync',
            '--host1', $srcHost,
            '--port1', (string) $srcPort,
            '--user1', $srcUser,
            '--password1', $srcPass,
            '--host2', $dstHost,
            '--port2', (string) $dstPort,
            '--user2', $dstUser,
            '--password2', $dstPass,
            '--nolog',
        ];

        if ($srcSsl)     { $cmd[] = '--ssl1'; }
        if ($dstSsl)     { $cmd[] = '--ssl2'; }
        if ($dryRun)     { $cmd[] = '--dry'; }
        if ($delete2)    { $cmd[] = '--delete2'; }
        if ($noSslCheck) { $cmd[] = '--nossl1_warn'; $cmd[] = '--nossl2_warn'; }
        if ($exclude !== '') { $cmd[] = '--exclude'; $cmd[] = $exclude; }
        if ($folder !== '')  { $cmd[] = '--folder';  $cmd[] = $folder; }
    
        return $cmd;
    }
}