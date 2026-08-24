<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB,Schema};

return new class extends Migration {
    public function up(): void
    {
        Schema::table('warehouses', function (Blueprint $t) {
            $t->string('location_type')->default('CENTRAL')->after('name');
            $t->foreignId('parent_warehouse_id')->nullable()->after('location_type')->constrained('warehouses')->nullOnDelete();
            $t->foreignId('assigned_employee_id')->nullable()->after('parent_warehouse_id')->constrained('employees')->nullOnDelete();
            $t->foreignId('customer_site_id')->nullable()->after('assigned_employee_id')->constrained('customer_sites')->nullOnDelete();
            $t->string('vehicle_code')->nullable()->after('customer_site_id');
            $t->string('plate_no')->nullable()->after('vehicle_code');
            $t->string('city')->nullable()->after('plate_no');
            $t->string('address')->nullable()->after('city');
            $t->boolean('is_mobile')->default(false)->after('address');
            $t->boolean('allow_negative_stock')->default(false)->after('is_mobile');
            $t->index(['tenant_id','location_type','status']);
        });

        Schema::create('warehouse_bins', function (Blueprint $t) {
            $t->id();
            $t->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $t->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $t->string('bin_code');
            $t->string('name')->nullable();
            $t->string('zone')->nullable();
            $t->string('status')->default('ACTIVE');
            $t->timestamps();
            $t->unique(['warehouse_id','bin_code']);
            $t->index(['tenant_id','status']);
        });

        Schema::create('inventory_transfer_orders', function (Blueprint $t) {
            $t->id();
            $t->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $t->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $t->string('transfer_no');
            $t->foreignId('from_warehouse_id')->constrained('warehouses')->restrictOnDelete();
            $t->foreignId('to_warehouse_id')->constrained('warehouses')->restrictOnDelete();
            $t->string('status')->default('DRAFT');
            $t->string('purpose')->nullable();
            $t->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $t->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete();
            $t->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('issued_at')->nullable();
            $t->timestamp('received_at')->nullable();
            $t->text('notes')->nullable();
            $t->timestamps();
            $t->unique(['tenant_id','transfer_no']);
            $t->index(['tenant_id','status','created_at']);
        });

        Schema::create('inventory_transfer_order_lines', function (Blueprint $t) {
            $t->id();
            $t->foreignId('inventory_transfer_order_id')->constrained(indexName: 'transfer_lines_order_fk')->cascadeOnDelete();
            $t->foreignId('item_id')->constrained()->restrictOnDelete();
            $t->decimal('requested_quantity',19,4);
            $t->decimal('issued_quantity',19,4)->default(0);
            $t->decimal('received_quantity',19,4)->default(0);
            $t->timestamps();
            $t->unique(['inventory_transfer_order_id','item_id'],'transfer_lines_order_item_uq');
        });

        Schema::create('warehouse_user_assignments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $t->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->string('access_level')->default('OPERATOR');
            $t->timestamps();
            $t->unique(['warehouse_id','user_id']);
        });

        foreach (['inventory.warehouse.read','inventory.warehouse.operate','inventory.warehouse.manage','inventory.transfer.request','inventory.transfer.issue','inventory.transfer.receive','inventory.count.manage'] as $permission) {
            DB::table('role_permissions')->updateOrInsert(
                ['tenant_id'=>null,'role_code'=>'STOREKEEPER','permission_code'=>$permission],
                ['created_at'=>now(),'updated_at'=>now()]
            );
        }
        foreach (['inventory.stock.read','inventory.stock.move'] as $permission) {
            DB::table('role_permissions')->updateOrInsert(
                ['tenant_id'=>null,'role_code'=>'STOREKEEPER','permission_code'=>$permission],
                ['created_at'=>now(),'updated_at'=>now()]
            );
        }
    }

    public function down(): void
    {
        DB::table('role_permissions')->whereNull('tenant_id')->where('role_code','STOREKEEPER')->delete();
        Schema::dropIfExists('warehouse_user_assignments');
        Schema::dropIfExists('inventory_transfer_order_lines');
        Schema::dropIfExists('inventory_transfer_orders');
        Schema::dropIfExists('warehouse_bins');
        Schema::table('warehouses', function (Blueprint $t) {
            $t->dropConstrainedForeignId('parent_warehouse_id');
            $t->dropConstrainedForeignId('assigned_employee_id');
            $t->dropConstrainedForeignId('customer_site_id');
            $t->dropColumn(['location_type','vehicle_code','plate_no','city','address','is_mobile','allow_negative_stock']);
        });
    }
};
