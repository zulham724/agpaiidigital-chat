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
}
