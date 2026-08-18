<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Str;
use RuntimeException;

class JwtService
{
    public function issue(User $user, int $ttlSeconds=3600): string
    {
        $now=time();
        return $this->encode([
            'iss'=>config('app.url'),'sub'=>(string)$user->id,'tid'=>(int)$user->tenant_id,'oid'=>$user->organization_id ? (int)$user->organization_id : null,
            'role'=>$user->role,'iat'=>$now,'nbf'=>$now,'exp'=>$now+$ttlSeconds,'jti'=>(string)Str::uuid(),
        ]);
    }

    public function decode(string $jwt): array
    {
        $parts=explode('.',$jwt); if(count($parts)!==3) throw new RuntimeException('Malformed token');
        [$h,$p,$s]=$parts; $expected=$this->b64(hash_hmac('sha256',$h.'.'.$p,$this->secret(),true));
        if(!hash_equals($expected,$s)) throw new RuntimeException('Invalid signature');
        $header=json_decode($this->unb64($h),true); $payload=json_decode($this->unb64($p),true);
        if(($header['alg']??null)!=='HS256' || !is_array($payload)) throw new RuntimeException('Invalid token');
        $now=time(); if(($payload['nbf']??0)>$now || ($payload['exp']??0)<=$now) throw new RuntimeException('Expired token');
        return $payload;
    }

    private function encode(array $payload): string
    {
        $h=$this->b64(json_encode(['typ'=>'JWT','alg'=>'HS256'],JSON_UNESCAPED_SLASHES)); $p=$this->b64(json_encode($payload,JSON_UNESCAPED_SLASHES));
        return $h.'.'.$p.'.'.$this->b64(hash_hmac('sha256',$h.'.'.$p,$this->secret(),true));
    }
    private function secret(): string { $key=(string)config('app.key'); return str_starts_with($key,'base64:') ? base64_decode(substr($key,7)) : $key; }
    private function b64(string $v): string { return rtrim(strtr(base64_encode($v),'+/','-_'),'='); }
    private function unb64(string $v): string { return (string)base64_decode(strtr($v,'-_','+/')); }
}
