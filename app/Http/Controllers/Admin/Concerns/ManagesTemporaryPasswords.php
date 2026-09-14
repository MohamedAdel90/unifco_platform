<?php

namespace App\Http\Controllers\Admin\Concerns;

use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

trait ManagesTemporaryPasswords
{
    private function applyTemporaryPassword(Request $request, $managed, AuditService $audit): void
    {
        $data=$request->validate(['password'=>['required','string','min:10','confirmed']]);
        $before=['locked_at'=>$managed->locked_at,'force_password_change'=>$managed->force_password_change,'session_version'=>$managed->session_version];
        $managed->update([
            'password'=>$data['password'],
            'force_password_change'=>true,
            'locked_at'=>null,
            'session_version'=>$managed->session_version+1,
        ]);
        if(Schema::hasTable('user_sessions')) DB::table('user_sessions')->where('user_id',$managed->id)->where('status','ACTIVE')->update([
            'status'=>'REVOKED','revoked_at'=>now(),'revoked_by'=>$request->user()->id,
            'revoke_reason'=>'Administrator set temporary password','updated_at'=>now(),
        ]);
        $audit->record('security.user.temporary_password_set',$managed,$before,[
            'reset_by'=>$request->user()->id,'locked_at'=>null,'force_password_change'=>true,
            'sessions_revoked'=>true,'session_version'=>$managed->session_version,
        ],reason:'Administrator temporary password for controlled onboarding/UAT');
    }
}
