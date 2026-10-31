<?php
namespace App\Services;

use App\Models\PrivacyRequest;
use App\Models\User;
use App\Models\ConsultationRequest;
use Illuminate\Support\Facades\DB;

class PrivacyErasure
{
    public function execute(int $id, int $adminId): void
    {
        abort_unless(User::find($adminId)?->hasRole('super-admin'),403);
        DB::transaction(function () use ($id,$adminId): void {
            $request = PrivacyRequest::lockForUpdate()->findOrFail($id);
            abort_unless(in_array($request->status,['pending','in_review']),422);
            if ($request->consultation_request_id) {
                ConsultationRequest::findOrFail($request->consultation_request_id)->delete();
            } elseif ($request->user_id) {
                $user = User::lockForUpdate()->findOrFail($request->user_id);
                abort_if($user->roles()->where('name','!=','member')->exists() || $user->id===$adminId,422,'لا يمكن حذف حساب فريق العمل عبر هذا المسار');
                // Delete models individually so all database cascades are applied consistently.
                $user->consultationRequests()->get()->each->delete();
                $user->emailDeliveries()->delete();
                DB::table('sessions')->where('user_id',$user->id)->delete();
                DB::table('password_reset_tokens')->where('email',$user->email)->delete();
                $user->delete();
            } else {
                abort(422,'البيانات المستهدفة لم تعد متاحة؛ راجع الطلب');
            }
            $request->update(['status'=>'completed','reviewed_at'=>now(),'reviewed_by'=>$adminId]);
        });
    }
}
