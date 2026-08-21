<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class CustomerInboxController extends Controller
{
    private function ensureReady(): void
    {
        abort_unless(
            Schema::hasTable('customer_conversations') && Schema::hasTable('customer_messages'),
            503,
            'Customer messaging is being initialized. Please try again shortly.'
        );
    }

    public function customerIndex(Request $request): View
    {
        $this->ensureReady();
        $user = auth()->user();
        abort_unless($user && $user->role === 'CUSTOMER' && $user->customer_id, 403);
        $customer = Customer::findOrFail($user->customer_id);
        $conversationId = $request->integer('conversation');

        $conversations = DB::table('customer_conversations')
            ->where('customer_id',$customer->id)
            ->orderByDesc('last_message_at')->orderByDesc('id')->get();

        $activeConversation = $conversationId
            ? DB::table('customer_conversations')->where('customer_id',$customer->id)->where('id',$conversationId)->first()
            : $conversations->first();

        $messages = collect();
        if ($activeConversation) {
            DB::table('customer_messages')->where('conversation_id',$activeConversation->id)->where('sender_side','UNIFCO')->whereNull('read_at')->update(['read_at'=>now()]);
            $messages = DB::table('customer_messages')->where('conversation_id',$activeConversation->id)->orderBy('id')->get();
        }

        $unread = DB::table('customer_messages')->join('customer_conversations','customer_conversations.id','=','customer_messages.conversation_id')
            ->where('customer_conversations.customer_id',$customer->id)->where('customer_messages.sender_side','UNIFCO')->whereNull('customer_messages.read_at')->count();

        return view('customer.inbox',compact('customer','conversations','activeConversation','messages','unread'));
    }

    public function customerStart(Request $request): RedirectResponse
    {
        $this->ensureReady();
        $user = auth()->user();
        abort_unless($user && $user->role === 'CUSTOMER' && $user->customer_id,403);
        $data = $request->validate(['subject'=>['required','string','max:255'],'body'=>['required','string','max:5000']]);
        $id = DB::table('customer_conversations')->insertGetId(['customer_id'=>$user->customer_id,'subject'=>$data['subject'],'status'=>'OPEN','last_message_at'=>now(),'created_at'=>now(),'updated_at'=>now()]);
        DB::table('customer_messages')->insert(['conversation_id'=>$id,'sender_user_id'=>$user->id,'sender_side'=>'CUSTOMER','body'=>$data['body'],'created_at'=>now(),'updated_at'=>now()]);
        return redirect()->route('customer.inbox',['conversation'=>$id])->with('status','تم إرسال الرسالة إلى UNIFCO.');
    }

    public function customerReply(Request $request, int $conversation): RedirectResponse
    {
        $this->ensureReady();
        $user = auth()->user();
        abort_unless($user && $user->role === 'CUSTOMER' && $user->customer_id,403);
        $exists = DB::table('customer_conversations')->where('id',$conversation)->where('customer_id',$user->customer_id)->exists();
        abort_unless($exists,404);
        $data = $request->validate(['body'=>['required','string','max:5000']]);
        DB::table('customer_messages')->insert(['conversation_id'=>$conversation,'sender_user_id'=>$user->id,'sender_side'=>'CUSTOMER','body'=>$data['body'],'created_at'=>now(),'updated_at'=>now()]);
        DB::table('customer_conversations')->where('id',$conversation)->update(['status'=>'OPEN','last_message_at'=>now(),'updated_at'=>now()]);
        return back()->with('status','تم إرسال الرد.');
    }

    public function adminIndex(Request $request): View
    {
        $this->ensureReady();
        $conversationId = $request->integer('conversation');
        $conversations = DB::table('customer_conversations')->join('customers','customers.id','=','customer_conversations.customer_id')
            ->select('customer_conversations.*','customers.name as customer_name','customers.customer_code')
            ->orderByDesc('customer_conversations.last_message_at')->orderByDesc('customer_conversations.id')->get();
        $activeConversation = $conversationId
            ? $conversations->firstWhere('id',$conversationId)
            : $conversations->first();
        $messages = collect();
        if ($activeConversation) {
            DB::table('customer_messages')->where('conversation_id',$activeConversation->id)->where('sender_side','CUSTOMER')->whereNull('read_at')->update(['read_at'=>now()]);
            $messages = DB::table('customer_messages')->where('conversation_id',$activeConversation->id)->orderBy('id')->get();
        }
        return view('crm.customer-inbox',compact('conversations','activeConversation','messages'));
    }

    public function adminReply(Request $request, int $conversation): RedirectResponse
    {
        $this->ensureReady();
        $data = $request->validate(['body'=>['required','string','max:5000']]);
        abort_unless(DB::table('customer_conversations')->where('id',$conversation)->exists(),404);
        DB::table('customer_messages')->insert(['conversation_id'=>$conversation,'sender_user_id'=>auth()->id(),'sender_side'=>'UNIFCO','body'=>$data['body'],'created_at'=>now(),'updated_at'=>now()]);
        DB::table('customer_conversations')->where('id',$conversation)->update(['status'=>'OPEN','last_message_at'=>now(),'updated_at'=>now()]);
        return back()->with('status','Reply sent to customer.');
    }
}
