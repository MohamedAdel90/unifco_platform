<div class="icon-library-modal" id="icon-library-modal" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="icon-library-title">
  <div class="icon-library-shell">
    <div class="icon-library-head">
      <div><strong id="icon-library-title">Homepage Icon Library</strong><small id="icon-library-help">Choose an icon image. Selection is shared between Arabic and English.</small></div>
      <button type="button" class="icon-library-close" aria-label="Close">×</button>
    </div>
    <div class="icon-library-toolbar">
      <input type="search" id="icon-library-search" placeholder="Search icons…" autocomplete="off">
      <button type="button" class="btn-sm primary" id="icon-library-upload">Upload external icon</button>
      <input type="file" id="icon-library-file" accept="image/*,.svg" hidden>
    </div>
    <div class="icon-library-status" id="icon-library-status" aria-live="polite"></div>
    <div class="icon-library-grid" id="icon-library-grid"></div>
  </div>
</div>
<style>
.hp-icon-field{display:grid;grid-template-columns:64px minmax(0,1fr);gap:10px;align-items:center;padding:8px;border:1px solid #dce5f0;border-radius:10px;background:#fff}.hp-icon-field.is-active{border-color:#2563eb;box-shadow:0 0 0 3px rgba(37,99,235,.1)}
.hp-icon-preview{width:64px;height:64px;display:grid;place-items:center;border:1px solid #d7e1ed;border-radius:10px;background:linear-gradient(145deg,#f8fafc,#edf3f9);overflow:hidden}.hp-icon-preview img{width:38px;height:38px;display:block;object-fit:contain}.hp-icon-preview span{font-size:9px;color:#8a97aa}.hp-icon-actions{display:flex;gap:6px;flex-wrap:wrap}.hp-icon-name{display:block;margin-top:6px;color:#728096;font-size:9px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.icon-library-modal{position:fixed;inset:0;z-index:12000;display:none;align-items:center;justify-content:center;padding:18px;background:rgba(8,18,35,.76)}.icon-library-modal.open{display:flex}.icon-library-shell{width:min(820px,100%);max-height:min(86vh,780px);display:grid;grid-template-rows:auto auto auto minmax(0,1fr);overflow:hidden;border-radius:14px;background:#fff;box-shadow:0 26px 80px rgba(0,0,0,.34)}
.icon-library-head{display:flex;align-items:center;justify-content:space-between;gap:16px;padding:17px 20px;background:#102d5c;color:#fff}.icon-library-head strong{display:block;font-size:15px}.icon-library-head small{display:block;margin-top:3px;color:#d5e2f2;font-size:10px}.icon-library-close{width:34px;height:34px;border:1px solid rgba(255,255,255,.45);border-radius:8px;background:transparent;color:#fff;font-size:23px;cursor:pointer}
.icon-library-toolbar{display:flex;gap:9px;padding:13px 16px;border-bottom:1px solid #e3e8ef}.icon-library-toolbar input{flex:1;min-width:0;border:1px solid #d6dee9;border-radius:8px;padding:8px 11px}.icon-library-status{display:none;padding:8px 16px;font-size:10px;color:#53647d;background:#f7f9fc}.icon-library-status.show{display:block}.icon-library-status.error{color:#a90f2d;background:#fff4f4}
.icon-library-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(112px,1fr));gap:10px;overflow:auto;padding:16px;background:#f7f9fc}.icon-library-cell{position:relative;min-height:106px;display:grid;place-items:center;align-content:center;gap:8px;border:1px solid #dce4ef;border-radius:11px;background:#fff;cursor:pointer;transition:.15s}.icon-library-cell:hover,.icon-library-cell.selected{border-color:#2563eb;box-shadow:0 3px 12px rgba(37,99,235,.16);transform:translateY(-1px)}.icon-library-cell img{width:48px;height:48px;object-fit:contain}.icon-library-cell span{width:92%;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;text-align:center;color:#526177;font-size:9px}.icon-library-delete{position:absolute;top:4px;right:4px;width:23px;height:23px;border:2px solid #fff;border-radius:50%;background:#d7193f;color:#fff;font-size:16px;line-height:16px;cursor:pointer}.icon-library-empty{grid-column:1/-1;padding:35px;text-align:center;color:#728096;font-size:12px}
@media(max-width:640px){.hp-icon-field{grid-template-columns:52px minmax(0,1fr)}.hp-icon-preview{width:52px;height:52px}.hp-icon-preview img{width:32px;height:32px}.icon-library-modal{padding:5px}.icon-library-shell{max-height:96vh}.icon-library-grid{grid-template-columns:repeat(3,minmax(0,1fr));padding:10px}.icon-library-toolbar{flex-wrap:wrap}.icon-library-toolbar input{flex-basis:100%}}
</style>
<script>
(function(){
  var modal=document.getElementById('icon-library-modal');
  var grid=document.getElementById('icon-library-grid');
  var search=document.getElementById('icon-library-search');
  var fileInput=document.getElementById('icon-library-file');
  var uploadButton=document.getElementById('icon-library-upload');
  var status=document.getElementById('icon-library-status');
  var csrf='{{ csrf_token() }}';
  var listUrl='{{ route("admin.homepage.images.list") }}?section=icons';
  var uploadUrl='{{ route("admin.homepage.images.upload") }}';
  var deleteBase='{{ url('/admin/homepage/image-library') }}';
  var icons=[];
  var active=null;
  if(!modal||!grid)return;

  function iconSource(value){
    value=(value||'').trim();
    if(!value)return '';
    if(value.charAt(0)==='/'||/^https?:\/\//i.test(value)||value.indexOf('data:image')===0)return value;
    return '/images/home/icons/'+value.toLowerCase()+'.svg';
  }
  function paired(input){
    if(!input||!input.name)return null;
    var name=input.name.indexOf('item_ar_')===0?input.name.replace('item_ar_','item_en_'):input.name.indexOf('item_en_')===0?input.name.replace('item_en_','item_ar_'):'';
    return name?document.querySelector('[name="'+CSS.escape(name)+'"]'):null;
  }
  function refresh(input){
    var field=input&&input.closest('[data-icon-field]');
    if(!field)return;
    var value=(input.value||'').trim();
    var preview=field.querySelector('[data-icon-preview]');
    var name=field.querySelector('[data-icon-name]');
    var clear=field.querySelector('.hp-icon-clear');
    preview.innerHTML='';
    if(value){var img=document.createElement('img');img.src=iconSource(value);img.alt='';preview.appendChild(img);}else{var empty=document.createElement('span');empty.textContent='No icon';preview.appendChild(empty);}
    if(name)name.textContent=value?(value.split('/').pop()||value):'No icon selected';
    if(clear)clear.disabled=!value;
  }
  function commit(value){
    if(!active)return;
    [active,paired(active)].filter(Boolean).forEach(function(input){input.value=value;input.dispatchEvent(new Event('input',{bubbles:true}));refresh(input);});
    close();
  }
  function activate(field){
    active=field&&field.querySelector('.icon-picker-target');
    document.querySelectorAll('[data-icon-field]').forEach(function(el){el.classList.toggle('is-active',el===field);});
    return !!active;
  }
  function open(){modal.classList.add('open');modal.setAttribute('aria-hidden','false');render();setTimeout(function(){search.focus();},30);}
  function close(){modal.classList.remove('open');modal.setAttribute('aria-hidden','true');}
  function message(text,isError){status.textContent=text;status.className='icon-library-status show'+(isError?' error':'');if(!isError)setTimeout(function(){status.className='icon-library-status';},2200);}
  function render(){
    var term=(search.value||'').trim().toLowerCase();
    var current=active?(active.value||'').trim():'';
    grid.innerHTML='';
    icons.filter(function(icon){return !term||(icon.name||'').toLowerCase().indexOf(term)!==-1;}).forEach(function(icon){
      var cell=document.createElement('div');cell.className='icon-library-cell'+(current===icon.url?' selected':'');cell.title=icon.name||'Icon';cell.dataset.url=icon.url;cell.setAttribute('role','button');cell.tabIndex=0;
      var img=document.createElement('img');img.src=icon.url;img.alt='';var label=document.createElement('span');label.textContent=(icon.name||'icon').replace(/\.[^.]+$/,'').replace(/[-_]/g,' ');cell.appendChild(img);cell.appendChild(label);
      if(icon.deletable&&icon.delete_key){var del=document.createElement('button');del.type='button';del.className='icon-library-delete';del.dataset.deleteKey=icon.delete_key;del.title='Delete uploaded icon';del.setAttribute('aria-label','Delete uploaded icon');del.textContent='×';cell.appendChild(del);}
      grid.appendChild(cell);
    });
    if(!grid.children.length)grid.innerHTML='<div class="icon-library-empty">No matching icons.</div>';
  }
  function load(){
    message('Loading icon library…',false);
    fetch(listUrl,{headers:{Accept:'application/json'},credentials:'same-origin'}).then(function(r){if(!r.ok)throw new Error('Could not load icon library.');return r.json();}).then(function(data){icons=data.images||[];status.className='icon-library-status';render();}).catch(function(error){message(error.message||'Could not load icon library.',true);});
  }
  function normalize(file){
    return new Promise(function(resolve,reject){
      if(!file||!/^image\//i.test(file.type||'')){reject(new Error('Choose an image file for the icon.'));return;}
      var url=URL.createObjectURL(file);var img=new Image();
      img.onload=function(){try{var canvas=document.createElement('canvas');canvas.width=512;canvas.height=512;var ctx=canvas.getContext('2d');var max=400;var scale=Math.min(max/img.naturalWidth,max/img.naturalHeight);var w=Math.max(1,Math.round(img.naturalWidth*scale));var h=Math.max(1,Math.round(img.naturalHeight*scale));ctx.clearRect(0,0,512,512);ctx.drawImage(img,(512-w)/2,(512-h)/2,w,h);canvas.toBlob(function(blob){URL.revokeObjectURL(url);if(!blob){reject(new Error('Could not prepare this icon.'));return;}resolve(new File([blob],(file.name||'icon').replace(/\.[^.]+$/,'')+'.png',{type:'image/png',lastModified:Date.now()}));},'image/png');}catch(error){URL.revokeObjectURL(url);reject(error);}};
      img.onerror=function(){URL.revokeObjectURL(url);reject(new Error('This image format could not be read. Use SVG, PNG, JPG or WEBP.'));};img.src=url;
    });
  }
  function upload(file){
    message('Preparing icon to match the homepage design…',false);
    normalize(file).then(function(prepared){var data=new FormData();data.append('file',prepared);data.append('section','icons');return fetch(uploadUrl,{method:'POST',headers:{'X-CSRF-TOKEN':csrf,Accept:'application/json','X-Requested-With':'XMLHttpRequest'},credentials:'same-origin',body:data});}).then(function(r){return r.json().then(function(data){if(!r.ok)throw new Error(data.message||'Upload failed.');return data;});}).then(function(data){icons.unshift(data.image);render();message('Icon uploaded and resized to a transparent square.',false);commit(data.image.url);}).catch(function(error){message(error.message||'Upload failed.',true);});
  }
  document.addEventListener('click',function(e){
    var choose=e.target.closest('.hp-icon-select');var uploadField=e.target.closest('.hp-icon-upload');var clearField=e.target.closest('.hp-icon-clear');
    if(choose||uploadField){var field=(choose||uploadField).closest('[data-icon-field]');if(!activate(field))return;if(uploadField){fileInput.click();}else{open();}return;}
    if(clearField){var clearContainer=clearField.closest('[data-icon-field]');if(activate(clearContainer))commit('');return;}
  });
  grid.addEventListener('click',function(e){
    var del=e.target.closest('.icon-library-delete');
    if(del){e.preventDefault();e.stopPropagation();if(!confirm('Delete this uploaded icon permanently?'))return;var cell=del.closest('.icon-library-cell');fetch(deleteBase+'/'+encodeURIComponent(del.dataset.deleteKey),{method:'DELETE',headers:{'X-CSRF-TOKEN':csrf,Accept:'application/json','X-Requested-With':'XMLHttpRequest'},credentials:'same-origin'}).then(function(r){if(!r.ok)throw new Error('Delete failed.');icons=icons.filter(function(icon){return icon.url!==cell.dataset.url;});document.querySelectorAll('.icon-picker-target').forEach(function(input){if(input.value===cell.dataset.url){input.value='';refresh(input);}});render();message('Uploaded icon deleted.',false);}).catch(function(error){message(error.message,true);});return;}
    var cell=e.target.closest('.icon-library-cell');if(cell)commit(cell.dataset.url);
  });
  grid.addEventListener('keydown',function(e){var cell=e.target.closest('.icon-library-cell');if(cell&&(e.key==='Enter'||e.key===' ')){e.preventDefault();cell.click();}});
  search.addEventListener('input',render);fileInput.addEventListener('change',function(){if(this.files&&this.files[0])upload(this.files[0]);this.value='';});uploadButton.addEventListener('click',function(){if(active)fileInput.click();});
  modal.querySelector('.icon-library-close').addEventListener('click',close);modal.addEventListener('click',function(e){if(e.target===modal)close();});document.addEventListener('keydown',function(e){if(e.key==='Escape'&&modal.classList.contains('open'))close();});
  document.addEventListener('input',function(e){if(e.target.classList&&e.target.classList.contains('icon-picker-target'))refresh(e.target);});
  document.querySelectorAll('.icon-picker-target').forEach(refresh);load();
})();
</script>
