<?php
class WhatsAppSender {
    private $apiUrl = 'https://api.whatsapp.com/send';
    private $phoneNumber; // Tu número de WhatsApp Business

    public function __construct($phoneNumber) {
        $this->phoneNumber = $phoneNumber;
    }

    public function enviarMensaje($numeroDestino, $mensaje) {
        // Limpiar y formatear el número
        $numeroDestino = preg_replace('/[^0-9]/', '', $numeroDestino);
        if (strlen($numeroDestino) == 9) {
            $numeroDestino = '51' . $numeroDestino; // Agregar código de país para Perú
        }

        // Codificar el mensaje para URL
        $mensajeCodificado = urlencode($mensaje);
        
        // Crear el enlace de WhatsApp
        $whatsappLink = "{$this->apiUrl}?phone={$numeroDestino}&text={$mensajeCodificado}";
        
        // Devolver el enlace para que el usuario pueda hacer clic
        return $whatsappLink;
    }
} 