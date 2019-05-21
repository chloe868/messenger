<?php

namespace Increment\Messenger\Http;

use Illuminate\Http\Request;
use App\Http\Controllers\APIController;
use Increment\Messenger\Models\MessengerMessage;
use Increment\Messenger\Models\MessengerGroup;
use Carbon\Carbon;
use App\Events\Message;

class MessengerMessageController extends APIController
{
    function __construct(){
      $this->model = new MessengerMessage;
      $this->notRequired = array(
        'status'
      );
    }

    public function create(Request $request){
      $data = $request->all();
      $data['status'] = 0;
      $this->insertDB($data);
      $data['account'] = $this->retrieveAccountDetails($data['account_id']);
      $data['created_at_human'] =  Carbon::now()->copy()->tz('Asia/Manila')->format('F j, Y');
      broadcast(new Message($data))->toOthers();
      MessengerGroup::where('id', '=', $data['messenger_group_id'])->update(array('updated_at' => Carbon::now()));
      return response()->json(array(
        'data' => $data
      ));
    }
    public function retrieve(Request $request){
      $data = $request->all();
      $this->model = new MessengerMessage();
      $this->retrieveDB($data);
      $result = $this->response['data'];
      if(sizeof($result) > 0){
        $i = 0;
        foreach ($result as $key) {
          $this->response['data'][$i]['account'] = $this->retrieveAccountDetails($result[$i]['account_id']);
          $this->response['data'][$i]['created_at_human'] = Carbon::createFromFormat('Y-m-d H:i:s', $result[$i]['created_at'])->copy()->tz('Asia/Manila')->format('F j, Y');
          $i++;
        }
      }
      return $this->response();
    }

    public function getLastMessageSupport($messengerGroupId){
      $message = MessengerMessage::where('messenger_group_id', '=', $messengerGroupId)->orderBy('created_at', 'desc')->get();
      if(sizeof($message) > 0){
        $message[0]['account'] = $this->retrieveAccountDetails($message[0]['account_id']);
        $message[0]['created_at_human'] = Carbon::createFromFormat('Y-m-d H:i:s', $message[0]['created_at'])->copy()->tz('Asia/Manila')->format('F j, Y');
        return $message[0];
      }
      return null;
    }

    public function getLastMessage($messengerGroupId, $accountId = null){
      $message = '';
      if($accountId != null){
        $message = MessengerMessage::where('messenger_group_id', '=', $messengerGroupId)->where('account_id', '!=', $accountId)->orderBy('created_at', 'desc')->limit(1)->get();

        $lastMessage = MessengerMessage::where('messenger_group_id', '=', $messengerGroupId)->orderBy('created_at', 'desc')->limit(1)->get();
        if(sizeof($message) > 0 && sizeof($lastMessage) > 0){
          $message[0]['message'] = (intval($lastMessage[0]['account_id']) == intval($accountId)) ? 'You: ' .$lastMessage[0]['message'] : $lastMessage[0]['message'];
        }
      }else{
        $message = MessengerMessage::where('messenger_group_id', '=', $messengerGroupId)->orderBy('created_at', 'desc')->limit(1)->get();
      }
      
      $response = array();
      if(sizeof($message) > 0){
        $response['title'] = $this->retrieveAccountDetails($message[0]['account_id']);
        $response['description'] = $message[0]['message'];
        $response['created_at_human'] = Carbon::createFromFormat('Y-m-d H:i:s', $message[0]['created_at'])->copy()->tz('Asia/Manila')->format('F j, Y');
        $response['total_unread_messages'] = $this->getTotalUnreadMessages($messengerGroupId, $accountId);
        $response['messenger_group_id'] = $message[0]['messenger_group_id'];
        return $response;
      }
      return null;
    }

    public function getTotalUnreadMessages($messengerGroupId, $accountId){
      // status 1 = to read
      $lastReadMessage = MessengerMessage::where('messenger_group_id', '=', $messengerGroupId)->where('status', '=', 1)->orderBy('created_at', 'DESC')->first();
      $to = Carbon::now();
      if($lastReadMessage){
        $from = Carbon::createFromFormat('Y-m-d H:i:s', $lastReadMessage->created_at)->addSecond();
        $messages =  MessengerMessage::where('messenger_group_id', '=', $messengerGroupId)->whereBetween('created_at', array($from, $to))->get();
        if(sizeof($messages) > 0){
          $i = 0;
          $counter = 0;
          foreach ($messages as $key) {
            if(intval($messages[$i]['account_id']) != intval($accountId)){
              $counter++;
            }
            $i++;
          }
          return $counter;
        }
        return 0;
      }else{
        $accountLastMessage = MessengerMessage::where('messenger_group_id', '=', $messengerGroupId)->where('account_id', '=', $accountId)->orderBy('created_at', 'DESC')->first();
        if($accountLastMessage){
          // get the message from to
          $from = Carbon::createFromFormat('Y-m-d H:i:s', $accountLastMessage->created_at)->addSecond();
          return MessengerMessage::where('messenger_group_id', '=', $messengerGroupId)->whereBetween('created_at', array($from, $to))->count();
        }else{
          return MessengerMessage::where('messenger_group_id', '=', $messengerGroupId)->count();
        }
      }
    }
}
