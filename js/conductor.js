// Para el reloj y la fecha
const diasES   = ['Domingo','Lunes','Martes','Miércoles','Jueves','Viernes','Sábado'];
const mesesES  = ['enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'];

function actualizarReloj() {
    const ahora = moment();
    document.getElementById('reloj').textContent = ahora.format('HH:mm');
    const fecha =
        diasES[ahora.day()] +
        ', ' +
        ahora.date() +
        ' de ' +
        mesesES[ahora.month()] +
        ' de ' +
        ahora.year();
    document.getElementById('fecha-hoy').textContent = fecha;
}

actualizarReloj();
setInterval(actualizarReloj, 1000);

// Para los horarios y la próxima salida
let proximaHoraMoment = null;

function cargarHorarios() {

    fetch('obtener_horarios.php')
        .then(r => r.json())
        .then(data => {
            const contenedor = document.getElementById('contenedor-horarios');

            if (!data.length) {
                contenedor.innerHTML =
                    '<p class="cargando">No hay viajes programados para hoy.</p>';
                // Sin viajes, limpiar la sección de próxima salida
                document.getElementById('proxima-hora').textContent      = '—';
                document.getElementById('proxima-ruta').textContent      = 'Sin viajes hoy';
                document.getElementById('cuenta-regresiva').textContent  = 'Fin del día';
                proximaHoraMoment = null;
                return;
            }

            const ahora           = moment();
            let proximoEncontrado = false;

            const html = data.map(viaje => {
                const horaSalida      = moment(viaje.hora_salida, 'HH:mm:ss');
                const estadoRecorrido = viaje.estado_recorrido; // viene de la BD

                let badgeClass;
                let badgeTexto;
                let claseItem;

                // Prioridad 1: el conductor ya marcó este viaje como completado en la BD
                if (estadoRecorrido === 'completado') {
                    claseItem  = 'completado';
                    badgeClass = 'badge-completado';
                    badgeTexto = 'Completado';

                // Prioridad 2: el viaje está en proceso (conductor lo inició)
                } else if (
                    estadoRecorrido === 'en_camino'      ||
                    estadoRecorrido === 'llegando'        ||
                    estadoRecorrido === 'proximo_salir'
                ) {
                    claseItem  = 'en-proceso';
                    badgeClass = 'badge-en-proceso';
                    badgeTexto = 'En proceso';

                    // Este es el viaje activo, lo usamos como referencia de próxima salida
                    if (!proximoEncontrado) {
                        proximoEncontrado = true;
                        proximaHoraMoment = horaSalida;
                        actualizarProximaSalida(viaje, horaSalida);
                    }

                // Prioridad 3: sin estado en BD, decidir por hora
                } else {
                    if (horaSalida.isBefore(ahora)) {
                        // La hora ya pasó pero no tiene estado → mostrar como completado
                        claseItem  = 'completado';
                        badgeClass = 'badge-completado';
                        badgeTexto = 'Completado';

                    } else if (!proximoEncontrado) {
                        // Es el próximo viaje futuro sin estado
                        claseItem  = 'proximo';
                        badgeClass = 'badge-proximo';
                        badgeTexto = 'Próximo';
                        proximoEncontrado = true;
                        proximaHoraMoment = horaSalida;
                        actualizarProximaSalida(viaje, horaSalida);

                    } else {
                        // Viajes futuros después del próximo
                        claseItem  = 'pendiente';
                        badgeClass = 'badge-pendiente';
                        badgeTexto = 'Pendiente';
                    }
                }

                return `
                    <div class="viaje-item ${claseItem}">
                        <span class="viaje-hora">${horaSalida.format('HH:mm')}</span>
                        <span class="viaje-ruta">${viaje.origen} → ${viaje.destino}</span>
                        <span class="viaje-badge ${badgeClass}">${badgeTexto}</span>
                    </div>
                `;
            }).join('');

            contenedor.innerHTML = html;

            // Si no se encontró ningún viaje próximo o en proceso, actualizar sección
            if (!proximoEncontrado) {
                document.getElementById('proxima-hora').textContent     = '—';
                document.getElementById('proxima-ruta').textContent     = 'Sin más viajes hoy';
                document.getElementById('cuenta-regresiva').textContent = 'Fin del día';
                proximaHoraMoment = null;
            }
        })
        .catch(error => {
            console.error('Error cargando horarios:', error);
            document.getElementById('contenedor-horarios').innerHTML =
                '<p class="cargando">Error al cargar horarios.</p>';
        });
}

function actualizarProximaSalida(viaje, horaMoment) {
    document.getElementById('proxima-hora').textContent =
        horaMoment.format('HH:mm');
    document.getElementById('proxima-ruta').textContent =
        viaje.origen + ' → ' + viaje.destino;
}

// Cuenta regresiva para la próxima salida
function actualizarCuentaRegresiva() {
    if (!proximaHoraMoment) return;

    const ahora = moment();
    const diff  = proximaHoraMoment.diff(ahora);

    if (diff <= 0) {
        document.getElementById('cuenta-regresiva').textContent = '¡Es hora de salir!';
        cargarHorarios();
        return;
    }

    const horas    = Math.floor(diff / 3600000);
    const minutos  = Math.floor((diff % 3600000) / 60000);
    const segundos = Math.floor((diff % 60000) / 1000);
    let texto = '';

    if (horas > 0) texto += horas + 'h ';
    texto += minutos + 'min ' + segundos + 's';

    document.getElementById('cuenta-regresiva').textContent = texto;
}

cargarHorarios();
setInterval(actualizarCuentaRegresiva, 1000);

// Estado y capacidad del micro
let estadoSeleccionado    = null;
let capacidadSeleccionada = null;

document.querySelectorAll('.btn-estado').forEach(btn => {
    btn.addEventListener('click', () => {
        document.querySelectorAll('.btn-estado')
            .forEach(b => b.classList.remove('activo'));
        btn.classList.add('activo');
        estadoSeleccionado = btn.dataset.estado;
    });
});

document.querySelectorAll('.btn-capacidad').forEach(btn => {
    btn.addEventListener('click', () => {
        document.querySelectorAll('.btn-capacidad')
            .forEach(b => b.classList.remove('activo'));
        btn.classList.add('activo');
        capacidadSeleccionada = btn.dataset.cap;
    });
});

// Resetea los botones de estado y capacidad para el siguiente viaje
function resetearSeleccionEstado() {
    document.querySelectorAll('.btn-estado')
        .forEach(b => b.classList.remove('activo'));
    document.querySelectorAll('.btn-capacidad')
        .forEach(b => b.classList.remove('activo'));
    estadoSeleccionado    = null;
    capacidadSeleccionada = null;
    document.getElementById('ultima-actualizacion').textContent = '';
}

document.getElementById('btn-guardar-estado')
.addEventListener('click', () => {
    if (!estadoSeleccionado || !capacidadSeleccionada) {
        Swal.fire({
            icon: 'warning',
            title: 'Campos incompletos',
            text: 'Selecciona el estado y la capacidad antes de guardar.',
            confirmButtonColor: '#0d2346',
            width: '320px'
        });
        return;
    }

    const datos = new FormData();
    datos.append('estado', estadoSeleccionado);
    datos.append('capacidad', capacidadSeleccionada);

    fetch('actualizar_estado.php', {
        method: 'POST',
        body: datos
    })
    .then(r => r.json())
    .then(resp => {
        if (resp.success) {

            const ahora = moment().format('HH:mm');
            document.getElementById('ultima-actualizacion').textContent =
                'Última actualización: ' + ahora;

            // Si el conductor llegó al destino, el viaje quedó completado
            // Se resetean los botones y se recargan los horarios para mostrar el siguiente viaje
            if (estadoSeleccionado === 'llegando') {
                resetearSeleccionEstado();
                cargarHorarios();
            } else {
                // Para cualquier otro estado, recargar horarios para reflejar "En proceso"
                cargarHorarios();
            }

            Swal.fire({
                icon: 'success',
                title: '¡Listo!',
                text: resp.message,
                confirmButtonColor: '#0d2346',
                timer: 2000,
                showConfirmButton: false,
                width: '320px'
            });

        } else {
            Swal.fire({
                icon: 'error',
                title: 'No disponible',
                text: resp.message,
                confirmButtonColor: '#0d2346',
                width: '320px'
            });
        }
    })
    .catch(error => {
        console.error('Error guardando estado:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'No se pudo guardar el estado.',
            confirmButtonColor: '#0d2346',
            width: '320px'
        });
    });
});

// Mapa leaflet y GPS
const sedes = [
    {
        nombre: 'Campus Ciudad Universitaria',
        lat: 13.509544916895331,
        lng: -88.23213992427416
    },
    {
        nombre: 'Sede Central',
        lat: 13.482012750212768,
        lng: -88.18369862029535
    },
    {
        nombre: 'Campus Agronomía y Veterinaria',
        lat: 13.430735664341332,
        lng: -88.06646258443071
    }
];

const mapa = L.map('mapa-conductor')
.setView([13.4820, -88.1780], 14);

// Para el estilo del mapa
L.tileLayer(
    'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
    {
        attribution: '© OpenStreetMap'
    }
).addTo(mapa);

// Marcar las sedes fijas en el mapa
sedes.forEach(sede => {
    L.marker([sede.lat, sede.lng])
        .addTo(mapa)
        .bindPopup(`<b>${sede.nombre}</b>`);
});

// GPS
let gpsActivo         = false;
let watchId           = null;
let marcadorConductor = null;
let primeraUbicacion  = true;

document.getElementById('btn-gps')
.addEventListener('click', () => {
    if (!gpsActivo) {
        activarGPS();
    } else {
        desactivarGPS();
    }
});

function activarGPS() {
    if (!navigator.geolocation) {
        Swal.fire({
            icon: 'error',
            title: 'Sin GPS',
            text: 'Tu navegador no soporta geolocalización.',
            width: '320px'
        });
        return;
    }

    watchId = navigator.geolocation.watchPosition(
        pos => {
            const { latitude, longitude } = pos.coords;

            // Crear marcador del conductor
            if (!marcadorConductor) {
                marcadorConductor = L.marker(
                    [latitude, longitude],
                    {
                        icon: L.divIcon({
                            className: '',
                            html: `
                                <div style="
                                    background:#f5c518;
                                    border:3px solid #0d2346;
                                    width:16px;
                                    height:16px;
                                    border-radius:50%;
                                "></div>
                            `
                        })
                    }
                )
                .addTo(mapa)
                .bindPopup('Tu ubicación');
            } else {
                marcadorConductor.setLatLng([latitude, longitude]);
            }

            // Centrar mapa solo la primera vez
            if (primeraUbicacion) {
                mapa.setView([latitude, longitude], 15);
                primeraUbicacion = false;
            }

            // Enviar ubicación al servidor
            const datos = new FormData();
            datos.append('lat', latitude);
            datos.append('lng', longitude);

            fetch('actualizar_ubicacion.php', {
                method: 'POST',
                body: datos
            })
            .then(r => r.json())
            .then(resp => {
                console.log('Ubicación enviada');
            })
            .catch(error => {
                console.error('Error enviando ubicación:', error);
            });

            document.getElementById('gps-estado').textContent =
                'GPS activo — ubicación compartida';
        },
        err => {
            console.error('Error GPS:', err);
            Swal.fire({
                icon: 'error',
                title: 'Error GPS',
                text: 'No se pudo obtener tu ubicación.',
                width: '320px'
            });
        },
        {
            enableHighAccuracy: true,
            maximumAge: 5000,
            timeout: 10000
        }
    );

    gpsActivo = true;

    const btn = document.getElementById('btn-gps');
    btn.classList.add('activo');
    btn.innerHTML = '<i class="ri-gps-fill"></i> Desactivar GPS';
}

function desactivarGPS() {
    if (watchId) {
        navigator.geolocation.clearWatch(watchId);
    }

    // Eliminar marcador del conductor
    if (marcadorConductor) {
        mapa.removeLayer(marcadorConductor);
        marcadorConductor = null;
    }

    primeraUbicacion = true;
    gpsActivo        = false;

    const btn = document.getElementById('btn-gps');
    btn.classList.remove('activo');
    btn.innerHTML = '<i class="ri-gps-line"></i> Activar GPS';

    document.getElementById('gps-estado').textContent = 'GPS inactivo';
}

// Para el scroll manual y el enlace activo en el menú
document.addEventListener('DOMContentLoaded', () => {
    const sections = document.querySelectorAll('section[id]');
    const navLinks = document.querySelectorAll('.conductor-nav-link');

    window.addEventListener('scroll', () => {
        let current = '';
        // scrollPosition añade el offset del Header + Nav + 10px de holgura
        const scrollPosition = window.scrollY + 140;

        sections.forEach(section => {
            const sectionTop    = section.offsetTop;
            const sectionHeight = section.offsetHeight;

            if (
                scrollPosition >= sectionTop &&
                scrollPosition < sectionTop + sectionHeight
            ) {
                current = section.getAttribute('id');
            }
        });

        // Si llegamos al final de la página
        if (
            (window.innerHeight + window.scrollY)
            >= document.body.offsetHeight - 5
        ) {
            current = 'seccion-mapa';
        }

        navLinks.forEach(link => {
            link.classList.remove('active');
            if (link.getAttribute('href') === `#${current}`) {
                link.classList.add('active');
            }
        });
    });
});