<?php

namespace Increment\Messenger\Http;

use Illuminate\Http\Request;
use App\Http\Controllers\APIController;
use App\Events\Call;

class AudioCallController extends APIController
{
    function __construct(){
    }
    public function send (Request $request){
      $data = $request->all();
      $sender = $this->retrieveAccountDetails($data['sender']);
      $receiver = $this->retrieveAccountDetails($data['receiver']);
      $user = array(
        'sender' => $sender,
        'receiver' => $receiver,
        'action' => $data['action']
      );
      broadcast(new Call($user));
      echo 'hi';
    }
}
