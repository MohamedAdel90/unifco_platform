<div class="card" data-repeater>
  <div class="col-head"><b>{{ $listKey }} — <span style="color:#1e315b">{{ $locale==='ar'?'ARABIC':'ENGLISH' }}</span></b><button type="button" class="btn-sm primary add-row">+ Add {{ rtrim($listKey,'s') }}</button></div>
  <div class="rows repeat-grid">
    @foreach($rows as $i => $row)
      <div class="item-block">
        <div class="item-title"><span>#{{ $i+1 }} <span class="idx">{{ $listKey }}</span></span><button type="button" class="btn-sm danger remove-row">Remove</button></div>
        <div class="item-subrow">
          @foreach($itemFields as $f)
            <div>
              <label>{{ $f }}</label>
              @if($f==='image')
                <div class="image-slot"><input type="text" name="item_{{$locale}}_{{$listKey}}_{{$i}}_{{$f}}" class="img-picker-target" value="{{ $row[$f] ?? '' }}" placeholder="/images/..."></div>
              @elseif($f==='desc')
                <textarea name="item_{{$locale}}_{{$listKey}}_{{$i}}_{{$f}}">{{ $row[$f] ?? '' }}</textarea>
              @else
                <input type="text" name="item_{{$locale}}_{{$listKey}}_{{$i}}_{{$f}}" value="{{ $row[$f] ?? '' }}">
              @endif
            </div>
          @endforeach
        </div>
        <input type="hidden" name="item_{{$locale}}_{{$listKey}}_index[]" value="{{ $i }}">
      </div>
    @endforeach
  </div>
  <template class="row-template">
    <div class="item-block">
      <div class="item-title"><span>#<span>+</span> <span class="idx">{{ $listKey }}</span></span><button type="button" class="btn-sm danger remove-row">Remove</button></div>
      <div class="item-subrow">
        @foreach($itemFields as $f)
          <div>
            <label>{{ $f }}</label>
            @if($f==='image')
              <div class="image-slot"><input type="text" name="item_{{$locale}}_{{$listKey}}_<?= '{{index}}' ?>_{{$f}}" class="img-picker-target" placeholder="/images/..."></div>
            @elseif($f==='desc')
              <textarea name="item_{{$locale}}_{{$listKey}}_<?= '{{index}}' ?>_{{$f}}"></textarea>
            @else
              <input type="text" name="item_{{$locale}}_{{$listKey}}_<?= '{{index}}' ?>_{{$f}}">
            @endif
          </div>
        @endforeach
      </div>
      <input type="hidden" name="item_{{$locale}}_{{$listKey}}_index[]" value="<?= '{{index}}' ?>">
    </div>
  </template>
</div>
