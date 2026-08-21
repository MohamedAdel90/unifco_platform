<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('asset_category_templates', function (Blueprint $table) {
            $table->id();
            $table->string('code',60)->unique();
            $table->string('name',140);
            $table->string('system_group',80);
            $table->string('default_criticality',20)->default('MEDIUM');
            $table->unsignedInteger('default_useful_life_months')->nullable();
            $table->boolean('meter_based_supported')->default(false);
            $table->string('default_meter_unit',30)->nullable();
            $table->string('status',20)->default('ACTIVE');
            $table->timestamps();
        });

        Schema::create('asset_category_template_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_category_template_id')->constrained()->cascadeOnDelete();
            $table->string('field_key',100);
            $table->string('label',160);
            $table->string('data_type',20)->default('TEXT');
            $table->string('unit',30)->nullable();
            $table->boolean('is_required')->default(false);
            $table->unsignedInteger('sort_order')->default(100);
            $table->text('help_text')->nullable();
            $table->timestamps();
            $table->unique(['asset_category_template_id','field_key']);
        });

        if (! Schema::hasColumn('assets','asset_category_template_id')) {
            Schema::table('assets', function (Blueprint $table) {
                $table->foreignId('asset_category_template_id')->nullable()->constrained('asset_category_templates')->nullOnDelete();
            });
        }

        Schema::create('maintenance_plan_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('maintenance_plan_id')->constrained('maintenance_plans')->cascadeOnDelete();
            $table->unsignedInteger('sort_order')->default(100);
            $table->string('task_code',60)->nullable();
            $table->string('task_title',255);
            $table->text('instructions')->nullable();
            $table->string('response_type',30)->default('PASS_FAIL');
            $table->string('unit',30)->nullable();
            $table->decimal('min_value',18,4)->nullable();
            $table->decimal('max_value',18,4)->nullable();
            $table->boolean('photo_required')->default(false);
            $table->boolean('required')->default(true);
            $table->timestamps();
        });

        Schema::table('maintenance_plans', function (Blueprint $table) {
            if (! Schema::hasColumn('maintenance_plans','service_contract_id')) $table->foreignId('service_contract_id')->nullable()->constrained('service_contracts')->nullOnDelete();
            if (! Schema::hasColumn('maintenance_plans','maintenance_strategy')) $table->string('maintenance_strategy',30)->default('PREVENTIVE');
            if (! Schema::hasColumn('maintenance_plans','estimated_duration_minutes')) $table->unsignedInteger('estimated_duration_minutes')->nullable();
            if (! Schema::hasColumn('maintenance_plans','meter_type')) $table->string('meter_type',60)->nullable();
            if (! Schema::hasColumn('maintenance_plans','safety_instructions')) $table->text('safety_instructions')->nullable();
            if (! Schema::hasColumn('maintenance_plans','required_skill')) $table->string('required_skill',120)->nullable();
            if (! Schema::hasColumn('maintenance_plans','auto_generate_work_orders')) $table->boolean('auto_generate_work_orders')->default(true);
            if (! Schema::hasColumn('maintenance_plans','lead_days')) $table->unsignedInteger('lead_days')->default(7);
        });

        $now = now();
        $templates = [
            ['GENERATOR','Generator','ELECTRICAL','CRITICAL',180,true,'HOURS'],
            ['ATS','Automatic Transfer Switch (ATS)','ELECTRICAL','CRITICAL',180,false,null],
            ['UPS','Uninterruptible Power Supply (UPS)','ELECTRICAL','CRITICAL',120,true,'HOURS'],
            ['TRANSFORMER','Power Transformer','ELECTRICAL','CRITICAL',300,false,null],
            ['MV_SWITCHGEAR','Medium Voltage Switchgear','ELECTRICAL','CRITICAL',300,false,null],
            ['LV_PANEL','Low Voltage Distribution Panel','ELECTRICAL','HIGH',240,false,null],
            ['BATTERY_BANK','Battery Bank','ELECTRICAL','HIGH',60,true,'CYCLES'],
        ];
        foreach ($templates as [$code,$name,$group,$criticality,$life,$meter,$unit]) {
            DB::table('asset_category_templates')->updateOrInsert(['code'=>$code],[
                'name'=>$name,'system_group'=>$group,'default_criticality'=>$criticality,'default_useful_life_months'=>$life,
                'meter_based_supported'=>$meter,'default_meter_unit'=>$unit,'status'=>'ACTIVE','created_at'=>$now,'updated_at'=>$now,
            ]);
        }

        $fieldMap = [
            'GENERATOR'=>[
                ['rated_kva','Rated Capacity','NUMBER','kVA',1,10],['rated_kw','Rated Power','NUMBER','kW',0,20],['rated_voltage','Rated Voltage','NUMBER','V',1,30],
                ['frequency','Frequency','NUMBER','Hz',1,40],['phase','Phase','TEXT',null,1,50],['fuel_type','Fuel Type','TEXT',null,1,60],['engine_model','Engine Model','TEXT',null,0,70],
                ['alternator_model','Alternator Model','TEXT',null,0,80],['fuel_tank_capacity','Fuel Tank Capacity','NUMBER','L',0,90],['battery_voltage','Starting Battery Voltage','NUMBER','V',0,100],
            ],
            'ATS'=>[
                ['rated_current','Rated Current','NUMBER','A',1,10],['rated_voltage','Rated Voltage','NUMBER','V',1,20],['poles','Number of Poles','NUMBER',null,1,30],
                ['transfer_type','Transfer Type','TEXT',null,1,40],['controller_model','Controller Model','TEXT',null,0,50],['short_circuit_rating','Short Circuit Rating','NUMBER','kA',0,60],
            ],
            'UPS'=>[
                ['rated_kva','Rated Capacity','NUMBER','kVA',1,10],['rated_kw','Rated Power','NUMBER','kW',0,20],['input_voltage','Input Voltage','NUMBER','V',1,30],
                ['output_voltage','Output Voltage','NUMBER','V',1,40],['topology','UPS Topology','TEXT',null,1,50],['battery_type','Battery Type','TEXT',null,1,60],
                ['battery_quantity','Battery Quantity','NUMBER','pcs',0,70],['battery_capacity','Battery Capacity','NUMBER','Ah',0,80],['backup_time','Designed Backup Time','NUMBER','min',0,90],
            ],
            'TRANSFORMER'=>[
                ['rated_kva','Rated Capacity','NUMBER','kVA',1,10],['primary_voltage','Primary Voltage','NUMBER','kV',1,20],['secondary_voltage','Secondary Voltage','NUMBER','V',1,30],
                ['transformer_type','Transformer Type','TEXT',null,1,40],['vector_group','Vector Group','TEXT',null,0,50],['impedance','Impedance','NUMBER','%',0,60],
                ['cooling_type','Cooling Type','TEXT',null,0,70],['oil_type','Insulating Oil Type','TEXT',null,0,80],
            ],
            'MV_SWITCHGEAR'=>[
                ['rated_voltage','Rated Voltage','NUMBER','kV',1,10],['rated_current','Rated Current','NUMBER','A',1,20],['short_circuit_rating','Short Circuit Rating','NUMBER','kA',1,30],
                ['breaker_type','Breaker Type','TEXT',null,1,40],['breaker_model','Breaker Model','TEXT',null,0,50],['protection_relay','Protection Relay','TEXT',null,0,60],['number_of_feeders','Number of Feeders','NUMBER',null,0,70],
            ],
            'LV_PANEL'=>[
                ['rated_voltage','Rated Voltage','NUMBER','V',1,10],['rated_current','Rated Current','NUMBER','A',1,20],['short_circuit_rating','Short Circuit Rating','NUMBER','kA',0,30],
                ['panel_type','Panel Type','TEXT',null,1,40],['main_breaker','Main Breaker','TEXT',null,0,50],['number_of_outgoings','Number of Outgoing Feeders','NUMBER',null,0,60],
            ],
            'BATTERY_BANK'=>[
                ['battery_type','Battery Type','TEXT',null,1,10],['battery_quantity','Battery Quantity','NUMBER','pcs',1,20],['nominal_voltage','Nominal Voltage','NUMBER','V',1,30],
                ['capacity_ah','Capacity','NUMBER','Ah',1,40],['string_count','Number of Strings','NUMBER',null,0,50],['design_life_years','Design Life','NUMBER','years',0,60],
            ],
        ];

        foreach ($fieldMap as $code=>$fields) {
            $templateId = DB::table('asset_category_templates')->where('code',$code)->value('id');
            foreach ($fields as [$key,$label,$type,$unit,$required,$sort]) {
                DB::table('asset_category_template_fields')->updateOrInsert(
                    ['asset_category_template_id'=>$templateId,'field_key'=>$key],
                    ['label'=>$label,'data_type'=>$type,'unit'=>$unit,'is_required'=>$required,'sort_order'=>$sort,'created_at'=>$now,'updated_at'=>$now]
                );
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_plan_tasks');
        Schema::dropIfExists('asset_category_template_fields');
        Schema::table('assets', function (Blueprint $table) {
            if (Schema::hasColumn('assets','asset_category_template_id')) $table->dropConstrainedForeignId('asset_category_template_id');
        });
        Schema::dropIfExists('asset_category_templates');
    }
};
