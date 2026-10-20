<?php

/**
 * Nova hat eine deutsche Oberfläche. Auch bei vorhandener lokaler Einstellung
 * APP_LOCALE=en bleiben Standard-Formularfehler deutsch. Keine Änderung an .env,
 * Datumsformaten oder der Sprache anderer Frameworkbereiche erforderlich.
 */
return require __DIR__.'/../de/validation.php';
