<?php

namespace Increment\Messenger\Http;

use Illuminate\Http\Request;
use App\Http\Controllers\APIController;
use Increment\Messenger\Models\MessengerMessageFile;
use Carbon\Carbon;

class MessengerMessageFileController extends APIController
{
    function __construct(){
      $this->model = new MessengerMessageFile;
    }

    public function insert($data){
      $data['created_at'] = Carbon::now();
      MessengerMessageFile::insert($data);
      return true;
    }
}
