<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    //
    
    //
    public function getLatestChat(Request $request){
        $now = date("Y-m-d h:i:s");
        $conversations = app('db')->connection('mysql2')->table('conversations');
        $conversations = $conversations->whereIn('id',$request->conversation_ids)->whereExists(function($query){
            $query->select(app('db')->raw(1))
            ->from('user_conversations')
            ->where('user_conversations.user_id',app('decoded_array')['sub'])
            ->whereColumn('user_conversations.conversation_id', 'conversations.id');
        })->get();
        foreach($conversations as $conversation){
            $conversation->chats = app('db')->connection('mysql')->table('chats')
            ->where('conversation_id',$conversation->id)
            ->orderBy('id','desc')->simplePaginate();
            
        }
        return $conversations;
    }
    public function getMyConversations(){
         // mendapatkan data conversations dari user
         $conversations = app('db')->connection('mysql2')
         ->table('conversations')->whereExists(function($query){
             $query->select(app('db')->raw(1))
             ->from('user_conversations')
             ->where('user_conversations.user_id',app('decoded_array')['sub'])
             ->whereColumn('user_conversations.conversation_id', 'conversations.id');
         });
         return $conversations;
    }
    public function getUnreadCount(){
        // mendapatkan data conversations dari user
        $conversations = $this->getMyConversations()->get();
        $conversation_ids = [];
        foreach($conversations as $conversation){
            array_push($conversation_ids, $conversation->id);
        }

        // meng-count data chats yg mempunyai conversation_id tertentu
        $chats = app('db')->connection('mysql')->table('chats')->whereIn('conversation_id',$conversation_ids)->count();
        return ['unread_count'=>$chats];

    }
    public function getUnreadConversationCount(){
        return $this->getMyConversations()->whereNull('read_at')->count();
    }
    public function getUnreadConversationCountByMessage(){
        $conversations = $this->getMyConversations()->get();
        $conversation_ids = [];
        foreach($conversations as $conversation){
            array_push($conversation_ids, $conversation->id);
        }
        $auth_id = app('decoded_array')['sub'];
        $chats = app('db')->connection('mysql')->table('chats')
        ->select('conversation_id')
        ->whereNull('read_at')
        ->whereIn('conversation_id',$conversation_ids)
        ->where('sender_id','!=', $auth_id)
        ->groupBy('conversation_id')->get();
        return $chats;

    }
    // diakses setiap membuka ChatPage.vue
    public function readConversation($id){
        // hanya bisa diread oleh participant SELAIN sender, dgn conversation yang sama
        $now = date("Y-m-d h:i:s");
        $user_id = app('decoded_array')['sub'];
        $myconversation = $this->getMyConversations()->where('id',$id)->get();
        if(count($myconversation)==0){
            return response('conversation tidak ada',404);
        }
        $chats = app('db')->connection('mysql')
        ->table('chats')
        ->whereNull('read_at') // jikan null, maka belum dibaca
        ->where('conversation_id',$myconversation[0]->id)
        ->where('sender_id','!=',$user_id); // langsung membaca semua message dari participant
      
        $chat_ids = [];
        foreach($chats->get() as $chat){
            array_push($chat_ids, $chat->id);
        }

        $update = $chats->update(['read_at'=>$now]);

        // if($update)return ['read_at'=>$now, 'unread_conversation_count'=>$this->getUnreadConversationCount()];
        if($update){
            $read_chats = app('db')->connection('mysql')
            ->table('chats')
            ->whereIn('id',$chat_ids)
            ->whereNotNull('read_at') // jikan null, maka belum dibaca
            ->where('conversation_id',$myconversation[0]->id)
            ->where('sender_id','!=',$user_id); // langsung membaca semua message dari participant   
            
            return ['read_at'=>$now, 
                'unread_conversations'=>$this->getUnreadConversationCountByMessage(),
                'read_chats'=>$read_chats->get()
                ];
        }
        else return response('chats sudah terbaca semua!! tidak ada yang diupdate',500);
    }
}
