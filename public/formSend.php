<?php
header('Content-Type: application/json');

$formData = $_POST;

if (empty($formData)) {
  die(json_encode(['error' => 'NO_DATA']));
}

if (!isset($formData['method']) || ($formData['method'] != 'POST')) {
  die(json_encode(['error' => 'MISSING_METHOD']));
}

$response = sendForm($formData);
echo json_encode($response);

function sendForm($data)
{
  $fileJson = '../siteConfig.json';
  if (!file_exists($fileJson)) {
    return ['error' => 'FILE_NOT_FOUND'];
  }
  
  $config = json_decode(file_get_contents($fileJson), true);
  $userKey = $config['user-key'];

  $legacyMap = [
    'fullname' => 'Nombre',
    'name' => 'Nombre',
    'text' => 'Nombre',
    'email' => 'Email',
    'phone' => 'Telefono',
    'message' => 'Mensaje',
  ];

  $normalizedData = [];
  foreach ($data as $key => $value) {
    $normalizedKey = $legacyMap[$key] ?? $key;
    if (!isset($normalizedData[$normalizedKey])) {
      $normalizedData[$normalizedKey] = $value;
    }
  }

  $curlPost = array(
    'Nombre' => $normalizedData['Nombre'] ?? '',
    'Apellido' => $normalizedData['Apellido'] ?? '',
    'Email' => $normalizedData['Email'] ?? '',
    'Telefono' => $normalizedData['Telefono'] ?? '',
    'Mensaje' => $normalizedData['Mensaje'] ?? '',
    'sendTo' => $normalizedData['sendTo'] ?? '',
    'titleWeb' => $normalizedData['titleWeb'] ?? '',
    'recaptchaToken' => $normalizedData['recaptchaToken'] ?? '',
    'secret' => $normalizedData['secret'] ?? '',
    'fromEmail' => $normalizedData['fromEmail'] ?? '',
    'Fecha' => $normalizedData['Fecha'] ?? '',
    'ID' => $normalizedData['ID'] ?? '',
  );

  $reservedFields = ['method', 'Nombre', 'Apellido', 'Email', 'Telefono', 'Mensaje', 'sendTo', 'titleWeb', 'recaptchaToken', 'secret', 'fromEmail', 'Fecha', 'ID', 'fullname', 'name', 'text', 'email', 'phone', 'message'];

  foreach ($normalizedData as $key => $value) {
    if (!in_array($key, $reservedFields)) {
      $curlPost[$key] = $value;
    }
  }

  // Manejar archivos si existen
  if (!empty($_FILES)) {
    foreach ($_FILES as $fieldName => $file) {
      if ($file['error'] === UPLOAD_ERR_OK) {
        $curlPost[$fieldName] = new CURLFile($file['tmp_name'], $file['type'], $file['name']);
      }
    }
  }

  $curl = curl_init();
  curl_setopt_array($curl, array(
    CURLOPT_URL => $config['ApiUrl'],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => 'POST',
    CURLOPT_POSTFIELDS => $curlPost, // Enviar como array (multipart/form-data)
    CURLOPT_HTTPHEADER => array(
      "Authorization: Bearer $userKey",
      // NO incluir Content-Type: application/json
      // CURL lo establecerá automáticamente como multipart/form-data
    ),
  ));

  $response = curl_exec($curl);
  $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
  $curlError = curl_error($curl);
  curl_close($curl);

  if ($curlError) {
    return ['error' => 'CURL_ERROR', 'message' => $curlError];
  }

  if ($httpCode !== 200) {
    return ['error' => 'API_ERROR', 'httpCode' => $httpCode, 'response' => $response];
  }

  $decodedResponse = json_decode($response, true);
  return $decodedResponse ?: ['success' => true];
}