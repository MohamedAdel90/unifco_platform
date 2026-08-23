<?php
namespace App\Models;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
class PerformanceCycle extends Model { use BelongsToTenant; protected $fillable=['tenant_id','organization_id','code','name','starts_on','ends_on','status']; protected function casts(): array { return ['starts_on'=>'date','ends_on'=>'date']; } public function goals(): HasMany { return $this->hasMany(EmployeeGoal::class); } public function reviews(): HasMany { return $this->hasMany(PerformanceReview::class); } }
