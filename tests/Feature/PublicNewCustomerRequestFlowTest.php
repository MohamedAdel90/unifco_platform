<?php

namespace Tests\Feature;

use Tests\TestCase;

class PublicNewCustomerRequestFlowTest extends TestCase
{
    public function test_new_customer_flow_uses_direct_equipment_card_and_keeps_shared_request_cards(): void
    {
        $this->get('/request-service')
            ->assertOk()
            ->assertSee('id="new-customer-panel"', false)
            ->assertSee('id="new-customer-equipment-panel"', false)
            ->assertSee('أدخل بيانات المعدة مباشرة.', false)
            ->assertSee('id="new_equipment_name"', false)
            ->assertSee('id="new_equipment_type"', false)
            ->assertSee('body.uf-new-customer-mode .uf-customer-context-layout', false)
            ->assertSee('body.uf-new-customer-mode .uf-workspace .uf-asset-pane{display:none!important}', false)
            ->assertSee("detailsNum.textContent=isNew?'3':'5'", false)
            ->assertSee('appendEquipmentDetails()', false)
            ->assertDontSee('id="new_project_name"', false)
            ->assertDontSee('id="new_equipment_location"', false);
    }

    public function test_new_customer_equipment_card_is_localized_in_english(): void
    {
        $this->get('/request-service?lang=en')
            ->assertOk()
            ->assertSee('<html lang="en" dir="ltr">', false)
            ->assertSee("'أدخل بيانات المعدة مباشرة.':'Enter the equipment information directly.'", false)
            ->assertSee("'اسم مسؤول التواصل':'Contact Name'", false)
            ->assertSee("'رقم الأصل إن وجد':'Asset Number (if available)'", false);
    }

    public function test_new_customer_spare_parts_quote_has_repeatable_parts_card_without_replacing_current_customer_ui(): void
    {
        $this->get('/request-service')
            ->assertOk()
            ->assertSee('id="unifco-new-spare-parts-script-v1"', false)
            ->assertSee('uf-new-spare-part', false)
            ->assertSee('data-part-description', false)
            ->assertSee('id="uf-new-spare-add"', false)
            ->assertSee('صور القطعة أو الجزء المطلوب', false)
            ->assertSee("newPanel.classList.contains('show')&&service.value==='quotation'&&subtype.value==='parts'", false)
            ->assertSee("textarea[name=\"details\"]:not(:disabled)", false);
    }
}
