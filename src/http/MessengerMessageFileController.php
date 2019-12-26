<?php

namespace Increment\Messenger\Http;

use Illuminate\Http\Request;
use App\Http\Controllers\APIController;
use Increment\Messenger\Models\MessengerMessageFile;
class MessengerMessageFileController extends APIController
{
  function __construct(){
    $this->model = new MessengerMessageFile();
  }

  public function insert($data){
    MessengerMessageFile::insert($data);
    return true;
  }

  public function getByParams($column, $value){
    $result = MessengerMessageFile::where($column, '=', $value)->get();
    return (sizeof($result) > 0) ? $result : null;
  }
}
