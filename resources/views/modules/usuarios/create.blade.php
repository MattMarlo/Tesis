@extends('layouts.main')

@section('title', 'Registrar usuario')

@section('header', 'Usuarios')

@section('content')
    @include('modules.usuarios.partials.formulario')
@endsection

@section('scripts')
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="{{ asset('js/usuarios-formulario.js') }}"></script>
@endsection