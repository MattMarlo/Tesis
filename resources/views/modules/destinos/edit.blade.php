@extends('layouts.main')

@section('title', $titulo)

@section('header', 'Paquetes turísticos')

@section('content')

    @include('modules.destinos.partials.formulario')

@endsection

@section('scripts')

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    <script src="{{ asset('js/paquetes-formulario.js') }}"></script>

@endsection