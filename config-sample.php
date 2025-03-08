<?php
/* ----------------------------------------------------------------------------
 * Easy!Appointments - Online Appointment Scheduler
 *
 * @package     EasyAppointments
 * @author      A.Tselegidis <alextselegidis@gmail.com>
 * @copyright   Copyright (c) Alex Tselegidis
 * @license     https://opensource.org/licenses/GPL-3.0 - GPLv3
 * @link        https://easyappointments.org
 * @since       v1.0.0
 * ---------------------------------------------------------------------------- */

/**
 * Easy!Appointments Configuration File
 *
 * Set your installation BASE_URL * without the trailing slash * and the database
 * credentials in order to connect to the database. You can enable the DEBUG_MODE
 * while developing the application.
 *
 * Set the default language by changing the LANGUAGE constant. For a full list of
 * available languages look at the /application/config/config.php file.
 *
 * IMPORTANT:
 * If you are updating from version 1.0 you will have to create a new "config.php"
 * file because the old "configuration.php" is not used anymore.
 */
class Config
{
    // ------------------------------------------------------------------------
    // GENERAL SETTINGS
    // ------------------------------------------------------------------------

    const BASE_URL = 'http://test.dnsinfo.fr/easyappointments/'; // Need '/' at the end
    const LANGUAGE = 'english';
    const DEBUG_MODE = true;

    // ------------------------------------------------------------------------
    // DATABASE SETTINGS
    // ------------------------------------------------------------------------

    const DB_HOST = '';
    const DB_NAME = '';
    const DB_USERNAME = '';
    const DB_PASSWORD = '';

    // ------------------------------------------------------------------------
    // GOOGLE CALENDAR SYNC
    // ------------------------------------------------------------------------

    const GOOGLE_SYNC_FEATURE = FALSE; // Enter TRUE or FALSE
    const GOOGLE_CLIENT_ID = '';
    const GOOGLE_CLIENT_SECRET = '';
    const GOOGLE_API_KEY = '';

    // ------------------------------------------------------------------------
    // ODOO SETTINGS
    // ------------------------------------------------------------------------

    const ODOO_URL      = 'http://odoo.host:8069';   // URL d'Odoo
    const ODOO_DB       = 'odoodb';                  // Nom de la base Odoo
    const ODOO_USERNAME = 'odoouser';                        // Identifiant Odoo
    const ODOO_PASSWORD = 'odoopass';                     // Mot de passe Odoo
    const ODOO_EMAIL    = 'email@example.com';         // Email partenaire par défaut



    // ------------------------------------------------------------------------
    // EMAIL SETTINGS
    // ------------------------------------------------------------------------

    const SMTP_USERAGENT = 'Easy!Appointments';
    const SMTP_PROTOCOL  = 'smtp';
    const SMTP_MAILTYPE  = 'html';
    const SMTP_DEBUG     = '1';
    const SMTP_AUTH      = true;
    const SMTP_HOST      = '';
    const SMTP_USER      = '';
    const SMTP_PASS      = '';
    const SMTP_CRYPTO    = 'ssl';
    const SMTP_PORT      = 465;
    const SMTP_CRLF      = "\r\n";
    const SMTP_NEWLINE   = "\r\n";
    const FROM_NAME      = "";          //Optional
    const FROM_ADDRESS   = "";          //Optional
    const REPLY_TO       =  "";         //Optional


}
