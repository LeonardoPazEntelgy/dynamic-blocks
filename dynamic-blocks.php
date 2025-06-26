<?php
/**
 * Plugin Name: Dynamic Blocks Builder
 * Description: Crea bloques dinámicos personalizados desde el administrador.
 * Version: 1.0
 * Author: Entelgy
 */

if (!defined('ABSPATH')) exit;

// Definir constantes
define('DBB_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('DBB_PLUGIN_URL', plugin_dir_url(__FILE__));
define('DBB_PLUGIN_INCLUDES', DBB_PLUGIN_DIR . 'includes/');
define('DBB_USER_BLOCKS_DIR', WP_CONTENT_DIR . '/uploads/dbb-blocks/');
define('DBB_USER_BLOCKS_URL', content_url('uploads/dbb-blocks/'));

// Cargar archivos principales
require_once DBB_PLUGIN_INCLUDES . 'class-blocks-register.php';
require_once DBB_PLUGIN_INCLUDES . 'class-admin-interface.php';
require_once DBB_PLUGIN_INCLUDES . 'class-rest-api.php';
require_once DBB_PLUGIN_INCLUDES . 'class-blocks-render.php';
require_once DBB_PLUGIN_INCLUDES . 'class-blocks-utils.php';
require_once DBB_PLUGIN_INCLUDES . 'class-acf-integration.php';

// Inicializar componentes
$dbb_blocks_register = new DBB_Blocks_Register();
$dbb_admin_interface = new DBB_Admin_Interface();
$dbb_rest_api = new DBB_REST_API();
$dbb_acf_integration = new DBB_ACF_Integration();