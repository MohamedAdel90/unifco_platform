<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = ['tenant_id','organization_id','customer_id','employee_id','name','email','password','role','customer_portal_role','status','last_login_at','force_password_change','mfa_status','locked_at','session_version'];
    protected $hidden = ['password','remember_token'];
    protected function casts(): array { return ['email_verified_at'=>'datetime','last_login_at'=>'datetime','locked_at'=>'datetime','force_password_change'=>'boolean','session_version'=>'integer','password'=>'hashed']; }
}
