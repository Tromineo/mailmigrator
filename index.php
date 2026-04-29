<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Migrador de E-mail</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/styles.css" rel="stylesheet">
</head>
<body class="bg-dark text-light">
<div class="container py-5">
    <h1 class="mb-1">Migrador de E-mail</h1>
    <p class="text-secondary mb-4">Migração via <code>imapsync</code></p>

    <form action="migrate.php" method="POST" target="output-frame" onsubmit="showOutput()">
        <div class="row g-4">

            <!-- Origem -->
            <div class="col-md-6">
                <div class="card bg-secondary-subtle border-secondary">
                    <div class="card-header fw-bold">Origem</div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Servidor IMAP</label>
                            <input type="text" name="src_host" class="form-control" placeholder="imap.exemplo.com" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Porta</label>
                            <input type="number" name="src_port" class="form-control" value="993">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Usuário</label>
                            <input type="email" name="src_user" class="form-control" placeholder="usuario@origem.com" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Senha</label>
                            <input type="password" name="src_pass" class="form-control" required>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="src_ssl" id="src_ssl" checked>
                            <label class="form-check-label" for="src_ssl">SSL/TLS</label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Destino -->
            <div class="col-md-6">
                <div class="card bg-secondary-subtle border-secondary">
                    <div class="card-header fw-bold">Destino</div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Servidor IMAP</label>
                            <input type="text" name="dst_host" class="form-control" placeholder="imap.destino.com" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Porta</label>
                            <input type="number" name="dst_port" class="form-control" value="993">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Usuário</label>
                            <input type="email" name="dst_user" class="form-control" placeholder="usuario@destino.com" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Senha</label>
                            <input type="password" name="dst_pass" class="form-control" required>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="dst_ssl" id="dst_ssl" checked>
                            <label class="form-check-label" for="dst_ssl">SSL/TLS</label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Opções -->
            <div class="col-12">
                <div class="card bg-secondary-subtle border-secondary">
                    <div class="card-header fw-bold">Opções</div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="dry_run" id="dry_run">
                                    <label class="form-check-label" for="dry_run">Dry-run (simulação)</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="delete2" id="delete2">
                                    <label class="form-check-label" for="delete2">Deletar no destino o que não existe na origem</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="no_ssl_check" id="no_ssl_check">
                                    <label class="form-check-label" for="no_ssl_check">Ignorar erros SSL</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Excluir pastas (regex, opcional)</label>
                                <input type="text" name="exclude" class="form-control" placeholder="Spam|Trash">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Pasta específica (opcional)</label>
                                <input type="text" name="folder" class="form-control" placeholder="INBOX">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <div class="mt-4 d-flex gap-3 align-items-center">
            <button type="submit" class="btn btn-success btn-lg px-5">Iniciar Migração</button>
            <span class="text-secondary small">A saída aparecerá ao vivo abaixo.</span>
        </div>
    </form>

    <!-- Área de output -->
    <div id="output-wrapper" class="mt-4" style="display:none;">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <h5 class="mb-0">Output</h5>
            <button class="btn btn-sm btn-outline-secondary" onclick="clearOutput()">Limpar</button>
        </div>
        <iframe name="output-frame" id="output-frame" class="output-frame"></iframe>
    </div>
</div>

<script>
function showOutput() {
    const wrapper = document.getElementById('output-wrapper');
    wrapper.style.display = 'block';
    wrapper.scrollIntoView({ behavior: 'smooth' });
}
function clearOutput() {
    document.getElementById('output-frame').src = 'about:blank';
    document.getElementById('output-wrapper').style.display = 'none';
}
</script>
</body>
</html>
