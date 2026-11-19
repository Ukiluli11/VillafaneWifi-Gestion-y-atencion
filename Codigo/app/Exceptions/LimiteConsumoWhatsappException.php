<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Señala que el envío de WhatsApp supera el límite de consumo aplicable.
 */
class LimiteConsumoWhatsappException extends RuntimeException {}
