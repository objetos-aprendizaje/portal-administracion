<?php

namespace App\Jobs;

use App\Services\EmbeddingsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Regenera todos los embeddings de los cursos
 */
class RegenerateAllEmbeddingsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $embeddingsService;

    public function __construct()
    {
        $this->embeddingsService = new EmbeddingsService();
    }

    public function handle()
    {
        $this->embeddingsService->regenerateAllEmbeddings();
    }
}
