<?php
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\ImapSyncFactory;
use App\ProcessoMigrador;
use App\Viewer;

if($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Método não permitido');
}

try{
    $sync = new ImapSyncFactory($_POST);
}catch(InvalidArgumentException $e){
    http_response_code(400);
    exit($e->getMessage());
}

$viewer = new Viewer();
$viewer->sendHeaders();
$viewer->begin($sync->displayCommand());

$gen = (new ProcessoMigrador())->run($sync->command());
try {
    foreach ($gen as [$stream, $line]) {
        $viewer->line($line, $stream === 'stderr');
    }
} catch (RuntimeException $e) {
    $viewer->fatal($e->getMessage());
    exit;
}

$viewer->finish($gen->getReturn());