<?php

return [
    'validation_failed' => 'Les données fournies sont invalides.',
    'unauthenticated' => 'Non authentifié.',

    'user' => [
        'registered' => 'Inscription réussie.',
        'logged_in' => 'Connexion réussie.',
        'logged_out' => 'Vous avez été déconnecté.',
        'profile' => 'Profil récupéré avec succès.',
        'profile_updated' => 'Profil mis à jour avec succès.',
    ],

    'wallet' => [
        'retrieved' => 'Portefeuille récupéré avec succès.',
        'transactions_retrieved' => 'Transactions récupérées avec succès.',
        'adjusted' => 'Portefeuille mis à jour avec succès.',
        'direction_invalid' => 'Choisissez d’ajouter ou de retirer des fonds.',
        'amount_required' => 'Veuillez saisir un montant.',
        'amount_format' => 'Saisissez un montant valide avec 2 décimales maximum.',
        'insufficient_balance' => 'Votre portefeuille n’a pas assez de fonds.',
    ],

    'promo' => [
        'invalid' => 'Code de parrainage invalide.',
        'inactive' => 'Ce code de parrainage est inactif.',
        'locked' => 'Ce code de parrainage est verrouillé.',
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

    'banners' => [
        'retrieved' => 'Bannières récupérées avec succès.',
    ],

    'notifications' => [
        'retrieved' => 'Notifications récupérées avec succès.',
        'marked_read' => 'Notification marquée comme lue.',
        'all_marked_read' => 'Toutes les notifications ont été marquées comme lues.',
        'not_found' => 'Notification introuvable.',
    ],

    'esim' => [
        'packages_retrieved' => 'Forfaits eSIM récupérés avec succès.',
        'countries_retrieved' => 'Pays récupérés avec succès.',
        'unavailable' => 'Le service eSIM est temporairement indisponible.',
        'invalid_country' => 'Pays invalide.',
        'purchased' => 'eSIM acheté avec succès.',
        'order_retrieved' => 'Commande eSIM récupérée avec succès.',
        'orders_retrieved' => 'Commandes eSIM récupérées avec succès.',
        'purchase_failed' => 'Impossible d’acheter l’eSIM.',
        'package_not_found' => 'Forfait introuvable.',
        'package_unavailable' => 'Forfait indisponible.',
        'payment_required' => 'Paiement requis.',
        'payment_failed' => 'La vérification du paiement a échoué.',
        'insufficient_balance' => 'Solde du portefeuille insuffisant pour acheter ce forfait.',
        'provisioning_failed' => 'Échec de l’activation de l’eSIM.',
        'already_processed' => 'La commande a déjà été traitée.',
        'unauthorized_order' => 'Accès non autorisé à la commande.',
        'pricing_unavailable' => 'La tarification n’est actuellement pas disponible pour ce forfait.',
    ],

    'validation' => [
        'name_required' => 'Veuillez saisir votre nom.',
        'phone_required' => 'Veuillez saisir votre numéro de téléphone.',
        'phone_unique' => 'Ce numéro de téléphone est déjà enregistré.',
        'email_unique' => 'Cet e-mail est déjà enregistré.',
        'password_min' => 'Le mot de passe doit contenir au moins 8 caractères.',
        'password_confirmed' => 'La confirmation du mot de passe ne correspond pas.',
        'current_password_required' => 'Veuillez saisir votre mot de passe actuel.',
        'current_password_invalid' => 'Le mot de passe actuel est incorrect.',
    ],
];
