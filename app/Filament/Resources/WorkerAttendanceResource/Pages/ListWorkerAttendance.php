<?php

namespace App\Filament\Resources\WorkerAttendanceResource\Pages;

use App\Filament\Resources\WorkerAttendanceResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListWorkerAttendance extends ListRecords
{
    protected static string $resource = WorkerAttendanceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
