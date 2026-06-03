// pasajero.js - Control dinámico de la interfaz del pasajero

const sedesCoords = {
    1: { nombre: 'Sede Central', lat: 13.482012750212768, lng: -88.18369862029535 },
    2: { nombre: 'Campus Ciudad Universitaria', lat: 13.509544916895331, lng: -88.23213992427416 },
    3: { nombre: 'Campus Agronomía y Veterinaria', lat: 13.430735664341332, lng: -88.06646258443071 }
};

let todosLosSchedules = [];
let mapa = null;
let markerBus = null;
let markerSedes = [];
let pollingInterval = null;
let filtroOrigen = '';
let filtroDestino = '';

// Reloj en tiempo real
function iniciarReloj() {
    const actualizarReloj = () => {
        const ahora = moment();
        document.getElementById('reloj').textContent = ahora.format('HH:mm');
        const diasES = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
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
        attribution: '© OpenStreetMap'
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
        contenedor.innerHTML = `<div class="cargando text-muted"><i class="ri-calendar-close-line" style="font-size:24px;"></i><p class="mt-2">No hay horarios programados para esta selección hoy.</p></div>`;
        return;
    }

    const mapEstadoRecorrido = {
        'pendiente': { texto: 'Pendiente', clase: 'badge-pendiente' },
        'en_sede': { texto: 'En sede', clase: 'badge-en_sede' },
        'proximo_salir': { texto: 'Próximo a salir', clase: 'badge-proximo_salir' },
        'en_camino': { texto: 'En camino', clase: 'badge-en_camino' },
        'llegando': { texto: 'Llegando', clase: 'badge-llegando' },
        'completado': { texto: 'Completado', clase: 'badge-completado' }
    };

    const mapCapacidad = {
        'desconocida': { texto: 'Sin datos', clase: 'badge-pendiente' },
        'vacio': { texto: 'Asientos libres', clase: 'badge-cap-vacio' },
        'medio_lleno': { texto: 'Medio lleno', clase: 'badge-cap-medio_lleno' },
        'lleno': { texto: 'Lleno', clase: 'badge-cap-lleno' }
    };

    const html = schedulesFiltrados.map(s => {
        const est = mapEstadoRecorrido[s.estado_recorrido] || { texto: s.estado_recorrido, clase: 'badge-pendiente' };
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
            <div class="viaje-item" data-id="${s.id_cronograma}">
                <div class="viaje-item-header">
                    <div>
                        <span class="viaje-hora me-2">${horaFormato}</span>
                        <span class="viaje-ruta">${s.origen} <i class="ri-arrow-right-line" style="vertical-align: middle;"></i> ${s.destino}</span>
                    </div>
                    <div class="viaje-badges">
                        <span class="viaje-badge ${est.clase}">${est.texto}</span>
                        ${s.estado_recorrido !== 'pendiente' && s.estado_recorrido !== 'completado' ? `
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

// Actualizar Mapa y Tarjeta de ETA en Tiempo Real
function actualizarTiempoRealYTrayecto() {
    // Si hay filtros aplicados, buscaremos si hay algún viaje activo para la ruta seleccionada
    let viajeActivo = null;

    if (filtroOrigen && filtroDestino) {
        viajeActivo = todosLosSchedules.find(s => 
            s.origen_id == filtroOrigen && 
            s.destino_id == filtroDestino && 
            s.estado_recorrido !== 'pendiente' && 
            s.estado_recorrido !== 'completado' &&
            s.gps !== null
        );
    } else {
        // Si no hay filtro, tomamos el primer viaje activo en el sistema
        viajeActivo = todosLosSchedules.find(s => 
            s.estado_recorrido !== 'pendiente' && 
            s.estado_recorrido !== 'completado' &&
            s.gps !== null
        );
    }

    const etaTiempo = document.getElementById('eta-tiempo');
    const etaDesc = document.getElementById('eta-desc');
    const etaRuta = document.getElementById('eta-ruta');

    if (!mapa) {
        inicializarMapa();
    } else {
        mapa.invalidateSize();
    }

    if (viajeActivo && viajeActivo.gps) {
        // Actualizar o crear marcador de la unidad en el mapa
        const { lat, lng } = viajeActivo.gps;

        if (!markerBus) {
            markerBus = L.marker([lat, lng], {
                icon: L.divIcon({
                    className: '',
                    html: `
                        <div class="animate__animated animate__pulse animate__infinite" style="
                            background:#f5c518;
                            border:3px solid #0d2346;
                            width:22px;
                            height:22px;
                            border-radius:50%;
                            box-shadow: 0 0 10px rgba(245, 197, 24, 0.6);
                            display: flex;
                            align-items: center;
                            justify-content: center;
                        "><i class="ri-bus-2-fill" style="font-size:12px; color:#0d2346;"></i></div>
                    `
                })
            }).addTo(mapa)
            .bindPopup(`<b>${viajeActivo.unidad ? viajeActivo.unidad.nombre : 'Transporte'}</b><br>Estado: ${viajeActivo.estado_recorrido}`);
        } else {
            markerBus.setLatLng([lat, lng]);
            markerBus.getPopup().setContent(`<b>${viajeActivo.unidad ? viajeActivo.unidad.nombre : 'Transporte'}</b><br>Estado: ${viajeActivo.estado_recorrido}`);
        }

        // Auto centrar mapa en el bus
        mapa.setView([lat, lng], 14);

        // Calcular ETA
        const destCoords = sedesCoords[viajeActivo.destino_id];
        let etaTexto = '-- min';
        let descTexto = 'Calculando estimado de llegada...';

        if (destCoords) {
            const dist = calcularDistancia(lat, lng, destCoords.lat, destCoords.lng);
            
            if (viajeActivo.estado_recorrido === 'llegando') {
                etaTexto = '< 2 min';
                descTexto = 'El transporte está llegando a la sede de destino.';
            } else if (viajeActivo.estado_recorrido === 'proximo_salir') {
                etaTexto = 'Por salir';
                descTexto = 'El transporte se encuentra en la sede de origen listo para partir.';
            } else if (viajeActivo.estado_recorrido === 'en_sede') {
                etaTexto = 'En sede';
                descTexto = 'El transporte está estacionado en la sede.';
            } else {
                // Velocidad promedio de 30 km/h en ciudad
                const tiempoHoras = dist / 30;
                const tiempoMinutos = Math.round(tiempoHoras * 60);
                
                etaTexto = `${tiempoMinutos < 2 ? 2 : tiempoMinutos} min`;
                descTexto = `Tiempo estimado hacia ${viajeActivo.destino} (a ${dist.toFixed(1)} km).`;
            }
        }

        if (etaTiempo) etaTiempo.textContent = etaTexto;
        if (etaDesc) etaDesc.textContent = descTexto;
        if (etaRuta) {
            etaRuta.style.display = 'inline-flex';
            etaRuta.innerHTML = `<i class="ri-route-line"></i> ${viajeActivo.origen} a ${viajeActivo.destino}`;
        }

    } else {
        // No hay bus activo
        if (markerBus && mapa) {
            mapa.removeLayer(markerBus);
            markerBus = null;
        }

        if (etaTiempo) etaTiempo.textContent = '-- min';
        if (etaDesc) etaDesc.textContent = 'No hay unidades activas en esta ruta en este momento.';
        if (etaRuta) etaRuta.style.display = 'none';
    }
}

// Alertas de Salida 10 minutos antes
function verificarNotificacionesSalida() {
    const ahora = moment();
    
    // Obtener los IDs de viajes ya notificados hoy
    let notificadosHoy = JSON.parse(localStorage.getItem('notificados_hoy') || '[]');
    let fechaGuardada = localStorage.getItem('fecha_notificados');
    
    // Si cambió el día, limpiar el historial de notificaciones
    const hoyStr = ahora.format('YYYY-MM-DD');
    if (fechaGuardada !== hoyStr) {
        notificadosHoy = [];
        localStorage.setItem('fecha_notificados', hoyStr);
        localStorage.setItem('notificados_hoy', JSON.stringify([]));
    }

    todosLosSchedules.forEach(s => {
        // Si ya fue notificado hoy, omitir
        if (notificadosHoy.includes(s.id_cronograma)) return;

        // Si hay un filtro aplicado y el viaje actual no coincide, o no tiene asignación, omitir
        if (filtroOrigen && filtroDestino) {
            if (s.origen_id != filtroOrigen || s.destino_id != filtroDestino) return;
        }

        const horaSalida = moment(s.hora_salida, 'HH:mm:ss');
        // Calcular minutos de diferencia
        const diffMinutos = horaSalida.diff(ahora, 'minutes', true);

        // Notificar si faltan entre 0 y 10 minutos para salir
        if (diffMinutos > 0 && diffMinutos <= 10) {
            mostrarAlertaSalida(s);
            
            // Registrar como notificado
            notificadosHoy.push(s.id_cronograma);
            localStorage.setItem('notificados_hoy', JSON.stringify(notificadosHoy));
        }
    });
}

function mostrarAlertaSalida(viaje) {
    const horaFmt = moment(viaje.hora_salida, 'HH:mm:ss').format('HH:mm');
    const msg = `El transporte de ${viaje.origen} hacia ${viaje.destino} saldrá en unos minutos (a las ${horaFmt}). ¡Prepárate para abordar!`;

    // Toast en pantalla
    const container = document.getElementById('toast-container');
    if (container) {
        const toast = document.createElement('div');
        toast.className = 'toast-premium animate__animated animate__slideInRight';
        toast.innerHTML = `
            <i class="ri-notification-3-fill"></i>
            <div>
                <div class="toast-title">Próxima Salida</div>
                <div class="toast-message">${msg}</div>
            </div>
        `;
        container.appendChild(toast);
        
        // Auto eliminar después de 7 segundos
        setTimeout(() => {
            toast.classList.replace('animate__slideInRight', 'animate__fadeOutRight');
            setTimeout(() => toast.remove(), 500);
        }, 7000);
    }

    // Notificación nativa de navegador si está permitida
    if (Notification.permission === "granted") {
        new Notification("Transport-UNIVO: Próxima Salida", {
            body: msg,
            icon: '../img/logo.png'
        });
    }
}

// Solicitar permisos de notificación nativa
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
                    title: 'Ruta inválida',
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
