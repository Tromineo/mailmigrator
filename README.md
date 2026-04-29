# email-migrator

Interface web para migração de caixas de e-mail entre servidores IMAP, usando [imapsync](https://imapsync.lamiral.info/) como motor de sincronização. O output do processo é transmitido em tempo real diretamente no navegador.

## Stack

- **PHP 8.3-FPM** (Alpine)
- **Nginx**
- **imapsync**
- **Bootstrap 5.3**
- **Docker / Docker Compose**

## Estrutura

```
email-migrator/
├── index.php          # Interface web (formulário)
├── migrate.php        # Backend: monta e executa o imapsync com streaming
├── nginx.conf         # Configuração do Nginx
├── Dockerfile         # PHP 8.3-FPM Alpine + imapsync + Nginx
├── docker-compose.yml # Sobe o container na porta 8080
├── entrypoint.sh      # Inicia php-fpm e nginx dentro do container
└── css/
    └── styles.css     # Estilos complementares ao Bootstrap
```

## Como usar

### Pré-requisito

Docker instalado e em execução.

### Subir o projeto

```bash
docker compose up -d --build
```

Acesse no navegador: [http://localhost:8080](http://localhost:8080)

### Parar

```bash
docker compose down
```

## Campos do formulário

| Campo | Descrição |
|---|---|
| Servidor IMAP (origem/destino) | Hostname do servidor IMAP, ex: `imap.gmail.com` |
| Porta | Padrão `993` (IMAPS) |
| Usuário | E-mail completo da conta |
| Senha | Senha da conta |
| SSL/TLS | Usar conexão criptografada (recomendado) |

## Opções avançadas

| Opção | Flag imapsync | Descrição |
|---|---|---|
| Dry-run (simulação) | `--dry` | Executa sem copiar nada, apenas simula |
| Deletar no destino | `--delete2` | Remove no destino mensagens ausentes na origem |
| Ignorar erros SSL | `--nossl1_warn --nossl2_warn` | Aceita certificados inválidos ou autoassinados |
| Excluir pastas | `--exclude <regex>` | Ex: `Spam\|Trash` — exclui pastas pelo nome |
| Pasta específica | `--folder <nome>` | Sincroniza apenas a pasta informada, ex: `INBOX` |

## Output em tempo real

Após clicar em **Iniciar Migração**, o terminal de saída exibe as linhas do imapsync com colorização:

| Cor | Significado |
|---|---|
| 🟢 Ciano | Linha informativa normal |
| 🟡 Amarelo | Aviso (`warning`) |
| 🔴 Vermelho | Erro (`error` ou stderr) |
| 🟩 Verde | Fim de sincronização (`End sync` / `Success`) |

## Segurança

- Todos os argumentos passados ao `imapsync` são escapados com `escapeshellarg()` para prevenir injeção de shell.
- O output exibido no navegador é sempre escapado com `htmlspecialchars()` para prevenir XSS.
- Somente requisições `POST` são aceitas em `migrate.php`.
- Não há persistência de credenciais — os dados trafegam apenas na requisição.

> **Atenção:** Use este projeto em rede local ou VPN. As credenciais de e-mail trafegam em texto sobre HTTP. Para uso em produção, configure HTTPS.
