<?php

// Set the following for your MySQL installation
// Utilisez ces informations pour configurer votre instance de BD (MySQL)
// Copy or rename this file to .htconfig.php
// Copier ou renomer ce fichier .htconfig.php et placer le à la racine de l'installation de la Matrice Rouge.

$db_host = '{{$dbhost}}';
$db_port = '{{$dbport}}';
$db_user = '{{$dbuser}}';
$db_pass = '{{$dbpass}}';
$db_data = '{{$dbdata}}';

/*
 * Note: Plusieurs de ces réglages seront disponibles via le panneau d'administration
 * après l'installation. Lorsque des modifications sont apportés à travers le panneau d'administration
 * elle sont automatiquement enregistrées dans la base de données.
 * Les configurations inscrites dans la BD prévalent sur celles de ce fichier de configuration.
 *
 * En cas de difficultés d'accès au panneau d'administration, nous mettons à votre disposition,
 * un outil en ligne de commande est disponible [util/config] pour rechercher et apporter des modifications
 * sur les entrées dans la BD.
 *
 */ 

// Choisissez votre emplacement géographique. Si vous n'êtes pas certain, utilisez "America/Los_Angeles".
// Vous pourrez le changer plus tard et ce réglage n'affecte que les visiteurs anonymes.

$default_timezone = '{{$timezone}}';

// Quel sera le nom de votre site?

$a->config['system']['baseurl'] = '{{$siteurl}}';
$a->config['system']['sitename'] = "Matrice Rouge";
$a->config['system']['location_hash'] = '{{$site_id}}';

// Vos choix sont REGISTER_OPEN, REGISTER_APPROVE, ou REGISTER_CLOSED.
// Soyez certains de créer votre compte personnel avant de déclarer
// votre site REGISTER_CLOSED. 'register_text' (si vous décider de l'utiliser) 
// renvois son contenu systématiquement sur la page d'enregistrement des nouveaux membres.
// REGISTER_APPROVE requiert la configuration de 'admin_email' avec l'adresse de courriel
// d'un membre déjà inscrit qui pourra autoriser et/ou approuver/supprimer la demande.

$a->config['system']['register_policy'] = REGISTER_OPEN;
$a->config['system']['register_text'] = '';
$a->config['system']['admin_email'] = '{{$adminmail}}';

// taille maximale pour l'importation d'un message, 0 est illimité

$a->config['system']['max_import_size'] = 200000;

// taille maximale pour le téléversement de photos

$a->config['system']['maximagesize'] = 8000000;

// Lien absolu vers le compilateur PHP

$a->config['system']['php_path'] = '{{$phpath}}';

// configurez la façon dont votre site communique avec les autres serveurs. [Répertoire des membres inscrits à la Matrice]
// DIRECTORY_MODE_NORMAL     = client du répertoire de membres, nous vous trouverons un répertoire accessible autre serveur.
// DIRECTORY_MODE_SECONDARY  = copie mirroir du répertoire des membres.
// DIRECTORY_MODE_PRIMARY    = répertoire des membres principal.
// DIRECTORY_MODE_STANDALONE = "autonome/déconnecté" ou répertoire de membres privés

$a->config['system']['directory_mode']  = DIRECTORY_MODE_NORMAL;

// Thème par défaut

$a->config['system']['theme'] = 'redbasic';

