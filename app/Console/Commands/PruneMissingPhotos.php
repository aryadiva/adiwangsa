<?php

namespace App\Console\Commands;

use App\Models\DailyReportPhoto;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class PruneMissingPhotos extends Command
{
    protected $signature = 'photos:prune {--dry-run : Report without deleting rows}';

    protected $description = 'Delete daily_report_photos rows whose file is missing from storage, and dedupe repeated paths.';

    public function handle(): int
    {
        $disk = Storage::disk('photos');

        $missing = 0;
        $duplicates = 0;
        $seen = [];

        DailyReportPhoto::query()
            ->orderBy('created_at')
            ->get()
            ->each(function (DailyReportPhoto $photo) use ($disk, &$seen, &$missing, &$duplicates): void {
                if (! $disk->exists($photo->file_path)) {
                    $this->delete($photo);
                    $missing++;

                    return;
                }

                $key = $photo->daily_report_id.'|'.$photo->file_path;

                if (isset($seen[$key])) {
                    $this->delete($photo);
                    $duplicates++;

                    return;
                }

                $seen[$key] = true;
            });

        $this->info(sprintf('Pruned %d missing and %d duplicate photo row(s).', $missing, $duplicates));

        return self::SUCCESS;
    }

    protected function delete(DailyReportPhoto $photo): void
    {
        if ($this->option('dry-run')) {
            return;
        }

        $photo->delete();
    }
}
