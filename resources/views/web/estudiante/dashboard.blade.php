@extends('layouts.estudiante')
@section('titulo', ' Dashboard')

@section('content')

    {{-- <div class="row">
        @if (!$estudiante->edit)
            <w-confirmar-datos :estudiante="{{ $estudiante }}">
                </w-confirmacion-datos>
        @endif
    </div> --}}
    <div class="row">

        <div class="col-md-5 col-xs-12">

            @if ($estado == true)
                @if (isset($matricula))
                    @if ($matricula->estado == 0)
                        <w-matricula-estudiante></w-matricula-estudiante>
                    @elseif($matricula->estado == 1)
                        <div class="container">
                            <div class="row">
                                <div class="col text-center">
                                    <div class="card">
                                        <div class="card-header">
                                            <h4 class="m-0">Bienvenido a su Panel de Estudiante</h4>
                                        </div>
                                        <div class="card-body">En este panel Ud. puede visualizar todo referente a
                                            sus cursos.
                                            <div class="container">
                                                <div class="row">
                                                    <div class="col text-center">

                                                        <w-dashboard-local></w-dashboard-local>

                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>
                    @else
                        <w-matricula-estudiante></w-matricula-estudiante>
                    @endif
                @else
                    <div class="container">
                        <div class="row">
                            <div class="col text-center">
                                <div class="card">
                                    <div class="card-header">
                                        <h4 class="m-0">Bienvenido a su Panel de Estudiante</h4>
                                    </div>
                                    <div class="card-body">
                                        <div class="alert alert-warning" role="alert">
                                            <strong>Aviso</strong> Sus cursos estan en proceso de matricula.
                                        </div>
                                        <div class="container">
                                            <div class="row">
                                                <div class="col text-center">

                                                    <w-dashboard-local></w-dashboard-local>

                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            @else
                <div class="container">
                    <div class="row">
                        <div class="col text-center">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="m-0">Bienvenido a su Panel de Estudiante</h4>
                                </div>
                                <div class="card-body">
                                    <div class="alert alert-info" role="alert">
                                        <strong>Atencion:</strong>
                                        <p>El proceso de habilitación de sus cursos con Classroom se está llevando a
                                            cabo, por favor ingrese a sus clases mediante el link que se muestra en su
                                            módulo de cursos.</p>

                                    </div>
                                    <div class="container">
                                        <div class="row">
                                            <div class="col text-center">

                                                <w-dashboard-local></w-dashboard-local>

                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

        </div>
        <div class="col-md-7 col-xs-12">
            <div class="row">
                <div class="col">
                    <div class="card card-accent-success">
                        <div class="card-header font-weight-bold">Notificación</div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-12 col-xs-12 text-center">
                                    <comunicado mostrar="1" />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>




@endsection

@section('scripts')


@endsection
