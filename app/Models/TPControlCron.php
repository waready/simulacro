<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TPControlCron extends Model
{
    protected $connection = "mysql2";

    protected $table = "control_crons";
}
