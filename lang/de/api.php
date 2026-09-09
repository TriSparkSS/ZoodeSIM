<?php

return [
    'validation_failed' => 'Die angegebenen Daten sind ungültig.',
    'unauthenticated' => 'Nicht authentifiziert.',

    'user' => [
        'registered' => 'Registrierung erfolgreich.',
        'logged_in' => 'Anmeldung erfolgreich.',
        'logged_out' => 'Du wurdest abgemeldet.',
        'profile' => 'Profil erfolgreich abgerufen.',
    ],

    'promo' => [
        'invalid' => 'Ungültiger Empfehlungscode.',
        'inactive' => 'Dieser Empfehlungscode ist inaktiv.',
        'expired' => 'Dieser Empfehlungscode ist abgelaufen.',
        'exhausted' => 'Dieser Empfehlungscode hat das Nutzungslimit erreicht.',
        'partner_inactive' => 'Dieser Empfehlungscode ist nicht verfügbar.',
        'self_referral' => 'Du kannst deinen eigenen Empfehlungscode nicht verwenden.',
        'valid' => 'Empfehlungscode ist gültig.',
        'code_required' => 'Bitte gib einen Empfehlungscode ein.',
        'device_required' => 'Für einen Empfehlungscode ist eine Geräte-ID erforderlich.',
        'device_invalid' => 'Die Geräte-ID ist ungültig.',
        'device_already_used' => 'Dieses Gerät hat bereits einen Empfehlungsbonus genutzt.',
        'ip_blocked' => 'Registrierungen aus diesem Netzwerk sind nicht erlaubt.',
        'ip_limited' => 'Zu viele Konten aus diesem Netzwerk. Bitte später erneut versuchen.',
    ],

    'notifications' => [
        'retrieved' => 'Benachrichtigungen erfolgreich abgerufen.',
        'marked_read' => 'Benachrichtigung als gelesen markiert.',
        'all_marked_read' => 'Alle Benachrichtigungen als gelesen markiert.',
        'not_found' => 'Benachrichtigung nicht gefunden.',
    ],

    'esim' => [
        'packages_retrieved' => 'eSIM-Pakete erfolgreich abgerufen.',
        'countries_retrieved' => 'Länder erfolgreich abgerufen.',
        'unavailable' => 'Der eSIM-Dienst ist vorübergehend nicht verfügbar.',
        'invalid_country' => 'Ungültiges Land.',
        'purchased' => 'eSIM erfolgreich gekauft.',
        'order_retrieved' => 'eSIM-Bestellung erfolgreich abgerufen.',
        'purchase_failed' => 'eSIM konnte nicht gekauft werden.',
        'package_not_found' => 'Paket nicht gefunden.',
        'package_unavailable' => 'Paket nicht verfügbar.',
        'payment_required' => 'Zahlung erforderlich.',
        'payment_failed' => 'Zahlungsprüfung fehlgeschlagen.',
        'provisioning_failed' => 'eSIM-Bereitstellung fehlgeschlagen.',
        'already_processed' => 'Die Bestellung wurde bereits verarbeitet.',
        'unauthorized_order' => 'Unbefugter Zugriff auf die Bestellung.',
        'pricing_unavailable' => 'Die Preisgestaltung ist für dieses Paket derzeit nicht verfügbar.',
    ],

    'admin' => [
        'pricing' => [
            'slabs_retrieved' => 'Preisstufen erfolgreich abgerufen.',
            'slab_created' => 'Preisstufe erfolgreich erstellt.',
            'slab_updated' => 'Preisstufe erfolgreich aktualisiert.',
            'slab_deleted' => 'Preisstufe erfolgreich gelöscht.',
            'preview_calculated' => 'Preisvorschau erfolgreich berechnet.',
        ],
    ],

    'validation' => [
        'name_required' => 'Bitte gib deinen Namen ein.',
        'phone_required' => 'Bitte gib deine Telefonnummer ein.',
        'phone_unique' => 'Diese Telefonnummer ist bereits registriert.',
        'password_min' => 'Das Passwort muss mindestens 8 Zeichen haben.',
        'password_confirmed' => 'Die Passwortbestätigung stimmt nicht überein.',
    ],
];
