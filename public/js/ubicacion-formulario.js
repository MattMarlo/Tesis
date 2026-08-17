document.addEventListener('DOMContentLoaded', () => {
    const contenedor = document.getElementById('selectorMapaUbicacion');

    if (!contenedor || typeof L === 'undefined') {
        return;
    }

    const campoLatitud = document.getElementById('latitud');
    const campoLongitud = document.getElementById('longitud');
    const campoLocalidad = document.getElementById('localidad');
    const campoDireccion = document.getElementById('direccion');
    const campoConsultaMapa = document.getElementById('consulta_mapa');
    const campoEnlaceMapa = document.getElementById('enlace_mapa');
    const textoCoordenadas = document.getElementById('coordenadasUbicacion');
    const estadoDireccion = document.getElementById('estadoDireccionMapa');
    const latitudGuardada = Number.parseFloat(contenedor.dataset.latitud);
    const longitudGuardada = Number.parseFloat(contenedor.dataset.longitud);
    const tieneCoordenadas = Number.isFinite(latitudGuardada) &&
        Number.isFinite(longitudGuardada);
    const centroInicial = tieneCoordenadas
        ? [latitudGuardada, longitudGuardada]
        : [-1.0439, -78.5904];
    const mapa = L.map(contenedor).setView(
        centroInicial,
        tieneCoordenadas ? 17 : 14
    );
    let marcador = null;
    let temporizadorDireccion = null;
    let controladorDireccion = null;

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap contributors',
    }).addTo(mapa);

    const mostrarEstado = (tipo, mensaje, icono) => {
        estadoDireccion.className = `estado-direccion-mapa ${tipo}`;
        estadoDireccion.innerHTML =
            `<i class="bi ${icono}"></i><span>${mensaje}</span>`;
    };

    const primerValor = (objeto, propiedades) => {
        for (const propiedad of propiedades) {
            if (objeto[propiedad]) {
                return objeto[propiedad];
            }
        }

        return '';
    };

    const crearDireccionVisible = (resultado) => {
        const datos = resultado.address || {};
        const via = primerValor(datos, [
            'road',
            'pedestrian',
            'footway',
            'neighbourhood',
        ]);
        const numeroYVia = [datos.house_number, via]
            .filter(Boolean)
            .join(' ');
        const localidad = primerValor(datos, [
            'city',
            'town',
            'village',
            'municipality',
            'city_district',
            'county',
        ]);
        const partes = [
            numeroYVia,
            localidad,
            datos.state,
            datos.country,
        ].filter((parte, indice, lista) =>
            parte && lista.indexOf(parte) === indice
        );

        return (partes.join(', ') || resultado.display_name || '')
            .slice(0, 255);
    };

    const completarDatos = async (latitud, longitud) => {
        if (controladorDireccion) {
            controladorDireccion.abort();
        }

        controladorDireccion = new AbortController();
        mostrarEstado(
            'cargando',
            'Buscando la dirección del punto seleccionado...',
            'bi-arrow-repeat'
        );

        const parametros = new URLSearchParams({
            format: 'jsonv2',
            lat: latitud,
            lon: longitud,
            zoom: '18',
            addressdetails: '1',
            'accept-language': 'es',
        });

        try {
            const respuesta = await fetch(
                `https://nominatim.openstreetmap.org/reverse?${parametros}`,
                {
                    headers: {
                        Accept: 'application/json',
                    },
                    signal: controladorDireccion.signal,
                }
            );

            if (!respuesta.ok) {
                throw new Error('No fue posible consultar la dirección.');
            }

            const resultado = await respuesta.json();
            const datos = resultado.address || {};
            const localidad = primerValor(datos, [
                'city',
                'town',
                'village',
                'municipality',
                'city_district',
                'county',
            ]);
            const direccion = crearDireccionVisible(resultado);
            const referencia = (resultado.display_name || direccion)
                .slice(0, 255);

            if (localidad) {
                campoLocalidad.value = localidad.slice(0, 100);
            }

            if (direccion) {
                campoDireccion.value = direccion;
            }

            if (referencia) {
                campoConsultaMapa.value = referencia;
            }

            campoEnlaceMapa.value =
                'https://www.google.com/maps/search/?api=1&query=' +
                encodeURIComponent(`${latitud},${longitud}`);

            mostrarEstado(
                'completado',
                'Datos completados. Revísalos y guarda la ubicación.',
                'bi-check-circle-fill'
            );
        } catch (error) {
            if (error.name === 'AbortError') {
                return;
            }

            mostrarEstado(
                'error',
                'El punto quedó seleccionado, pero no se pudo obtener la dirección. Puedes completar los campos manualmente.',
                'bi-exclamation-circle'
            );
        }
    };

    const programarDatos = (latitud, longitud) => {
        window.clearTimeout(temporizadorDireccion);

        if (controladorDireccion) {
            controladorDireccion.abort();
            controladorDireccion = null;
        }

        mostrarEstado(
            'cargando',
            'Preparando la búsqueda de la dirección...',
            'bi-arrow-repeat'
        );

        temporizadorDireccion = window.setTimeout(
            () => completarDatos(latitud, longitud),
            1100
        );
    };

    const actualizarSeleccion = (
        latitud,
        longitud,
        debeCompletarDatos = false
    ) => {
        const latitudNormalizada = latitud.toFixed(7);
        const longitudNormalizada = longitud.toFixed(7);

        campoLatitud.value = latitudNormalizada;
        campoLongitud.value = longitudNormalizada;
        textoCoordenadas.textContent =
            `${latitudNormalizada}, ${longitudNormalizada}`;
        textoCoordenadas.classList.add('seleccionadas');

        if (marcador) {
            marcador.setLatLng([latitud, longitud]);
        } else {
            marcador = L.marker([latitud, longitud], {
                draggable: true,
            }).addTo(mapa);

            marcador.on('dragend', (evento) => {
                const posicion = evento.target.getLatLng();
                actualizarSeleccion(posicion.lat, posicion.lng, true);
            });
        }

        if (debeCompletarDatos) {
            programarDatos(latitudNormalizada, longitudNormalizada);
        }
    };

    if (tieneCoordenadas) {
        actualizarSeleccion(latitudGuardada, longitudGuardada);
    }

    mapa.on('click', (evento) => {
        actualizarSeleccion(evento.latlng.lat, evento.latlng.lng, true);
    });

    const reajustarMapa = () => mapa.invalidateSize({
        animate: false,
        pan: false,
    });

    window.requestAnimationFrame(reajustarMapa);
    window.setTimeout(reajustarMapa, 150);
    window.setTimeout(reajustarMapa, 400);

    if (typeof ResizeObserver !== 'undefined') {
        const observadorMapa = new ResizeObserver(reajustarMapa);
        observadorMapa.observe(contenedor);
    }
});
