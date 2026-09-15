@include('customer.partials.portal-shell-open',[
    'customer'=>$customer,
    'activeSection'=>'inbox',
    'pageTitle'=>'صندوق الوارد والدعم · Inbox & Support',
    'pageDescription'=>'محادثات موحدة بين شركتك وفريق UNIFCO',
    'unreadInbox'=>$unread,
])

<div class="portal-page-head"><div><h2>صندوق الوارد والدعم</h2><p>جميع مراسلات الشركة مع UNIFCO في سجل واحد قابل للمتابعة.</p></div><span class="portal-pill {{ $unread ? 'red' : 'green' }}">{{ $unread }} غير مقروء</span></div>
<div class="inbox-layout">
    <section class="portal-card inbox-list">
        <div class="inbox-title"><h3>المحادثات</h3><span>{{ $conversations->count() }}</span></div>
        <form class="inbox-new" method="POST" action="{{ route('customer.inbox.start') }}">@csrf
            <input name="subject" maxlength="180" placeholder="عنوان الرسالة" required>
            <textarea name="body" maxlength="4000" placeholder="اكتب رسالتك إلى فريق UNIFCO" required></textarea>
            <button class="portal-btn red">بدء محادثة جديدة</button>
        </form>
        <div class="threads">@forelse($conversations as $conversation)<a class="thread {{ $activeConversation && $activeConversation->id===$conversation->id?'active':'' }}" href="{{ route('customer.inbox',['conversation'=>$conversation->id]) }}"><strong>{{ $conversation->subject }}</strong><small>{{ $conversation->last_message_at ?: $conversation->created_at }}</small></a>@empty<div class="portal-empty"><strong>لا توجد محادثات</strong>ابدأ رسالة جديدة للتواصل مع الفريق.</div>@endforelse</div>
    </section>
    <section class="portal-card chat-panel">
        @if($activeConversation)
            <div class="chat-head"><div><strong>{{ $activeConversation->subject }}</strong><small>محادثة مرتبطة بحساب الشركة الموحد</small></div></div>
            <div class="messages">@forelse($messages as $message)<div class="message {{ strtolower($message->sender_side) }}"><div>{{ $message->body }}</div><small>{{ $message->created_at }}</small></div>@empty<div class="portal-empty">لا توجد رسائل في هذه المحادثة.</div>@endforelse</div>
            <form class="reply" method="POST" action="{{ route('customer.inbox.reply',$activeConversation->id) }}">@csrf<textarea name="body" maxlength="4000" placeholder="اكتب رد الشركة..." required></textarea><button class="portal-btn">إرسال الرد</button></form>
        @else
            <div class="portal-empty chat-empty"><strong>اختر محادثة للمتابعة</strong>أو ابدأ محادثة جديدة مع فريق UNIFCO.</div>
        @endif
    </section>
</div>

@push('late-styles')<style>
.inbox-layout{display:grid;grid-template-columns:340px minmax(0,1fr);gap:12px}.inbox-list,.chat-panel{min-height:640px;overflow:hidden}.inbox-title,.chat-head{padding:15px;border-bottom:1px solid var(--line);display:flex;align-items:center;justify-content:space-between}.inbox-title h3{margin:0;font-size:13px}.inbox-title span{font-size:9px;color:var(--muted)}.inbox-new{padding:13px;border-bottom:1px solid var(--line)}.inbox-new input,.inbox-new textarea,.reply textarea{width:100%;border:1px solid #d8e1eb;border-radius:8px;padding:9px;font-size:10px;margin-bottom:8px}.inbox-new textarea{min-height:76px}.threads{max-height:390px;overflow:auto}.thread{display:block;padding:12px 14px;border-bottom:1px solid #edf1f5}.thread:hover,.thread.active{background:#eef4fb}.thread strong,.thread small{display:block}.thread strong{font-size:10px}.thread small,.chat-head small{font-size:8px;color:var(--muted);margin-top:4px}.chat-panel{display:flex;flex-direction:column}.messages{flex:1;background:#f8fafc;padding:16px;overflow:auto;display:flex;flex-direction:column;gap:9px}.message{max-width:76%;padding:10px 12px;border-radius:11px;background:#e7eff9;font-size:10px;line-height:1.7}.message.unifco{margin-inline-start:auto;background:var(--navy);color:#fff}.message small{display:block;opacity:.65;font-size:8px;margin-top:4px}.reply{padding:13px;border-top:1px solid var(--line)}.reply textarea{min-height:70px;resize:vertical}.chat-empty{margin:auto}@media(max-width:880px){.inbox-layout{grid-template-columns:1fr}.inbox-list,.chat-panel{min-height:auto}.chat-panel{min-height:520px}}
</style>@endpush
@include('customer.partials.portal-shell-close')
