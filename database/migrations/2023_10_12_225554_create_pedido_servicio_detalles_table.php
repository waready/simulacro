<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePedidoServicioDetallesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pedido_servicio_detalles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('pedido_id');
            //$table->foreign('pedido_id')->references('id')->on('nosotros_tipos_datos');
            $table->unsignedInteger('cantidad');
            //$table->unsignedInteger('entidades_id');
            $table->unsignedInteger('clasificador_id');
            $table->string('finalidad',150);
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
        Schema::dropIfExists('pedido_servicio_detalles');
    }
}
