<?php

namespace App\Services;

use App\Filament\Resources\ConsultationRequests\ConsultationRequestResource;
use App\Models\ConsultationRequest;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Email alerts about consultation requests.
 * Emails never include the description or messages, only the request number,
 * category, urgency and a link that requires signing in.
 */
class RequestNotifications
{
    public const STAFF_ROLES = ['super-admin', 'security-consultant', 'support-agent'];

    public function __construct(private PlatformMail $mail) {}

    /** A new request was submitted: alert every active staff member who handles requests. */
    public function newRequest(ConsultationRequest $case): int
    {
        return $this->notifyStaff($this->staff(), 'staff_new_request', $case);
    }

    /** The requester replied: alert the assigned staff member, or all request staff if unassigned. */
    public function requesterReplied(ConsultationRequest $case): int
    {
        $assignee = $case->assignedTo;
        $recipients = $assignee && $this->staff()->contains('id', $assignee->id) ? collect([$assignee]) : $this->staff();

        return $this->notifyStaff($recipients, 'staff_request_reply', $case);
    }

    /** Staff replied: alert the requester through their account email or verified contact email. */
    public function staffReplied(ConsultationRequest $case): bool
    {
        if ($case->user) {
            return $this->mail->send($case->user, 'request_replied');
        }
        if ($case->preferred_contact_method === 'email' && $case->contact_verified_at && filter_var($case->contact_details, FILTER_VALIDATE_EMAIL)) {
            return $this->mail->sendTo($case->contact_details, __('workflow.requester', [], 'ar'), 'request_replied_contact', route('consultation.track'));
        }

        return false;
    }

    /** @return Collection<int, User> */
    public function staff(): Collection
    {
        return User::where('is_active', true)
            ->whereHas('roles', fn ($query) => $query->whereIn('name', self::STAFF_ROLES))
            ->orderBy('id')->get();
    }

    private function notifyStaff(Collection $recipients, string $template, ConsultationRequest $case): int
    {
        $summary = $this->summary($case);

        return $recipients->filter(fn (User $user) => filled($user->email))
            ->filter(fn (User $user) => $this->mail->send($user, $template, $summary))
            ->count();
    }

    private function summary(ConsultationRequest $case): string
    {
        return implode("\n", [
            'رقم الطلب: '.$case->request_number,
            'التصنيف: '.__('admin.overview.categories.'.$case->category, [], 'ar'),
            'الأولوية: '.__('admin.overview.urgency.'.$case->urgency, [], 'ar'),
            'الرابط: '.ConsultationRequestResource::getUrl('view', ['record' => $case], panel: 'admin'),
        ]);
    }
}
