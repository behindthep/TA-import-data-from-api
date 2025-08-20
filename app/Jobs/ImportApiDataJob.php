<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ImportApiDataJob implements ShouldQueue
{
    use Queueable;

    public $tries = 5;

    protected $endpoint;
    protected $dateFrom;
    protected $dateTo;
    protected $page;
    protected $limit   =  500;
    protected $baseUrl = 'http://109.73.206.144:6969/api';
    protected $apiKey  = 'E6kUTYrYwZq2tN4QEtyzsbEBk3ie';

    public function __construct(string $endpoint, string $dateFrom, string $dateTo, int $page = 1)
    {
        $this->endpoint = $endpoint;
        $this->dateFrom = $dateFrom;
        $this->dateTo = $dateTo;
        $this->page = $page;
    }

    public function handle(): void
    {
        try {
            $response = Http::get("{$this->baseUrl}/{$this->endpoint}", [
                'dateFrom' => $this->dateFrom,
                'dateTo' => $this->dateTo,
                'page' => $this->page,
                'limit' => $this->limit,
                'key' => $this->apiKey,
            ]);

            if (!$response->successful()) {
                Log::error("Failed to fetch {$this->endpoint} page {$this->page}, status: " . $response->status());
                return;
            }

            $json = $response->json();

            if (empty($json['data'])) {
                Log::info("No data found for {$this->endpoint} page {$this->page}");
                return;
            }

            DB::table($this->endpoint)->insert($json['data']);

            $lastPage = $json['meta']['last_page'] ?? 1;

            if ($this->page < $lastPage) {
                // Запускаем следующий джоб с page+1
                self::dispatch($this->endpoint, $this->dateFrom, $this->dateTo, $this->page + 1);
            }

            Log::info('Done');
        } catch (\Exception $e) {
            Log::error("Import job failed for {$this->endpoint} page {$this->page}: " . $e->getMessage());
        }
    }
}
