<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB,Schema};

return new class extends Migration {
    public function up(): void
    {
        Schema::table('work_order_part_request_lines', function (Blueprint $t) {
            $t->decimal('consumed_quantity',19,4)->default(0)->after('received_quantity');
            $t->decimal('returned_quantity',19,4)->default(0)->after('consumed_quantity');
        });

        Schema::create('asset_part_installations', function (Blueprint $t) {
            $t->id();
            $t->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $t->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('work_order_id')->constrained()->cascadeOnDelete();
            $t->foreignId('asset_id')->constrained()->cascadeOnDelete();
            $t->foreignId('work_order_part_request_line_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('item_id')->constrained()->restrictOnDelete();
            $t->string('warehouse_code');
            $t->decimal('quantity',19,4);
            $t->decimal('unit_cost',19,2)->default(0);
            $t->decimal('total_cost',19,2)->default(0);
            $t->foreignId('installed_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('installed_at');
            $t->foreignId('removed_item_id')->nullable()->constrained('items')->nullOnDelete();
            $t->string('removed_serial')->nullable();
            $t->string('removed_disposition')->nullable();
            $t->text('notes')->nullable();
            $t->timestamps();
            $t->index(['asset_id','installed_at']);
            $t->index(['work_order_id','installed_at']);
        });

        Schema::create('work_order_part_returns', function (Blueprint $t) {
            $t->id();
            $t->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $t->foreignId('work_order_part_request_line_id')->constrained()->cascadeOnDelete();
            $t->foreignId('item_id')->constrained()->restrictOnDelete();
            $t->foreignId('from_warehouse_id')->constrained('warehouses')->restrictOnDelete();
            $t->foreignId('to_warehouse_id')->constrained('warehouses')->restrictOnDelete();
            $t->decimal('quantity',19,4);
            $t->string('reason')->nullable();
            $t->foreignId('returned_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('returned_at');
            $t->timestamps();
        });

        foreach (['parts.consume','parts.return'] as $permission) {
            foreach (['TECHNICIAN','SUPERVISOR','MANAGER','STOREKEEPER'] as $role) {
                DB::table('role_permissions')->updateOrInsert(['tenant_id'=>null,'role_code'=>$role,'permission_code'=>$permission],['created_at'=>now(),'updated_at'=>now()]);
            }
        }
    }

    public function down(): void
    {
        DB::table('role_permissions')->whereNull('tenant_id')->whereIn('permission_code',['parts.consume','parts.return'])->delete();
        Schema::dropIfExists('work_order_part_returns');
        Schema::dropIfExists('asset_part_installations');
        Schema::table('work_order_part_request_lines', fn(Blueprint $t)=>$t->dropColumn(['consumed_quantity','returned_quantity']));
    }
};
