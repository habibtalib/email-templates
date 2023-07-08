<?php

namespace Visualbuilder\EmailTemplates\Resources\EmailTemplateResource\Pages;

use Visualbuilder\EmailTemplates\Resources\EmailTemplateResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\EditRecord;

class EditEmailTemplate extends EditRecord
{
    protected static string $resource = EmailTemplateResource::class;

    protected function getActions(): array
    {
        return [
            Actions\ViewAction::make()->label('Preview'),
            Actions\DeleteAction::make(),
            Actions\ForceDeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }
}
