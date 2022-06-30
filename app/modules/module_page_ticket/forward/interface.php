<?php
/**
 * @author Daniil Shreyder<daniil.scrhoder@gmail.com>
 * @link https://github.com/ReDestroyDeR
 */
?>


<?php
if (!isset($_SESSION['steamid'])) {
    echo "<div class='col-12' style='text-align: center'><h1>" . $Translate->get_translate_module_phrase('module_page_ticket', '_TicketLoginRequest') . "</h1></div>";
    return;
}


// Checking for a GET request
if (isset($_GET['id'])) {
    $ticket_id = $_GET['id'];

    $ticket = $Db->query("ticket", 0, 0, "
            SELECT `target`, `description`, `proofs`, `lvl_web_tickets`.`author`, `lvl_web_tickets`.`timestamp`,
                   `lvl_web_tickets`.`closed`
            FROM `lvl_web_tickets`
            WHERE `lvl_web_tickets`.`id` = :id
            LIMIT 1;", ['id' => $ticket_id]);

    $messages = $Db->queryAll("ticket", 0, 0, "
            SELECT `author`, `response`, `timestamp`
            FROM `lvl_web_tickets_responses` `lwtr`
            WHERE `lwtr`.`ticket` = :id
            ORDER BY `lwtr`.`timestamp` ASC;", ['id' => $ticket_id]);

    if ($ticket == -1 || $ticket['target'] == null) return http_send_status(404);

    $Auth->check_session_admin();
    if ($_SESSION['user_admin'] != 1 && $_SESSION['steamid'] != $ticket['author']) {
        echo "<div class='col-12' style='text-align: center'><h1>" . $Translate->get_translate_module_phrase('module_page_ticket', '_TicketNoAccess') . "</h1></div>";
        return false;
    }

    echo '
        <div class="row">
            <div class="col-2"></div>
            <div class="card col-8">
                <div class="card-container">
                    <div class="card-block">
                        <h1 class="text-center">' . $Translate->get_translate_module_phrase('module_page_ticket', '_TicketNumber') . ' № ' . $ticket_id . '</h1>
                    </div>
                    <div style="display: flex; flex-direction: row; align-items: stretch">
                        <div style="width: 80%;">
                            <h3>' . $Translate->get_translate_module_phrase('module_page_ticket', '_TicketMessengerAuthor') . ': ' . $General->checkName($ticket['author']) . '</h3>
                            <h4>' . $Translate->get_translate_module_phrase('module_page_ticket', '_TicketMessengerTarget') . ': ' . $ticket['target'] . '</h4>
                            <h4>' . $Translate->get_translate_module_phrase('module_page_ticket', '_TicketMessengerProofs') . ': ' . $ticket['proofs'] . '</h4>
                            <p> ' . $ticket['description'] . '
                            <span> - ' . date('Y-m-d H:i:s', $ticket['timestamp']) . '</span>
                            </p>
                        </div>
                        <div style="width: 20%; flex-direction: column; align-items: center" class="text-center">
                        <form class="input-form" method="post" role="form">
                            <input type="hidden" name="ticket_id" value="' . $ticket_id . '">
                            <input type="hidden" name="close" value="' . true . '">
                            <div>
                                <button type="submit" ';
    if ($ticket['closed']) echo '                       disabled';
    echo '                                                         class="button col-4">' . $Translate->get_translate_module_phrase('module_page_ticket', '_TicketMessengerCloseTicket') . '
                                                         </button>
                            </div>
                        </form>
                        </div>
                    </div>';

    foreach ($messages as $message) {
        if ($message['author'] != $ticket['author']) {
            echo '  <div class="text-right">
                       <h3>' . $Translate->get_translate_module_phrase('module_page_ticket', '_TicketMessengerAdmin') . ': ' . $General->checkName($message['author']) . '</h3>
                       <p> ' . $message['response'] . '
                       <span> - ' . date('Y-m-d H:i:s', $message['timestamp']) . '</span>
                       </p>
                   </div>
                   <br><br>';
        } else {
            echo '  <div class="text-left">
                       <h3>' . $Translate->get_translate_module_phrase('module_page_ticket', '_TicketMessengerAuthor') . ': ' . $General->checkName($message['author']) . '</h3>
                       <p> ' . $message['response'] . '
                       <span> - ' . date('Y-m-d H:i:s', $message['timestamp']) . '</span>
                       </p>
                   </div>
                   <br><br>';
        }
    }

    if (!$ticket['closed']) {
        echo '      <div class="text-center">
                        <form class="input-form" method="post" role="form">
                            <input type="hidden" name="ticket_id" value="' . $ticket_id . '">
                            <div>
                                <input 
                                    type="text" 
                                    class="input_text" 
                                    name="response" 
                                    placeholder="' . $Translate->get_translate_module_phrase('module_page_ticket', '_TicketMessengerWriteHerePlaceholder') . '"
                                />
                            </div>
                            <div style="align-items: center; display: flex; flex-direction: column">
                                <button 
                                    type="submit" 
                                    class="button col-2">' . $Translate->get_translate_module_phrase('module_page_ticket', '_TicketSubmitButton') . '
                                </button>
                            </div>
                        </form>
                    </div>';
    }
    echo '      </div>
            </div>
        </div>
        <div class="footer">
            2021 © <span>Ticket Input Form</span> #0.0.1 by <a href="https://github.com/ReDestroyDeR/">red</a>
        </div>
    ';
    return;
}
?>
<div class="row">
    <div class="col-2"></div>
    <div class="card col-8">
        <div class="card-container text-center">
            <h3><?php echo $Translate->get_translate_module_phrase('module_page_ticket', '_TicketInput') ?>
            </h3>
            <div class="card-block"><?php echo $Translate->get_translate_module_phrase('module_page_ticket', '_TicketInputDescription') ?>
            </div>
            <div class="align-center" style="margin-top: 2rem">
                <form class="input-form" method="post" role="form">
                    <div style="padding-bottom: 1rem">
                        <span><?php echo $Translate->get_translate_module_phrase('module_page_ticket', '_TicketInputForm') ?>:
                        </span>
                        <label for="target"></label>
                        <input
                                id="target"
                                name="target"
                                type="text"
                                placeholder="<?php echo $Translate->get_translate_module_phrase('module_page_ticket', '_TicketRefernceUserPlaceholder') ?>"
                        >
                    </div>
                    <div style="padding-bottom: 1rem">
                        <label for="description"></label>
                        <input
                                id="description"
                                name="description"
                                type="text"
                                placeholder="<?php echo $Translate->get_translate_module_phrase('module_page_ticket', '_TicketExplainPlaceholder') ?>"
                        >
                    </div>
                    <div style="padding-bottom: 1rem">
                        <label for="proofs"></label>
                        <input
                                id="proofs"
                                name="proofs"
                                type="url"
                                placeholder="<?php echo $Translate->get_translate_module_phrase('module_page_ticket', '_TicketProofPlaceholder') ?>"
                        >
                    </div>
                    <div style="align-items: center; display: flex; flex-direction: column">
                        <button type="submit" class="button col-2">
                            <?php echo $Translate->get_translate_module_phrase('module_page_ticket', '_TicketSubmitButton') ?>
                        </button>
                    </div>
                </form>
            </div>

            <table class="table col-12" style="padding-top: 3rem">
                <thead>
                <tr>
                    <td><?php echo $Translate->get_translate_module_phrase('module_page_ticket', '_TicketMessengerAuthor') ?></td>
                    <td><?php echo $Translate->get_translate_module_phrase('module_page_ticket', '_TicketMessengerTarget') ?></td>
                    <td><?php echo $Translate->get_translate_module_phrase('module_page_ticket', '_TicketStatus') ?></td>
                    <td><?php echo $Translate->get_translate_module_phrase('module_page_ticket', '_TicketUpdateTime') ?></td>
                </tr>
                </thead>
                <tbody>
                <?php
                $Auth->check_session_admin();
                $tickets = [];

                function target($ticket, $General): string
                {
                    return ($_SESSION['user_admin'] == 1 ? '[' . $General->checkName($ticket['author']) . '] - ' : '') . $ticket['target'];
                }

                if ($_SESSION['user_admin'] == 1) {
                    $tickets = $Db->queryAll("ticket", 0, 0, "SELECT `lvl_web_tickets`.`author`, `lvl_web_tickets`.`id`, `target`, `description`,
                                   IFNULL(`lwtr`.`timestamp`, `lvl_web_tickets`.`timestamp`) AS `timestamp`, `lvl_web_tickets`.`closed`
                            FROM `lvl_web_tickets`
                            LEFT JOIN `lvl_web_tickets_responses` `lwtr` ON `lwtr`.`timestamp` = (
                                SELECT MAX(`lwtr`.`timestamp`)
                                FROM `lvl_web_tickets_responses` `lwtr`
                                WHERE `lwtr`.`ticket` = `lvl_web_tickets`.`id`
                            )
                            ORDER BY `lwtr`.`timestamp` DESC;
                            ", ['author' => $_SESSION['steamid']]);
                } else {
                    $tickets = $Db->queryAll("ticket", 0, 0, "SELECT `lvl_web_tickets`.`author`, `lvl_web_tickets`.`id`, `target`, `description`,
                                   IFNULL(`lwtr`.`timestamp`, `lvl_web_tickets`.`timestamp`) AS `timestamp`, `lvl_web_tickets`.`closed`
                            FROM `lvl_web_tickets`
                            LEFT JOIN `lvl_web_tickets_responses` `lwtr` ON `lwtr`.`timestamp` = (
                                SELECT MAX(`lwtr`.`timestamp`)
                                FROM `lvl_web_tickets_responses` `lwtr`
                                WHERE `lwtr`.`ticket` = `lvl_web_tickets`.`id`
                            )
                            WHERE `lvl_web_tickets`.`author` = :author
                            ORDER BY `lwtr`.`timestamp` DESC;
                            ", ['author' => $_SESSION['steamid']]);
                }

                function cut_long_description($str): string
                {
                    return strlen($str) > 100 ? substr($str, 0, 100) . '...' : $str;
                }

                foreach ($tickets as $ticket) {
                    $status = $ticket['closed'] ? "Closed" : "Open";
                    echo '
                            <tr>
                                <td>' . target($ticket, $General) . '</td>
                                <td>
                                    <a href="/ticket?id= ' . $ticket["id"] . '">
                                    ' . cut_long_description($ticket['description']) . '
                                    </a>
                                </td>
                                <td>' . $status . '</td>
                                <td>' . date("Y-m-d H:i:s", $ticket['timestamp']) . '</td>
                            </tr>
                        ';
                }
                ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<div class="footer">
    2021 © <span>Ticket Input Form</span> #0.0.1 by <a
            href="https://github.com/ReDestroyDeR/">red</a>
</div>