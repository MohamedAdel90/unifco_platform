<div class="card" data-checklist data-base="check_{{$locale}}_{{$listKey}}">
  <div class="col-head"><b>{{ $listKey }} — <span style="color:#1e315b">{{ $locale==='ar'?'ARABIC':'ENGLISH' }}</span></b><button type="button" class="btn-sm primary add-row">+ Add item</button></div>
  <div class="rows check-list">
    @foreach($rows as $v)
      <div class="cl-row">
        <input type="text" name="check_{{$locale}}_{{$listKey}}[]" value="{{ $v }}">
        <button type="button" class="btn-sm danger remove-row">×</button>
      </div>
    @endforeach
  </div>
</div>
