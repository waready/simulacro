<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ingresante extends Model
{
    // public function curso(){
    //     return $this->belongsTo('App\Models\Curso','cursos_id');
    // }
    // App\Models\Ingresante

    protected $fillable = ['dni', 'nombres', 'puntaje', 'carrera'];

}
