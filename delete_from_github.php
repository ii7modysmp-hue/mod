<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

$input = json_decode(file_get_contents('php://input'), true);
$modId = $input['id'] ?? '';

if (empty($modId)) {
    echo json_encode(['success' => false, 'message' => 'معرف المود مطلوب']);
    exit;
}

// GitHub configuration
$githubConfig = [
    'username' => 'ii7modysmp-hue',
    'repo' => 'mod',
    'token' => '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'
];

// حذف الملفات من GitHub
$success = deleteFromGitHub($githubConfig, $modId);

if ($success) {
    echo json_encode(['success' => true, 'message' => 'تم حذف الملفات من GitHub']);
} else {
    echo json_encode(['success' => false, 'message' => 'فشل في حذف الملفات من GitHub']);
}

function deleteFromGitHub($config, $modId) {
    $baseUrl = "https://api.github.com/repos/{$config['username']}/{$config['repo']}/contents/";
    
    // البحث عن الملفات المرتبطة بالـ modId
    $filesToDelete = [];
    
    // البحث في مجلد الصور
    $images = searchFiles($config, 'images', $modId);
    $filesToDelete = array_merge($filesToDelete, $images);
    
    // البحث في مجلد المودات
    $mods = searchFiles($config, 'mods', $modId);
    $filesToDelete = array_merge($filesToDelete, $mods);
    
    // حذف جميع الملفات الموجودة
    $success = true;
    foreach ($filesToDelete as $file) {
        if (!deleteFile($config, $file)) {
            $success = false;
        }
    }
    
    return $success;
}

function searchFiles($config, $folder, $modId) {
    $url = "https://api.github.com/repos/{$config['username']}/{$config['repo']}/contents/{$folder}";
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: token ' . $config['token'],
            'User-Agent: PHP-Script'
        ]
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        return [];
    }
    
    $files = json_decode($response, true);
    $matchedFiles = [];
    
    foreach ($files as $file) {
        if (strpos($file['name'], $modId) === 0) {
            $matchedFiles[] = $file;
        }
    }
    
    return $matchedFiles;
}

function deleteFile($config, $file) {
    $url = "https://api.github.com/repos/{$config['username']}/{$config['repo']}/contents/{$file['path']}";
    
    $data = [
        'message' => "Delete mod file: {$file['name']}",
        'sha' => $file['sha']
    ];
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => 'DELETE',
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => [
            'Authorization: token ' . $config['token'],
            'User-Agent: PHP-Script',
            'Content-Type: application/json'
        ]
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return $httpCode === 200;
}
?>