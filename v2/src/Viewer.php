<?php
declare(strict_types=1);

namespace App;

/**
 * Toda a saída HTML em streaming: cabeçalhos, estilo, linhas coloridas e mensagem final.
 *
 * Uso:
 *   $viewer->sendHeaders();
 *   $viewer->begin($sync->displayCommand());
 *   $viewer->line($linha, $isStderr);   // para cada linha
 *   $viewer->finish($exitCode);
 */
final class Viewer
{
    /** Cabeçalhos HTTP e desativação de buffers, para o streaming funcionar em tempo real. */
    public function sendHeaders(): void
    {
        header('Content-Type: text/html; charset=utf-8');
        header('X-Accel-Buffering: no'); // desativa o buffer do Nginx
        header('Cache-Control: no-cache');

        ob_implicit_flush(true);
        if (ob_get_level() > 0) {
            ob_end_flush();
        }
    }

    /** Abre o HTML, o CSS, a caixa com o comando e o <pre> onde as linhas serão escritas. */
    public function begin(string $displayCommand): void
    {
        echo '<!DOCTYPE html><html><head><meta charset="utf-8">';
        echo '<style>
body { background: #1e1e1e; color: #d4d4d4; font-family: monospace; font-size: 13px; padding: 1rem; margin: 0; }
pre { margin: 0; white-space: pre-wrap; word-break: break-all; }
.line-ok   { color: #4ec9b0; }
.line-warn { color: #dcdcaa; }
.line-err  { color: #f48771; }
.line-done { color: #6a9955; font-weight: bold; }
#cmd-box { background:#111; border:1px solid #333; padding:.5rem; margin-bottom:1rem; border-radius:4px; color:#9cdcfe; word-break:break-all; }
</style></head><body>';
        echo '<div id="cmd-box"><strong>Comando:</strong> ' . $this->e($displayCommand) . '</div>';
        echo '<pre>';
        flush();
    }

    /** Escreve uma linha de saída com a cor correspondente. */
    public function line(string $line, bool $isStderr): void
    {
        echo '<span class="' . $this->cssClass($line, $isStderr) . '">' . $this->e($line) . '</span>';
        flush();
    }

    /** Mensagem final conforme o código de saída (0 = sucesso) e fechamento do HTML. */
    public function finish(int $exitCode): void
    {
        $ok    = $exitCode === 0;
        $class = $ok ? 'line-done' : 'line-err';
        $msg   = $ok
            ? 'Migração concluída com sucesso.'
            : "Processo encerrado com código de saída: {$exitCode}";

        echo PHP_EOL . '<span class="' . $class . '">' . $this->e($msg) . '</span>';
        echo '</pre>';
        echo '<script>window.parent.document.getElementById("output-frame").scrollTop = 999999;</script>';
        echo '</body></html>';
    }

    /** Usada quando o processo nem consegue iniciar. */
    public function fatal(string $message): void
    {
        echo '<span class="line-err">Erro: ' . $this->e($message) . '</span>';
        echo '</pre></body></html>';
    }

    private function cssClass(string $line, bool $isStderr): string
    {
        return match (true) {
            $isStderr                                               => 'line-err',
            (bool) preg_match('/\berror\b/i', $line)                => 'line-err',
            (bool) preg_match('/\bwarning\b/i', $line)              => 'line-warn',
            (bool) preg_match('/\bEnd sync\b|\bSuccess\b/i', $line) => 'line-done',
            default                                                 => 'line-ok',
        };
    }

    private function e(string $text): string
    {
        return htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}