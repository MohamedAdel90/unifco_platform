<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB,Schema};

return new class extends Migration {
    public function up(): void
    {
        Schema::create('work_order_part_requests', function (Blueprint $t) {
            $t->id();
            $t->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $t->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $t->string('request_no');
            $t->foreignId('work_order_id')->constrained()->cascadeOnDelete();
            $t->foreignId('asset_id')->constrained()->cascadeOnDelete();
            $t->foreignId('source_warehouse_id')->constrained('warehouses')->restrictOnDelete();
            $t->foreignId('destination_warehouse_id')->constrained('warehouses')->restrictOnDelete();
            $t->string('priority')->default('NORMAL');
            $t->string('status')->default('REQUESTED');
            $t->string('reason')->nullable();
            $t->foreignId('requested_by')->constrained('users')->restrictOnDelete();
            $t->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $t->foreignId('picked_by')->nullable()->constrained('users')->nullOnDelete();
            $t->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete();
            $t->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('approved_at')->nullable();
            $t->timestamp('picked_at')->nullable();
            $t->timestamp('issued_at')->nullable();
            $t->timestamp('received_at')->nullable();
            $t->text('decision_note')->nullable();
            $t->timestamps();
            $t->unique(['tenant_id','request_no']);
            $t->index(['tenant_id','status','priority']);
            $t->index(['work_order_id','status']);
        });

        Schema::create('work_order_part_request_lines', function (Blueprint $t) {
            $t->id();
            $t->foreignId('work_order_part_request_id')->constrained()->cascadeOnDelete();
            $t->foreignId('item_id')->constrained()->restrictOnDelete();
            $t->decimal('requested_quantity',19,4);
            $t->decimal('approved_quantity',19,4)->default(0);
            $t->decimal('reserved_quantity',19,4)->default(0);
            $t->decimal('issued_quantity',19,4)->default(0);
            $t->decimal('received_quantity',19,4)->default(0);
            $t->timestamps();
            $t->unique(['work_order_part_request_id','item_id']);
        });

        foreach (['parts.request.create','parts.request.read'] as $permission) {
            foreach (['TECHNICIAN','SUPERVISOR','MANAGER'] as $role) {
                DB::table('role_permissions')->updateOrInsert(['tenant_id'=>null,'role_code'=>$role,'permission_code'=>$permission],['created_at'=>now(),'updated_at'=>now()]);
            }
        }
        foreach (['parts.request.read','parts.request.approve','parts.request.pick','parts.request.issue','parts.request.receive'] as $permission) {
            DB::table('role_permissions')->updateOrInsert(['tenant_id'=>null,'role_code'=>'STOREKEEPER','permission_code'=>$permission],['created_at'=>now(),'updated_at'=>now()]);
        }
        foreach (['parts.request.read','parts.request.approve'] as $permission) {
            foreach (['SUPERVISOR','MANAGER'] as $role) {
                DB::table('role_permissions')->updateOrInsert(['tenant_id'=>null,'role_code'=>$role,'permission_code'=>$permission],['created_at'=>now(),'updated_at'=>now()]);
            }
        }
    }

    public function down(): void
    {
        DB::table('role_permissions')->whereNull('tenant_id')->whereIn('permission_code',['parts.request.create','parts.request.read','parts.request.approve','parts.request.pick','parts.request.issue','parts.request.receive'])->delete();
        Schema::dropIfExists('work_order_part_request_lines');
        Schema::dropIfExists('work_order_part_requests');
    }
};
