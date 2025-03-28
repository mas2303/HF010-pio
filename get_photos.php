<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$metadataFile = __DIR__ . '/images/gallery/metadata.json';

try {
    if (!file_exists($metadataFile)) {
        echo json_encode(['success' => true, 'photos' => []]);
        exit;
    }

    $metadata = json_decode(file_get_contents($metadataFile), true);
    
    if ($metadata === null) {
        throw new Exception('Erreur lors de la lecture du fichier de métadonnées');
    }

    // Vérifier que chaque photo existe toujours
    $validPhotos = array_filter($metadata['photos'], function($photo) {
        return file_exists(__DIR__ . '/' . $photo['url']);
    });

    // Si certaines photos ont été supprimées, mettre à jour le fichier de métadonnées
    if (count($validPhotos) !== count($metadata['photos'])) {
        $metadata['photos'] = array_values($validPhotos);
        file_put_contents($metadataFile, json_encode($metadata, JSON_PRETTY_PRINT));
    }

    echo json_encode([
        'success' => true,
        'photos' => array_values($validPhotos)
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?> 