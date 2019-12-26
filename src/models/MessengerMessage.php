<?php

namespace Increment\Messenger\Models;
use Illuminate\Database\Eloquent\Model;
use App\APIModel;
class MessengerMessage extends APIModel
{
    protected $table = 'messenger_messages';
    protected $fillable = ['messenger_group_id', 'account_id','payload', 'payload_value', 'message', 'status'];

    public function getAccountIdAttribute($value){
      return intval($value);
    }

    public function getMessengerGroupIdAttribute($value){
      return intval($value);
    }

    public function getStatusAttribute($value){
      return intval($value);
    }
}
