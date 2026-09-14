<?php

namespace App\Services;

use App\Models\{User,UserInvitation};
use App\Notifications\UserInvitationNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

class InvitationService
{
    public function issue(User $user, ?User $inviter, int $hours=72): UserInvitation
    {
        UserInvitation::where('user_id',$user->id)->where('status','PENDING')->update(['status'=>'REVOKED','revoked_at'=>now()]);
        $token=Str::random(80);
        $invitation=UserInvitation::create([
            'tenant_id'=>$user->tenant_id,'user_id'=>$user->id,'email'=>$user->email,
            'token_hash'=>hash('sha256',$token),'status'=>'PENDING','invited_by'=>$inviter?->id,
            'expires_at'=>now()->addHours($hours),
        ]);

        if(request()?->boolean('set_temporary_password')) return $invitation;

        try {
            Notification::route('mail',$user->email)->notify(new UserInvitationNotification($user,$token,$invitation->expires_at));
        } catch (\Throwable $exception) {
            app(SecurityEventService::class)->record('EMAIL_DELIVERY_FAILURE','WARNING',$user,['purpose'=>'USER_INVITATION','invitation_id'=>$invitation->id]);
            report($exception);
        }
        return $invitation;
    }
}
