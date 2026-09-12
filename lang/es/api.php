<?php

return [
    'validation_failed' => 'Los datos enviados no son válidos.',
    'unauthenticated' => 'No autenticado.',

    'user' => [
        'registered' => 'Registro exitoso.',
        'logged_in' => 'Inicio de sesión exitoso.',
        'logged_out' => 'Has cerrado sesión.',
        'profile' => 'Perfil obtenido correctamente.',
    ],

    'wallet' => [
        'retrieved' => 'Billetera obtenida correctamente.',
        'transactions_retrieved' => 'Transacciones obtenidas correctamente.',
        'adjusted' => 'Billetera actualizada correctamente.',
        'direction_invalid' => 'Elige si añadir o descontar fondos.',
        'amount_required' => 'Introduce un importe.',
        'amount_format' => 'Introduce un importe válido con hasta 2 decimales.',
        'insufficient_balance' => 'Tu billetera no tiene fondos suficientes.',
    ],

    'promo' => [
        'invalid' => 'Código de referido no válido.',
        'inactive' => 'Este código de referido está inactivo.',
        'locked' => 'Este código de referido está bloqueado.',
        'expired' => 'Este código de referido ha caducado.',
        'exhausted' => 'Este código de referido ha alcanzado su límite de uso.',
        'partner_inactive' => 'Este código de referido no está disponible.',
        'self_referral' => 'No puedes usar tu propio código de referido.',
        'valid' => 'El código de referido es válido.',
        'code_required' => 'Introduce un código de referido.',
        'device_required' => 'Se requiere un identificador de dispositivo para aplicar un código.',
        'device_invalid' => 'El identificador de dispositivo no es válido.',
        'device_already_used' => 'Este dispositivo ya usó un bono de referido.',
        'ip_blocked' => 'El registro desde esta red no está permitido.',
        'ip_limited' => 'Demasiadas cuentas creadas desde esta red. Inténtalo más tarde.',
    ],

    'notifications' => [
        'retrieved' => 'Notificaciones obtenidas correctamente.',
        'marked_read' => 'Notificación marcada como leída.',
        'all_marked_read' => 'Todas las notificaciones marcadas como leídas.',
        'not_found' => 'Notificación no encontrada.',
    ],

    'esim' => [
        'packages_retrieved' => 'Paquetes eSIM obtenidos correctamente.',
        'countries_retrieved' => 'Países obtenidos correctamente.',
        'unavailable' => 'El servicio eSIM no está disponible temporalmente.',
        'invalid_country' => 'País no válido.',
        'purchased' => 'eSIM comprado correctamente.',
        'order_retrieved' => 'Pedido eSIM obtenido correctamente.',
        'orders_retrieved' => 'Pedidos eSIM obtenidos correctamente.',
        'purchase_failed' => 'No se pudo comprar el eSIM.',
        'package_not_found' => 'Paquete no encontrado.',
        'package_unavailable' => 'Paquete no disponible.',
        'payment_required' => 'Pago requerido.',
        'payment_failed' => 'La verificación del pago falló.',
        'insufficient_balance' => 'Saldo de billetera insuficiente para comprar este plan.',
        'provisioning_failed' => 'Error al provisionar el eSIM.',
        'already_processed' => 'El pedido ya fue procesado.',
        'unauthorized_order' => 'Acceso no autorizado al pedido.',
        'pricing_unavailable' => 'Los precios no están disponibles actualmente para este paquete.',
    ],

    'validation' => [
        'name_required' => 'Introduce tu nombre.',
        'phone_required' => 'Introduce tu número de teléfono.',
        'phone_unique' => 'Este número de teléfono ya está registrado.',
        'password_min' => 'La contraseña debe tener al menos 8 caracteres.',
        'password_confirmed' => 'La confirmación de la contraseña no coincide.',
    ],
];
