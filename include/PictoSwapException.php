<?php

declare(strict_types=1);

namespace ajf\PictoSwap;

// PictoSwap model (user.php) exception class
// Status code is the suggested HTTP status
class PictoSwapException extends \Exception
{
    private $statusCode;

    public function __construct(string $message, int $statusCode = 400) {
        parent::__construct($message);
        $this->statusCode = $statusCode;
    }

    public function getStatusCode(): int {
        return $this->statusCode;
    }
}
