<?php
declare(strict_types=1);

namespace App;

use InvalidArgumentException;

/**
 * Recebe os dados do formulário, valida e monta o comando do imapsync.
 *
 * Uso:
 *   $sync = new ImapSync($_POST);   // lança InvalidArgumentException se faltar campo
 *   $sync->command();               // array de argumentos (para o Process)
 *   $sync->displayCommand();        // texto com senhas mascaradas (para o Viewer)
 */
final class ImapSyncFactory
{
    // Conta de origem
    private string $srcHost;
    private int $srcPort;
    private string $srcUser;
    private string $srcPass;
    private bool $srcSsl;

    // Conta de destino
    private string $dstHost;
    private int $dstPort;
    private string $dstUser;
    private string $dstPass;
    private bool $dstSsl;

    // Opções
    private bool $dryRun;
    private bool $delete2;
    private bool $noSslCheck;
    private string $exclude;
    private string $folder;

    /**
     * @param array<string, mixed> $data normalmente $_POST
     * @throws InvalidArgumentException se algum campo obrigatório estiver vazio ou algum valor não for uma string
     */
    public function __construct(array $data)
    {
        $this->srcHost = trim(self::stringValue($data, 'src_host'));
        $this->srcPort = (int) self::stringValue($data, 'src_port', '993');
        $this->srcUser = trim(self::stringValue($data, 'src_user'));
        $this->srcPass = self::stringValue($data, 'src_pass'); // sem trim: preserva espaços
        $this->srcSsl  = isset($data['src_ssl']);

        $this->dstHost = trim(self::stringValue($data, 'dst_host'));
        $this->dstPort = (int) self::stringValue($data, 'dst_port', '993');
        $this->dstUser = trim(self::stringValue($data, 'dst_user'));
        $this->dstPass = self::stringValue($data, 'dst_pass');
        $this->dstSsl  = isset($data['dst_ssl']);

        $this->dryRun     = isset($data['dry_run']);
        $this->delete2    = isset($data['delete2']);
        $this->noSslCheck = isset($data['no_ssl_check']);
        $this->exclude    = trim(self::stringValue($data, 'exclude'));
        $this->folder     = trim(self::stringValue($data, 'folder'));

        $this->validate();
    }

    /**
     * Argumentos do imapsync, sem escapeshellarg: devem ser executados
     * via proc_open com array (sem shell).
     *
     * @return list<string>
     */
    public function command(): array
    {
        $args = [
            'imapsync',
            '--host1', $this->srcHost,
            '--port1', (string) $this->srcPort,
            '--user1', $this->srcUser,
            '--password1', $this->srcPass,
            '--host2', $this->dstHost,
            '--port2', (string) $this->dstPort,
            '--user2', $this->dstUser,
            '--password2', $this->dstPass,
            '--nolog',
        ];

        if ($this->srcSsl)  { $args[] = '--ssl1'; }
        if ($this->dstSsl)  { $args[] = '--ssl2'; }
        if ($this->dryRun)  { $args[] = '--dry'; }
        if ($this->delete2) { $args[] = '--delete2'; }
        if ($this->noSslCheck) { $args[] = '--nossl1_warn'; $args[] = '--nossl2_warn'; }
        if ($this->exclude !== '') { $args[] = '--exclude'; $args[] = $this->exclude; }
        if ($this->folder !== '')  { $args[] = '--folder';  $args[] = $this->folder; }

        return $args;
    }

    /** Comando como texto, com as senhas trocadas por ****** (seguro para exibir). */
    public function displayCommand(): string
    {
        $parts = [];
        $maskNext = false;

        foreach ($this->command() as $arg) {
            if ($maskNext) {
                $parts[] = '******';
                $maskNext = false;
                continue;
            }
            $parts[] = escapeshellarg($arg);
            $maskNext = in_array($arg, ['--password1', '--password2'], true);
        }

        return implode(' ', $parts);
    }

    private function validate(): void
    {
        if (
            $this->srcHost === '' || $this->srcUser === '' || $this->srcPass === ''
            || $this->dstHost === '' || $this->dstUser === '' || $this->dstPass === ''
        ) {
            throw new InvalidArgumentException('Preencha todos os campos obrigatórios.');
        }
    }

    /** @param array<string, mixed> $data */
    private static function stringValue(array $data, string $key, string $default = ''): string
    {
        $value = $data[$key] ?? $default;
        if (!is_string($value)) {
            throw new InvalidArgumentException('Os valores do formulário devem ser strings.');
        }

        return $value;
    }
}
