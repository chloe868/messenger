<?php

namespace Increment\Messenger\Http;

use Illuminate\Http\Request;
use App\Http\Controllers\APIController;
use Increment\Messenger\Models\MessengerMessage;
use Increment\Messenger\Models\MessengerGroup;
use Carbon\Carbon;
use App\Events\Message;
use App\Jobs\Notifications;

class MessengerMessageController extends APIController
{

    public $msFileClass = 'Increment\Messenger\Http\MessengerMessageFileController';
    public $memberClass = 'Increment\Messenger\Http\MessengerMemberController';
    public $requestValidationClass = 'App\Http\Controllers\RequestValidationController';
    function __construct(){
      $this->model = new MessengerMessage();
      $this->localization();
      $this->notRequired = array(
        'payload_value',
        'status',
        'message'
      );
    }

    public function create(Request $request){
      $data = $request->all();
      $data['status'] = 0;
      $this->insertDB($data);
      $error = null;
      if($this->response['data'] > 0){
        $data['account'] = $this->retrieveAccountDetailsProfileOnly($data['account_id']);
        $data['created_at_human'] =  Carbon::now()->copy()->tz($this->response['timezone'])->format('F j, Y h:i A');
        $data['custom_id'] = $data['messenger_group_id'];
        $data['topic'] = "message";
        $data['members'] = json_encode(app($this->memberClass)->getMembers($data['messenger_group_id']));
        MessengerGroup::where('id', '=', $data['messenger_group_id'])->update(array('updated_at' => Carbon::now()));
        $data['title'] = 'New Message';
        Notifications::dispatch('message', $data);
        $data['sending_flag'] = false;
        $data['error'] = null;
        // app('App\Http\Controllers\EmailController')->newMessage($data['account_id']);
      }else{
        $error = "Something went wrong";
        $data['error'] = "Something went wrong";
        $data['sending_flag'] = false;
      }
      return response()->json(array(
        'data' => $data,
        'error' => ($error != null) ? array(
          'status' => 400,
          'message' => $error 
        ) : null
      ));
    }

    public function createLessReturn(Request $request){
      $data = $request->all();
      $data['status'] = 0;
      $this->insertDB($data);
      $error = null;
      if($this->response['data'] > 0){
        $data['account'] = $this->retrieveAccountDetails($data['account_id']);
        $data['created_at_human'] =  Carbon::now()->copy()->tz($this->response['timezone'])->format('F j, Y h:i A');
        MessengerGroup::where('id', '=', $data['messenger_group_id'])->update(array('updated_at' => Carbon::now()));
        Notifications::dispatch('message', $data);
        $data['sending_flag'] = false;
        $data['error'] = null;
        // app('App\Http\Controllers\EmailController')->newMessage($data['account_id']);
      }else{
        $error = "Something went wrong";
        $data['error'] = "Something went wrong";
        $data['sending_flag'] = false;
      }
      return response()->json(array(
        'data' => array(
          'created_at_human'  => $data['created_at_human'],
          'id'  => $this->response['data']
        ),
        'error' => ($error != null) ? array(
          'status' => 400,
          'message' => $error 
        ) : null
      ));
    }

    public function createWithImageWithoutPayload(Request $request){
      $data = $request->all();
      $error = null;
      $this->model = new MessengerMessage();
      $this->insertDB($data);
      
      if($this->response['data'] > 0){
        // add image
        $msFileData = array(
          'messenger_message_id' => $this->response['data'],
          'type'  => 'image',
          'url' => $data['url'],
          'created_at' => Carbon::now()
        );
        app($this->msFileClass)->insert($msFileData);
        $data['account'] = $this->retrieveAccountDetails($data['account_id']);
        $data['created_at_human'] =  Carbon::now()->copy()->tz($this->response['timezone'])->format('F j, Y h:i A');
        $data['files'] = app($this->msFileClass)->getByParams('messenger_message_id', $this->response['data']);
        $data['validations'] = null;
        MessengerGroup::where('id', '=', $data['messenger_group_id'])->update(array('updated_at' => Carbon::now()));
        Notifications::dispatch('message', $data);
        $data['sending_flag'] = false;
        $data['error'] = null;
      }else{
        $error = "Something went wrong";
        $data['sending_flag'] = false;
        $data['error'] = $error;
      }
      return response()->json(array(
        'data' => $data,
        'error' => ($error != null) ? array(
          'status' => 400,
          'message' => $error 
        ) : null
      ));
    }

    public function createWithImages(Request $request){
      $data = $request->all();
      $error = null;
      $result = $this->checkIfExist($data['account_id'], $data['payload'], $data['payload_value']);
      $this->response['data'] = $result;
      if(!$result){
        $this->model = new MessengerMessage();
        $this->insertDB($data);
      }
      
      if($this->response['data'] > 0){
        // add image
        $msFileData = array(
          'messenger_message_id' => $this->response['data'],
          'type'  => 'image',
          'url' => $data['url'],
          'created_at' => Carbon::now()
        );
        app($this->msFileClass)->insert($msFileData);
        $data['account'] = $this->retrieveAccountDetails($data['account_id']);
        $data['created_at_human'] =  Carbon::now()->copy()->tz($this->response['timezone'])->format('F j, Y h:i A');
        $data['files'] = app($this->msFileClass)->getByParams('messenger_message_id', $this->response['data']);
        $data['validations'] = app($this->requestValidationClass)->getDetailsByParams('id', $data['payload_value']);
        MessengerGroup::where('id', '=', $data['messenger_group_id'])->update(array('updated_at' => Carbon::now()));
        Notifications::dispatch('message', $data);
        $data['sending_flag'] = false;
        $data['error'] = null;
      }else{
        $error = "Something went wrong";
        $data['sending_flag'] = false;
        $data['error'] = $error;
      }
      return response()->json(array(
        'data' => $data,
        'error' => ($error != null) ? array(
          'status' => 400,
          'message' => $error 
        ) : null
      ));
    }

    public function checkIfExist($accountId, $payload, $payloadValue){
      $result = MessengerMessage::where('account_id', '=', $accountId)->where('payload', '=', $payload)->where('payload_value', '=', $payloadValue)->get();
      return sizeof($result) > 0 ? $result[0]['id'] : null;
    }

    public function retrieve(Request $request){
      $data = $request->all();
      $this->model = new MessengerMessage();
      $this->retrieveDB($data);
      $result = $this->response['data'];
      if(sizeof($result) > 0){
        $i = 0;
        foreach ($result as $key) {
          $this->response['data'][$i] = $this->manageBasicResponse($result[$i]);
          $i++;
        }
      }
      return $this->response();
    }

    public function updateByStatus(Request $request){
      $data = $request->all();
      $result = MessengerMessage::where('messenger_group_id', '=', $data['messenger_group_id'])->orderBy('created_at', 'desc')->limit(1)->get();
      if(sizeof($result) > 0){
        MessengerMessage::where('id', '=', $result[0]['id'])->update(array(
          'status' => 1,
          'updated_at' => Carbon::now()
        ));
      }
      $this->response['data'] = true;
      return $this->response();
    }

    public function getByParams($column, $value){
      $result = MessengerMessage::where($column, '=', $value)->get();
      if(sizeof($result) > 0){
        $i = 0;
        foreach ($result as $key) {
          $result[$i] = $this->manageResponse($result[$i]);
          $result[$i]['code'] = $i;
          $data['sending_flag'] = false;
          $data['error'] = null;
          $i++;
        }
        return $result;
      }else{
        return null;
      }
    }

    public function manageResponse($result){
      $payload = $result['payload'];
      $payloadValue = $result['payload_value'];
      $result['product'] = $this->getMessageByPayload($payload, $payloadValue);
      $result['account'] = $this->retrieveAccountDetails($result['account_id']);
      $result['created_at_human'] = Carbon::createFromFormat('Y-m-d H:i:s', $result['created_at'])->copy()->tz($this->response['timezone'])->format('F j, Y h:i A');
      $result['files'] = app($this->msFileClass)->getByParams('messenger_message_id', $result['id']);
      $result['validations'] = null;
      if($payloadValue != null && intval($payloadValue) > 0){
        $result['validations'] = app($this->requestValidationClass)->getDetailsByParams('id', $payloadValue);
      }
      return $result;
    }

    public function manageBasicResponse($result){
      $payload = $result['payload'];
      $payloadValue = $result['payload_value'];
      $result['account'] = $this->retrieveAccountDetailsProfileOnly($result['account_id']);
      $result['created_at_human'] = Carbon::createFromFormat('Y-m-d H:i:s', $result['created_at'])->copy()->tz($this->response['timezone'])->format('F j, Y h:i A');
      $result['files'] = app($this->msFileClass)->getByParams('messenger_message_id', $result['id']);
      return $result;
    }
    
    public function getMessageByPayload($payload, $payloadValue){
      switch($payload){
        case 'product': 
          return app('Increment\Marketplace\Http\ProductController')->getProductById($payloadValue);
          break;
      }
    }

    public function getLastMessageSupport($messengerGroupId){
      $message = MessengerMessage::where('messenger_group_id', '=', $messengerGroupId)->orderBy('created_at', 'desc')->get();
      if(sizeof($message) > 0){
        $message[0]['account'] = $this->retrieveAccountDetails($message[0]['account_id']);
        $message[0]['created_at_human'] = Carbon::createFromFormat('Y-m-d H:i:s', $message[0]['created_at'])->copy()->tz('Asia/Manila')->format('F j, Y h:i A');
        return $message[0];
      }
      return null;
    }

    public function getLastMessage($messengerGroupId, $accountId = null){
      $message = '';
      $lastMessageAccountId = null;
      $message = MessengerMessage::where('messenger_group_id', '=', $messengerGroupId)->orderBy('created_at', 'desc')->limit(1)->get();
      $response = array();
      if(sizeof($message) > 0){
        $response['title'] = $this->retrieveAccountDetails(($lastMessageAccountId !== null) ? $lastMessageAccountId : $message[0]['account_id']);
        $response['description'] = $message[0]['message'];
        $response['created_at_human'] = Carbon::createFromFormat('Y-m-d H:i:s', $message[0]['created_at'])->copy()->tz('Asia/Manila')->format('F j, Y h:i A');
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
