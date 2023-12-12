<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePedidoServicioCotizacionTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pedido_servicio_cotizacion', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('entidades_id');
            $table->unsignedInteger('pedido_detalles_id');
            $table->decimal('precio',13,2);
            $table->char('estado',2);
            $table->unsignedInteger('cantidad');
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
        Schema::dropIfExists('pedido_servicio_cotizacion');
    }
}
