<?php

namespace App\Console\Commands;

use Illuminate\Support\Facades\DB;
use Illuminate\Console\Command;
use App\Jobs\ImportApiDataJob;
use Carbon\Carbon;

class ImportApiData extends Command
{
    protected $signature = 'import:api-data';
    protected $description = 'Import data from external API to local database';

    private $tables = ['sales', 'orders', 'stocks', 'incomes'];

    public function handle(): void
    {
        $this->info('Starting import...');

        foreach ($this->tables as $table) {
            if (!DB::getSchemaBuilder()->hasTable($table)) {
                $this->error("Table {$table} does not exist. Skipping.");
                continue;
            }

            if (DB::table($table)->exists()) {
                $this->info("Table {$table} already has data. Skipping import.");
                continue;
            }

            $dateFrom = '1970-01-01';
            $dateTo = Carbon::today()->toDateString();

            if ($table === 'stocks') {
                // Для складов берем только текущий день
                $dateFrom = $dateTo;
            }

            ImportApiDataJob::dispatch($table, $dateFrom, $dateTo, 1);

            $this->info("Dispatched import job for {$table}");
        }

        $this->info('Import jobs dispatched.');
    }
}
