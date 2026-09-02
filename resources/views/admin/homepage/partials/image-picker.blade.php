<div class="img-picker">
  <div class="img-picker-head">
    <strong>Image Picker</strong>
    <small>Click an image to insert its path, or upload a new one.</small>
  </div>
  <input type="file" id="img-picker-file" accept="image/*" style="display:none">
  <div class="img-picker-toolbar">
    <button type="button" class="btn btn-sm" id="img-picker-upload-btn">Upload New Image</button>
    <div class="img-picker-loading" id="img-picker-loading" style="display:none;font-size:11px;color:#728096">Uploading…</div>
  </div>
  <div class="img-picker-grid" id="img-picker-grid"></div>
  <template id="img-picker-empty"><div class="img-picker-none">No images yet. Upload one to get started.</div></template>
</div>
<style>
.img-picker{border:1px solid #e3e8ef;border-radius:12px;padding:14px;background:#fbfcfe;margin-top:8px}
.img-picker-head strong{font-size:13px;color:#17243c}.img-picker-head small{display:block;font-size:10px;color:#728096;margin-top:2px}
.img-picker-toolbar{margin:10px 0}
.img-picker-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(96px,1fr));gap:10px}
.img-picker-cell{border:1px solid #e3e8ef;border-radius:10px;background:#fff;padding:6px;cursor:pointer;display:block;text-align:center}
.img-picker-cell img{width:100%;height:64px;object-fit:cover;border-radius:6px;background:#f2f6fa}
.img-picker-cell span{display:block;font-size:9px;color:#526177;margin-top:4px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.img-picker-cell:hover{border-color:#2563eb;box-shadow:0 2px 8px rgba(37,99,235,.15)}
.img-picker-none{font-size:12px;color:#728096;padding:24px;text-align:center;border:1px dashed #bcc7d6;border-radius:10px}
</style>
<script>
(function(){
  var grid=document.getElementById('img-picker-grid');
  var pickerFile=document.getElementById('img-picker-file');
  var loading=document.getElementById('img-picker-loading');
  var uploadBtn=document.getElementById('img-picker-upload-btn');
  var targetInputs=document.querySelectorAll('input.img-picker-target');
  if(!grid)return;
  var insert=function(url){targetInputs.forEach(function(i){if(!i.value)i.value=url});};
  function loadImages(){
    fetch('{{ route("admin.homepage.images.list") }}',{headers:{'Accept':'application/json'}})
      .then(function(r){return r.json();})
      .then(function(data){
        if(!grid)return;
        grid.innerHTML='';
        var imgs=(data.images||[]);
        if(!imgs.length){grid.innerHTML='<div class="img-picker-none">No images yet. Upload one to get started.</div>';return;}
        imgs.forEach(function(img){
          var b=document.createElement('button');b.type='button';b.className='img-picker-cell';b.setAttribute('data-url',img.url);b.title=img.name;
          var im=document.createElement('img');im.src=img.url;im.alt=img.name;
          var sp=document.createElement('span');sp.textContent=(img.name||'').slice(0,18);
          b.appendChild(im);b.appendChild(sp);grid.appendChild(b);
        });
      });
  }
  grid.addEventListener('click',function(e){
    var cell=e.target.closest('.img-picker-cell');
    if(cell){insert(cell.getAttribute('data-url'));cell.style.outline='2px solid #2563eb';setTimeout(function(){cell.style.outline=''},800);}
  });
  uploadBtn.addEventListener('click',function(){pickerFile.click();});
  pickerFile.addEventListener('change',function(){
    if(!pickerFile.files.length)return;
    var fd=new FormData();fd.append('file',pickerFile.files[0]);
    loading.style.display='inline';
    fetch('{{ route("admin.homepage.images.upload") }}',{method:'POST',headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}','Accept':'application/json'},body:fd})
      .then(function(r){return r.json();})
      .then(function(data){
        loading.style.display='none';
        if(data && data.image && data.image.url){
          insert(data.image.url);
          loadImages();
        }
      }).catch(function(){loading.style.display='none';alert('Upload failed');});
  });
  loadImages();
})();
</script>
