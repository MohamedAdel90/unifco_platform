<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('asset_category_templates') || ! Schema::hasTable('tenants')) {
            return;
        }

        $tenants = DB::table('tenants')->select('id')->get();

        $definitions = [
            ['HVAC','AIR_COMPRESSOR','Air Compressor', ['fields'=>[
                ['key'=>'capacity','label'=>'Capacity','type'=>'text','unit'=>'CFM','required'=>true],
                ['key'=>'working_pressure','label'=>'Working Pressure','type'=>'text','unit'=>'bar','required'=>true],
                ['key'=>'motor_power','label'=>'Motor Power','type'=>'text','unit'=>'kW','required'=>true],
                ['key'=>'refrigerant','label'=>'Refrigerant / Cooling Type','type'=>'text','required'=>false],
            ]]],
            ['HVAC','CHILLER','Chiller', ['fields'=>[
                ['key'=>'cooling_capacity','label'=>'Cooling Capacity','type'=>'text','unit'=>'TR','required'=>true],
                ['key'=>'refrigerant','label'=>'Refrigerant','type'=>'text','required'=>true],
                ['key'=>'compressor_type','label'=>'Compressor Type','type'=>'text','required'=>true],
                ['key'=>'power_supply','label'=>'Power Supply','type'=>'text','required'=>false],
            ]]],
            ['ELECTRICAL','GENERATOR','Generator', ['fields'=>[
                ['key'=>'rated_power','label'=>'Rated Power','type'=>'text','unit'=>'kVA','required'=>true],
                ['key'=>'voltage','label'=>'Voltage','type'=>'text','unit'=>'V','required'=>true],
                ['key'=>'frequency','label'=>'Frequency','type'=>'text','unit'=>'Hz','required'=>true],
                ['key'=>'fuel_type','label'=>'Fuel Type','type'=>'text','required'=>true],
            ]]],
            ['ELECTRICAL','UPS','UPS', ['fields'=>[
                ['key'=>'capacity','label'=>'Capacity','type'=>'text','unit'=>'kVA','required'=>true],
                ['key'=>'input_voltage','label'=>'Input Voltage','type'=>'text','unit'=>'V','required'=>true],
                ['key'=>'output_voltage','label'=>'Output Voltage','type'=>'text','unit'=>'V','required'=>true],
                ['key'=>'battery_bank','label'=>'Battery Bank','type'=>'text','required'=>true],
            ]]],
            ['ELECTRICAL','TRANSFORMER','Transformer', ['fields'=>[
                ['key'=>'rating','label'=>'Rating','type'=>'text','unit'=>'kVA','required'=>true],
                ['key'=>'primary_voltage','label'=>'Primary Voltage','type'=>'text','unit'=>'kV','required'=>true],
                ['key'=>'secondary_voltage','label'=>'Secondary Voltage','type'=>'text','unit'=>'V','required'=>true],
                ['key'=>'vector_group','label'=>'Vector Group','type'=>'text','required'=>false],
            ]]],
            ['MECHANICAL','PUMP','Pump', ['fields'=>[
                ['key'=>'flow_rate','label'=>'Flow Rate','type'=>'text','unit'=>'m3/h','required'=>true],
                ['key'=>'head','label'=>'Head','type'=>'text','unit'=>'m','required'=>true],
                ['key'=>'motor_power','label'=>'Motor Power','type'=>'text','unit'=>'kW','required'=>true],
                ['key'=>'pump_type','label'=>'Pump Type','type'=>'text','required'=>true],
            ]]],
            ['HVAC','AHU','Air Handling Unit (AHU)', ['fields'=>[
                ['key'=>'airflow','label'=>'Air Flow','type'=>'text','unit'=>'CFM','required'=>true],
                ['key'=>'cooling_capacity','label'=>'Cooling Capacity','type'=>'text','unit'=>'TR','required'=>false],
                ['key'=>'fan_power','label'=>'Fan Motor Power','type'=>'text','unit'=>'kW','required'=>true],
                ['key'=>'filter_class','label'=>'Filter Class','type'=>'text','required'=>false],
            ]]],
            ['HVAC','FCU','Fan Coil Unit (FCU)', ['fields'=>[
                ['key'=>'cooling_capacity','label'=>'Cooling Capacity','type'=>'text','unit'=>'TR','required'=>true],
                ['key'=>'airflow','label'=>'Air Flow','type'=>'text','unit'=>'CFM','required'=>true],
                ['key'=>'fan_speed','label'=>'Fan Speed','type'=>'text','required'=>false],
                ['key'=>'power_supply','label'=>'Power Supply','type'=>'text','required'=>false],
            ]]],
        ];

        foreach ($tenants as $tenant) {
            $organizationId = Schema::hasTable('organizations')
                ? DB::table('organizations')->where('tenant_id', $tenant->id)->orderBy('id')->value('id')
                : null;

            foreach ($definitions as [$category, $assetType, $name, $schema]) {
                $exists = DB::table('asset_category_templates')
                    ->where('tenant_id', $tenant->id)
                    ->where('category', $category)
                    ->where('asset_type', $assetType)
                    ->exists();

                if ($exists) {
                    continue;
                }

                DB::table('asset_category_templates')->insert([
                    'tenant_id' => $tenant->id,
                    'organization_id' => $organizationId,
                    'code' => 'SYS-'.$tenant->id.'-'.$assetType,
                    'system_group' => $category,
                    'category' => $category,
                    'asset_type' => $assetType,
                    'name' => $name,
                    'specification_schema' => json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    'active' => true,
                    'status' => 'ACTIVE',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('asset_category_templates')) {
            return;
        }

        DB::table('asset_category_templates')->where('code', 'like', 'SYS-%')->delete();
    }
};
