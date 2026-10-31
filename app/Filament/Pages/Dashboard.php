<?php

namespace App\Filament\Pages;

use App\Filament\Resources\Articles\ArticleResource;
use App\Filament\Resources\ConsultationRequests\ConsultationRequestResource;
use App\Filament\Resources\Countries\CountryResource;
use App\Filament\Resources\Events\EventResource;
use App\Filament\Resources\HomepageSections\HomepageSectionResource;
use App\Models\ConsultationRequest;
use Illuminate\Contracts\View\View;

class Dashboard extends \Filament\Pages\Dashboard
{
    public function getHeader(): ?View
    {
        $attention = null;
        if (ConsultationRequestResource::canAccess()) {
            $attention = [
                'urgent' => ConsultationRequest::open()->whereIn('urgency', ['urgent', 'critical'])->count(),
                'unassigned' => ConsultationRequest::open()->whereNull('assigned_to')->count(),
                'overdue' => ConsultationRequest::overdue()->count(),
            ];
        }

        return view('filament.dashboard-header', [
            'attention' => $attention,
            'operationsUrl' => Operations::canAccess() ? Operations::getUrl() : null,
            'actions' => array_values(array_filter([
                ConsultationRequestResource::canAccess() ? ['action_requests', ConsultationRequestResource::getUrl(), 'heroicon-o-chat-bubble-left-right'] : null,
                Operations::canAccess() ? ['action_operations', Operations::getUrl(), 'heroicon-o-queue-list'] : null,
                ArticleResource::canAccess() && ArticleResource::canCreate() ? ['action_article', ArticleResource::getUrl('create'), 'heroicon-o-pencil-square'] : null,
                EventResource::canAccess() ? ['action_event', EventResource::getUrl(), 'heroicon-o-calendar-days'] : null,
                CountryResource::canAccess() ? ['action_countries', CountryResource::getUrl(), 'heroicon-o-globe-alt'] : null,
                HomepageSectionResource::canAccess() ? ['action_homepage', HomepageSectionResource::getUrl(), 'heroicon-o-home-modern'] : null,
            ])),
        ]);
    }
}
