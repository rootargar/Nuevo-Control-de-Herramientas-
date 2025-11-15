/**
 * Sistema de Control de Herramientas de Taller
 * JavaScript - Validaciones y Funciones
 */

// Funciones de validación
function validarCampoVacio(valor, nombreCampo) {
    if (!valor || valor.trim() === '') {
        mostrarAlerta(`El campo ${nombreCampo} es obligatorio`, 'error');
        return false;
    }
    return true;
}

function validarNumeroPositivo(valor, nombreCampo) {
    if (isNaN(valor) || valor < 0) {
        mostrarAlerta(`El campo ${nombreCampo} debe ser un número positivo`, 'error');
        return false;
    }
    return true;
}

function validarEmail(email) {
    const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!regex.test(email)) {
        mostrarAlerta('El email no tiene un formato válido', 'error');
        return false;
    }
    return true;
}

// Función para mostrar alertas
function mostrarAlerta(mensaje, tipo = 'info') {
    const alertaDiv = document.createElement('div');
    alertaDiv.className = `alert alert-${tipo}`;
    alertaDiv.textContent = mensaje;

    const container = document.querySelector('.container');
    if (container) {
        container.insertBefore(alertaDiv, container.firstChild);

        // Auto-ocultar después de 5 segundos
        setTimeout(() => {
            alertaDiv.style.animation = 'fadeIn 0.3s reverse';
            setTimeout(() => alertaDiv.remove(), 300);
        }, 5000);

        // Scroll al inicio
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
}

// Función para confirmar eliminación
function confirmarEliminacion(mensaje = '¿Está seguro de que desea eliminar este registro?') {
    return confirm(mensaje);
}

// Función para formatear fecha
function formatearFecha(fecha) {
    if (!fecha) return '';
    const date = new Date(fecha);
    const dia = String(date.getDate()).padStart(2, '0');
    const mes = String(date.getMonth() + 1).padStart(2, '0');
    const anio = date.getFullYear();
    return `${dia}/${mes}/${anio}`;
}

// Función para validar stock disponible
function validarStockDisponible(cantidadSolicitada, stockDisponible) {
    if (parseInt(cantidadSolicitada) > parseInt(stockDisponible)) {
        mostrarAlerta('La cantidad solicitada excede el stock disponible', 'error');
        return false;
    }
    return true;
}

// Modal functions
function abrirModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'block';
    }
}

function cerrarModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'none';
    }
}

// Cerrar modal al hacer clic fuera
window.onclick = function(event) {
    if (event.target.className === 'modal') {
        event.target.style.display = 'none';
    }
}

// Función para filtrar tablas
function filtrarTabla(inputId, tablaId) {
    const input = document.getElementById(inputId);
    const tabla = document.getElementById(tablaId);

    if (!input || !tabla) return;

    input.addEventListener('keyup', function() {
        const filtro = this.value.toLowerCase();
        const filas = tabla.getElementsByTagName('tr');

        for (let i = 1; i < filas.length; i++) {
            const fila = filas[i];
            const textoFila = fila.textContent.toLowerCase();

            if (textoFila.includes(filtro)) {
                fila.style.display = '';
            } else {
                fila.style.display = 'none';
            }
        }
    });
}

// Función para exportar tabla a CSV
function exportarCSV(tablaId, nombreArchivo) {
    const tabla = document.getElementById(tablaId);
    if (!tabla) {
        mostrarAlerta('No se encontró la tabla para exportar', 'error');
        return;
    }

    let csv = [];
    const filas = tabla.querySelectorAll('tr');

    for (let i = 0; i < filas.length; i++) {
        const fila = [];
        const columnas = filas[i].querySelectorAll('td, th');

        for (let j = 0; j < columnas.length - 1; j++) { // -1 para excluir columna de acciones
            fila.push(columnas[j].textContent.trim());
        }

        csv.push(fila.join(','));
    }

    descargarCSV(csv.join('\n'), nombreArchivo);
}

// Función para descargar CSV
function descargarCSV(contenido, nombreArchivo) {
    const blob = new Blob(['\ufeff' + contenido], { type: 'text/csv;charset=utf-8;' });
    const enlace = document.createElement('a');
    const url = URL.createObjectURL(blob);

    enlace.setAttribute('href', url);
    enlace.setAttribute('download', nombreArchivo + '.csv');
    enlace.style.visibility = 'hidden';

    document.body.appendChild(enlace);
    enlace.click();
    document.body.removeChild(enlace);
}

// Validación de formularios
function validarFormularioHerramienta(form) {
    const nombre = form.querySelector('[name="nombre"]').value;
    const existenciaTotal = form.querySelector('[name="existencia_total"]').value;
    const existenciaDisponible = form.querySelector('[name="existencia_disponible"]').value;

    if (!validarCampoVacio(nombre, 'Nombre')) return false;
    if (!validarNumeroPositivo(existenciaTotal, 'Existencia Total')) return false;
    if (!validarNumeroPositivo(existenciaDisponible, 'Existencia Disponible')) return false;

    if (parseInt(existenciaDisponible) > parseInt(existenciaTotal)) {
        mostrarAlerta('La existencia disponible no puede ser mayor que la existencia total', 'error');
        return false;
    }

    return true;
}

function validarFormularioTecnico(form) {
    const nombre = form.querySelector('[name="nombre"]').value;
    const apellido = form.querySelector('[name="apellido"]').value;
    const email = form.querySelector('[name="email"]');

    if (!validarCampoVacio(nombre, 'Nombre')) return false;
    if (!validarCampoVacio(apellido, 'Apellido')) return false;

    if (email && email.value && !validarEmail(email.value)) return false;

    return true;
}

function validarFormularioPrestamo(form) {
    const idHerramienta = form.querySelector('[name="id_herramienta"]').value;
    const idTecnico = form.querySelector('[name="id_tecnico"]').value;
    const cantidad = form.querySelector('[name="cantidad"]').value;

    if (!validarCampoVacio(idHerramienta, 'Herramienta')) return false;
    if (!validarCampoVacio(idTecnico, 'Técnico')) return false;
    if (!validarNumeroPositivo(cantidad, 'Cantidad')) return false;

    return true;
}

// Función para actualizar stock disponible al seleccionar herramienta
function actualizarStockDisponible(selectHerramienta, spanStock) {
    const select = document.getElementById(selectHerramienta);
    const span = document.getElementById(spanStock);

    if (!select || !span) return;

    select.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        const stock = selectedOption.getAttribute('data-stock');

        if (stock) {
            span.textContent = `Stock disponible: ${stock}`;
            span.style.color = parseInt(stock) > 0 ? '#27ae60' : '#e74c3c';
        } else {
            span.textContent = '';
        }
    });
}

// Función para calcular total en cajas
function calcularTotalCaja() {
    const tabla = document.querySelector('#tablaHerramientasCaja tbody');
    if (!tabla) return;

    const filas = tabla.querySelectorAll('tr');
    let total = 0;

    filas.forEach(fila => {
        const cantidad = fila.querySelector('input[type="number"]');
        if (cantidad) {
            total += parseInt(cantidad.value) || 0;
        }
    });

    const spanTotal = document.getElementById('totalHerramientas');
    if (spanTotal) {
        spanTotal.textContent = total;
    }
}

// Función para agregar fila de herramienta a caja
function agregarHerramientaCaja(selectHerramientas) {
    const select = document.getElementById(selectHerramientas);
    if (!select || select.value === '') return;

    const selectedOption = select.options[select.selectedIndex];
    const idHerramienta = select.value;
    const nombreHerramienta = selectedOption.text;
    const stockDisponible = selectedOption.getAttribute('data-stock');

    const tabla = document.querySelector('#tablaHerramientasCaja tbody');
    if (!tabla) return;

    // Verificar si la herramienta ya está en la tabla
    const filaExistente = tabla.querySelector(`tr[data-herramienta="${idHerramienta}"]`);
    if (filaExistente) {
        mostrarAlerta('Esta herramienta ya está en la caja', 'warning');
        return;
    }

    // Crear nueva fila
    const nuevaFila = tabla.insertRow();
    nuevaFila.setAttribute('data-herramienta', idHerramienta);
    nuevaFila.innerHTML = `
        <td>${nombreHerramienta}</td>
        <td>
            <input type="number" name="cantidades[${idHerramienta}]"
                   class="cantidad-herramienta" min="1" max="${stockDisponible}"
                   value="1" required style="width: 80px; padding: 0.5rem;">
            <input type="hidden" name="herramientas[]" value="${idHerramienta}">
        </td>
        <td>${stockDisponible}</td>
        <td>
            <button type="button" class="btn btn-danger btn-sm" onclick="eliminarFilaCaja(this)">
                Eliminar
            </button>
        </td>
    `;

    // Resetear select
    select.value = '';

    // Actualizar total
    calcularTotalCaja();
}

// Función para eliminar fila de caja
function eliminarFilaCaja(boton) {
    const fila = boton.closest('tr');
    if (fila) {
        fila.remove();
        calcularTotalCaja();
    }
}

// Función de carga de página
document.addEventListener('DOMContentLoaded', function() {
    // Agregar navegación activa
    const currentPage = window.location.pathname.split('/').pop() || 'index.php';
    const navLinks = document.querySelectorAll('nav a');

    navLinks.forEach(link => {
        if (link.getAttribute('href') === currentPage) {
            link.classList.add('active');
        }
    });

    // Auto-cerrar alertas existentes
    const alertas = document.querySelectorAll('.alert');
    alertas.forEach(alerta => {
        setTimeout(() => {
            alerta.style.animation = 'fadeIn 0.3s reverse';
            setTimeout(() => alerta.remove(), 300);
        }, 5000);
    });
});

// Función para refrescar página con mensaje
function refrescarConMensaje(mensaje, tipo = 'success') {
    sessionStorage.setItem('mensaje', mensaje);
    sessionStorage.setItem('tipoMensaje', tipo);
    location.reload();
}

// Mostrar mensaje almacenado en sessionStorage
window.addEventListener('load', function() {
    const mensaje = sessionStorage.getItem('mensaje');
    const tipo = sessionStorage.getItem('tipoMensaje');

    if (mensaje) {
        mostrarAlerta(mensaje, tipo || 'info');
        sessionStorage.removeItem('mensaje');
        sessionStorage.removeItem('tipoMensaje');
    }
});

// Prevenir envío duplicado de formularios
let formularioEnviado = false;

function prevenirEnvioDoble(form) {
    if (formularioEnviado) {
        mostrarAlerta('Procesando solicitud, por favor espere...', 'info');
        return false;
    }

    formularioEnviado = true;

    // Resetear después de 5 segundos
    setTimeout(() => {
        formularioEnviado = false;
    }, 5000);

    return true;
}

// Función para imprimir
function imprimirReporte() {
    window.print();
}

// Estilos de impresión
const estiloImpresion = document.createElement('style');
estiloImpresion.textContent = `
    @media print {
        nav, .btn, .no-print {
            display: none !important;
        }

        body {
            background: white;
        }

        .card {
            box-shadow: none;
            border: 1px solid #ddd;
        }
    }
`;
document.head.appendChild(estiloImpresion);
