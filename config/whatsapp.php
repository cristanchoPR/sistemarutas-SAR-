<?php
class WhatsApp {
    private $token;
    private $phoneNumberId;
    private $version = 'v17.0'; // Versión actual de la API

    public function __construct() {
        // Configuración de WhatsApp
        $this->token = 'TU_TOKEN_DE_ACCESO'; // Reemplazar con tu token
        $this->phoneNumberId = 'TU_PHONE_NUMBER_ID'; // Reemplazar con tu ID de número
    }

    public function enviarMensaje($numero, $mensaje) {
        $url = "https://graph.facebook.com/{$this->version}/{$this->phoneNumberId}/messages";
        
        // Formatear el número de teléfono (debe incluir código de país)
        $numero = preg_replace('/[^0-9]/', '', $numero);
        if (strlen($numero) == 10) {
            $numero = '51' . $numero; // Agregar código de país para Perú
        }

        $data = [
            'messaging_product' => 'whatsapp',
            'to' => $numero,
            'type' => 'text',
            'text' => [
                'body' => $mensaje
            ]
        ];

        $headers = [
            'Authorization: Bearer ' . $this->token,
            'Content-Type: application/json'
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return [
            'success' => ($httpCode >= 200 && $httpCode < 300),
            'response' => $response,
            'httpCode' => $httpCode
        ];
    }
} 