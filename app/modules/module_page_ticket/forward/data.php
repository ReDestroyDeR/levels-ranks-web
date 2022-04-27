<?php

use app\modules\module_page_ticket\ext\Ticket;
use app\modules\module_page_ticket\ext\TicketResponse;

$Modules->set_page_title( $General->arr_general['short_name'] . ' :: Ticket System' );

// Creating short aliases for potential ticket submit form data
$target = $_POST['target'];
$description = $_POST['description'];
$proofs = $_POST['proofs'];

// Creating short aliases for potential ticket response form data
$ticket_id = $_POST['ticket_id'];
$close = $_POST['close'];
$response = $_POST['response'];

// TODO: Rewrite notifications to satisfy $Notifications API and add Translations for all Strings
// Check for cancel
if (isset($_SESSION['steamid32']) and isset($ticket_id) and isset($close)) {
    if (!$close) {
        return;
    }

    $ticket = $Db->query("ticket",
                0,
                0,
                "SELECT `lvl_web_tickets`.`author` FROM `lvl_web_tickets` WHERE id = :id",
                ['id' => $ticket_id]);

    if (count($ticket) == 0) {
        http_response_code(402);
        $General->sendNote("ERROR: Ticket not found for this request", false);
        header("Location: /ticket/?id=" . $ticket_id);
        return;
    }

    if ($ticket['author'] != $_SESSION['steamid32']) {
        http_response_code(403);
        $General->sendNote("ERROR: You don't own this ticket", false);
        header("Location: /ticket/?id=" . $ticket_id);
        return;
    }

    $closed =
        count(
            $Db->query("ticket",
                0,
                0,
                "UPDATE `lvl_web_tickets`
                    SET `closed` = :closed
                    WHERE `lvl_web_tickets`.`id` = :id;",
                ['id' => $ticket_id, 'closed' => $close])
        ) != 0;

    if (!$closed) {
        http_response_code(500);
        $General->sendNote("ERROR: Ticket closing failed for this request", false);
        header("Location: /ticket/?id=" . $ticket_id);
        return;
    }

    $General->sendNote("Ticket closed", false);
    header("Location: /ticket/?id=" . $ticket_id);
}

// Check for a data after Submit
if (isset($_SESSION['steamid32']) and isset($target) and isset($description) and isset($proofs)) {
    if (strlen($target) < 3 or strlen($description) < 10 or strlen($proofs) < 9) {
        $General->sendNote("Target should be at least 3 letters;
                                Description should be at least 10 letters;
                                Proofs should be at least 9 letters", true, 9);
        http_response_code(400);
        header("Location: /ticket");
        return;
    }

    $ticket = new Ticket(
        $_SESSION['steamid32'],
        $target,
        $description,
        $proofs,
        time(),
        null
    );

    if (add_ticket($ticket, $Db)) {
        printf("Something %s", $General->sendNote("You just sent a ticket", false));
        header("Location: /ticket");
        return;
    }

    $General->sendNote("You need to wait for at least 3 hours before sending a next ticket", false);
    http_response_code(400);
    header("Location: /ticket");
    return;
}

if (isset($_SESSION['steamid32']) and isset($response) and isset($ticket_id)) {
    $exists =
        count(
            $Db->query("ticket",
                0,
                0,
                "SELECT 1 FROM `lvl_web_tickets` WHERE id = :id",
                ['id' => $ticket_id])
        ) != 0;

    if (!$exists) {
        http_response_code(402);
        $General->sendNote("ERROR: Ticket not found for this request", true);
        header("Location: /ticket/?id=" . $ticket_id);
        return;
    }


    $ticket_response = new TicketResponse(
        $_SESSION['steamid32'],
        $response,
        time(),
        $ticket_id
    );

    // Insert new ticket response to corresponding table
    $Db->query(
        "ticket",
        0,
        0,
        "INSERT INTO `lvl_web_tickets_responses` (`author`, `response`, `timestamp`, `ticket`)
             VALUES (:author, :response, :timestamp, :ticket_id);",
        [
            "author" => $ticket_response->author,
            "response" => $ticket_response->response,
            "timestamp" => $ticket_response->timestamp,
            "ticket_id" => $ticket_response->ticket_id
        ]
    );

    // Get newly created ticket id
    $response_id = $Db->query(
        "ticket",
        0,
        0,
        "SELECT `id` 
            FROM `lvl_web_tickets_responses` 
            WHERE `timestamp` = :timestamp AND `author` = :author",
        [
            "timestamp" => $ticket_response->timestamp,
            "author" => $ticket_response->author
        ]
    );
    header("Location: /ticket/?id=" . $ticket_id);
    return;
}

function is_user_allowed_to_post_a_ticket(string $user_steam_id, $Db): bool
{
    // Expensive operation TODO: Caching
    $timestamp = $Db->query("ticket", 0, 0,
        "SELECT `timestamp`
        FROM `lvl_web_tickets`
        WHERE `author` = :author
        ORDER BY `timestamp` DESC 
        LIMIT 1", ['author' => $user_steam_id]
    )['timestamp'];

    // If user have never posted a ticket or last ticket was sent more than 3 hours ago
    return true; // $timestamp == null or $timestamp + (3 * 60 * 60) < time() ;
}

function add_ticket(Ticket $ticket, $Db) : bool {
    // Expensive operation TODO: Extra caching
    if (!is_user_allowed_to_post_a_ticket($ticket->author, $Db)) {
       return false;
    }

    $param = [
        'author' => $ticket->author,
        'target' => $ticket->target,
        'description' => $ticket->description,
        'proofs' => $ticket->proofs,
        'timestamp' => $ticket->timestamp
    ];


    $Db->query(
        "ticket",
        0,
        0,
        "INSERT INTO `lvl_web_tickets` (`author`, `target`, `description`, `proofs`, `timestamp`) 
        VALUES (:author, :target, :description, :proofs, :timestamp)",
        $param
    );

    return true;
}