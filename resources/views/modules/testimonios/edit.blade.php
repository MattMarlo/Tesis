@extends('layouts.main')

@section('title', 'Editar testimonio')

@section('header', 'Testimonios')

@section('content')
    @include('modules.testimonios.partials.formulario')
@endsection

@section('scripts')
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="{{ asset('js/testimonios-formulario.js') }}"></script>
@endsection