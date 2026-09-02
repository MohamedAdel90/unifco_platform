<div class="hp-img-field" data-img-field>
  <div class="hp-img-preview" data-img-preview>
    @if(!empty($value))
      <img src="{{ $value }}" alt="">
      <button type="button" class="hp-img-clear" data-img-clear title="Remove image">×</button>
    @else
      <span class="hp-img-ph">No image</span>
    @endif
  </div>
  <input
    type="text"
    class="img-picker-target hp-img-input"
    name="{{ $name }}"
    value="{{ $value }}"
    placeholder="{{ $placeholder ?? '/images/...' }}"
    data-img-input
  >
  <div class="hp-img-actions">
    <button type="button" class="btn-sm primary hp-img-select" data-img-select>Select / Upload</button>
  </div>
</div>
