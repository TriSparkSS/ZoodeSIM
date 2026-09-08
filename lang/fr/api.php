<?php

return [
    'validation_failed' => 'Les données fournies sont invalides.',
    'unauthenticated' => 'Non authentifié.',

    'user' => [
        'registered' => 'Inscription réussie.',
        'logged_in' => 'Connexion réussie.',
        'logged_out' => 'Vous avez été déconnecté.',
        'profile' => 'Profil récupéré avec succès.',
    ],

    'promo' => [
        'invalid' => 'Code de parrainage invalide.',
        'inactive' => 'Ce code de parrainage est inactif.',
        'expired' => 'Ce code de parrainage a expiré.',
        'exhausted' => 'Ce code de parrainage a atteint sa limite d’utilisation.',
        'partner_inactive' => 'Ce code de parrainage n’est pas disponible.',
        'self_referral' => 'Vous ne pouvez pas utiliser votre propre code de parrainage.',
        'valid' => 'Le code de parrainage est valide.',
        'code_required' => 'Veuillez saisir un code de parrainage.',
        'device_required' => 'Un identifiant d’appareil est requis pour appliquer un code.',
        'device_invalid' => 'L’identifiant d’appareil est invalide.',
        'device_already_used' => 'Cet appareil a déjà utilisé un bonus de parrainage.',
        'ip_blocked' => 'L’inscription depuis ce réseau n’est pas autorisée.',
        'ip_limited' => 'Trop de comptes créés depuis ce réseau. Réessayez plus tard.',
    ],

    'notifications' => [
        'retrieved' => 'Notifications récupérées avec succès.',
        'marked_read' => 'Notification marquée comme lue.',
        'all_marked_read' => 'Toutes les notifications ont été marquées comme lues.',
        'not_found' => 'Notification introuvable.',
    ],

    'esim' => [
        'packages_retrieved' => 'Forfaits eSIM récupérés avec succès.',
        'unavailable' => 'Le service eSIM est temporairement indisponible.',
        'invalid_country' => 'Pays invalide.',
        'purchased' => 'eSIM acheté avec succès.',
        'order_retrieved' => 'Commande eSIM récupérée avec succès.',
        'purchase_failed' => 'Impossible d’acheter l’eSIM.',
        'package_not_found' => 'Forfait introuvable.',
        'package_unavailable' => 'Forfait indisponible.',
        'payment_required' => 'Paiement requis.',
        'payment_failed' => 'La vérification du paiement a échoué.',
        'provisioning_failed' => 'Échec de l’activation de l’eSIM.',
        'already_processed' => 'La commande a déjà été traitée.',
        'unauthorized_order' => 'Accès non autorisé à la commande.',
        'pricing_unavailable' => 'La tarification n’est actuellement pas disponible pour ce forfait.',
    ],

    'admin' => [
        'pricing' => [
            'slabs_retrieved' => 'Tranches de prix récupérées avec succès.',
            'slab_created' => 'Tranche de prix créée avec succès.',
            'slab_updated' => 'Tranche de prix mise à jour avec succès.',
            'slab_deleted' => 'Tranche de prix supprimée avec succès.',
            'preview_calculated' => 'Aperçu du prix calculé avec succès.',
        ],
    ],

    'validation' => [
        'name_required' => 'Veuillez saisir votre nom.',
        'phone_required' => 'Veuillez saisir votre numéro de téléphone.',
        'phone_unique' => 'Ce numéro de téléphone est déjà enregistré.',
        'password_min' => 'Le mot de passe doit contenir au moins 8 caractères.',
        'password_confirmed' => 'La confirmation du mot de passe ne correspond pas.',
    ],
];
