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

    public $accountProfileClass = 'Increment\Account\Http\AccountProfileController';
    public $accountInformationClass = 'Increment\Account\Http\AccountInformationController';

    public function getMembers($messengerGroupId){
      $result = MessengerMember::where('messenger_group_id', '=', $messengerGroupId)->get();
      $response = array();

      if(sizeof($result) > 0){
        $i = 0;
        foreach ($result as $key) {
          $response[] = $result[$i]['account_id'];
          $i++;
        }
      }
      return $response;
    }

    public function retrieveMembers(Request $request) {
      $data = $request->all();
      $members = MessengerMember::where($data['condition'][0]['column'], $data['condition'][0]['clause'], $data['condition'][0]['value'])
      ->orderBy('created_at', $data['sort']['created_at'])->get();
      foreach($members as $i) {
        $i['account'] = ['profile' => app($this->accountProfileClass)->getAccountProfile($i['account_id']), 'username' => $this->retrieveNameOnly($i['account_id'])];
        $i['information'] = app($this->accountInformationClass)->getAccountInformation($i['account_id']);
      }
      $this->response['data'] = $members;
      return $this->response();
    }

    public function retrieveByParams($column, $value, $returns){
      $result = MessengerMember::where($column, '=', $value)->where('deleted_at', '=', null)->get($returns);
      return sizeof($result) > 0 ? $result[0] : null;
    }
}
