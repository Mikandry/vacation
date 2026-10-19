<?php
namespace App\Console\Commands;

use App\Services\AgentImportService;
use Illuminate\Console\Command;

class ImportAgents extends Command
{
    protected $signature = 'repartition:import {path : fichier .xlsx ou .csv}';
    protected $description = 'Importe des agents (et leurs exceptions F/X) depuis un fichier Excel';

    public function handle(AgentImportService $svc): int
    {
        $r = $svc->import($this->argument('path'));
        $this->info("Agents créés : {$r['created']} · mis à jour : {$r['updated']} · exceptions : {$r['exc']}");
        return self::SUCCESS;
    }
}
