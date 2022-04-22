<?php

namespace app\modules\module_page_ticket\ext;

class TicketResponse
{
    public ?string $author;
    public ?string $response;
    public ?int $timestamp;
    public ?int $ticket_id;

    /**
     * @param string|null $author
     * @param string|null $response
     * @param int|null $timestamp
     * @param int|null $ticket_id
     */
    public function __construct(?string $author,
                                ?string $response,
                                ?int $timestamp,
                                ?int $ticket_id)
    {
        $this->author = $author;
        $this->response = $response;
        $this->timestamp = $timestamp;
        $this->ticket_id = $ticket_id;
    }


}