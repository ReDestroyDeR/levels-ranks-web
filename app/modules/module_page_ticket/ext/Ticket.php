<?php

namespace app\modules\module_page_ticket\ext;

class Ticket
{
    public ?string $author;
    public ?string $target;
    public ?string $description;
    public ?string $proofs;
    public ?int $timestamp;
    public ?int $response_id;

    /**
     * @param string|null $author
     * @param string|null $target
     * @param string|null $description
     * @param string|null $proofs
     * @param int|null $timestamp
     * @param int|null $response_id
     */
    public function __construct(?string $author,
                                ?string $target,
                                ?string $description,
                                ?string $proofs,
                                ?int $timestamp,
                                ?int $response_id)
    {
        $this->author = $author;
        $this->target = $target;
        $this->description = $description;
        $this->proofs = $proofs;
        $this->timestamp = $timestamp;
        $this->response_id = $response_id;
    }


}