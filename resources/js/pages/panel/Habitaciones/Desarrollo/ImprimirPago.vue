<template>
    <Dialog 
        v-model:visible="dialogVisible" 
        modal 
        header="Vista previa del comprobante" 
        :style="{ width: '50vw' }"
        @hide="handleClose">
        
        <div v-if="loading" class="flex justify-center p-4">
            <i class="pi pi-spin pi-spinner" style="font-size: 2rem"></i>
        </div>
        
        <iframe v-else :src="localPdfUrl" width="100%" height="600px"></iframe>
    </Dialog>
</template>

<script setup>
import { ref, watch, onMounted } from 'vue';
import Dialog from 'primevue/dialog';
import axios from 'axios';
import jsPDF from 'jspdf';

const props = defineProps({
    pagoId: {
        type: String,
        default: null
    },
    modelValue: {
        type: Boolean,
        default: false
    }
});

const emit = defineEmits(['update:modelValue']);

const dialogVisible = ref(props.modelValue);
const loading = ref(true);
const localPdfUrl = ref('');

watch(() => props.modelValue, (newValue) => {
    dialogVisible.value = newValue;
    if (newValue && props.pagoId) {
        generatePDF();
    }
});

watch(() => dialogVisible.value, (newValue) => {
    emit('update:modelValue', newValue);
    if (!newValue) {
        limpiarPDF();
    }
});

const handleClose = () => {
    dialogVisible.value = false;
    emit('update:modelValue', false);
    limpiarPDF();
};

const limpiarPDF = () => {
    if (localPdfUrl.value) {
        URL.revokeObjectURL(localPdfUrl.value);
        localPdfUrl.value = '';
    }
};

const formatTime = (datetime) => {
    if (!datetime) return '';
    const parts = datetime.split(' ');
    return parts.length >= 2 ? `${parts[1]} ${parts[2] || ''}`.trim() : datetime;
};

const formatDate = (datetime) => {
    if (!datetime) return '';
    const parts = datetime.split(' ');
    return parts[0] || datetime;
};

const generatePDF = async () => {
    loading.value = true;
    try {
        // Obtener datos del pago
        const response = await axios.get(`/reporte-pagos/${props.pagoId}`);
        const data = response.data.data || response.data;

        const p = data;
        const booking = p.booking;

        // Crear PDF tipo ticket (82mm de ancho)
        const pdf = new jsPDF({
            unit: 'mm',
            format: [82, 297] // Ancho 82mm, alto ajustable
        });

        let y = 5;
        const lineHeight = 4;
        const margin = 5;

        // Fecha y hora actual
        const now = new Date();
        const fechaActual = now.toLocaleDateString('es-PE');
        const horaActual = now.toLocaleTimeString('es-PE');

        // Fecha y hora en la esquina
        pdf.setFontSize(7);
        pdf.setFont('helvetica', 'normal');
        pdf.text(`Fecha y Hora: ${fechaActual} ${horaActual}`, 20, y);
        y += 6;

        // Logo (si tienes uno, sino comentar estas líneas)
        // const baseUrl = window.location.origin;
        // const logoUrl = `${baseUrl}/images/logo.png`;
        // const logoBase64 = await fetch(logoUrl)
        //     .then((res) => res.blob())
        //     .then((blob) => {
        //         return new Promise((resolve) => {
        //             const reader = new FileReader();
        //             reader.onloadend = () => resolve(reader.result);
        //             reader.readAsDataURL(blob);
        //         });
        //     });
        // pdf.addImage(logoBase64, 'PNG', 12, y, 55, 20);
        // y += 24;

        // Encabezado de la empresa
        pdf.setFontSize(10);
        pdf.setFont('helvetica', 'bold');
        pdf.text('SISTEMA DE HOSPEDAJE', 41, y, { align: 'center' });
        y += 5;
        
        pdf.setFontSize(8);
        pdf.setFont('helvetica', 'normal');
        pdf.text('DIRECCIÓN DE TU EMPRESA', 41, y, { align: 'center' });
        y += 4;
        pdf.text('CIUDAD - REGIÓN', 41, y, { align: 'center' });
        y += 4;
        
        pdf.setFont('helvetica', 'bold');
        pdf.text('RUC: 20XXXXXXXXX', 41, y, { align: 'center' });
        y += 4;
        
        pdf.setFont('helvetica', 'normal');
        pdf.text(`${booking?.room?.sub_branch?.name || 'SUCURSAL'}`, 41, y, { align: 'center' });
        y += 6;

        // Línea y tipo de comprobante
        pdf.line(margin, y, 77, y);
        y += 4;
        
        pdf.setFont('helvetica', 'bold');
        pdf.setFontSize(10);
        pdf.text('COMPROBANTE DE PAGO', 41, y, { align: 'center' });
        y += 4;
        
        pdf.setFontSize(9);
        pdf.text(p.booking?.booking_code || 'N/A', 41, y, { align: 'center' });
        y += 4;
        
        pdf.line(margin, y, 77, y);
        y += 6;

        // Datos del pago
        pdf.setFont('helvetica', 'normal');
        pdf.setFontSize(7.5);
        pdf.text(`Cajero: ${p.created_by}`, margin, y);
        y += lineHeight;
        pdf.text(`Fecha: ${formatDate(p.payment_date)}`, margin, y);
        pdf.text(`Hora: ${formatTime(p.payment_date)}`, 50, y);
        y += lineHeight;
        pdf.text(`Estado: ${p.status_label}`, margin, y);
        y += lineHeight + 2;

        // Datos del cliente
        pdf.setFont('helvetica', 'bold');
        pdf.text('Datos del cliente:', margin, y);
        y += lineHeight;
        
        pdf.setFont('helvetica', 'normal');
        pdf.text(`Nombre: ${booking?.customer?.name || 'Sin registrar'}`, margin, y);
        y += lineHeight;
        pdf.text(`Documento: ${booking?.customer?.document || 'N/A'}`, margin, y);
        y += lineHeight;
        
        if (booking?.customer?.phone) {
            pdf.text(`Teléfono: ${booking.customer.phone}`, margin, y);
            y += lineHeight;
        }
        y += 2;

        // Datos de la reserva
        pdf.setFont('helvetica', 'bold');
        pdf.text('Datos de la reserva:', margin, y);
        y += lineHeight;
        
        pdf.setFont('helvetica', 'normal');
        pdf.text(`Código: ${booking?.booking_code || 'N/A'}`, margin, y);
        y += lineHeight;
        pdf.text(`Habitación: ${booking?.room?.number || 'N/A'}`, margin, y);
        y += lineHeight;
        pdf.text(`Estado: ${booking?.status_label || 'N/A'}`, margin, y);
        y += lineHeight + 2;

        // Horarios
        pdf.setFont('helvetica', 'bold');
        pdf.text('Horarios:', margin, y);
        y += lineHeight;
        
        pdf.setFont('helvetica', 'normal');
        pdf.text(`Check-in: ${formatDate(booking?.check_in)} ${formatTime(booking?.check_in)}`, margin, y);
        y += lineHeight;
        
        if (booking?.check_out) {
            pdf.text(`Check-out: ${formatDate(booking?.check_out)} ${formatTime(booking?.check_out)}`, margin, y);
            y += lineHeight;
        }
        
        pdf.text(`Duración: ${booking?.total_hours || 0} horas`, margin, y);
        y += lineHeight + 4;

        // Encabezado de servicios
        pdf.setFontSize(7.5);
        pdf.setFont('helvetica', 'bold');
        pdf.text('Servicio', margin, y);
        pdf.text('Precio', 50, y, { align: 'right' });
        pdf.text('Total', 75, y, { align: 'right' });
        y += 2;
        pdf.line(margin, y, 77, y);
        y += 4;

        // Habitación
        pdf.setFont('helvetica', 'normal');
        pdf.text('HABITACIÓN', margin, y);
        y += lineHeight;
        pdf.text(`${booking?.room?.number || 'N/A'} - ${booking?.total_hours || 0} hrs`, margin + 2, y);
        pdf.text(`S/. ${(booking?.rate_per_unit || 0).toFixed(2)}`, 50, y, { align: 'right' });
        pdf.text(`S/. ${(booking?.room_subtotal || 0).toFixed(2)}`, 75, y, { align: 'right' });
        y += lineHeight + 2;

        // Consumos (si hay)
        if (booking?.consumptions && booking.consumptions.length > 0) {
            pdf.setFont('helvetica', 'bold');
            pdf.text('CONSUMOS:', margin, y);
            y += lineHeight;
            
            pdf.setFont('helvetica', 'normal');
            booking.consumptions.forEach(consumo => {
                const nombreProducto = pdf.splitTextToSize(consumo.product, 28);
                
                nombreProducto.forEach((linea, index) => {
                    pdf.text(linea, margin + 2, y);
                    
                    if (index === 0) {
                        pdf.text(`S/. ${consumo.unit_price.toFixed(2)}`, 50, y, { align: 'right' });
                    }
                    
                    y += lineHeight;
                });
                
                pdf.text(`${consumo.quantity} und.`, margin + 4, y);
                pdf.text(`S/. ${consumo.total_price.toFixed(2)}`, 75, y, { align: 'right' });
                y += lineHeight + 1;
            });
            y += 1;
        }

        pdf.line(margin, y, 77, y);
        y += 4;

        // Resumen de totales
        pdf.setFont('helvetica', 'bold');
        
        if (booking?.products_subtotal > 0) {
            pdf.text(`Subtotal Habitación: S/. ${(booking.room_subtotal || 0).toFixed(2)}`, 75, y, { align: 'right' });
            y += lineHeight;
            pdf.text(`Subtotal Productos: S/. ${(booking.products_subtotal || 0).toFixed(2)}`, 75, y, { align: 'right' });
            y += lineHeight;
        }
        
        pdf.text(`Subtotal: S/. ${(booking?.subtotal || 0).toFixed(2)}`, 75, y, { align: 'right' });
        y += lineHeight;

        if (booking?.discount_amount > 0) {
            pdf.text(`Descuento: S/. ${booking.discount_amount.toFixed(2)}`, 75, y, { align: 'right' });
            y += lineHeight;
        }

        if (booking?.tax_amount > 0) {
            pdf.text(`IGV (18%): S/. ${booking.tax_amount.toFixed(2)}`, 75, y, { align: 'right' });
            y += lineHeight;
        }

        pdf.setFontSize(9);
        pdf.text(`TOTAL: S/. ${(booking?.total_amount || 0).toFixed(2)}`, 75, y, { align: 'right' });
        y += lineHeight + 2;

        // Línea
        pdf.line(margin, y, 77, y);
        y += 4;

        // Estado de pagos
        pdf.setFontSize(7.5);
        pdf.setFont('helvetica', 'normal');
        pdf.text(`Total Pagado: S/. ${(booking?.paid_amount || 0).toFixed(2)}`, 75, y, { align: 'right' });
        y += lineHeight;
        pdf.text(`Saldo Pendiente: S/. ${(booking?.balance || 0).toFixed(2)}`, 75, y, { align: 'right' });
        y += lineHeight;
        
        pdf.setFont('helvetica', 'bold');
        pdf.text(`Este Pago: S/. ${(p.amount || 0).toFixed(2)}`, 75, y, { align: 'right' });
        y += lineHeight + 2;

        // Línea
        pdf.line(margin, y, 77, y);
        y += 4;

        // Método de pago
        pdf.setFont('helvetica', 'normal');
        pdf.text(`Método de Pago: ${p.payment_method?.name || 'N/A'}`, margin, y);
        y += lineHeight;
        pdf.text(`Moneda: ${p.currency?.code || 'N/A'} - ${p.currency?.name || 'N/A'}`, margin, y);
        y += lineHeight;
        pdf.text(`Caja: ${p.cash_register?.name || 'N/A'}`, margin, y);
        y += lineHeight;
        
        if (p.operation_number) {
            pdf.text(`Nro. Operación: ${p.operation_number}`, margin, y);
            y += lineHeight;
        }
        
        if (p.reference) {
            pdf.text(`Referencia: ${p.reference}`, margin, y);
            y += lineHeight;
        }
        y += 2;
        const hasPaymentNotes = data.notas && data.notas.trim();
        const hasBookingNotes = data.notes_habitacion && data.notes_habitacion.trim();

        if (hasPaymentNotes || hasBookingNotes) {
            // Verificar espacio antes de empezar la sección
            if (y > 250) {
                pdf.addPage();
                y = 5;
            }
            
            pdf.line(margin, y, 77, y);
            y += 4;
            
            pdf.setFont('helvetica', 'bold');
            pdf.setFontSize(7.5);
            pdf.text('NOTAS:', margin, y);
            y += lineHeight;
            
            pdf.setFontSize(6.5);
            
            // Notas del pago
            if (hasPaymentNotes) {
                pdf.setFont('helvetica', 'bold');
                pdf.text('• Pago:', margin, y);
                y += lineHeight;
                
                pdf.setFont('helvetica', 'normal');
                const paymentLines = data.notas.trim().split('\n');
                
                paymentLines.forEach(linea => {
                    if (linea.trim()) {
                        const lines = pdf.splitTextToSize(linea.trim(), 65);
                        lines.forEach(line => {
                            if (y > 285) {
                                pdf.addPage();
                                y = 5;
                            }
                            pdf.text(line, margin + 2, y);
                            y += lineHeight;
                        });
                    }
                });
                y += 2;
            }
            
            // Notas de la reserva
            if (hasBookingNotes) {
                if (y > 280) {
                    pdf.addPage();
                    y = 5;
                }
                
                pdf.setFont('helvetica', 'bold');
                pdf.text('• Reserva:', margin, y);
                y += lineHeight;
                
                pdf.setFont('helvetica', 'normal');
                const bookingLines = data.notes_habitacion.trim().split('\n');
                
                bookingLines.forEach(linea => {
                    if (linea.trim()) {
                        const lines = pdf.splitTextToSize(linea.trim(), 65);
                        lines.forEach(line => {
                            if (y > 285) {
                                pdf.addPage();
                                y = 5;
                            }
                            pdf.text(line, margin + 2, y);
                            y += lineHeight;
                        });
                    }
                });
                y += 2;
            }
            
            pdf.setFontSize(7.5);
            y += 2;
        }

        // Línea final
        pdf.line(margin, y, 77, y);
        y += 4;

        // Footer
        pdf.setFont('helvetica', 'bold');
        pdf.setFontSize(8);
        pdf.text('¡GRACIAS POR SU PREFERENCIA!', 41, y, { align: 'center' });
        y += lineHeight + 2;
        
        pdf.setFont('helvetica', 'normal');
        pdf.setFontSize(6);
        pdf.text(`Registrado: ${p.created_at}`, 41, y, { align: 'center' });
        y += lineHeight;
        pdf.text('Sistema de Gestión Hotelera', 41, y, { align: 'center' });

        // Convertir a Blob URL
        const pdfBlob = pdf.output('blob');
        localPdfUrl.value = URL.createObjectURL(pdfBlob);

    } catch (error) {
        console.error('Error al generar PDF:', error);
    } finally {
        loading.value = false;
    }
};

onMounted(() => {
    if (props.pagoId && props.modelValue) {
        generatePDF();
    }
});

watch(() => props.pagoId, (newId) => {
    if (newId && dialogVisible.value) {
        generatePDF();
    }
});
</script>