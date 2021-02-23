<?php

namespace Increment\Messenger\Http;

use Illuminate\Http\Request;
use App\Http\Controllers\APIController;
use Increment\Messenger\Models\MessengerMember;

class MessengerMemberController extends APIController
{
    function __construct(){
      $this->model = new MessengerMember();
    }


    public function getMembers($messengerGroupId){
      $result = MessengerMember::where('messenger_group_id', '=', $messengerGroupId)->get();
      $response = array();

      if(sizeof($result) > 0){
        $i = 0;
        foreach ($result as $key) {
          $response[] = $result[$i]['account_id'];
        }
      }
      return $response;
    }
}
