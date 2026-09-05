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

<script>
(function(){
  if(window.__unifcoCmsUploadGuardInstalled)return;
  window.__unifcoCmsUploadGuardInstalled=true;

  function setLoading(text,show){
    var el=document.getElementById('img-picker-loading');
    if(!el)return;
    el.textContent=text||'Preparing image…';
    el.style.display=show?'inline':'none';
  }

  function encodeJpeg(file){
    return new Promise(function(resolve,reject){
      var url=URL.createObjectURL(file);
      var img=new Image();
      img.onload=function(){
        try{
          var maxSide=2200;
          var scale=Math.min(1,maxSide/Math.max(img.naturalWidth||img.width,img.naturalHeight||img.height));
          var canvas=document.createElement('canvas');
          canvas.width=Math.max(1,Math.round((img.naturalWidth||img.width)*scale));
          canvas.height=Math.max(1,Math.round((img.naturalHeight||img.height)*scale));
          var ctx=canvas.getContext('2d');
          ctx.drawImage(img,0,0,canvas.width,canvas.height);

          var qualities=[0.88,0.80,0.72,0.64];
          var i=0;
          var next=function(){
            canvas.toBlob(function(blob){
              if(!blob){URL.revokeObjectURL(url);reject(new Error('Could not prepare this image for upload.'));return;}
              if(blob.size<=1400*1024 || i===qualities.length-1){
                URL.revokeObjectURL(url);
                resolve(new File([blob],(file.name||'homepage-image').replace(/\.[^.]+$/,'')+'.jpg',{type:'image/jpeg',lastModified:Date.now()}));
                return;
              }
              i++;next();
            },'image/jpeg',qualities[i]);
          };
          next();
        }catch(err){URL.revokeObjectURL(url);reject(err);}
      };
      img.onerror=function(){URL.revokeObjectURL(url);reject(new Error('The selected image format cannot be read by this browser.'));};
      img.src=url;
    });
  }

  function uploadPrepared(file){
    var target=window.__imgActiveTarget;
    if(!target)throw new Error('Choose the exact image field first.');
    var fd=new FormData();
    fd.append('file',file);
    setLoading('Uploading…',true);
    return fetch('{{ route("admin.homepage.images.upload") }}',{
      method:'POST',
      headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}','Accept':'application/json','X-Requested-With':'XMLHttpRequest'},
      credentials:'same-origin',
      body:fd
    }).then(function(r){
      return r.text().then(function(text){
        var data=null;
        try{data=text?JSON.parse(text):null;}catch(e){}
        if(!r.ok){throw new Error((data&&data.message)?data.message:'Upload failed (HTTP '+r.status+')');}
        if(!data||!data.image||!data.image.url)throw new Error('Upload completed without an image URL.');
        target.value=data.image.url;
        target.dispatchEvent(new Event('input',{bubbles:true}));
        return data;
      });
    });
  }

  document.addEventListener('change',function(e){
    var input=e.target;
    if(!input||input.id!=='img-picker-file'||!input.files||!input.files.length)return;
    e.preventDefault();
    e.stopImmediatePropagation();
    var file=input.files[0];
    input.value='';
    setLoading('Preparing image…',true);
    encodeJpeg(file)
      .then(uploadPrepared)
      .then(function(){setLoading('',false);})
      .catch(function(err){setLoading('',false);alert(err&&err.message?err.message:'Upload failed');});
  },true);
})();
</script>
