<div class="hp-img-field" data-img-field>
  <div class="hp-img-preview" data-img-preview>
    @if(!empty($value))
      <img src="{{ $value }}" alt="">
      <button type="button" class="hp-img-clear" data-img-clear title="Remove image">×</button>
    @else
      <span class="hp-img-ph">No image</span>
    @endif
  </div>
  <input type="hidden" class="img-picker-target hp-img-input" name="{{ $name }}" value="{{ $value }}" data-img-input data-img-label="{{ $label ?? $name }}">
  <div class="hp-img-actions">
    <button type="button" class="btn-sm primary hp-img-upload" data-img-upload>Upload Image</button>
    <button type="button" class="btn-sm hp-img-select" data-img-select>Choose Existing</button>
  </div>
</div>
