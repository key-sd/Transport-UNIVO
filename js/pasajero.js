// pasajero.js - Control dinamico de la interfaz del pasajero

const sedesCoords = {
    1: { nombre: 'Sede Central', lat: 13.482012750212768, lng: -88.18369862029535 },
    2: { nombre: 'Campus Ciudad Universitaria', lat: 13.509544916895331, lng: -88.23213992427416 },
    3: { nombre: 'Campus Agronomia y Veterinaria', lat: 13.430735664341332, lng: -88.06646258443071 }
};

let todosLosSchedules = [];
let mapa = null;
let markersBuses = new Map();
let markerSedes = [];
let pollingInterval = null;
let filtroOrigen = '';
let filtroDestino = '';
const NOTIFICACIONES_VERSION = '2026-06-10-cancelado-v2';
if (localStorage.getItem('notificaciones_version') !== NOTIFICACIONES_VERSION) {
    localStorage.setItem('notificaciones_version', NOTIFICACIONES_VERSION);
    localStorage.removeItem('notificados_hoy');
    localStorage.removeItem('cancelados_notificados_hoy');
    localStorage.removeItem('fecha_notificados');
}
// Reloj en tiempo real
function iniciarReloj() {
    const actualizarReloj = () => {
        const ahora = moment();
        document.getElementById('reloj').textContent = ahora.format('HH:mm');
        const diasES = ['Domingo', 'Lunes', 'Martes', 'Miercoles', 'Jueves', 'Viernes', 'Sabado'];
        const mesesES = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
        document.getElementById('fecha-hoy').textContent = `${diasES[ahora.day()]}, ${ahora.date()} de ${mesesES[ahora.month()]} de ${ahora.year()}`;
    };
    actualizarReloj();
    setInterval(actualizarReloj, 1000);
}

// Inicializar Mapa Leaflet
function inicializarMapa() {
    const mapDiv = document.getElementById('mapa-pasajero');
    if (!mapDiv) return;

    // Crear mapa centrado en San Miguel, El Salvador
    mapa = L.map('mapa-pasajero').setView([13.4820, -88.1780], 13);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: 'OpenStreetMap'
    }).addTo(mapa);

    // Colocar marcadores de las sedes
    Object.keys(sedesCoords).forEach(id => {
        const s = sedesCoords[id];
        const marker = L.marker([s.lat, s.lng])
            .addTo(mapa)
            .bindPopup(`<b>${s.nombre}</b>`);
        markerSedes.push(marker);
    });
}

// Cargar y Actualizar Datos
function cargarInformacion() {
    fetch('obtener_info_pasajero.php')
        .then(res => res.json())
        .then(data => {
            if (data.success === false) {
                console.error(data.message);
                return;
            }
            todosLosSchedules = data;
            renderizarSchedules();
            actualizarTiempoRealYTrayecto();
            verificarNotificacionesSalida();
        })
        .catch(err => console.error("Error al obtener datos:", err));
}

function normalizarEstadoRecorrido(estado) {
    return String(estado || 'pendiente').trim().toLowerCase();
}
// Dibujar el Cronograma/Horarios
function renderizarSchedules() {
    const contenedor = document.getElementById('contenedor-horarios');
    if (!contenedor) return;

    // Aplicar filtros si existen
    let schedulesFiltrados = todosLosSchedules;
    if (filtroOrigen && filtroDestino) {
        schedulesFiltrados = todosLosSchedules.filter(s => 
            s.origen_id == filtroOrigen && s.destino_id == filtroDestino
        );
    }

    if (schedulesFiltrados.length === 0) {
        contenedor.innerHTML = `<div class="cargando text-muted"><i class="ri-calendar-close-line" style="font-size:24px;"></i><p class="mt-2">No hay horarios programados para esta seleccion hoy.</p></div>`;
        return;
    }

    const mapEstadoRecorrido = {
        'pendiente': { texto: 'Pendiente', clase: 'badge-pendiente' },
        'en_sede': { texto: 'En sede', clase: 'badge-en_sede' },
        'proximo_salir': { texto: 'Proximo a salir', clase: 'badge-proximo_salir' },
        'en_camino': { texto: 'En camino', clase: 'badge-en_camino' },
        'llegando': { texto: 'Llegando', clase: 'badge-llegando' },
        'completado': { texto: 'Completado', clase: 'badge-completado' },
        'cancelado': { texto: 'Cancelado', clase: 'badge-cancelado' }
    };

    const mapCapacidad = {
        'desconocida': { texto: 'Sin datos', clase: 'badge-pendiente' },
        'vacio': { texto: 'Asientos libres', clase: 'badge-cap-vacio' },
        'medio_lleno': { texto: 'Medio lleno', clase: 'badge-cap-medio_lleno' },
        'lleno': { texto: 'Lleno', clase: 'badge-cap-lleno' }
    };

    const html = schedulesFiltrados.map(s => {
        const estadoNormal = normalizarEstadoRecorrido(s.estado_recorrido);
        const est = mapEstadoRecorrido[estadoNormal] || { texto: s.estado_recorrido, clase: 'badge-pendiente' };
        const cap = mapCapacidad[s.capacidad] || { texto: s.capacidad, clase: 'badge-pendiente' };
        const horaFormato = moment(s.hora_salida, 'HH:mm:ss').format('HH:mm');

        let bodyHTML = '';
        if (s.conductor || s.unidad) {
            bodyHTML = `
                <div class="viaje-item-body">
                    ${s.conductor ? `
                    <div class="info-snippet">
                        <i class="ri-steering-fill"></i>
                        <span>${s.conductor.nombre}</span>
                    </div>` : ''}
                    ${s.unidad ? `
                    <div class="info-snippet">
                        <i class="ri-bus-fill"></i>
                        <span>${s.unidad.nombre} (${s.unidad.placa})</span>
                    </div>` : ''}
                </div>
            `;
        }

        return `
            <div class="viaje-item ${estadoNormal === 'cancelado' ? 'viaje-cancelado' : ''}" data-id="${s.id_cronograma}">
                <div class="viaje-item-header">
                    <div>
                        <span class="viaje-hora me-2">${horaFormato}</span>
                        <span class="viaje-ruta">${s.origen} <i class="ri-arrow-right-line" style="vertical-align: middle;"></i> ${s.destino}</span>
                    </div>
                    <div class="viaje-badges">
                        <span class="viaje-badge ${est.clase}">${est.texto}</span>
                        ${estadoNormal !== 'pendiente' && estadoNormal !== 'completado' && estadoNormal !== 'cancelado' ? `
                            <span class="viaje-badge ${cap.clase}">${cap.texto}</span>
                        ` : ''}
                    </div>
                </div>
                ${bodyHTML}
            </div>
        `;
    }).join('');

    contenedor.innerHTML = html;
}

// Calcular distancia Haversine
function calcularDistancia(lat1, lon1, lat2, lon2) {
    const R = 6371; // Radio de la Tierra en km
    const dLat = (lat2 - lat1) * Math.PI / 180;
    const dLon = (lon2 - lon1) * Math.PI / 180;
    const a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
              Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
              Math.sin(dLon / 2) * Math.sin(dLon / 2);
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
    return R * c;
}

function marcadorKey(viaje) {
    return String(viaje.id_asignacion || (viaje.conductor && viaje.conductor.id) || viaje.id_cronograma);
}

function crearIconoBus(indice) {
    const colores = ['#f5c518', '#22c55e', '#38bdf8', '#f97316', '#a78bfa'];
    const color = colores[indice % colores.length];
    return L.divIcon({
        className: '',
        html: `
            <div class="animate__animated animate__pulse animate__infinite" style="
                background:${color};
                border:3px solid #0d2346;
                width:24px;
                height:24px;
                border-radius:50%;
                box-shadow: 0 0 10px rgba(13, 35, 70, 0.35);
                display:flex;
                align-items:center;
                justify-content:center;
            "><i class="ri-bus-2-fill" style="font-size:12px; color:#0d2346;"></i></div>
        `
    });
}

function limpiarMarcadoresInactivos(keysActivas) {
    markersBuses.forEach((marker, key) => {
        if (!keysActivas.has(key)) {
            mapa.removeLayer(marker);
            markersBuses.delete(key);
        }
    });
}

// Actualizar Mapa y Tarjeta de ETA en Tiempo Real
function actualizarTiempoRealYTrayecto() {
    const esViajeActivo = (s) => {
        const estado = normalizarEstadoRecorrido(s.estado_recorrido);
        return estado !== 'pendiente' && estado !== 'completado' && estado !== 'cancelado';
    };

    const viajesActivos = todosLosSchedules.filter(s => {
        if (filtroOrigen && filtroDestino) {
            if (s.origen_id != filtroOrigen || s.destino_id != filtroDestino) return false;
        }
        return esViajeActivo(s);
    });

    const viajesConGps = viajesActivos.filter(s => s.gps);
    const etaTiempo = document.getElementById('eta-tiempo');
    const etaDesc = document.getElementById('eta-desc');
    const etaRuta = document.getElementById('eta-ruta');

    if (!mapa) {
        inicializarMapa();
    } else {
        mapa.invalidateSize();
    }

    if (!viajesActivos.length) {
        limpiarMarcadoresInactivos(new Set());
        if (etaTiempo) etaTiempo.textContent = '-- min';
        if (etaDesc) etaDesc.textContent = filtroOrigen && filtroDestino
            ? 'No hay unidades activas en esta ruta en este momento.'
            : 'Selecciona una ruta para monitorear el transporte activo.';
        if (etaRuta) etaRuta.style.display = 'none';
        return;
    }

    const keysActivas = new Set();
    const bounds = [];

    viajesConGps.forEach((viaje, index) => {
        const key = marcadorKey(viaje);
        keysActivas.add(key);
        const { lat, lng } = viaje.gps;
        const titulo = viaje.unidad ? `${viaje.unidad.nombre} (${viaje.unidad.placa})` : 'Transporte';
        const conductor = viaje.conductor ? `<br>Conductor: ${viaje.conductor.nombre}` : '';
        const hora = moment(viaje.hora_salida, 'HH:mm:ss').format('HH:mm');
        const edadSeg = Number(viaje.gps.edad_seg || 0);
        const actualizado = edadSeg < 60 ? 'actualizado hace menos de 1 min' : `actualizado hace ${Math.round(edadSeg / 60)} min`;
        const popup = `<b>${titulo}</b><br>${viaje.origen} a ${viaje.destino}<br>Salida: ${hora}<br>Estado: ${viaje.estado_recorrido}${conductor}<br><small>${actualizado}</small>`;

        if (!markersBuses.has(key)) {
            markersBuses.set(key, L.marker([lat, lng], { icon: crearIconoBus(index) }).addTo(mapa).bindPopup(popup));
        } else {
            const marker = markersBuses.get(key);
            marker.setLatLng([lat, lng]);
            marker.getPopup().setContent(popup);
        }
        bounds.push([lat, lng]);
    });

    limpiarMarcadoresInactivos(keysActivas);

    if (bounds.length === 1) {
        mapa.setView(bounds[0], 14);
    } else if (bounds.length > 1) {
        mapa.fitBounds(bounds, { padding: [35, 35], maxZoom: 14 });
    }

    const prioridadEstado = { llegando: 1, en_camino: 2, proximo_salir: 3, en_sede: 4 };
    const viajePrincipal = [...viajesActivos].sort((a, b) => {
        const estadoA = prioridadEstado[normalizarEstadoRecorrido(a.estado_recorrido)] || 9;
        const estadoB = prioridadEstado[normalizarEstadoRecorrido(b.estado_recorrido)] || 9;
        if (estadoA !== estadoB) return estadoA - estadoB;
        return String(a.hora_salida).localeCompare(String(b.hora_salida));
    })[0];

    const estadoPrincipal = normalizarEstadoRecorrido(viajePrincipal.estado_recorrido);
    let etaTexto = viajesActivos.length > 1 ? `${viajesActivos.length} activos` : '-- min';
    let descTexto = viajesActivos.length > 1
        ? `Se muestran ${viajesConGps.length} de ${viajesActivos.length} unidades con GPS activo.`
        : 'Estado del transporte actualizado.';

    if (!viajesConGps.length) {
        etaTexto = viajesActivos.length > 1 ? `${viajesActivos.length} activos` : 'Sin GPS';
        descTexto = viajesActivos.length > 1
            ? `${viajesActivos.length} unidades activas, esperando que compartan ubicacion.`
            : 'La unidad esta activa, esperando ubicacion GPS.';
    } else if (estadoPrincipal === 'en_sede') {
        etaTexto = viajesActivos.length > 1 ? `${viajesActivos.length} activos` : 'En sede';
        descTexto = viajesActivos.length > 1 ? descTexto : 'El transporte esta en la sede de origen.';
    } else if (estadoPrincipal === 'proximo_salir') {
        etaTexto = viajesActivos.length > 1 ? `${viajesActivos.length} activos` : 'Por salir';
        descTexto = viajesActivos.length > 1 ? descTexto : 'El transporte esta listo para salir.';
    } else if (estadoPrincipal === 'llegando') {
        etaTexto = viajesActivos.length > 1 ? `${viajesActivos.length} activos` : '< 2 min';
        descTexto = viajesActivos.length > 1 ? descTexto : 'El transporte esta llegando a la sede de destino.';
    }

    const viajeParaEta = viajesConGps.find(v => normalizarEstadoRecorrido(v.estado_recorrido) === 'en_camino') || viajesConGps[0];
    if (viajeParaEta && viajeParaEta.gps && normalizarEstadoRecorrido(viajeParaEta.estado_recorrido) === 'en_camino') {
        const destCoords = sedesCoords[viajeParaEta.destino_id];
        if (destCoords) {
            const dist = calcularDistancia(viajeParaEta.gps.lat, viajeParaEta.gps.lng, destCoords.lat, destCoords.lng);
            const tiempoHoras = dist / 30;
            const tiempoMinutos = Math.round(tiempoHoras * 60);
            etaTexto = `${tiempoMinutos < 2 ? 2 : tiempoMinutos} min`;
            descTexto = viajesActivos.length > 1
                ? `Unidad mas proxima hacia ${viajeParaEta.destino}. Tambien hay ${viajesActivos.length - 1} unidad(es) activa(s).`
                : `Tiempo estimado hacia ${viajeParaEta.destino} (a ${dist.toFixed(1)} km).`;
        }
    }

    if (etaTiempo) etaTiempo.textContent = etaTexto;
    if (etaDesc) etaDesc.textContent = descTexto;
    if (etaRuta) {
        etaRuta.style.display = 'inline-flex';
        etaRuta.innerHTML = `<i class="ri-route-line"></i> ${viajePrincipal.origen} a ${viajePrincipal.destino}`;
    }
}
// Alertas de Salida 10 minutos antes y cancelaciones
function verificarNotificacionesSalida() {
    const ahora = moment();

    let notificadosHoy = JSON.parse(localStorage.getItem('notificados_hoy') || '[]');
    let canceladosNotificadosHoy = JSON.parse(localStorage.getItem('cancelados_notificados_hoy') || '[]');
    let fechaGuardada = localStorage.getItem('fecha_notificados');

    const hoyStr = ahora.format('YYYY-MM-DD');
    if (fechaGuardada !== hoyStr) {
        notificadosHoy = [];
        canceladosNotificadosHoy = [];
        localStorage.setItem('fecha_notificados', hoyStr);
        localStorage.setItem('notificados_hoy', JSON.stringify([]));
        localStorage.setItem('cancelados_notificados_hoy', JSON.stringify([]));
    }

    todosLosSchedules.forEach(s => {
        if (filtroOrigen && filtroDestino) {
            if (s.origen_id != filtroOrigen || s.destino_id != filtroDestino) return;
        }

        const estadoNormal = normalizarEstadoRecorrido(s.estado_recorrido);
        const claveViaje = `${s.id_cronograma}-${s.hora_salida}`;
        const claveCancelado = `cancelado-${claveViaje}`;

        if (estadoNormal === 'cancelado') {
            if (!canceladosNotificadosHoy.includes(claveCancelado)) {
                mostrarAlertaCancelacion(s);
                canceladosNotificadosHoy.push(claveCancelado);
                localStorage.setItem('cancelados_notificados_hoy', JSON.stringify(canceladosNotificadosHoy));
            }
            return;
        }

        if (estadoNormal === 'completado') return;
        if (notificadosHoy.includes(claveViaje)) return;

        const horaSalida = moment(s.hora_salida, 'HH:mm:ss');
        const diffMinutos = horaSalida.diff(ahora, 'minutes', true);

        if (diffMinutos > 0 && diffMinutos <= 10) {
            mostrarAlertaSalida(s);
            notificadosHoy.push(claveViaje);
            localStorage.setItem('notificados_hoy', JSON.stringify(notificadosHoy));
        }
    });
}

function mostrarAlertaSalida(viaje) {
    const horaFmt = moment(viaje.hora_salida, 'HH:mm:ss').format('HH:mm');
    const msg = `El transporte de ${viaje.origen} hacia ${viaje.destino} saldrá a las ${horaFmt}. Prepárate para abordar.`;

    // Toast en pantalla
    const container = document.getElementById('toast-container');
    if (container) {
        const toast = document.createElement('div');
        toast.className = 'toast-premium animate__animated animate__slideInRight';
        toast.innerHTML = `
            <i class="ri-notification-3-fill"></i>
            <div>
                <div class="toast-title">Proxima Salida</div>
                <div class="toast-message">${msg}</div>
            </div>
        `;
        container.appendChild(toast);
        
        // Auto eliminar despues de 7 segundos
        setTimeout(() => {
            toast.classList.replace('animate__slideInRight', 'animate__fadeOutRight');
            setTimeout(() => toast.remove(), 500);
        }, 7000);
    }
    notificarNativo('Transport-UNIVO: Proxima Salida', msg);
}

function mostrarAlertaCancelacion(viaje) {
    const horaFmt = moment(viaje.hora_salida, 'HH:mm:ss').format('HH:mm');
    const msg = `El transporte de ${viaje.origen} hacia ${viaje.destino} de las ${horaFmt} fue cancelado.`;

    const container = document.getElementById('toast-container');
    if (container) {
        const toast = document.createElement('div');
        toast.className = 'toast-premium toast-alerta animate__animated animate__slideInRight';
        toast.innerHTML = `
            <i class="ri-error-warning-fill"></i>
            <div>
                <div class="toast-title">Viaje cancelado</div>
                <div class="toast-message">${msg}</div>
            </div>
        `;
        container.appendChild(toast);

        setTimeout(() => {
            toast.classList.replace('animate__slideInRight', 'animate__fadeOutRight');
            setTimeout(() => toast.remove(), 500);
        }, 9000);
    }

    notificarNativo('Transport-UNIVO: Viaje cancelado', msg);
}

function notificarNativo(titulo, mensaje) {
    if (!("Notification" in window) || Notification.permission !== "granted") return;

    new Notification(titulo, {
        body: mensaje,
        icon: '../img/logo-app.png'
    });
}
// Solicitar permisos de notificacion nativa
function solicitarPermisosNotificacion() {
    if ("Notification" in window) {
        if (Notification.permission !== "granted" && Notification.permission !== "denied") {
            Notification.requestPermission();
        }
    }
}

// Configurar los Filtros
document.addEventListener('DOMContentLoaded', () => {
    iniciarReloj();
    inicializarMapa();
    solicitarPermisosNotificacion();

    const selectOrigen = document.getElementById('filtro-origen');
    const selectDestino = document.getElementById('filtro-destino');
    const btnBuscar = document.getElementById('btn-buscar');

    if (btnBuscar) {
        btnBuscar.addEventListener('click', () => {
            const origenVal = selectOrigen.value;
            const destinoVal = selectDestino.value;

            if (!origenVal || !destinoVal) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Campos incompletos',
                    text: 'Por favor selecciona la sede de origen y la sede de destino.',
                    confirmButtonColor: '#0d2346',
                    width: '320px'
                });
                return;
            }

            if (origenVal === destinoVal) {
                Swal.fire({
                    icon: 'error',
                    title: 'Ruta invalida',
                    text: 'La sede de origen no puede ser igual a la sede de destino.',
                    confirmButtonColor: '#0d2346',
                    width: '320px'
                });
                return;
            }

            // Aplicar filtros
            filtroOrigen = origenVal;
            filtroDestino = destinoVal;

            cargarInformacion();
            
            Swal.fire({
                icon: 'success',
                title: 'Filtro aplicado',
                text: 'Buscando viajes disponibles...',
                confirmButtonColor: '#0d2346',
                timer: 1500,
                showConfirmButton: false,
                width: '320px'
            });
        });
    }

    // Carga inicial y bucle de refresco (cada 10 segundos)
    cargarInformacion();
    pollingInterval = setInterval(cargarInformacion, 10000);
});
