<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePedidoServiciosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pedido_servicios', function (Blueprint $table) {
            $table->id();
            $table->string('codigo',150);
            $table->unsignedInteger('correlativo');
            $table->unsignedInteger('solicitante_id');
            $table->unsignedInteger('receptor_id');
            $table->string('ff_Rb',20);
            $table->string('meta',20);
            $table->string('funcion',20);
            $table->string('div_func',20);
            $table->string('grup_func',20);
            $table->string('programa',20);
            $table->string('prod_pry',20);
            $table->string('act_ai_obr',20);
            $table->char('tipo',1);
            $table->char('estado',2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('pedido_servicios');
    }
}
