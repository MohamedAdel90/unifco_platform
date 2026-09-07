@php
  $iconValue = trim((string) ($value ?? ''));
  $iconPreview = $iconValue;
  if ($iconPreview !== '' && !str_starts_with($iconPreview, '/') && !str_starts_with($iconPreview, 'http') && !str_starts_with($iconPreview, 'data:image')) {
      $iconPreview = '/images/home/icons/'.strtolower($iconPreview).'.svg';
  }
@endphp
<div class="hp-icon-field" data-icon-field>
  <div class="hp-icon-preview" data-icon-preview>
    @if($iconValue !== '')
      <img src="{{ $iconPreview }}" alt="">
    @else
      <span>No icon</span>
    @endif
  </div>
  <div class="hp-icon-control">
    <input type="hidden" class="icon-picker-target" name="{{ $name }}" value="{{ $iconValue }}" data-icon-label="{{ $label ?? $name }}">
    <div class="hp-icon-actions">
      <button type="button" class="btn-sm primary hp-icon-select">Choose from library</button>
      <button type="button" class="btn-sm hp-icon-upload">Upload icon</button>
      <button type="button" class="btn-sm danger hp-icon-clear" @disabled($iconValue === '')>Clear</button>
    </div>
    <small class="hp-icon-name" data-icon-name>{{ $iconValue !== '' ? basename($iconValue) : 'No icon selected' }}</small>
  </div>
</div>
