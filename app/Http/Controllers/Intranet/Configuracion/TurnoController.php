<?php

namespace App\Http\Controllers\Intranet\Configuracion;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\VueTables\EloquentVueTables;
use Illuminate\Validation\Rule;
use App\Models\Turno;
use DB;

class TurnoController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view("intranet.configuracion.turno");
    }

    public function lista(Request $request)
    {
        $table = new EloquentVueTables;
        $data = $table->get(new Turno, ['id','denominacion','estado']);
        $response = $table->finish($data);

        return response()->json($response);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $rules = $request->validate([
            'denominacion' => 'required',
            'estado' => 'required',
        ], $messages = [
            'required' => '*El campo :attribute es obligatorio.',
        ]);

        DB::beginTransaction();
        try {
            $data = new Turno;
            $data->denominacion = $request->denominacion;
            $data->estado = (string)$request->estado;
            $data->save();

            DB::commit();
            $response["message"] = 'Turno guardado con éxito.';
            $response["status"] = true;
        } catch (\Exception $e) {
            DB::rollback();
            $response["message"] = "Error al guardar turno, intentelo nuevamente.";
            $response["status"] = false;
        }

        return $response;
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $response = Turno::find($id);

        return $response;
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $rules = $request->validate([
            'denominacion' => 'required',
            'estado' => 'required',
        ], $messages = [
            'required' => '*El campo :attribute es obligatorio.',
        ]);

        DB::beginTransaction();
        try {
            $data = Turno::find($id);
            $data->denominacion = $request->denominacion;
            $data->estado = (string)$request->estado;
            $data->save();

            DB::commit();
            $response["message"] = 'Turno actualizado con éxito.';
            $response["status"] = true;
        } catch (\Exception $e) {
            DB::rollback();
            $response["message"] = "Error al actualizar turno, intentelo nuevamente.";
            $response["status"] = false;
        }

        return $response;
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
