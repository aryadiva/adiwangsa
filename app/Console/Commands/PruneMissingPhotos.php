<?php

namespace App\Console\Commands;

use App\Models\DailyReportPhoto;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class PruneMissingPhotos extends Command
{
    protected $signature = 'photos:prune {--dry-run : Report without deleting rows}';

    protected $description = 'Delete daily_report_photos rows whose before/after files are missing from storage.';

    public function handle(): int
    {
        $disk = Storage::disk('photos');

        $missing = 0;
        $duplicates = 0;

        DailyReportPhoto::query()
            ->orderBy('created_at')
            ->get()
            ->each(function (DailyReportPhoto $photo) use ($disk, &$missing, &$duplicates): void {
                if (! $disk->exists($photo->before_file_path)) {
                    $this->delete($photo);
                    $missing++;

                    return;
                }

                // One active pair per report is enforced by the partial
                // unique index; soft-deleted leftovers are still cleaned up.
                $duplicate = DailyReportPhoto::query()
                    ->where('daily_report_id', $photo->daily_report_id)
                    ->whereKeyNot($photo->id)
                    ->exists();

                if ($duplicate) {
                    $this->delete($photo);
                    $duplicates++;
                }
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
