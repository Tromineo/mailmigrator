<?php
declare(strict_types=1);

namespace App;

use Generator;
use RuntimeException;

final class ProcessoMigrador
{
    /** @return Generator<int, array{0:string,1:string}, mixed, int> */
    public function run(array $argv): Generator
    {
        $proc = proc_open($argv, [
            0 => ['file', '/dev/null', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ], $pipes);

        if (!is_resource($proc)) {
            throw new RuntimeException('Não foi possível iniciar o processo.');
        }

        stream_set_blocking($pipes[1], false);
        stream_set_blocking($pipes[2], false);
        $names = [1 => 'stdout', 2 => 'stderr'];
        $buf   = [1 => '', 2 => ''];

        try {
            while (!feof($pipes[1]) || !feof($pipes[2])) {
                $read = array_filter([$pipes[1], $pipes[2]], fn ($p) => !feof($p));
                $w = $e = null;
                if (stream_select($read, $w, $e, 1) === false) break;

                foreach ($read as $stream) {
                    $id    = $stream === $pipes[1] ? 1 : 2;
                    $chunk = fread($stream, 8192);
                    if ($chunk === false || $chunk === '') continue;

                    $buf[$id] .= $chunk;
                    while (($pos = strpos($buf[$id], "\n")) !== false) {
                        yield [$names[$id], substr($buf[$id], 0, $pos + 1)];
                        $buf[$id] = substr($buf[$id], $pos + 1);
                    }
                }
            }
            foreach ($buf as $id => $rest) {          // sobra sem \n final
                if ($rest !== '') yield [$names[$id], $rest];
            }
        } finally {
            $status = proc_get_status($proc);
            if ($status['running']) proc_terminate($proc);
            fclose($pipes[1]);
            fclose($pipes[2]);
            $code = proc_close($proc);
        }

        return $code;
    }
}