<?php
declare(strict_types=1); // Ativa tipagem estrita: erros de tipo passam a lançar exceções em vez de serem silenciados

// Rejeita qualquer requisição que não seja POST, retornando 405 Method Not Allowed
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);          // Define o código HTTP de resposta como 405
    exit('Method Not Allowed');       // Encerra o script com mensagem de erro
}

// --- Coleta e sanitização dos inputs ---

// Servidor IMAP de origem (remove espaços em branco das bordas; usa string vazia se não enviado)
$srcHost    = trim($_POST['src_host'] ?? '');
// Porta IMAP de origem (converte para inteiro; padrão 993 = IMAPS)
$srcPort    = (int) ($_POST['src_port'] ?? 993);
// Usuário/e-mail da conta de origem
$srcUser    = trim($_POST['src_user'] ?? '');
// Senha da conta de origem (não é feito trim para preservar espaços intencionais)
$srcPass    = $_POST['src_pass'] ?? '';
// Flag: true se o checkbox SSL da origem estiver marcado no formulário
$srcSsl     = isset($_POST['src_ssl']);

// Servidor IMAP de destino
$dstHost    = trim($_POST['dst_host'] ?? '');
// Porta IMAP de destino (padrão 993)
$dstPort    = (int) ($_POST['dst_port'] ?? 993);
// Usuário/e-mail da conta de destino
$dstUser    = trim($_POST['dst_user'] ?? '');
// Senha da conta de destino
$dstPass    = $_POST['dst_pass'] ?? '';
// Flag: true se o checkbox SSL do destino estiver marcado
$dstSsl     = isset($_POST['dst_ssl']);

// Flag: execução em modo simulação (--dry), nenhum e-mail é copiado de fato
$dryRun     = isset($_POST['dry_run']);
// Flag: apaga no destino mensagens que não existem mais na origem (--delete2)
$delete2    = isset($_POST['delete2']);
// Flag: desativa avisos de certificado SSL inválido em ambos os servidores
$noSslCheck = isset($_POST['no_ssl_check']);
// Padrão regex de pastas a excluir da sincronização (ex.: "Spam|Lixeira")
$exclude    = trim($_POST['exclude'] ?? '');
// Sincroniza apenas esta pasta específica, se informada
$folder     = trim($_POST['folder'] ?? '');

// --- Validação básica ---

// Se qualquer campo obrigatório estiver vazio, retorna 400 Bad Request e encerra
if (!$srcHost || !$srcUser || !$srcPass || !$dstHost || !$dstUser || !$dstPass) {
    http_response_code(400);                            // Código HTTP de requisição inválida
    exit('Preencha todos os campos obrigatórios.');     // Encerra informando o motivo
}

// --- Monta o comando ---

// Inicia o array com o executável imapsync e seus argumentos obrigatórios
$cmd = [
    'imapsync',                               // Ferramenta de linha de comando para migração IMAP
    '--host1', escapeshellarg($srcHost),      // Hostname do servidor de origem (escapeshellarg previne injeção de shell)
    '--port1', (string) $srcPort,             // Porta de origem convertida para string (exigido pelo array de args)
    '--user1', escapeshellarg($srcUser),      // Usuário da conta de origem com escape seguro
    '--password1', escapeshellarg($srcPass),  // Senha da origem com escape seguro
    '--host2', escapeshellarg($dstHost),      // Hostname do servidor de destino
    '--port2', (string) $dstPort,             // Porta de destino
    '--user2', escapeshellarg($dstUser),      // Usuário da conta de destino
    '--password2', escapeshellarg($dstPass),  // Senha do destino
    '--nolog',                                // Desativa criação de arquivo de log pelo imapsync
];

if ($srcSsl)     { $cmd[] = '--ssl1'; }                                    // Habilita SSL/TLS na conexão com a origem
if ($dstSsl)     { $cmd[] = '--ssl2'; }                                    // Habilita SSL/TLS na conexão com o destino
if ($dryRun)     { $cmd[] = '--dry'; }                                     // Modo simulação: não realiza cópias reais
if ($delete2)    { $cmd[] = '--delete2'; }                                 // Apaga no destino o que não existe na origem
if ($noSslCheck) { $cmd[] = '--nossl1_warn'; $cmd[] = '--nossl2_warn'; }   // Ignora avisos de certificado SSL inválido
if ($exclude !== '') { $cmd[] = '--exclude'; $cmd[] = escapeshellarg($exclude); } // Exclui pastas que correspondam ao padrão
if ($folder !== '')  { $cmd[] = '--folder';  $cmd[] = escapeshellarg($folder); }  // Sincroniza somente a pasta especificada

// Junta todos os tokens do array em uma única string de comando shell
$cmdStr = implode(' ', $cmd);

// --- Streaming do output ---

// Informa ao navegador que o conteúdo é HTML em UTF-8
header('Content-Type: text/html; charset=utf-8');
// Desativa o buffer do Nginx (X-Accel-Buffering) para que as linhas apareçam em tempo real
header('X-Accel-Buffering: no');
// Impede que navegador ou proxies façam cache desta resposta
header('Cache-Control: no-cache');

// Ativa o flush implícito: cada echo envia os dados imediatamente ao cliente
ob_implicit_flush(true);
// Se houver um buffer de saída ativo, encerra-o para garantir streaming sem acúmulo
if (ob_get_level() > 0) {
    ob_end_flush();
}

// Inicia o documento HTML com charset declarado no <head>
echo '<!DOCTYPE html><html><head><meta charset="utf-8">';
// Bloco de estilos CSS inline para o terminal de saída do imapsync
echo '<style>
body { background: #1e1e1e; color: #d4d4d4; font-family: monospace; font-size: 13px; padding: 1rem; margin: 0; }
pre { margin: 0; white-space: pre-wrap; word-break: break-all; }
.line-ok   { color: #4ec9b0; }
.line-warn { color: #dcdcaa; }
.line-err  { color: #f48771; }
.line-done { color: #6a9955; font-weight: bold; }
#cmd-box { background:#111; border:1px solid #333; padding:.5rem; margin-bottom:1rem; border-radius:4px; color:#9cdcfe; word-break:break-all; }
</style></head><body>';

// Exibe o comando exato que será executado (escapado para HTML para evitar XSS)
echo '<div id="cmd-box"><strong>Comando:</strong> ' . htmlspecialchars($cmdStr, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</div>';
// Abre a tag <pre> para preservar formatação de saída do terminal
echo '<pre>';
// Envia o HTML acima imediatamente ao cliente antes de iniciar o processo
flush();

// Define os descritores de arquivo para o processo filho: stdin, stdout e stderr como pipes
$descriptors = [
    0 => ['pipe', 'r'],  // stdin  — pipe de leitura para o processo (não usaremos)
    1 => ['pipe', 'w'],  // stdout — pipe de escrita do processo para capturarmos a saída padrão
    2 => ['pipe', 'w'],  // stderr — pipe de escrita do processo para capturarmos erros
];

// Abre o processo imapsync como subprocesso; $pipes receberá os handles dos descritores acima
$process = proc_open($cmdStr, $descriptors, $pipes);

// Se o processo não pôde ser iniciado (ex.: imapsync não instalado), exibe erro e encerra
if (!is_resource($process)) {
    echo '<span class="line-err">Erro: não foi possível iniciar o imapsync. Verifique se está instalado.</span>';
    echo '</pre></body></html>';
    exit;
}

// Fecha stdin do processo filho, pois não vamos enviar dados para ele
fclose($pipes[0]);

// Configura stdout como não-bloqueante para não travar enquanto aguarda mais dados
stream_set_blocking($pipes[1], false);
// Configura stderr como não-bloqueante pelo mesmo motivo
stream_set_blocking($pipes[2], false);

// Loop principal de leitura: continua enquanto o processo estiver em execução
while (true) {
    // Lista de streams a monitorar para leitura (stdout e stderr do processo)
    $read = [$pipes[1], $pipes[2]];
    $write = null;   // Não monitoramos streams para escrita
    $except = null;  // Não monitoramos exceções

    // Aguarda até 1 segundo por atividade em qualquer um dos streams; retorna quantos mudaram
    $changed = stream_select($read, $write, $except, 1);

    // stream_select retorna false em caso de erro (ex.: sinal recebido); interrompe o loop
    if ($changed === false) {
        break;
    }

    // Itera apenas sobre os streams que têm dados disponíveis (retornados por stream_select)
    foreach ($read as $stream) {
        // Lê uma linha do stream; retorna false se não há dados disponíveis no momento
        $line = fgets($stream);
        if ($line === false) {
            continue; // Nenhum dado disponível neste stream agora; passa para o próximo
        }

        // Escapa a linha para HTML prevenindo XSS antes de exibi-la no navegador
        $escaped = htmlspecialchars($line, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        // Aplica classe CSS de acordo com o conteúdo/origem da linha
        if ($stream === $pipes[2]) {
            // Linha veio do stderr: sempre exibe como erro (vermelho)
            echo '<span class="line-err">' . $escaped . '</span>';
        } elseif (preg_match('/\berror\b/i', $line)) {
            // Linha do stdout contém a palavra "error": exibe em vermelho
            echo '<span class="line-err">' . $escaped . '</span>';
        } elseif (preg_match('/\bwarning\b/i', $line)) {
            // Linha do stdout contém "warning": exibe em amarelo
            echo '<span class="line-warn">' . $escaped . '</span>';
        } elseif (preg_match('/\bEnd sync\b|\bSuccess\b/i', $line)) {
            // Linha indica fim bem-sucedido da sincronização: exibe em verde negrito
            echo '<span class="line-done">' . $escaped . '</span>';
        } else {
            // Linha informativa normal: exibe em ciano
            echo '<span class="line-ok">' . $escaped . '</span>';
        }

        // Força o envio imediato do buffer de saída ao cliente (streaming em tempo real)
        flush();
    }

    // Se ambos os pipes chegaram ao fim (EOF), o processo encerrou; sai do loop
    if (feof($pipes[1]) && feof($pipes[2])) {
        break;
    }
}

// Fecha o pipe de stdout do processo filho, liberando o recurso
fclose($pipes[1]);
// Fecha o pipe de stderr do processo filho, liberando o recurso
fclose($pipes[2]);

// Encerra o processo e obtém o código de saída (0 = sucesso, outro valor = falha)
$exitCode = proc_close($process);

// Escolhe a classe CSS de acordo com o código de saída: verde para sucesso, vermelho para falha
$statusClass = $exitCode === 0 ? 'line-done' : 'line-err';
// Monta a mensagem de status final para exibir ao usuário
$statusMsg   = $exitCode === 0 ? 'Migração concluída com sucesso.' : "Processo encerrado com código de saída: {$exitCode}";

// Exibe a mensagem final de status com a classe CSS correspondente (escapada para HTML)
echo PHP_EOL . '<span class="' . $statusClass . '">' . htmlspecialchars($statusMsg, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</span>';
// Fecha a tag <pre> do terminal de saída
echo '</pre>';
// Script JS que rola o iframe pai até o fim, para manter o terminal sempre na última linha
echo '<script>window.parent.document.getElementById("output-frame").scrollTop = 999999;</script>';
// Fecha as tags HTML
echo '</body></html>';
