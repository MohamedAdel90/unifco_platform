        </div>
    </main>
</div>
@stack('late-styles')
@stack('scripts')
<script>(()=>{const app=document.querySelector('.portal-app'),button=document.getElementById('portal-sidebar-toggle');if(!app||!button)return;const key='unifco-customer-sidebar-collapsed';if(localStorage.getItem(key)==='1')app.classList.add('sidebar-collapsed');button.addEventListener('click',()=>{app.classList.toggle('sidebar-collapsed');localStorage.setItem(key,app.classList.contains('sidebar-collapsed')?'1':'0');button.textContent=app.classList.contains('sidebar-collapsed')?'⇥':'⇤'})})();</script>
</body>
</html>
