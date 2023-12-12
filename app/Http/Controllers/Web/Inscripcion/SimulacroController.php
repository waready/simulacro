<?php

namespace App\Http\Controllers\Web\Inscripcion;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use App\Models\Estudiante;
use App\Models\Inscripciones;
use App\Models\Periodo;
use App\Models\Apoderado;
use App\Models\EstudianteApoderado;
use App\Models\Pago;
use App\Models\BancoPago;
use App\Models\Colegio;
use App\Models\ConfiguracionInscripciones;
use App\Models\ConfiguracionVacante;
use App\Models\Docente;
use App\Models\Tarifa;
use App\Models\InscripcionPago;
use App\Models\Parentesco;
use App\Models\TarifaEstudiante;
use App\Services\GWorkspace;
use Illuminate\Validation\Rule;
use PDF;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SimulacroController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

    private $dateTime;
    private $dateTimePartial;
    public function __construct()
    {
        date_default_timezone_set("America/Lima"); //Zona horaria de Peru
        $this->dateTime = date("Y-m-d H:i:s");
        $this->dateTimePartial = date("m-Y");
    }

    public function index()
    {
        $response["configuracion"] = ConfiguracionInscripciones::where([['tipo_usuario', 1], ['estado', '1']])->first();
        return view('web.inscripcion.estudiante-simulacro', $response);
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

    public function showLogin()
    {
        return view('web.inscripcion.validacion');
    }
    public function login(Request $request)
    {


        $credentials = $request->only('email', 'password');

        if ($estudiante = Estudiante::where('usuario', $credentials['email'])->first()) {
            $estudiante = Estudiante::join('inscripciones', 'estudiantes.id', '=', 'inscripciones.estudiantes_id')
                ->join('sedes', 'inscripciones.sedes_id', '=', 'sedes.id')
                ->select('estudiantes.*', 'inscripciones.*', 'sedes.*')
                ->where('estudiantes.usuario', $credentials['email'])
                ->where('estudiantes.password', $credentials['password'])
                ->first();
            // Verificar si se encontró el estudiante y la contraseña coincide
            if ($estudiante->usuario == $credentials['email'] && $estudiante->password == $credentials['password']) {

                $response["configuracion"] = ConfiguracionInscripciones::where([['tipo_usuario', 1], ['estado', '1']])->first();
                // return redirect()->intended('/');

                $response['estudiante'] = json_encode($estudiante);
                return view('web.inscripcion.estudiante-simulacro', $response);
                // return $estudiante;
            }
        }


        return redirect()->back()->with('error', 'El correo o la contraseña ingresados son incorrectos. Por favor, verifica tus credenciales y vuelve a intentarlo.');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $correo = new GWorkspace();
        $password = Str::random(8);

        $rules = $request->validate([
            'nombres' => 'required|string',
            'paterno' => 'required|string',
            'materno' => 'required|string',
            'tipo_documento' => 'required',
            'nro_documento' => 'required',
            'email' => 'required|email',
            'celular' => 'required',
            'sede' => 'required',
            'area' => 'required',
            'escuela' => 'required',
            'tokens' => 'required',
        ], $messages = [
            'required' => '* El campo :attribute es obligatorio.',
            'email.email' => '* El formato del correo no es el correcto',
        ]);

        // dd($cantidadVacantes->cantidad);
            $tokens = $request->tokens;
            $pagoExistente = true;
            $documentoValidado = true;
            $sumaPagoDB = 0;
            $cont = 0;
            $validarPago = 0;
            $comisionBanco = 0;
            if (isset($tokens)) {
                while ($cont < count($tokens)) {

                    $validarPago = Pago::where('token', $tokens[$cont])->first();
                    $comisionBanco = $comisionBanco + 1;

                    if (empty($validarPago)) {
                        $pagoExistente = false;
                    } else {
                        if ($validarPago->estado == '1') {
                            $sumaPagoDB = $sumaPagoDB + $validarPago->monto;
                            // validar pago con el numero de documento del estudiante
                            $validarDocumento = BancoPago::where([
                                ["secuencia", $validarPago->secuencia],
                                ["imp_pag", $validarPago->monto],
                                ["fch_pag", $validarPago->fecha],
                                ["num_doc", str_pad($request->nro_documento, 15, '0', STR_PAD_LEFT)],
                            ])
                                ->first();
                            if (empty($validarDocumento)) {
                                $documentoValidado = false;
                            }
                        } else {
                            $pagoExistente = false;
                        }
                    }
                    $cont = $cont + 1;
                }
            } else {
                $pagoExistente = false;
            }

       
            $totalPagar = round(11, 2);
            if (empty($validarPago)) {
                $pagoExistente = false;
            }

            $periodoActual = Periodo::where('estado', '1')->first();

            // $consultaInscripcion = DB::table('inscripciones as i')
            //     ->select(
            //         'i.id'
            //     )
            //     ->join('estudiantes as e', 'e.id', 'i.estudiantes_id')
            //     ->where([
            //         ['i.periodos_id', $periodoActual->id],
            //         ['e.nro_documento', $request->nro_documento]
            //     ])
            //     ->first();

            // $docente = Docente::where('nro_documento', $request->nro_documento)->first();
            // if (isset($docente)) {
            //     $response = array(
            //         "message" => "Ud. ya se ah inscrito como docente en el ciclo $periodoActual->inicio_ciclo - $periodoActual->fin_ciclo ",
            //         "status" => false,
            //     );
            // } else {


            // if (isset($consultaInscripcion)) {
            //     $response = array(
            //         "message" => "Ud. ya se ah inscrito en el ciclo $periodoActual->inicio_ciclo - $periodoActual->fin_ciclo ",
            //         "status" => false,
            //     );
            // } else {
                if ($pagoExistente) {
                    if ($documentoValidado) {
                        if (round($sumaPagoDB, 2) >= $totalPagar) {
                            DB::beginTransaction();
                            try {

                                // $controlEstudiante = Estudiante::where('nro_documento', $request->nro_documento)->first();

                                // if (isset($controlEstudiante)) {

                                //     $controlEstudiante->celular = $request->celular;
                                //     $controlEstudiante->email = $request->email;
                                //     $controlEstudiante->pais_id = $request->pais;
                                //     $controlEstudiante->ubigeos_id = $request->ubigeo;
                                //     $controlEstudiante->foto = $this->save_image($request->foto);
                                //     $controlEstudiante->save();
                                // } else {
                                    // $pdf_dni = $this->save_file("DNI", $request->file_dni, $request->file('file_dni')->getClientOriginalExtension(), 'inscripcion');
                                    // $estudiante = new Estudiante();
                                    // $estudiante->nombres = mb_strtoupper($request->nombres);
                                    // $estudiante->paterno = mb_strtoupper($request->paterno);
                                    // $estudiante->materno = mb_strtoupper($request->materno);
                                    // $estudiante->nro_documento = $request->nro_documento;
                                    // $estudiante->fecha_nac = $request->fecha_nac;
                                    // $estudiante->celular = $request->celular;
                                    // $estudiante->email = $request->email;
                                    // $estudiante->sexo = $request->sexo;
                                    // $estudiante->anio_egreso = $request->egreso;
                                    // $estudiante->pais_id = $request->pais;
                                    // $estudiante->ubigeos_id = $request->ubigeo;
                                    // $estudiante->ubigeos_nacimiento = $request->ubigeon;
                                    // $estudiante->tipo_documentos_id = $request->tipo_documento;
                                    // $estudiante->colegios_id = $request->colegio;
                                    // $estudiante->foto = $this->save_image($request->foto);
                                    // $estudiante->usuario = $request->nro_documento . "@cepreuna.edu.pe";
                                    // $estudiante->password = $password;
                                    // $estudiante->path_dni = $pdf_dni;
                                    // $estudiante->estado_colegio = $request->estado_colegio;
                                    // $estudiante->estado_universidad = $request->estado_universidad;
                                    // $estudiante->estado_discapacidad = $request->estado_discapacidad;
                                    // $estudiante->discapacidad = $request->discapacidad;
                                    // $estudiante->save();
                               // }
                     

                                $periodo = Periodo::where('estado', '1')->first();

                                // $inscripciones = DB::table('inscripciones')
                                //     ->select(
                                //         DB::raw('MAX(correlativo) as correlativo')
                                //     )
                                //     ->where([
                                //         ['periodos_id', $periodo->id]
                                //     ])
                                //     ->first();
                                // $inscripcion = new Inscripciones();

                                // $inscripcion->correlativo = isset($inscripciones->correlativo) ? str_pad(($inscripciones->correlativo + 1), 5, '0', STR_PAD_LEFT) : str_pad('1', 5, '0', STR_PAD_LEFT);
                                // $inscripcion->tipo_estudiante = '1';
                                // $inscripcion->estado = '1';
                                // $inscripcion->cantidad_inscrito = $request->cantidad_inscrito;
                                // $inscripcion->estudiantes_id = isset($controlEstudiante) ? $controlEstudiante->id : $estudiante->id;
                                // $inscripcion->dni_hermano = $request->nro_documento_hermano;
                                // $inscripcion->areas_id = $request->area;
                                // $inscripcion->sedes_id = $request->sede;
                                // $inscripcion->periodos_id = $periodo->id;
                                // $inscripcion->turnos_id = $request->turno;
                                // $inscripcion->escuela_id = $request->escuela;
                                // $inscripcion->modalidad = $request->modalidad;
                                // $inscripcion->save();

                                $cont = 0;
                                $sumaImporte = 0;
                                while ($cont < count($tokens)) {
                                    $pago = Pago::where('token', $tokens[$cont])->first();
                                    if (!empty($pago)) {
                                        $pago = Pago::find($pago->id);
                                        $pago->estado = '2';
                                        $pago->save();

                                        $pagoSinComision = round($pago->monto - 1, 2);
                                        $sumaImporte = $sumaImporte + $pagoSinComision;
                                            
                                        $sumaInscripcion = 11;
                                      
                                        //  215.00
                                        if (round($sumaInscripcion, 2) >= round(11, 2)) {
                                            $inscripcionPago = new InscripcionPago();
                                            $inscripcionPago->monto = $pagoSinComision;
                                            $inscripcionPago->inscripciones_id = 5088;
                                            $inscripcionPago->pagos_id = $pago->id;
                                            $inscripcionPago->concepto_pagos_id = 2;
                                            $inscripcionPago->save();
                                        } else {
                                            if ($sumaImporte <= 11) {
                                                $inscripcionPago = new InscripcionPago();
                                                $inscripcionPago->monto = $pagoSinComision;
                                                $inscripcionPago->inscripciones_id = 5088;
                                                $inscripcionPago->pagos_id = $pago->id;
                                                $inscripcionPago->concepto_pagos_id = 1;
                                                $inscripcionPago->save();
                                            } else {
                                                $diferencia = round($sumaImporte - 11, 2);
                                                if ($diferencia != 0) {
                                                    $mensualPago = new InscripcionPago();
                                                    $mensualPago->monto = $diferencia;
                                                    $mensualPago->inscripciones_id = 5088;
                                                    $mensualPago->pagos_id = $pago->id;
                                                    $mensualPago->concepto_pagos_id = 2;
                                                    $mensualPago->save();
                                                }

                                                $inscripcionPago = new InscripcionPago();
                                                $inscripcionPago->monto = round($pagoSinComision - $diferencia, 2);
                                                $inscripcionPago->inscripciones_id = 5088;
                                                $inscripcionPago->pagos_id = $pago->id;
                                                $inscripcionPago->concepto_pagos_id = 1;
                                                $inscripcionPago->save();
                                            }
                                        }
                                    }
                                    $cont = $cont + 1;
                                  
                                }

                         

                             
                                DB::commit();
                                $message = 'Inscripcion realizada con exito.';
                                $status = true;
                                $error = '';
                            } catch (\Exception $e) {
                                DB::rollback();
                                $message = 'Error al inscribirse, intentelo nuevamante.';
                                $status = false;
                                $error = 'error';
                            }

                            $response = array(
                                "message" => $message,
                                "status" => $status,
                                "url" => url("inscripciones/simulacro/" . Crypt::encryptString(1)),
                                "error" => $error
                            );
                        } else {
                            $response = array(
                                "message" => '* El monto total de pago no es valido, ingrese nuevamente el pago correspondiente.',
                                "status" => false,
                            );
                        }
                    } else {
                        $response = array(
                            "message" => '* Error al validar pago, Ud. esta intentando ingresar un pago que no esta a su nombre.',
                            "status" => false,
                        );
                    }
                } else {
                    DB::beginTransaction();
                    try {

                     
                        DB::commit();
                        $message = 'Inscripcion realizada con exito.';
                        $status = true;
                        $error = '';
                    } catch (\Exception $e) {
                        DB::rollback();
                        $message = 'Error al inscribirse, intentelo nuevamante.';
                        $status = false;
                        $error = $e;
                    }

                    $response = array(
                        "message" => $message,
                        "status" => $status,
                        "url" => url("inscripciones/simulacro/" . Crypt::encryptString($inscripcion->id)),
                        "error" => $error
                    );
                }
            
            
        


  
        return response()->json($response);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $id = Crypt::decryptString($id);
        $inscripcion = Inscripciones::where('id', $id)->first();

        $response = array(
            "status" => true,
            //"id" => Crypt::encryptString($id),
            "id" => 5239,
            "tipo" => empty($inscripcion) ? false : $inscripcion->estado,
        );
        return view('web.inscripcion.simulacro-inscrito', $response);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
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
        //
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
    public function pdf($id_encrypt)
    {
        // $id_simulacro = Crypt::decryptString($id_encrypt);
        // $id=DB::table('examen_simulacro')->where('id',$id_simulacro)->min('id_inscripcion');
        $id = 5239;
        // $periodo = Periodo::where("estado","1")->first();
        if (!isset($id)) {
            abort(401, "no existe la inscripcion");
        }
        $inscripcion = DB::table("inscripciones as i")
            ->select(
                "i.id",
                "i.periodos_id",
                "i.estudiantes_id",
                DB::raw("DATE_FORMAT(i.created_at,'%d/%m/%Y %h:%i %p') as Fecha"),
                "s.denominacion as Sede",
                "a.denominacion as Area",
                "a.id as area_id",
                "t.denominacion as Turno",
                "i.estado",
                "i.cantidad_inscrito",
                "i.modalidad"
            )
            ->join("sedes as s", "s.id", "i.sedes_id")
            ->join("areas as a", "a.id", "i.areas_id")
            ->join("turnos as t", "t.id", "i.turnos_id")
            ->where("i.id", $id)
            ->first();
        $periodo = Periodo::find($inscripcion->periodos_id);
        $estudiante = DB::table("estudiantes as e")
            ->select(
                "e.*",
                "td.denominacion as TipoDocumento",
                "c.denominacion as Colegio",
                "p.denominacion as Pais",
                "u.departamento",
                "u.distrito",
                "u.provincia",
                "ub.departamento as departamenton",
                "ub.distrito as distriton",
                "ub.provincia as provincian",
                "a.paterno as Apaterno",
                "a.materno as Amaterno",
                "a.nombres as Anombres",
                "a.celular as Acelular",
                "pa.denominacion as Parentesco"
            )
            ->join("tipo_documentos as td", "td.id", "e.tipo_documentos_id")
            ->join("colegios as c", "c.id", "e.colegios_id")
            ->join("pais as p", "p.id", "e.pais_id")
            ->leftJoin("estudiante_apoderados as ea", "ea.estudiantes_id", "e.id")
            ->leftJoin("apoderados as a", "a.id", "ea.apoderados_id")
            ->leftJoin("parentescos as pa", "pa.id", "a.parentescos_id")
            ->leftJoin("ubigeos as u", "u.id", "e.ubigeos_id")
            ->leftJoin("ubigeos as ub", "ub.id", "e.ubigeos_nacimiento")
            ->where("e.id", $inscripcion->estudiantes_id)
            ->first();
        $pagos = DB::table("pagos as p")
            ->select(
                "p.*"
            )
            ->join("inscripcion_pagos as ip", "ip.pagos_id", "p.id")
            ->where("ip.inscripciones_id", $id)
            ->groupBy("p.id")
            ->get();
        $inscripcionPagos = InscripcionPago::where('inscripciones_id', $id)->orderBy('concepto_pagos_id')->get();
        // $tipo_documento = TipoDocumento::find($inscripcion->estudiantes_id);
        // dd($estudiante);
        $pdf = new PDF();
        PDF::setFooterCallback(function ($pdf) use ($periodo, $inscripcion) {
            $pdf->SetY(-15);
            // $y = $pdf->SetY(-15);
            $pdf->Line(10, 283, 200, 283);
            $pdf->SetFont('helvetica', '', 8);
            $pdf->Cell(0, 10, 'CEPREUNA SETIEMBRE 2023 - DICIEMBRE 2023 - Fecha y Hora de Registro: ' . $inscripcion->Fecha, "t", false, 'L', 0, '', 0, false, 'T', 'M');
        });
        $pdf::SetTitle('Solicitud');
        $pdf::AddPage();
        $pdf::SetMargins(0, 0, 0);
        $pdf::SetAutoPageBreak(true, 0);

        $image = "";
        if ($inscripcion->modalidad == '1') {
            $image = "fondov.png";
        } else {
            switch ($inscripcion->area_id) {
                case 1:
                    $image = "fondob.png";
                    break;
                case 2:
                    $image = "fondoi.png";
                    break;
                case 3:
                    $image = "fondos.png";
                    break;
                default:

                    break;
            }
        }
        // $pdf::Image('images/' . $image, 0, 0, 210, "", 'PNG');
        $pdf::SetMargins(20, 40, 20, true);
        $pdf::setCellHeightRatio(1.5);
        // $pdf::Image('images/UNAPUNO.png', 10, 6, 20, 20, 'PNG', '', '', false, 150, '', false, false, 0, false, false, false);
        // $pdf::Image('images/logo.png', 167, 6, 34, 18, 'PNG', '', '', false, 140, '', false, false, 0, false, false, false);

        // $pdf::Image(Storage::disk('fotos')->path($estudiante->foto), 156, 49, 44, 52, 'PNG', '', '', true, 150, '', false, false, 1, false, false, false);

        // $pdf::SetFont('helvetica', 'b', 12);

        $pdf::SetFont('helvetica', 'b', 14);
        $pdf::Cell(0, 5, 'UNIVERSIDAD NACIONAL DEL ALTIPLANO PUNO', 0, 1, 'C', 0, '', 0);
        $pdf::SetFont('helvetica', 'b', 12);
        $pdf::Cell(0, 5, "Centro de Estudios Pre Universitario", 0, 1, 'C', 0, '', 0);
        $pdf::SetFont('helvetica', '', 9);
        $pdf::Cell(0, 5, 'Jr Acora #235', 0, 1, 'C', 0, '', 0);

        $pdf::ln();
        $pdf::ln();
        $pdf::SetFont('helvetica', 'b', 14);

        $pdf::Cell(0, 5, 'FICHA DE ' . ($inscripcion->estado == 1 ? 'INSCRIPCION' : 'PREINSCRIPCION') . ' CEPREUNA CICLO SETIEMBRE 2023 - DICIEMBRE 2023 ', 0, 1, 'C', 0, '', 0);

        $pdf::ln();
        $pdf::SetFont('helvetica', 'b', 10);
        $pdf::Cell(130, 6, 'DATOS DEL POSTULANTE', 1, 1, 'C', 0, '', 0);
        // $pdf::SetFont('helvetica', 'b', 8);
        // **********

        $pdf::SetFont('helvetica', 'b', 7);
        $pdf::Cell(30, 5, 'TIPO DOCUMENTO:', 0, 0, 'L', 0, '', 1);
        $pdf::SetFont('helvetica', '', 8);
        $pdf::Cell(40, 5, $estudiante->TipoDocumento, 0, 0, 'L', 0, '', 1);

        $pdf::SetFont('helvetica', 'b', 7);
        $pdf::Cell(30, 5, 'NÚMERO DE DOCUMENTO:', 0, 0, 'L', 0, '', 1);
        $pdf::SetFont('helvetica', '', 8);
        $pdf::Cell(40, 5, $estudiante->nro_documento, 0, 1, 'L', 0, '', 1);
        // ********
        $pdf::SetFont('helvetica', 'b', 7);
        $pdf::Cell(30, 5, 'APELLIDO PATERNO:', 0, 0, 'L', 0, '', 1);
        $pdf::SetFont('helvetica', '', 8);
        $pdf::Cell(40, 5, $estudiante->paterno, 0, 0, 'L', 0, '', 1);

        $pdf::SetFont('helvetica', 'b', 7);
        $pdf::Cell(30, 5, 'APELLIDO MATERNO:', 0, 0, 'L', 0, '', 1);
        $pdf::SetFont('helvetica', '', 8);
        $pdf::Cell(40, 5, $estudiante->materno, 0, 1, 'L', 0, '', 1);
        // ********
        $pdf::SetFont('helvetica', 'b', 7);
        $pdf::Cell(30, 5, 'NOMBRES:', 0, 0, 'L', 0, '', 1);
        $pdf::SetFont('helvetica', '', 8);
        $pdf::Cell(40, 5, $estudiante->nombres, 0, 0, 'L', 0, '', 1);

        $pdf::SetFont('helvetica', 'b', 7);
        $pdf::Cell(30, 5, 'CELULAR:', 0, 0, 'L', 0, '', 1);
        $pdf::SetFont('helvetica', '', 8);
        $pdf::Cell(40, 5, $estudiante->celular, 0, 0, 'L', 0, '', 1);

        $pdf::ln();
        $pdf::SetFont('helvetica', 'b', 7);
        $pdf::Cell(30, 5, 'EMAIL:', 0, 0, 'L', 0, '', 1);
        $pdf::SetFont('helvetica', '', 8);
        $pdf::Cell(40, 5, $estudiante->email, 0, 0, 'L', 0, '', 1);


        // ********
        // ***********************DATOS ADICIONALES****************
        $pdf::ln();
        $pdf::SetFont('helvetica', 'b', 10);
        $pdf::Cell(130, 6, 'DATOS ADICIONALES', 1, 1, 'C', 0, '', 0);

        $pdf::SetFont('helvetica', 'b', 7);
        $pdf::Cell(30, 5, 'SEDE:', 0, 0, 'L', 0, '', 1);
        $pdf::SetFont('helvetica', '', 8);
        $pdf::Cell(40, 5, $inscripcion->Sede, 0, 0, 'L', 0, '', 1);

        $pdf::SetFont('helvetica', 'b', 7);
        $pdf::Cell(30, 5, 'AREA:', 0, 0, 'L', 0, '', 1);
        $pdf::SetFont('helvetica', '', 8);
        $pdf::Cell(40, 5, $inscripcion->Area, 0, 1, 'L', 0, '', 1);
        // ********
        $pdf::SetFont('helvetica', 'b', 7);
        $pdf::Cell(30, 5, 'PROGRAMA DE ESTUDIOS:', 0, 0, 'L', 0, '', 1);
        $pdf::SetFont('helvetica', '', 8);
        $pdf::Cell(40, 5, 'Ingenieria Nuclear', 0, 0, 'L', 0, '', 1);
        $pdf::Image('images/UNAPUNO.png', 10, 5, 24, 24, 'PNG', '', '', true, 150, '', false, false, 0, false, false, false);
        $pdf::Image('images/logo.png', 170, 8, 24, 20, 'PNG', '', '', true, 150, '', false, false, 0, false, false, false);
        $style = array(
            'border' => true,
            'padding' => 1,
            'fgcolor' => array(0, 0, 0),
            'bgcolor' => false
        );


        // QRCODE,H : QR-CODE Best error correction
        $pdf::write2DBarcode("holaaa", 'QRCODE,L', 156, 60, 44, 52, $style, 'N');
        // $pdf::Text(20, 25, 'QRCODE L');


        // ********


        // 
        if ($inscripcion->estado == '1') {
            $pdf::ln();
            $pdf::SetFont('helvetica', 'b', 10);
            $pdf::Cell(170, 6, 'DETALLES DE VOUCHER', 1, 1, 'C', 0, '', 0);

            $total = 0;
            $comision = 0;
            foreach ($pagos as $pago) {
                $comision = $comision + 1;
                $pdf::SetFont('helvetica', 'b', 7);
                $pdf::Cell(30, 5, 'SECUENCIA:', 0, 0, 'L', 0, '', 1);
                $pdf::SetFont('helvetica', '', 8);
                $pdf::Cell(40, 5, $pago->secuencia, 0, 0, 'L', 0, '', 1);

                $pdf::SetFont('helvetica', 'b', 7);
                $pdf::Cell(30, 5, 'FECHA:', 0, 0, 'L', 0, '', 1);
                $pdf::SetFont('helvetica', '', 8);
                $pdf::Cell(30, 5, $pago->fecha, 0, 0, 'L', 0, '', 1);

                $pdf::SetFont('helvetica', 'b', 7);
                $pdf::Cell(25, 5, 'MONTO:', 0, 0, 'L', 0, '', 1);
                $pdf::SetFont('helvetica', '', 8);
                $pdf::Cell(30, 5, "S/ " . number_format((float)$pago->monto, 2, '.', ''), 0, 1, 'L', 0, '', 1);

                $total += $pago->monto;
            }
            $pdf::Cell(130, 5, '', 0, 0, 'L', 0, '', 1);
            $pdf::SetFont('helvetica', 'b', 7);
            $pdf::Cell(25, 5, 'TOTAL:', 0, 0, 'L', 0, '', 1);
            $pdf::SetFont('helvetica', 'b', 8);

            $pdf::Cell(30, 5, "S/ " . number_format((float)$total, 2, '.', ''), 0, 1, 'L', 0, '', 1);

            $pdf::ln();
            $pdf::SetFont('helvetica', 'b', 10);
            $pdf::Cell(170, 6, 'DETALLES DE PAGO', 1, 1, 'C', 0, '', 0);

            $total = 0;
            foreach ($inscripcionPagos as $inscripcionPago) {
                $pdf::SetFont('helvetica', 'b', 7);
                $pdf::Cell(30, 5, 'CONCEPTO DE PAGO:', 0, 0, 'L', 0, '', 1);
                $pdf::SetFont('helvetica', '', 8);
                $pdf::Cell(95, 5, $inscripcionPago->concepto_pagos_id == 1 ? 'MATRÍCULA' : 'ADELANTO MENSUALIDAD', 0, 0, 'L', 0, '', 1);

                $pdf::SetFont('helvetica', 'b', 7);
                $pdf::Cell(30, 5, 'MONTO:', 0, 0, 'L', 0, '', 1);
                $pdf::SetFont('helvetica', '', 8);
                $pdf::Cell(30, 5, "S/ " . number_format((float)$inscripcionPago->monto, 2, '.', ''), 0, 1, 'L', 0, '', 1);

                $total += $inscripcionPago->monto;
            }
            $pdf::Cell(125, 5, '', 0, 0, 'L', 0, '', 1);
            $pdf::SetFont('helvetica', 'b', 7);
            $pdf::Cell(30, 5, 'COMISIÓN BANCO:', 0, 0, 'L', 0, '', 1);
            $pdf::SetFont('helvetica', '', 8);
            $pdf::Cell(30, 5, "S/ " . number_format((float)$comision, 2, '.', ''), 0, 1, 'L', 0, '', 1);

            $pdf::Cell(125, 5, '', 0, 0, 'L', 0, '', 1);
            $pdf::SetFont('helvetica', 'b', 7);
            $pdf::Cell(30, 5, 'TOTAL:', 0, 0, 'L', 0, '', 1);
            $pdf::SetFont('helvetica', 'b', 8);

            $pdf::Cell(30, 5, "S/ " . number_format((float)$total + $comision, 2, '.', ''), 0, 1, 'L', 0, '', 1);
        }

        $pdf::SetFont('helvetica', '', 10);
        $pdf::ln();
        $pdf::SetFont('helvetica', 'b', 10);
        $pdf::SetXY(20, 141);
        $pdf::Cell(170, 6, 'DECLARACIÓN JURADA ELECTRÓNICA', 1, 1, 'C', 0, '', 0);

        $pdf::SetFont('helvetica', '', 10);
        $html = ' 
        <table>
        <tr>
            <th>El quien suscribe declara bajo juramento que la información proporcionada durante el proceso de inscripción y registro para participar del "Simulacro de Examen de Admisión CEPREUNA 2023", es precisa, completa y veraz. Asumo plena responsabilidad por los datos proporcionados y soy consciente de que cualquier falsedad, omisión o información incorrecta resultará en la exclusión de mi participación en el simulacro. En consecuencia, ratifico la veracidad de la presente declaración jurada. </th>
        </tr>
        </table>
        <table border="0.5">
        <tr style="text-align:center; font-weight:bold;">
            <th>RECOMENDACIONES PARA PRESENTARSE EN EL DIA DEL SIMULACRO DE EXAMEN DE ADMISIÓN CEPREUNA 2023</th>
        </tr>
        </table>
        <table>
        <tr >
            <th>
                <ul>
                    <li>
                    Horario de Ingreso: El ingreso será desde las 07:30 a.m. hasta las 09:00 a.m. Es fundamental llegar dentro de este período de tiempo.
                    </li>
                    <li>
                    Inicio del Examen: El examen dará comienzo puntualmente a las 10:00 a.m. y se extenderá hasta las 12:00 p.m. Llegar con antelación para ubicarte cómodamente en tu lugar asignado.  
                    </li>
                </ul>
            </th>
        </tr>
        </table>


        <table border="0.5">
        <tr style="text-align:center; font-weight:bold;">
            <th>Documentos Obligatorios:</th>
        </tr>
        </table>
        <table>
        <tr >
            <th>
                <ul>
                    <li>
                    Presenta tu Documento Nacional de Identidad (D.N.I) en original.
                    </li>
                    <li>
                    Asegúrate de llevar contigo el impreso de la constancia de inscripción para agilizar el proceso de registro. 
                    </li>
                </ul>
            </th>
        </tr>
        </table>

        <table border="0.5">
        <tr style="text-align:center; font-weight:bold;">
            <th>MATERIALES NECESARIOS</th>
        </tr>
        </table>
        <table>
        <tr >
            <th>
            <ul>
                <li>
                    Tablero
                </li>
                <li>
                    Lápiz 2B
                </li>
            </ul>
            </th>
            <th>
            <ul>
                <li>
                    Borrador
                </li>
                <li>
                    Tajador
                </li>
            </ul>
            </th>
        </tr>
        </table>

        <table border="0.5">
        <tr style="text-align:center; font-weight:bold;">
            <th>IMPORTANTE</th>
        </tr>
        </table>
        <table>
        <tr >
            <th>
            El presente examen de simulacro no otorga alguna vacante o puntaje en los procesos de Admisión a la Universidad Nacional del Altiplano.
            </th>
        </tr>
        </table>
        ';

        $pdf::writeHTML($html, true, false, true, false, '');

        $pdf::SetAutoPageBreak(TRUE, 0);
        $pdf::Output('inscripcion.pdf', 'I');
    }
    public function pdfConstancia($id)
    {
        $idMatricula = Crypt::decryptString($id);
        $m = DB::table('matriculas')
            ->select('estudiantes_id as id')
            ->where('id', $idMatricula)
            ->first();
        $id = $m->id;
        // dd($id);
        // return $id;
        // $periodo = Periodo::where("estado","1")->first();
        $inscripcion = DB::table("inscripciones as i")
            ->select(
                "i.id",
                "i.periodos_id",
                "i.estudiantes_id",
                DB::raw("DATE_FORMAT(i.created_at,'%d/%m/%Y %h:%i %p') as Fecha"),
                "s.denominacion as Sede",
                "a.denominacion as Area",
                "t.denominacion as Turno",
                "i.estado",
                "i.cantidad_inscrito",
                "i.correlativo",
                "i.tipo_estudiante"
            )
            ->join("sedes as s", "s.id", "i.sedes_id")
            ->join("areas as a", "a.id", "i.areas_id")
            ->join("turnos as t", "t.id", "i.turnos_id")
            ->where("i.estudiantes_id", $id)
            ->first();
        $periodo = Periodo::find($inscripcion->periodos_id);
        $estudiante = DB::table("estudiantes as e")
            ->select(
                "e.*",
                "td.denominacion as TipoDocumento",
                "c.denominacion as Colegio",
                "p.denominacion as Pais",
                "u.departamento",
                "u.distrito",
                "u.provincia",
                "a.paterno as Apaterno",
                "a.materno as Amaterno",
                "a.nombres as Anombres",
                "a.celular as Acelular",
                "pa.denominacion as Parentesco"
            )
            ->join("tipo_documentos as td", "td.id", "e.tipo_documentos_id")
            ->join("colegios as c", "c.id", "e.colegios_id")
            ->join("pais as p", "p.id", "e.pais_id")
            ->leftJoin("estudiante_apoderados as ea", "ea.estudiantes_id", "e.id")
            ->leftJoin("apoderados as a", "a.id", "ea.apoderados_id")
            ->leftJoin("parentescos as pa", "pa.id", "a.parentescos_id")
            ->leftJoin("ubigeos as u", "u.id", "e.ubigeos_id")
            ->where("e.id", $id)
            ->first();
        $pagos = DB::table("pagos as p")
            ->select(
                "p.*"
            )
            ->join("inscripcion_pagos as ip", "ip.pagos_id", "p.id")
            ->where("ip.inscripciones_id", $id)
            ->groupBy("p.id")
            ->get();
        $matricula = DB::table('matriculas as m')
            ->select('g.denominacion')
            ->join('grupo_aulas as ga', 'ga.id', 'm.grupo_aulas_id')
            ->join('grupos as g', 'g.id', 'ga.grupos_id')
            ->where('m.id', $idMatricula)
            ->first();
        $inscripcionPagos = InscripcionPago::where('inscripciones_id', $id)->orderBy('concepto_pagos_id')->get();
        // $tipo_documento = TipoDocumento::find($inscripcion->estudiantes_id);
        // dd($estudiante);
        $pdf = new PDF();

        PDF::setFooterCallback(function ($pdf) use ($periodo, $inscripcion) {
            $pdf->SetY(-15);
            // $y = $pdf->SetY(-15);
            $pdf->Line(10, 283, 200, 283);
            $pdf->SetFont('helvetica', '', 8);
            $pdf->Cell(0, 10, 'Oficina de Computo e Informática || CEPREUNA ' . $periodo->inicio_ciclo . ' - ' . $periodo->fin_ciclo . ' - Generado: ' . date('d/m/Y h:i a'), "t", false, 'L', 0, '', 0, false, 'T', 'M');
        });
        $pdf::SetTitle('Solicitud');

        $pdf::AddPage();
        $pdf::SetMargins(0, 0, 0);
        $pdf::SetAutoPageBreak(true, 0);
        $pdf::Image('images/fondo-constancia.png', 0, 0, 210, "", 'PNG');
        $pdf::SetMargins(20, 40, 20, true);
        $pdf::setCellHeightRatio(1.5);
        if (isset($matricula)) {


            $pdf::Image('images/UNAPUNO.png', 20, 8, 20, 20, 'PNG', '', '', false, 150, '', false, false, 0, false, false, false);
            $pdf::Image('images/logo.png', 157, 8, 34, 18, 'PNG', '', '', false, 150, '', false, false, 0, false, false, false);

            // $pdf::Image(Storage::disk('fotos')->path($estudiante->foto), 156, 49, 44, 52, 'PNG', '', '', true, 150, '', false, false, 1, false, false, false);

            // $pdf::SetFont('helvetica', 'b', 12);

            $pdf::SetFont('helvetica', 'b', 13);
            $pdf::Cell(0, 5, 'UNIVERSIDAD NACIONAL DEL ALTIPLANO PUNO', 0, 1, 'C', 0, '', 0);
            $pdf::SetFont('helvetica', 'b', 12);
            $pdf::Cell(0, 5, "Centro de Estudios Pre Universitario", 0, 1, 'C', 0, '', 0);
            $pdf::SetFont('helvetica', '', 9);
            $pdf::Cell(0, 5, 'Jr Acora #235 - Telefono 051-363684', 0, 1, 'C', 0, '', 0);
            $pdf::SetFont('helvetica', '', 9);
            $pdf::Cell(0, 5, '“Año de la unidad, la paz y el desarrollo”', 0, 1, 'C', 0, '', 0);
            $pdf::writeHTML("<hr>");



            $pdf::SetFont('helvetica', 'b', 20);

            $pdf::Cell(0, 5, 'CONSTANCIA', 0, 1, 'C', 0, '', 0);

            $pdf::ln();
            // $pdf::SetFont('helvetica', 'b', 10);
            // $pdf::Cell(140, 6, 'El que suscribe, Presidente de la Comisión del Centro Preuniversitario de la Universidad Nacional del Altiplano – Puno,', 0, 0, 'C', 0, '', 0);
            // $pdf::SetFont('helvetica', 'b', 8);
            // **********
            $pdf::SetFont('helvetica', '', 15);
            $html = '<p style="text-align:justify">El que suscribe, Presidente de la Comisión del Centro Preuniversitario de la Universidad Nacional del Altiplano – Puno.
        </p><p>HACE CONSTAR QUE:</p><p>Sr. (Srta):</p>';

            $pdf::writeHTML($html, true, false, true, false, '');

            $pdf::ln();
            $pdf::SetFont('helvetica', 'b', 16);
            $pdf::Cell(0, 5, $estudiante->paterno . ' ' . $estudiante->materno . ' ' . $estudiante->nombres, 0, 1, 'C', 0, '', 0);
            $pdf::ln();
            $pdf::SetFont('helvetica', '', 13);
            if ($inscripcion->tipo_estudiante == '5') {
                $html = '<p style="text-align:justify">Esta Apto para la inscripcion al examen de CEPREUNA ciclo ' . $periodo->inicio_ciclo . ' - ' . $periodo->fin_ciclo . ', Segun RESOLUCION RECTORAL Nº: <b>2732-2022-R-UNA</b>';
            } else {
                $html = '<p style="text-align:justify"><b>NO ADEUDA A CEPREUNA</b> en el ciclo ' . $periodo->inicio_ciclo . ' - ' . $periodo->fin_ciclo . ', tal como consta en los archivos de esta dependencia.';
            }
            $html .= '</p><p style="text-align:justify;">Se expide la presente constancia únicamente para fines de inscripción en la Dirección General de Admisión de la UNA – Puno, modalidad CEPREUNA ' . $periodo->inicio_ciclo . ' - ' . $periodo->fin_ciclo . '.</p>';
            $html .= '<i style="text-align:justify;font-size: 10px;">Nota: Solo podran rendir el examen de Cepreuna los estudiantes que cuentan con el Certificado de estudios del primero al quinto año de educacion secundaria visado por la UGEL o DRE correspondiente.</i><br>';
            $pdf::writeHTML($html, true, false, true, false, '');

            $pdf::SetFont('helvetica', 'b', 14);
            $pdf::Cell(0, 5, 'DETALLE DE ESTUDIANTE', 0, 1, 'C', 0, '', 0);


            // $style6 = array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => '10,10', 'color' => array(0, 128, 0));
            $pdf::SetLineStyle(array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
            // $pdf::RoundedRect(5, 255, 40, 30, 3.50, '1111', 'DF');
            $pdf::RoundedRect(20, 188, 170, 90, 2, '1111');
            // $pdf::RoundedRect(95, 255, 40, 30, 10.0, '1111', null, $style6);
            // $pdf::RoundedRect(140, 255, 40, 30, 8.0, '0101', 'DF', $style6, array(200, 200, 200));

            // $pdf::MultiCell(190 / 3, 6, "Listado de Archivos Adjuntos al Trámite", 'B', 'C', 1, 1, 10, 20 + 50 + 140, true, 0, false, true, 0, 'M', true);
            // $s = 1;
            // $pdf::SetFont('helvetica', '', 8);

            // $pdf::MultiCell(190 / 2.5, 6, $s . ". " . 'reqiosotp asd', 'BT', 'L', 1, 1, 10, 20 + 50 + 140 + ($s * 6), true, 0, false, true, 0, 'M', true);

            // $pdf::SetFont('helvetica', '', 11);
            // $pdf::MultiCell(190 / 3 + 3, 6, "RECIBÍ CONFORME", 'B', 'C', 1, 1, 10 + 190 / 3 + 60, 20 + 50 + 140, true, 0, false, true, 0, 'M', true);
            // $pdf::MultiCell(190 / 3 + 3, 6, "FIRMA", 'T', 'C', 1, 1, 10 + 190 / 3 + 60, 100 + 153, true, 0, false, true, 0, 'M', true);
            // $pdf::MultiCell(190 / 3 + 3, 6, "D.N.I.: .................................................", 0, 'L', 1, 1, 10 + 190 / 3 + 60, 106 + 153, true, 0, false, true, 0, 'T', true);
            // $pdf::MultiCell(190 / 3 + 3, 6, "FECHA: ...................................................", 0, 'L', 1, 1, 10 + 190 / 3 + 60, 112 + 153, true, 0, false, true, 0, 'T', true);

            // $pdf::MultiCell(51, 28, "En la siguiente URL podrá ver el estado de su trámite ingresando el CUT", 'BT', 'C', 1, 1, 10, 240, true, 0, false, true, 0, 'M', true);
            // $pdf::MultiCell(76, 6, "https://tramites.unap.edu.pe/tramite/ver-estado-tramite", 'TB', 'C', 1, 1, 10, 265, true, 0, false, true, 0, 'M', true);
            $pdf::ln();
            $pdf::setCellMargins(5, 0, 0, 0);
            $pdf::SetFont('helvetica', 'b', 12);
            $pdf::Cell(20, 5, 'DNI:', 0, 0, 'L', 0, '', 1);
            // $pdf::SetFont('helvetica', '', 8);
            $pdf::Cell(40, 5, $estudiante->nro_documento, 0, 0, 'L', 0, '', 1);

            // $pdf::SetFont('helvetica', '', 8);

            $pdf::ln();
            // $pdf::SetFont('helvetica', 'b', 7);
            $pdf::Cell(20, 5, 'AREA:', 0, 0, 'L', 0, '', 1);
            // $pdf::SetFont('helvetica', '', 8);
            $pdf::Cell(40, 5, $inscripcion->Area, 0, 0, 'L', 0, '', 1);
            $pdf::ln();
            // $pdf::SetFont('helvetica', 'b', 7);
            $pdf::Cell(20, 5, 'SALON:', 0, 0, 'L', 0, '', 1);
            // $pdf::SetFont('helvetica', '', 8);
            $pdf::Cell(40, 5, $matricula->denominacion, 0, 0, 'L', 0, '', 1);



            $style = array(
                'border' => 2,
                'vpadding' => 'auto',
                'hpadding' => 'auto',
                'fgcolor' => array(0, 0, 0),
                'bgcolor' => false, //array(255,255,255)
                'module_width' => 1, // width of a single module in points
                'module_height' => 1 // height of a single module in points
            );
            $urlQR = $inscripcion->correlativo . ' ' . $estudiante->nro_documento . ' ' . $estudiante->paterno . ' ' . $estudiante->materno . ' ' . $estudiante->nombres;
            // $urlBC = $estudiante->nro_documento;
            // $pdf::write1DBarcode($urlBC, 'C39', 150, 215, '', 15, 0.4, $style, 'N');
            //$url=route('downloadsolicitud',$sol);

            $pdf::write2DBarcode($urlQR, 'QRCODE,H', 142, 230, 40, 40, $style, 'N');

            $pdf::SetXY(87, 215);
            $pdf::SetLineWidth(1);
            $pdf::Image(Storage::disk('fotos')->path($estudiante->foto), '', '', 45, 55, '', '', 'T', false, 300, '', false, false, 1, false, false, false);

            // $pdf::Image(Storage::disk('fotos')->path($estudiante->foto), '', '', 45, 55, '', '', 'T', false, 300, '', false, false, 1, false, false, false);

            $pdf::Image('images/firma2.png', 27, 244, 57.5, 24, 'PNG', '', '', false, 150, '', false, false, 0, false, false, false);
            // $pdf::Image(Storage::disk('fotos')->path($estudiante->foto), 156, 49, 44, 52, 'PNG', '', '', true, 150, '', false, false, 1, false, false, false);
        } else {
            $pdf::SetFont('helvetica', '', 15);
            $html = '<p style="text-align:center"> EL ESTUDIANTE NO ESTA MATRICULADO</p>';

            $pdf::writeHTML($html, true, false, true, false, '');
        }


        $pdf::SetAutoPageBreak(TRUE, 0);

        $pdf::Output('inscripcion.pdf', 'I');
    }
    public function getFotografia($id)
    {
        $idMatricula = Crypt::decryptString($id);
        $matricula = DB::table('matriculas')
            ->select('estudiantes_id')
            ->where('id', $idMatricula)
            ->first();
        $idEstudiante = $matricula->estudiantes_id;

        $estudiante = Estudiante::find($idEstudiante);
        $files = Storage::disk("fotos")->download($estudiante->foto, 'fotografia', [
            'Content-Disposition' => 'inline'
        ]);
        return $files;
    }
    public function save_image($base64_image)
    {

        $img = $this->getB64Image($base64_image);

        $ldate = date('Ymd_His');
        $first = substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 4);
        $img_name = $first . $ldate . '.png';

        Storage::disk('fotos')->put($img_name, $img);
        // Storage::disk('fotos')->putFileAs($this->dateTimePartial, $img, $img_name);
        return $img_name;
    }
    public function getB64Image($base64_image)
    {

        $image_service_str = substr($base64_image, strpos($base64_image, ",") + 1);
        $image = base64_decode($image_service_str);

        return $image;
    }
    public function save_file($tipo, $file, $extension, $disk)
    {
        $date = date('Ymd_His');
        $first = substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 4);
        $file_name = $tipo . '-' . $date . $first . '.' . $extension;
        $name_complete = $this->dateTimePartial . '/' . $file_name;
        Storage::disk($disk)->putFileAs($this->dateTimePartial, $file, $file_name);
        return $name_complete;
    }
}
