//reloj y fecha
const diasES  = ['Domingo','Lunes','Martes','Miercoles','Jueves','Viernes','Sabado'];
const mesesES = ['enero','febrero','marzo','abril','mayo','junio','julio','agosto',
                 'septiembre','octubre','noviembre','diciembre'];

function actualizarReloj() {
    const ahora = moment();
    document.getElementById('reloj').textContent = ahora.format('HH:mm');
    document.getElementById('fecha-hoy').textContent =
        diasES[ahora.day()] + ', ' + ahora.date() +
        ' de ' + mesesES[ahora.month()] + ' de ' + ahora.year();
}
actualizarReloj();
setInterval(actualizarReloj, 1000);

//para cargar horarios y determinar "Proxima salida"
let proximaHoraMoment  = null;   // moment() del prÃƒÆ’Ã‚Â³ximo viaje (para cuenta regresiva)
let proximaHoraTexto   = null;   // 'HH:mm' del prÃƒÆ’Ã‚Â³ximo viaje (para evitar recargas en loop)
let proximoViajeGlobal = null;   // objeto del viaje candidato a "Proxima salida"

const ESTADOS_EN_PROCESO = ['en_sede', 'proximo_salir', 'en_camino', 'llegando'];

function cargarHorarios() {
    fetch('obtener_horarios.php')
        .then(r => r.json())
        .then(data => {
            const contenedor = document.getElementById('contenedor-horarios');

            if (!data.length) {
                contenedor.innerHTML =
                    '<p class="cargando">No hay viajes programados para hoy.</p>';
                limpiarProximaSalida('Sin viajes hoy');
                return;
            }

            let proximoViaje      = null;   // objeto viaje candidato a "Proxima salida"
            let proximoHoraMomentLocal = null; // local al map, evita shadowing de la var global

            const html = data.map(viaje => {
                const horaSalida      = moment(viaje.hora_salida, 'HH:mm:ss');
                const estadoRecorrido = viaje.estado_recorrido; // '' si no hay registro

                let badgeClass, badgeTexto, claseItem;

                //CASO 1: completado 
                if (estadoRecorrido === 'completado') {
                    claseItem  = 'completado';
                    badgeClass = 'badge-completado';
                    badgeTexto = 'Completado';

                } else if (estadoRecorrido === 'cancelado') {
                    claseItem  = 'cancelado';
                    badgeClass = 'badge-cancelado';
                    badgeTexto = 'Cancelado';

                // CASO 2: viaje activo (iniciado pero no terminado)
                } else if (ESTADOS_EN_PROCESO.includes(estadoRecorrido)) {
                    claseItem  = 'en-proceso';
                    badgeClass = 'badge-en-proceso';
                    badgeTexto = 'En proceso';

                    // El viaje activo tiene maxima prioridad para "Proxima salida"
                    if (!proximoViaje || claseItem === 'en-proceso') {
                        proximoViaje           = viaje;
                        proximoHoraMomentLocal = horaSalida;
                    }

                // CASO 3: pendiente (sin registro en BD)
                // La hora puede haber pasado; igual lo mostramos como pendiente
                // porque el conductor aÃƒÆ’Ã‚Âºn no lo iniciÃƒÆ’Ã‚Â³.
                } else {
                    claseItem  = 'pendiente';
                    badgeClass = 'badge-pendiente';
                    badgeTexto = 'Pendiente';

                    // Solo ocupa "Proxima salida" si no hay ya un viaje activo
                    // ni otro pendiente ya asignado (tomamos el primero en orden ASC)
                    if (!proximoViaje) {
                        proximoViaje           = viaje;
                        proximoHoraMomentLocal = horaSalida;
                    }
                }

                return `
                    <div class="viaje-item ${claseItem}">
                        <span class="viaje-hora">${horaSalida.format('HH:mm')}</span>
                        <span class="viaje-ruta">${viaje.origen} a ${viaje.destino}</span>
                        <span class="viaje-badge ${badgeClass}">${badgeTexto}</span>
                    </div>
                `;
            }).join('');

            contenedor.innerHTML = html;

            if (proximoViaje) {
                // Siempre actualizar el objeto moment para que diff() sea fresco
                // (aunque la hora sea la misma, el objeto puede estar "viejo")
                const nuevaHoraTexto = proximoHoraMomentLocal.format('HH:mm');
                proximaHoraMoment = proximoHoraMomentLocal; // actualizar SIEMPRE
                if (nuevaHoraTexto !== proximaHoraTexto) {
                    proximaHoraTexto = nuevaHoraTexto;
                }
                proximoViajeGlobal = proximoViaje;
                actualizarProximaSalida(proximoViaje, proximoHoraMomentLocal);
            } else {
                proximoViajeGlobal = null;
                limpiarProximaSalida('Sin mas viajes hoy');
            }
        })
        .catch(error => {
            console.error('Error cargando horarios:', error);
            document.getElementById('contenedor-horarios').innerHTML =
                '<p class="cargando">Error al cargar horarios.</p>';
        });
}

// Restaura la selecciÃƒÆ’Ã‚Â³n de estado y capacidad segÃƒÆ’Ã‚Âºn el viaje dado (usado al cargar horarios o actualizar prÃƒÆ’Ã‚Â³ximo viaje)
function restaurarSeleccionDesdeViaje(viaje) {
    document.querySelectorAll('.btn-estado').forEach(b => b.classList.remove('activo'));
    document.querySelectorAll('.btn-capacidad').forEach(b => b.classList.remove('activo'));

    if (!viaje || !ESTADOS_EN_PROCESO.includes(viaje.estado_recorrido)) {
        estadoSeleccionado = null;
        capacidadSeleccionada = null;
        return;
    }

    estadoSeleccionado = viaje.estado_recorrido;
    const estadoBtn = document.querySelector(`.btn-estado[data-estado="${estadoSeleccionado}"]`);
    if (estadoBtn) estadoBtn.classList.add('activo');

    capacidadSeleccionada = viaje.estado_unidad === 'vacio'
        ? 'disponible'
        : (viaje.estado_unidad || null);

    if (capacidadSeleccionada) {
        const capBtn = document.querySelector(`.btn-capacidad[data-cap="${capacidadSeleccionada}"]`);
        if (capBtn) capBtn.classList.add('activo');
    }
}

function actualizarProximaSalida(viaje, horaMoment) {
    document.getElementById('proxima-hora').textContent = horaMoment.format('HH:mm');
    document.getElementById('proxima-ruta').textContent = viaje.origen + ' a ' + viaje.destino;
    document.getElementById('btn-guardar-estado').dataset.idAsignacion = viaje.id_asignacion;
    restaurarSeleccionDesdeViaje(viaje);
    validarEdicionEstado();
}

function limpiarProximaSalida(mensaje) {
    document.getElementById('proxima-hora').textContent     = '--';
    document.getElementById('proxima-ruta').textContent     = mensaje;
    document.getElementById('cuenta-regresiva').textContent = 'Fin del dia';
    document.getElementById('btn-guardar-estado').removeAttribute('data-id-asignacion');
    proximaHoraMoment  = null;
    proximaHoraTexto   = null;
    proximoViajeGlobal = null;
    validarEdicionEstado();
}

// para habilitar/deshabilitar botones de ediciÃƒÆ’Ã‚Â³n de estado segÃƒÆ’Ã‚Âºn reglas de negocio
function validarEdicionEstado() {
    const btnEstados    = document.querySelectorAll('.btn-estado');
    const btnCapacidad  = document.querySelectorAll('.btn-capacidad');
    const btnGuardar    = document.getElementById('btn-guardar-estado');
    const msgBloqueo    = document.getElementById('msg-bloqueo-estado');

    // Determinar si la ediciÃƒÆ’Ã‚Â³n estÃƒÆ’Ã‚Â¡ permitida
    let permitido = false;
    let mensajeBloqueoTexto = '';

    // Diagnostico temporal de validacion
    const _ahora = moment().format('HH:mm:ss');
    const _viaje = proximoViajeGlobal
        ? `id=${proximoViajeGlobal.id_asignacion} estado="${proximoViajeGlobal.estado_recorrido}" hora="${proximoViajeGlobal.hora_salida}"`
        : 'null';
    const _horaMoment = proximaHoraMoment ? proximaHoraMoment.format('HH:mm:ss') : 'null';
    const _diffMin = proximaHoraMoment ? Math.round(proximaHoraMoment.diff(moment()) / 60000) : 'N/A';
    console.log(`[validar] ahora=${_ahora} | viaje=${_viaje} | proximaHoraMoment=${_horaMoment} | diff=${_diffMin}min`);

    if (!proximoViajeGlobal) {
        mensajeBloqueoTexto = 'No hay viajes pendientes para hoy.';
        console.log('[validar] sin viaje: bloqueado');
    } else {
        const estadoViaje = proximoViajeGlobal.estado_recorrido;

        if (ESTADOS_EN_PROCESO.includes(estadoViaje)) {
            permitido = true;
            console.log('[validar] viaje activo: permitido');
        } else {
            if (proximaHoraMoment) {
                const diffMs  = proximaHoraMoment.diff(moment());
                const minutos = Math.round(diffMs / 60000);

                if (minutos <= 20) {
                    permitido = true;
                    console.log(`[validar] pendiente, ${minutos}min <= 20: permitido`);
                } else {
                    const h = Math.floor(minutos / 60);
                    const m = minutos % 60;
                    const textoTiempo = h > 0 ? (m > 0 ? `${h}h ${m}min` : `${h}h`) : `${minutos} min`;
                    mensajeBloqueoTexto = `No puedes editar el estado en este momento. Tu proxima salida es en ${textoTiempo}.`;
                    console.log(`[validar] pendiente, ${minutos}min > 20: bloqueado`);
                }
            } else {
                console.log('[validar] proximaHoraMoment null: bloqueado sin mensaje');
            }
        }
    }

    if (permitido) {
        btnEstados.forEach(b => {
            b.disabled = false;
            b.style.opacity = '';
            b.style.pointerEvents = '';
        });
        btnCapacidad.forEach(b => {
            b.disabled = false;
            b.style.opacity = '';
            b.style.pointerEvents = '';
        });
        btnGuardar.disabled = false;
        btnGuardar.style.opacity = '';
        if (msgBloqueo) msgBloqueo.textContent = '';
        console.log('[validar] botones habilitados');
    } else {
        btnEstados.forEach(b => {
            b.disabled = true;
            b.style.opacity = '0.4';
            b.style.pointerEvents = 'none';
            b.classList.remove('activo');
        });
        btnCapacidad.forEach(b => {
            b.disabled = true;
            b.style.opacity = '0.4';
            b.style.pointerEvents = 'none';
            b.classList.remove('activo');
        });
        btnGuardar.disabled = true;
        btnGuardar.style.opacity = '0.4';
        estadoSeleccionado    = null;
        capacidadSeleccionada = null;
        if (msgBloqueo) msgBloqueo.textContent = mensajeBloqueoTexto;
        console.log('[validar] botones bloqueados, msg:', mensajeBloqueoTexto);
    }
}
// para la cuenta regresiva de la prÃƒÆ’Ã‚Â³xima salida
let cuentaRegregsivaCeroNotificado = false;

function actualizarCuentaRegresiva() {
    if (!proximaHoraMoment) return;

    const ahora = moment();
    const diff  = proximaHoraMoment.diff(ahora);

    if (diff <= 0) {
        // La hora pasÃƒÆ’Ã‚Â³: si el viaje aÃƒÆ’Ã‚Âºn es "pendiente" mostramos aviso
        document.getElementById('cuenta-regresiva').textContent = 'Salida pendiente!';

        // Recargar horarios solo una vez cuando el contador llega a 0
        if (!cuentaRegregsivaCeroNotificado) {
            cuentaRegregsivaCeroNotificado = true;
            cargarHorarios();
        }
        validarEdicionEstado();
        return;
    }

    // Hora aun no llego: mostrar conteo normal
    cuentaRegregsivaCeroNotificado = false;

    const horas    = Math.floor(diff / 3600000);
    const minutos  = Math.floor((diff % 3600000) / 60000);
    const segundos = Math.floor((diff % 60000) / 1000);

    let texto = '';
    if (horas > 0) texto += horas + 'h ';
    texto += minutos + 'min ' + segundos + 's';

    document.getElementById('cuenta-regresiva').textContent = texto;
    validarEdicionEstado();
}

// Bloquear botones de inmediato mientras el primer fetch no responde
// IMPORTANTE: estas variables deben declararse ANTES de llamar a validarEdicionEstado()
let estadoSeleccionado    = null;
let capacidadSeleccionada = null;

validarEdicionEstado();
cargarHorarios();
setInterval(actualizarCuentaRegresiva, 1000);
setInterval(cargarHorarios, 60000);   // refresco periÃƒÆ’Ã‚Â³dico


document.querySelectorAll('.btn-estado').forEach(btn => {
    btn.addEventListener('click', () => {
        document.querySelectorAll('.btn-estado').forEach(b => b.classList.remove('activo'));
        btn.classList.add('activo');
        estadoSeleccionado = btn.dataset.estado;

        if (estadoSeleccionado === 'cancelado') {
            document.querySelectorAll('.btn-capacidad').forEach(b => b.classList.remove('activo'));
            capacidadSeleccionada = 'disponible';
        } else if (!document.querySelector('.btn-capacidad.activo')) {
            capacidadSeleccionada = null;
        }
    });
});

document.querySelectorAll('.btn-capacidad').forEach(btn => {
    btn.addEventListener('click', () => {
        document.querySelectorAll('.btn-capacidad').forEach(b => b.classList.remove('activo'));
        btn.classList.add('activo');
        capacidadSeleccionada = btn.dataset.cap;
    });
});

function resetearSeleccionEstado() {
    document.querySelectorAll('.btn-estado').forEach(b => b.classList.remove('activo'));
    document.querySelectorAll('.btn-capacidad').forEach(b => b.classList.remove('activo'));
    estadoSeleccionado    = null;
    capacidadSeleccionada = null;
    document.getElementById('ultima-actualizacion').textContent = '';
}

document.getElementById('btn-guardar-estado').addEventListener('click', () => {
    // Barrera de seguridad en JS por si el disabled de HTML fue eludido
    if (!proximoViajeGlobal) {
        return; // sin viaje, ignorar
    }
    const estadoViajeActual = proximoViajeGlobal.estado_recorrido;
    const esViajeActivo = ESTADOS_EN_PROCESO.includes(estadoViajeActual);
    if (!esViajeActivo && proximaHoraMoment) {
        const diffMs  = proximaHoraMoment.diff(moment());
        const minutos = Math.round(diffMs / 60000);
        if (minutos > 20) {
            const h = Math.floor(minutos / 60);
            const m = minutos % 60;
            const textoTiempo = h > 0 ? (m > 0 ? `${h}h ${m}min` : `${h}h`) : `${minutos} min`;
            Swal.fire({
                icon: 'warning',
                title: 'Muy temprano',
                text: `No puedes editar el estado en este momento. Tu proxima salida es en ${textoTiempo}.`,
                confirmButtonColor: '#0d2346',
                width: '340px'
            });
            return;
        }
    }

    if (!estadoSeleccionado || (estadoSeleccionado !== 'cancelado' && !capacidadSeleccionada)) {
        Swal.fire({
            icon: 'warning',
            title: 'Campos incompletos',
            text: estadoSeleccionado === 'cancelado' ? 'Selecciona el estado antes de guardar.' : 'Selecciona el estado y la capacidad antes de guardar.',
            confirmButtonColor: '#0d2346',
            width: '320px'
        });
        return;
    }

    const idAsignacion = document.getElementById('btn-guardar-estado').dataset.idAsignacion || '';

    const datos = new FormData();
    datos.append('estado', estadoSeleccionado);
    datos.append('capacidad', capacidadSeleccionada || 'disponible');
    if (idAsignacion) {
        datos.append('id_asignacion', idAsignacion);
    }

    fetch('actualizar_estado.php', { method: 'POST', body: datos, credentials: 'same-origin' })
        .then(r => r.json())
        .then(resp => {
            if (resp.success) {
                document.getElementById('ultima-actualizacion').textContent =
                    'Ultima actualizacion: ' + moment().format('HH:mm');

                if (estadoSeleccionado === 'llegando' || estadoSeleccionado === 'cancelado') {
                    resetearSeleccionEstado();
                }

                cargarHorarios();

                Swal.fire({
                    icon: 'success',
                    title: 'Listo',
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

// Mapa Leaflet y GPS
const sedes = [
    { nombre: 'Campus Ciudad Universitaria',    lat: 13.509544916895331, lng: -88.23213992427416 },
    { nombre: 'Sede Central',                   lat: 13.482012750212768, lng: -88.18369862029535 },
    { nombre: 'Campus AgronomÃƒÆ’Ã‚Â­a y Veterinaria', lat: 13.430735664341332, lng: -88.06646258443071 }
];

const mapa = L.map('mapa-conductor').setView([13.4820, -88.1780], 14);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
    { attribution: 'OpenStreetMap' }).addTo(mapa);
sedes.forEach(sede =>
    L.marker([sede.lat, sede.lng]).addTo(mapa).bindPopup(`<b>${sede.nombre}</b>`)
);

let gpsActivo         = false;
let watchId           = null;
let marcadorConductor = null;
let primeraUbicacion  = true;

document.getElementById('btn-gps').addEventListener('click', () => {
    gpsActivo ? desactivarGPS() : activarGPS();
});

function activarGPS() {
    if (!navigator.geolocation) {
        Swal.fire({ icon: 'error', title: 'Sin GPS',
            text: 'Tu navegador no soporta geolocalizaciÃƒÆ’Ã‚Â³n.', width: '320px' });
        return;
    }
    watchId = navigator.geolocation.watchPosition(
        pos => {
            const { latitude, longitude } = pos.coords;
            if (!marcadorConductor) {
                marcadorConductor = L.marker([latitude, longitude], {
                    icon: L.divIcon({
                        className: '',
                        html: `<div style="background:#f5c518;border:3px solid #0d2346;
                               width:16px;height:16px;border-radius:50%;"></div>`
                    })
                }).addTo(mapa).bindPopup('Tu ubicaciÃƒÆ’Ã‚Â³n');
            } else {
                marcadorConductor.setLatLng([latitude, longitude]);
            }
            if (primeraUbicacion) { mapa.setView([latitude, longitude], 15); primeraUbicacion = false; }
            fetch('actualizar_ubicacion.php', {
                method: 'POST',
                body: (() => { const d = new FormData(); d.append('lat', latitude); d.append('lng', longitude); return d; })()
            }).catch(e => console.error('Error enviando ubicaciÃƒÆ’Ã‚Â³n:', e));
            document.getElementById('gps-estado').textContent = 'GPS activo - ubicacion compartida';
        },
        err => {
            console.error('Error GPS:', err);
            Swal.fire({ icon: 'error', title: 'Error GPS', text: 'No se pudo obtener tu ubicaciÃƒÆ’Ã‚Â³n.', width: '320px' });
        },
        { enableHighAccuracy: true, maximumAge: 5000, timeout: 10000 }
    );
    gpsActivo = true;
    const btn = document.getElementById('btn-gps');
    btn.classList.add('activo');
    btn.innerHTML = '<i class="ri-gps-fill"></i> Desactivar GPS';
}

function desactivarGPS() {
    if (watchId) navigator.geolocation.clearWatch(watchId);
    if (marcadorConductor) { mapa.removeLayer(marcadorConductor); marcadorConductor = null; }
    primeraUbicacion = true;
    gpsActivo = false;
    const btn = document.getElementById('btn-gps');
    btn.classList.remove('activo');
    btn.innerHTML = '<i class="ri-gps-line"></i> Activar GPS';
    document.getElementById('gps-estado').textContent = 'GPS inactivo';
}

// Nav activa al hacer scroll
document.addEventListener('DOMContentLoaded', () => {
    const sections = document.querySelectorAll('section[id]');
    const navLinks = document.querySelectorAll('.conductor-nav-link');
    window.addEventListener('scroll', () => {
        let current = '';
        const scrollPosition = window.scrollY + 140;
        sections.forEach(section => {
            if (scrollPosition >= section.offsetTop &&
                scrollPosition < section.offsetTop + section.offsetHeight)
                current = section.getAttribute('id');
        });
        if ((window.innerHeight + window.scrollY) >= document.body.offsetHeight - 5)
            current = 'seccion-mapa';
        navLinks.forEach(link => {
            link.classList.remove('active');
            if (link.getAttribute('href') === `#${current}`) link.classList.add('active');
        });
    });
});
// para el menÃƒÆ’Ã‚Âº de contacto flotante
const btnContactoFlotante = document.getElementById('btnContactoFlotante');
const contactoFlotante = document.getElementById('contactoFlotante');
const menuContactoFlotante = document.getElementById('menuContactoFlotante');

if (btnContactoFlotante && contactoFlotante && menuContactoFlotante) {
    btnContactoFlotante.addEventListener('click', () => {
        const abierto = contactoFlotante.classList.toggle('abierto');
        btnContactoFlotante.setAttribute('aria-expanded', abierto ? 'true' : 'false');
        menuContactoFlotante.setAttribute('aria-hidden', abierto ? 'false' : 'true');
    });

    document.addEventListener('click', (event) => {
        if (contactoFlotante.contains(event.target)) return;
        contactoFlotante.classList.remove('abierto');
        btnContactoFlotante.setAttribute('aria-expanded', 'false');
        menuContactoFlotante.setAttribute('aria-hidden', 'true');
    });
}
