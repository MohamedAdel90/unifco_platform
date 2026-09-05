<div class="img-picker">
  <div class="img-picker-head">
    <strong>Image Picker</strong>
    <small id="img-picker-help">Choose an image field first, then select or upload an image.</small>
  </div>
  <input type="file" id="img-picker-file" accept="image/*,.heic,.heif" style="display:none">
  <div class="img-picker-toolbar">
    <button type="button" class="btn btn-sm" id="img-picker-upload-btn">Upload New Image</button>
    <div class="img-picker-loading" id="img-picker-loading" style="display:none;font-size:11px;color:#728096">Preparing image…</div>
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
  var help=document.getElementById('img-picker-help');
  if(!grid)return;

  window.__imgActiveTarget=null;

  var targetLabel=function(input){
    if(!input)return '';
    return input.getAttribute('data-img-label') || input.name || 'selected image field';
  };

  var requireActive=function(){
    var active=window.__imgActiveTarget;
    if(active && document.body.contains(active))return active;
    if(help){
      help.textContent='Select “Choose Existing” or “Upload Image” beside the exact image field first.';
      help.style.color='#9f1f1f';
    }
    return null;
  };

  var commit=function(url,el){
    if(!el)return;
    el.value=url;
    el.dispatchEvent(new Event('input',{bubbles:true}));
  };

  var insert=function(url){
    var active=requireActive();
    if(!active)return false;
    commit(url,active);
    return true;
  };

  var activate=function(field){
    var input=field?field.querySelector('.img-picker-target'):null;
    if(!input)return null;
    window.__imgActiveTarget=input;
    document.querySelectorAll('[data-img-field]').forEach(function(f){f.classList.remove('active-img-field');});
    field.classList.add('active-img-field');
    if(help){
      help.textContent='Target: '+targetLabel(input)+'. Only this field will be changed.';
      help.style.color='#1f4e9c';
    }
    return input;
  };

  document.addEventListener('click',function(e){
    var upload=e.target.closest('.hp-img-upload');
    var choose=e.target.closest('.hp-img-select');
    if(!upload&&!choose)return;
    var field=(upload||choose).closest('[data-img-field]');
    if(!activate(field))return;
    if(upload){pickerFile.click();return;}
    if(choose){grid.scrollIntoView({behavior:'smooth',block:'center'});}
  });

  var refreshPreview=function(field){
    var input=field.querySelector('.img-picker-target');
    var pv=field.querySelector('[data-img-preview]');
    if(!input||!pv)return;
    var v=(input.value||'').trim();
    if(!v){pv.innerHTML='<span class="hp-img-ph">No image</span>';return;}
    pv.innerHTML='<img src="'+v.replace(/"/g,'&quot;')+'" alt=""><button type="button" class="hp-img-clear" data-img-clear title="Remove image">×</button>';
  };

  document.addEventListener('input',function(e){
    var t=e.target;
    if(t.classList&&t.classList.contains('img-picker-target')){
      var field=t.closest('[data-img-field]');
      if(field)refreshPreview(field);
    }
  });

  document.addEventListener('click',function(e){
    var clr=e.target.closest('[data-img-clear]');
    if(!clr)return;
    var field=clr.closest('[data-img-field]');
    var input=field?field.querySelector('.img-picker-target'):null;
    if(input){
      activate(field);
      input.value='';
      input.dispatchEvent(new Event('input',{bubbles:true}));
    }
  });

  function loadImages(){
    fetch('{{ route("admin.homepage.images.list") }}',{headers:{'Accept':'application/json'},credentials:'same-origin'})
      .then(function(r){return r.json();})
      .then(function(data){
        grid.innerHTML='';
        var imgs=(data.images||[]);
        if(!imgs.length){grid.innerHTML='<div class="img-picker-none">No images yet. Upload one to get started.</div>';return;}
        imgs.forEach(function(img){
          var b=document.createElement('button');
          b.type='button';b.className='img-picker-cell';b.setAttribute('data-url',img.url);b.title=img.name;
          var im=document.createElement('img');im.src=img.url;im.alt=img.name;
          var sp=document.createElement('span');sp.textContent=(img.name||'').slice(0,18);
          b.appendChild(im);b.appendChild(sp);grid.appendChild(b);
        });
      });
  }

  grid.addEventListener('click',function(e){
    var cell=e.target.closest('.img-picker-cell');
    if(!cell)return;
    if(insert(cell.getAttribute('data-url'))){
      cell.style.outline='2px solid #2563eb';
      setTimeout(function(){cell.style.outline=''},800);
    }
  });

  uploadBtn.addEventListener('click',function(){
    if(requireActive())pickerFile.click();
  });

  function normalizeForUpload(file){
    return new Promise(function(resolve){
      var standard=/^image\/(jpeg|png|webp|gif)$/i.test(file.type||'');
      var needsConversion=!standard || file.size>8*1024*1024;
      if(!needsConversion){resolve(file);return;}

      var url=URL.createObjectURL(file);
      var img=new Image();
      img.onload=function(){
        try{
          var maxSide=3200;
          var scale=Math.min(1,maxSide/Math.max(img.naturalWidth||img.width,img.naturalHeight||img.height));
          var canvas=document.createElement('canvas');
          canvas.width=Math.max(1,Math.round((img.naturalWidth||img.width)*scale));
          canvas.height=Math.max(1,Math.round((img.naturalHeight||img.height)*scale));
          var ctx=canvas.getContext('2d');
          ctx.drawImage(img,0,0,canvas.width,canvas.height);
          canvas.toBlob(function(blob){
            URL.revokeObjectURL(url);
            if(!blob){resolve(file);return;}
            resolve(new File([blob],(file.name||'homepage-image').replace(/\.[^.]+$/, '')+'.jpg',{type:'image/jpeg',lastModified:Date.now()}));
          },'image/jpeg',0.92);
        }catch(e){URL.revokeObjectURL(url);resolve(file);}
      };
      img.onerror=function(){URL.revokeObjectURL(url);resolve(file);};
      img.src=url;
    });
  }

  function uploadImage(file){
    var fd=new FormData();
    fd.append('file',file);
    loading.textContent='Uploading…';
    loading.style.display='inline';

    return fetch('{{ route("admin.homepage.images.upload") }}',{
      method:'POST',
      headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}','Accept':'application/json','X-Requested-With':'XMLHttpRequest'},
      credentials:'same-origin',
      body:fd
    }).then(function(r){
      return r.text().then(function(text){
        var data=null;
        try{data=text?JSON.parse(text):null;}catch(e){}
        if(!r.ok){
          var msg=(data&&data.message)?data.message:'Upload failed (HTTP '+r.status+')';
          if(r.status===419)msg='Your CMS session expired. Reload the page, sign in if requested, then upload again.';
          throw new Error(msg);
        }
        return data;
      });
    });
  }

  pickerFile.addEventListener('change',function(){
    if(!pickerFile.files.length)return;
    if(!requireActive()){pickerFile.value='';return;}
    var original=pickerFile.files[0];
    loading.textContent='Preparing image…';
    loading.style.display='inline';

    normalizeForUpload(original)
      .then(uploadImage)
      .then(function(data){
        loading.style.display='none';
        if(data && data.image && data.image.url){
          insert(data.image.url);
          loadImages();
        }else{
          throw new Error('Upload completed but no image URL was returned.');
        }
      })
      .catch(function(err){
        loading.style.display='none';
        alert(err && err.message ? err.message : 'Upload failed');
      });
    pickerFile.value='';
  });

  loadImages();
})();
</script>
