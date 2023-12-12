<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrdenServicioDetallesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('orden_servicio_detalles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('orden_servicio_id');
            //$table->foreign('pedido_id')->references('id')->on('nosotros_tipos_datos');
            $table->unsignedInteger('cantidad');
            $table->unsignedInteger('producto_id');
            //$table->unsignedInteger('clasificador_id');
            $table->text('descripcion');

            //$table->unsignedInteger('entidades_id');
            $table->unsignedInteger('pedido_detalles_id');
            $table->decimal('precio',13,2);
            $table->char('estado',2);
            //$table->unsignedInteger('cantidad');

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
        Schema::dropIfExists('orden_servicio_detalles');
    }
}
