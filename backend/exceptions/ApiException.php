<?php

class ApiException extends Exception
{
    public int $status;
    public string $errorCode;
    public ?array $details;
    public ?string $language;

    public function __construct(
        int $status,
        string $errorCode,
        ?string $language = null,
        ?array $details = null
    ) {
        parent::__construct();

        $this->status = $status;
        $this->errorCode = $errorCode;
        $this->language = $language;
        $this->details = $details;
    }
}