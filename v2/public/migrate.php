<?php
declare(strict_types=1);

if($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Método não permitido');
}

//servidor imap de origem removendo espaços em branco

