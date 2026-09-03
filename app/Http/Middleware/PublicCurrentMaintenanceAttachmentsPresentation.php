<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PublicCurrentMaintenanceAttachmentsPresentation
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $request->routeIs('public.current-maintenance') || ! method_exists($response, 'getContent') || ! method_exists($response, 'setContent')) {
            return $response;
        }

        $html = (string) $response->getContent();

        $styles = <<<'HTML'
<style id="unifco-current-maintenance-attachments-v1">
.maintenance-attachments{display:grid;grid-template-columns:repeat(3,1fr);gap:18px;direction:rtl}
.maintenance-attachment-card{min-height:118px;border:1px dashed #c6d5e6;border-radius:9px;background:#fff;padding:13px 16px 11px;display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center}
.maintenance-attachment-title{display:flex;align-items:center;justify-content:center;gap:7px;color:#102b58;font-size:11px;font-weight:900;margin-bottom:10px}
.maintenance-attachment-title svg{width:18px;height:18px;fill:none;stroke:currentColor;stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round}
.maintenance-attachment-actions{display:flex;align-items:center;justify-content:center;gap:8px;flex-wrap:wrap}
.maintenance-upload-btn{height:34px;min-width:122px;padding:0 13px;border-radius:6px;border:1px solid #cbd7e6;background:#fff;color:#17345e;display:inline-flex;align-items:center;justify-content:center;gap:6px;font-size:9px;font-weight:900;cursor:pointer}
.maintenance-upload-btn.camera{background:#071f4d;color:#fff;border-color:#071f4d}
.maintenance-upload-btn svg{width:14px;height:14px;fill:none;stroke:currentColor;stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round}
.maintenance-upload-input{position:absolute!important;width:1px!important;height:1px!important;overflow:hidden!important;clip:rect(0 0 0 0)!important;white-space:nowrap!important;clip-path:inset(50%)!important}
.maintenance-attachment-types{margin-top:8px;color:#74849b;font-size:8px;line-height:1.5;direction:ltr}
.maintenance-attachment-limit{color:#74849b;font-size:8px;line-height:1.4;margin-top:1px}
@media(max-width:850px){.maintenance-attachments{grid-template-columns:1fr 1fr}.maintenance-attachment-card:last-child{grid-column:1/-1}}
@media(max-width:560px){.maintenance-attachments{grid-template-columns:1fr}.maintenance-attachment-card:last-child{grid-column:auto}.maintenance-attachment-actions{width:100%}.maintenance-upload-btn{flex:1;min-width:0}}
</style>
HTML;

        $html = str_replace('</head>', $styles.'</head>', $html);

        $old = '<section class="panel"><h2 class="section-title"><span class="num">7</span> مرفقات الطلب</h2><div class="uploads"><label class="upload"><b>صور المشكلة</b><input type="file" name="problem_photos[]" accept="image/*" multiple></label><label class="upload"><b>صور المعدة</b><input type="file" name="equipment_photos[]" accept="image/*" multiple></label><label class="upload"><b>تقرير سابق (إن وجد)</b><input type="file" name="previous_reports[]" accept=".pdf,.xlsx,.xls,.doc,.docx,image/*" multiple></label></div></section>';

        $cameraIcon = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 8h3l1.5-2h7L17 8h3a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2v-8a2 2 0 0 1 2-2Z"/><circle cx="12" cy="14" r="3.5"/></svg>';
        $uploadIcon = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 16V4M7 9l5-5 5 5M5 20h14"/></svg>';
        $docIcon = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 3h8l4 4v14H6z"/><path d="M14 3v5h5"/></svg>';

        $card = static function (string $title, string $name, string $accept, string $icon, bool $report = false) use ($cameraIcon, $uploadIcon): string {
            $captureAccept = 'image/*';
            $types = $report ? 'PDF, DOC, DOCX, XLS, XLSX, JPEG, PNG' : 'JPEG, PNG';
            return '<div class="maintenance-attachment-card">'
                .'<div class="maintenance-attachment-title">'.$icon.'<span>'.$title.'</span></div>'
                .'<div class="maintenance-attachment-actions">'
                .'<label class="maintenance-upload-btn camera">'.$cameraIcon.'<span>التقاط صورة</span><input class="maintenance-upload-input" type="file" name="'.$name.'[]" accept="'.$captureAccept.'" capture="environment"></label>'
                .'<label class="maintenance-upload-btn">'.$uploadIcon.'<span>اختيار من الجهاز</span><input class="maintenance-upload-input" type="file" name="'.$name.'[]" accept="'.$accept.'" multiple></label>'
                .'</div>'
                .'<div class="maintenance-attachment-types">'.$types.'</div>'
                .'<div class="maintenance-attachment-limit">الحد الأقصى لحجم الملف 10MB</div>'
                .'</div>';
        };

        $problemIcon = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 8h3l1.5-2h7L17 8h3a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2v-8a2 2 0 0 1 2-2Z"/><circle cx="12" cy="14" r="3.5"/></svg>';
        $equipmentIcon = $problemIcon;

        $replacement = '<section class="panel"><h2 class="section-title"><span class="num">7</span> مرفقات الطلب</h2><div class="maintenance-attachments">'
            .$card('صور المشكلة', 'problem_photos', 'image/*', $problemIcon)
            .$card('صور المعدة', 'equipment_photos', 'image/*', $equipmentIcon)
            .$card('تقرير سابق (إن وجد)', 'previous_reports', '.pdf,.doc,.docx,.xls,.xlsx,image/*', $docIcon, true)
            .'</div></section>';

        if (str_contains($html, $old)) {
            $html = str_replace($old, $replacement, $html);
        }

        $response->setContent($html);
        return $response;
    }
}
