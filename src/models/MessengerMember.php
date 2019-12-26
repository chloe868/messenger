<?php

namespace Increment\Messenger\Models;
use Illuminate\Database\Eloquent\Model;
use App\APIModel;
class MessengerMember extends APIModel
{
    protected $table = 'messenger_members';
    protected $fillable = ['messenger_group_id', 'account_id', 'status'];
    public function getAccountIdAttribute($value){
      return intval($value);
    }

    public function getMessengerGroupIdAttribute($value){
      return intval($value);
    }
}
