<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

$uploadDir = __DIR__ . '/images/gallery/';
$maxFileSize = 100 * 1024 * 1024; // 100MB
$allowedImageTypes = ['image/jpeg', 'image/png', 'image/gif'];
$allowedVideoTypes = ['video/mp4', 'video/webm', 'video/ogg'];
$maxWidth = 3840;  // 4K
$maxHeight = 2160; // 4K

// Créer le dossier s'il n'existe pas
if (!file_exists($uploadDir)) {
    if (!mkdir($uploadDir, 0777, true)) {
        echo json_encode([
            'success' => false,
            'message' => 'Erreur lors de la création du dossier d\'upload'
        ]);
        exit;
    }
}

try {
    if (!isset($_FILES['photo'])) {
        throw new Exception('Aucun fichier n\'a été envoyé');
    }

    $file = $_FILES['photo'];
    $title = $_POST['title'] ?? '';
    $category = $_POST['category'] ?? '';
    
    // Vérifier les erreurs d'upload
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Erreur lors de l\'upload: ' . $file['error']);
    }

    // Vérifier la taille du fichier
    if ($file['size'] > $maxFileSize) {
        throw new Exception('Le fichier est trop volumineux (max 100MB)');
    }

    // Vérifier le type de fichier
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $isVideo = in_array($extension, ['mp4', 'webm', 'ogg']);
    $isImage = in_array($extension, ['jpg', 'jpeg', 'png', 'gif']);

    if (!$isVideo && !$isImage) {
        throw new Exception('Type de fichier non autorisé (JPG, PNG, GIF, MP4, WEBM, OGG uniquement)');
    }

    // Pour les images, vérifier les dimensions
    if ($isImage) {
        $imageInfo = getimagesize($file['tmp_name']);
        if ($imageInfo === false) {
            throw new Exception('Le fichier n\'est pas une image valide');
        }

        $width = $imageInfo[0];
        $height = $imageInfo[1];
        if ($width > $maxWidth || $height > $maxHeight) {
            throw new Exception('L\'image est trop grande (max 3840x2160)');
        }
    }

    // Générer un nom de fichier unique
    $filename = uniqid() . '.' . $extension;
    $filepath = $uploadDir . $filename;

    // Déplacer le fichier
    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        throw new Exception('Erreur lors du déplacement du fichier');
    }

    // Sauvegarder les métadonnées
    $metadataFile = $uploadDir . 'metadata.json';
    $metadata = [];
    
    if (file_exists($metadataFile)) {
        $metadata = json_decode(file_get_contents($metadataFile), true) ?: ['photos' => []];
    } else {
        $metadata = ['photos' => []];
    }

    $mediaData = [
        'filename' => $filename,
        'title' => htmlspecialchars($title),
        'category' => htmlspecialchars($category),
        'date' => date('Y-m-d H:i:s'),
        'url' => 'images/gallery/' . $filename,
        'type' => $isVideo ? 'video' : 'image'
    ];

    if ($isImage) {
        $mediaData['width'] = $width;
        $mediaData['height'] = $height;
    }

    $metadata['photos'][] = $mediaData;
    file_put_contents($metadataFile, json_encode($metadata, JSON_PRETTY_PRINT));

    echo json_encode([
        'success' => true,
        'message' => ($isVideo ? 'Vidéo' : 'Photo') . ' uploadée avec succès',
        'media' => $mediaData
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?> 