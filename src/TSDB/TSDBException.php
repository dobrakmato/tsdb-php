<?php

namespace TSDB;

use RuntimeException;

class TSDBException extends RuntimeException
{

    /**
     * TSDBException constructor.
     * @param string $message
     */
    public function __construct(string $message)
    {
        parent::__construct($message);
    }
}
