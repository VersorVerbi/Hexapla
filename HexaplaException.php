<?php

namespace Hexapla;

use Exception, Throwable;
use JetBrains\PhpStorm\Pure;

/**
 * Class HexaplaException
 */
class HexaplaException extends Exception {
    /** @var array $locals */
    private array $locals;

    /**
     * HexaplaException constructor.
     * @param string $message
     * @param int $code
     * @param Throwable|null $previous
     * @param array $locals
     */
    #[Pure] public function __construct($message = "", $code = 0, Throwable $previous = null, $locals = []) {
        $this->locals = $locals;
        parent::__construct($message, $code, $previous);
    }

    /**
     * @param Throwable $e
     * @return HexaplaException
     */
    public static function toHexaplaException(Throwable $e): HexaplaException {
        return new HexaplaException($e->getMessage(), $e->getCode(), $e->getPrevious());
    }

    /**
     * @return string
     */
    public function getLocals(): string
    {
        return print_r($this->locals, true);
    }
}