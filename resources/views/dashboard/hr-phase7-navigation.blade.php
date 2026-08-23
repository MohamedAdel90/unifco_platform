<script>
(function(){
function syncPhase7Nav(){
  const lang=document.documentElement.lang==='ar'?'ar':'en';
  document.querySelectorAll('.side .nav-group').forEach(function(group){
    const summary=group.querySelector('summary'); if(!summary)return;
    const title=summary.getAttribute('title')||'';
    if(title!=='People' && !/People|الموارد البشرية/.test(summary.textContent))return;
    const box=group.querySelector('.nav-children'); if(!box)return;
    let performance=box.querySelector('a[data-hr-phase7="performance"]');
    if(!performance){ performance=document.createElement('a'); performance.dataset.hrPhase7='performance'; performance.className='nav-link nav-secondary'; performance.href='/hr/performance'; box.appendChild(performance); }
    performance.textContent=lang==='ar'?'الأداء والتطوير الوظيفي':'Performance & Career';
    if(location.pathname==='/hr/performance'||location.pathname.startsWith('/hr/performance/'))performance.classList.add('active'); else performance.classList.remove('active');
    const skill=Array.from(box.querySelectorAll('a')).find(a=>/Skills \/ Certifications|المهارات \/ الشهادات/.test(a.textContent));
    if(skill){ skill.href='/hr/performance'; skill.textContent=lang==='ar'?'المهارات والشهادات':'Skills / Certifications'; }
  });
}
function boot(){setTimeout(syncPhase7Nav,0);setTimeout(syncPhase7Nav,80);new MutationObserver(syncPhase7Nav).observe(document.documentElement,{attributes:true,attributeFilter:['lang','dir']});}
document.readyState==='loading'?document.addEventListener('DOMContentLoaded',boot):boot();
})();
</script>
