<?php
// Load WordPress
require_once '/Users/atlas/Local Sites/volksimmobilien/app/public/wp-load.php';

echo "Initializing Importer...\n";
if ( ! class_exists( 'Leadwerk_Importer' ) ) {
    die("Error: Leadwerk_Importer class not found!\n");
}

$importer = new Leadwerk_Importer();
$manifest = $importer->get_manifest();

if ( ! $manifest || empty( $manifest['pages'] ) ) {
    die("Error: Manifest is empty or invalid.\n");
}

echo "Found " . count( $manifest['pages'] ) . " pages in manifest.\n";
echo "Starting import (this might take a while)...\n";

$success = 0;
$failed = 0;

// Need to reset the state first to ensure a clean import
$importer->reset_import_state();

// Process each page
foreach ( $manifest['pages'] as $index => $page ) {
    echo "Importing: " . $page['slug'] . " ... ";
    $result = $importer->process_page_by_index( $index );
    
    if ( is_wp_error( $result ) ) {
        echo "FAILED - " . $result->get_error_message() . "\n";
        $failed++;
    } else {
        echo "SUCCESS (Post ID: " . $result . ")\n";
        $success++;
    }
}

echo "\nImport completed!\n";
echo "Success: $success\n";
echo "Failed: $failed\n";

// Ensure permalinks are flushed
flush_rewrite_rules();
echo "Permalinks flushed.\n";
