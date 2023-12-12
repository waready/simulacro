@extends('layouts.app')
@section('titulo', 'Dashboard')

@section('content')

<h1>Importar Datos desde Excel</h1>
    <form action="{{ route('importar.ingresantes') }}" method="post" enctype="multipart/form-data">
        @csrf
        <input type="file" name="archivo_excel">
        <input type="hidden" name="X-CSRF-TOKEN" value="{{ csrf_token() }}">
        <button type="submit">Importar</button>
    </form>


    @can('ver distribucion')
        <i-pre-distribucion></i-pre-distribucion>
    @endcan
@endsection

@section('scripts')


@endsection
