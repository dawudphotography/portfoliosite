<?php
/**
 * La configuration de base de votre installation WordPress.
 *
 * Ce fichier contient les réglages de configuration suivants : réglages MySQL,
 * préfixe de table, clés secrètes, langue utilisée, et ABSPATH.
 * Vous pouvez en savoir plus à leur sujet en allant sur
 * {@link http://codex.wordpress.org/fr:Modifier_wp-config.php Modifier
 * wp-config.php}. C’est votre hébergeur qui doit vous donner vos
 * codes MySQL.
 *
 * Ce fichier est utilisé par le script de création de wp-config.php pendant
 * le processus d’installation. Vous n’avez pas à utiliser le site web, vous
 * pouvez simplement renommer ce fichier en "wp-config.php" et remplir les
 * valeurs.
 *
 * @package WordPress
 */

// ** Réglages MySQL - Votre hébergeur doit vous fournir ces informations. ** //
/** Nom de la base de données de WordPress. */
define( 'DB_NAME', 'portfoliosite_db' );

/** Utilisateur de la base de données MySQL. */
define( 'DB_USER', 'root' );

/** Mot de passe de la base de données MySQL. */
define( 'DB_PASSWORD', '' );

/** Adresse de l’hébergement MySQL. */
define( 'DB_HOST', 'localhost' );

/** Jeu de caractères à utiliser par la base de données lors de la création des tables. */
define( 'DB_CHARSET', 'utf8mb4' );

/** Type de collation de la base de données.
  * N’y touchez que si vous savez ce que vous faites.
  */
define('DB_COLLATE', '');

/**#@+
 * Clés uniques d’authentification et salage.
 *
 * Remplacez les valeurs par défaut par des phrases uniques !
 * Vous pouvez générer des phrases aléatoires en utilisant
 * {@link https://api.wordpress.org/secret-key/1.1/salt/ le service de clefs secrètes de WordPress.org}.
 * Vous pouvez modifier ces phrases à n’importe quel moment, afin d’invalider tous les cookies existants.
 * Cela forcera également tous les utilisateurs à se reconnecter.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',         'RRme{!H?J(c [-v!VxMw@IOT}3Svk?;J0[Z22yy[f>l`l=0@|7e|=arr!t``_{7]' );
define( 'SECURE_AUTH_KEY',  'Gh`(^QFOe2;]00C6S(&qZ:ce[0Pwo/=IF<5nRskBEn?6|&/%i]Clp*n/YYJD4y0/' );
define( 'LOGGED_IN_KEY',    'q4TZBZGeYO,?.lzbZ3fU/8)eN(%Qu)I6.=j<3jNp6ZDNRy&Tq~G[TNp.aikc~SLA' );
define( 'NONCE_KEY',        'Qryb!5KWo+{k0fbr)^A%uh;1?1%I{&&*wUaCAy-Faq/`zz~Z`pm/rURsY)}0P)(~' );
define( 'AUTH_SALT',        '$+QA/M3wj:T%ft,~)bM)#GpZ+.A.2PcbO);piyYmGqdRe89~d+-/DWS94! {ezU;' );
define( 'SECURE_AUTH_SALT', 'iJ3wJ1zL8;x.D|szqMc0c*<$5T?CXQe+&>N$K{=n%kOD;k^~Y,Mg6NlX<O1<4,n*' );
define( 'LOGGED_IN_SALT',   'uzI0?=^O%@~xze{Q+&Q5%eV^MBZp<M*$%Qp!ZbV@3RUV([}.zb$Nn#d_&W$UVn6Z' );
define( 'NONCE_SALT',       'lAAWBE*o}?*e,+>}A`gZTV~!u3wAKS$A<gw<1T+t}LE){o>[[@I)6rNiLk~d.@;D' );
/**#@-*/

/**
 * Préfixe de base de données pour les tables de WordPress.
 *
 * Vous pouvez installer plusieurs WordPress sur une seule base de données
 * si vous leur donnez chacune un préfixe unique.
 * N’utilisez que des chiffres, des lettres non-accentuées, et des caractères soulignés !
 */
$table_prefix = 'wp_';

/**
 * Pour les développeurs : le mode déboguage de WordPress.
 *
 * En passant la valeur suivante à "true", vous activez l’affichage des
 * notifications d’erreurs pendant vos essais.
 * Il est fortemment recommandé que les développeurs d’extensions et
 * de thèmes se servent de WP_DEBUG dans leur environnement de
 * développement.
 *
 * Pour plus d’information sur les autres constantes qui peuvent être utilisées
 * pour le déboguage, rendez-vous sur le Codex.
 *
 * @link https://codex.wordpress.org/Debugging_in_WordPress
 */
define('WP_DEBUG', false);

/* C’est tout, ne touchez pas à ce qui suit ! Bonne publication. */

/** Chemin absolu vers le dossier de WordPress. */
if ( !defined('ABSPATH') )
	define('ABSPATH', dirname(__FILE__) . '/');

/** Réglage des variables de WordPress et de ses fichiers inclus. */
require_once(ABSPATH . 'wp-settings.php');
