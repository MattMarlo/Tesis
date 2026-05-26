@extends('layouts.main')

@section('titulo', $titulo)

@section('content')
<main id="main" class="main">
  <div class="pagetitle">
    <h1>Nuevo Destino</h1>
    <nav>
      <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="/">Inicio</a></li>
        <li class="breadcrumb-item"><a href="{{ route('destinos') }}">Destinos</a></li>
        <li class="breadcrumb-item active">Nuevo</li>
      </ol>
    </nav>
  </div>

  <section class="section">
    <div class="row">
      <div class="col-md-3"></div>
      <div class="col-md-6">
        <div class="card">
          <div class="card-body">
            <h5 class="card-title">Nuevo Destino</h5>

            <form action="{{ route('destinos.store') }}" id="form_destino" method="post" enctype="multipart/form-data" novalidate>
              @csrf
              
              <div class="form-group mb-3">
                <label for="etiqueta">Etiqueta</label>
                <input placeholder="Ingrese la etiqueta" class="form-control" type="text" name="etiqueta" id="etiqueta">
              </div>
              <div class="form-group mb-3">
                <label for="pais">País</label>
                <input placeholder="Ingrese un pais " class="form-control" type="text" name="pais" id="pais">
              </div>
              <div class="form-group mb-3">
                <label for="precio">Precio</label>
                <input placeholder="Ingrese el precio" class="form-control" type="number" name="precio" id="precio">
              </div>
              <div class="form-group mb-3">
                <label for="dias">Días</label>
                <input placeholder="Ingrese los días " class="form-control" type="text" name="dias" id="dias">
              </div>
              <div class="form-group mb-3">
                <label for="capacidad">Capacidad</label>
                <input placeholder="Ingrese la capacidad" class="form-control" type="text" name="capacidad" id="capacidad">
              </div>

              <div class="form-group mb-3">
                <label for="imagen">Imagen</label>
                <input  type="file" class="form-control"  name="imagen" id="imagen" accept="image/*">
              </div>

              <div class="d-flex gap-2">
                <button class="btn btn-success" type="submit">Guardar</button>
                <a class="btn btn-outline-danger" href="{{ route('destinos') }}">Cancelar</a>
              </div>
            </form>

          </div>
        </div>
      </div>
    </div>
  </section>
</main>
@endsection