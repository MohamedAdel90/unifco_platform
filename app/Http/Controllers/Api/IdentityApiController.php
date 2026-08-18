<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\JwtService;
use Illuminate\Http\{JsonResponse,Request};
use Illuminate\Support\Facades\{DB,Hash};
use Illuminate\Validation\Rule;

class IdentityApiController extends Controller
{
    public function login(Request $request, JwtService $jwt): JsonResponse
    {
        $data=$request->validate(['email'=>['required','email'],'password'=>['required','string']]);
        $user=User::where('email',$data['email'])->where('status','ACTIVE')->first();
        abort_unless($user && Hash::check($data['password'],$user->password),401,'Invalid credentials.');
        return response()->json(['access_token'=>$jwt->issue($user),'token_type'=>'Bearer','expires_in'=>3600,'user'=>$this->userPayload($user)]);
    }

    public function me(Request $request): JsonResponse { return response()->json($this->userPayload($request->user())); }

    public function users(Request $request): JsonResponse
    {
        $this->admin($request);
        return response()->json(User::where('tenant_id',$request->user()->tenant_id)->orderBy('name')->get(['id','name','email','role','status','organization_id','customer_id','employee_id']));
    }

    public function storeUser(Request $request): JsonResponse
    {
        $this->admin($request);
        $data=$request->validate(['name'=>['required','string','max:180'],'email'=>['required','email','max:255','unique:users,email'],'password'=>['required','string','min:12'],'role'=>['required','string','max:60'],'organization_id'=>['nullable','integer','exists:organizations,id'],'customer_id'=>['nullable','integer','exists:customers,id'],'employee_id'=>['nullable','integer','exists:employees,id']]);
        $user=User::create($data+['tenant_id'=>$request->user()->tenant_id,'status'=>'ACTIVE']);
        return response()->json($this->userPayload($user),201);
    }

    public function updateUser(Request $request, User $user): JsonResponse
    {
        $this->admin($request); abort_unless((int)$user->tenant_id===(int)$request->user()->tenant_id,404);
        $data=$request->validate(['name'=>['sometimes','string','max:180'],'email'=>['sometimes','email','max:255',Rule::unique('users','email')->ignore($user->id)],'role'=>['sometimes','string','max:60'],'status'=>['sometimes','in:ACTIVE,INACTIVE'],'organization_id'=>['nullable','integer','exists:organizations,id'],'customer_id'=>['nullable','integer','exists:customers,id'],'employee_id'=>['nullable','integer','exists:employees,id']]);
        $user->update($data); return response()->json($this->userPayload($user->fresh()));
    }

    public function permissions(Request $request): JsonResponse
    {
        $this->admin($request); return response()->json(DB::table('role_permissions')->where('tenant_id',$request->user()->tenant_id)->orderBy('role_code')->orderBy('permission_code')->get());
    }

    public function grantPermission(Request $request): JsonResponse
    {
        $this->admin($request); $data=$request->validate(['role_code'=>['required','string','max:60'],'permission_code'=>['required','string','max:120']]);
        DB::table('role_permissions')->updateOrInsert(['tenant_id'=>$request->user()->tenant_id,'role_code'=>strtoupper($data['role_code']),'permission_code'=>$data['permission_code']],['created_at'=>now(),'updated_at'=>now()]);
        return response()->json(['granted'=>true],201);
    }

    public function revokePermission(Request $request, int $id): JsonResponse
    {
        $this->admin($request); $deleted=DB::table('role_permissions')->where('tenant_id',$request->user()->tenant_id)->where('id',$id)->delete(); abort_unless($deleted,404); return response()->json(['revoked'=>true]);
    }

    private function admin(Request $request): void { abort_unless(in_array($request->user()->role,['ADMIN','SECURITY_ADMIN'],true),403); }
    private function userPayload(User $u): array { return ['id'=>$u->id,'name'=>$u->name,'email'=>$u->email,'role'=>$u->role,'tenant_id'=>$u->tenant_id,'organization_id'=>$u->organization_id,'customer_id'=>$u->customer_id,'employee_id'=>$u->employee_id,'status'=>$u->status]; }
}
