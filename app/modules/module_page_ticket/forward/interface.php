<?php
/**
 * @author Daniil Shreyder<daniil.scrhoder@gmail.com>
 * @link https://github.com/ReDestroyDeR
 */
?>


<?php
if (!isset($_SESSION['steamid32'])) {
    echo "<div class='col-12' style='text-align: center'><h1>Please login to proceed with this feature</h1></div>";
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

    $messages = $Db->queryAll("ticket", 0, 0, "SELECT `author`, `response`, `timestamp`
            FROM `lvl_web_tickets_responses` `lwtr`
            WHERE `lwtr`.`ticket` = :id
            ORDER BY `lwtr`.`timestamp` ASC;", ['id' => $ticket_id]);

    if ($ticket == -1 || $ticket['target'] == null) return http_send_status(404);

    $Auth->check_session_admin();
    if ($_SESSION['user_admin'] != 1 && $_SESSION['steamid32'] != $ticket['author']) {
        echo "<div class='col-12' style='text-align: center'><h1>You do not have access to this page</h1></div>";
        return false;
    }

    echo '
        <div class="row">
            <div class="col-2"></div>
            <div class="card col-8">
                <div class="card-container">
                    <div class="card-block">
                        <h1 class="text-center">Ticket № ' . $ticket_id . '</h1>
                    </div>
                    <div style="display: flex; flex-direction: row; align-items: stretch">
                        <div style="width: 80%;">
                            <h3>Author: ' . $ticket['author'] . '</h3>
                            <h4>Target: ' . $ticket['target'] . '</h4>
                            <h4>Proofs: ' . $ticket['proofs'] . '</h4>
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
    echo '                                                         class="button col-4">Close ticket</button>
                            </div>
                        </form>
                        </div>
                    </div>';

    foreach ($messages as $message) {
        if ($message['author'] != $ticket['author']) {
            echo '  <div class="text-right">
                       <h3>Admin: ' . $message['author'] . '</h3>
                       <p> ' . $message['response'] . '
                       <span> - ' . date('Y-m-d H:i:s', $message['timestamp']) . '</span>
                       </p>
                   </div>
                   <br><br>';
        } else {
            echo '  <div class="text-left">
                       <h3>Author: ' . $message['author'] . '</h3>
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
                                <input type="text" class="input_text" name="response" placeholder="Write meesage here"/>
                            </div>
                            <div style="align-items: center; display: flex; flex-direction: column">
                                <button type="submit" class="button col-2">Submit</button>
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
            <h3><?php echo $Translate->get_translate_module_phrase('module_page_ticket', '_TicketInput',) ?></h3>
            <div class="card-block">Here you can file a ticket to an admin</div>
            <div class="align-center" style="margin-top: 2rem">
                <form class="input-form" method="post" role="form">
                    <div style="padding-bottom: 1rem">
                        <span>Fill the form below to submit a ticket: </span>
                        <label for="target"></label><input id="target" name="target" type="text"
                                                           placeholder="Reference user (Nickname)">
                    </div>
                    <div style="padding-bottom: 1rem">
                        <label for="description"></label><input id="description" name="description" type="text"
                                                                placeholder="Explain what happened (Minimum 10 characters)">
                    </div>
                    <div style="padding-bottom: 1rem">
                        <label for="proofs"></label><input id="proofs" name="proofs" type="url"
                                                           placeholder="Provide proofs (URL)">
                    </div>
                    <div style="align-items: center; display: flex; flex-direction: column">
                        <button type="submit" class="button col-2">Submit</button>
                    </div>
                </form>
            </div>

            <table class="table col-12" style="padding-top: 3rem">
                <thead>
                <tr>
                    <td>Target</td>
                    <td>Description</td>
                    <td>Ticket Status</td>
                    <td>Last activity</td>
                </tr>
                </thead>
                <tbody>
                <?php
                $Auth->check_session_admin();
                $tickets = [];

                if ($_SESSION['user_admin'] == 1) {
                    $tickets = $Db->queryAll("ticket", 0, 0, "SELECT `lvl_web_tickets`.`author`, `lvl_web_tickets`.`id`, `target`, `description`,
                                   `lwtr`.`timestamp`, `lvl_web_tickets`.`closed`
                            FROM `lvl_web_tickets`
                            LEFT JOIN `lvl_web_tickets_responses` `lwtr` ON `lwtr`.`timestamp` = (
                                SELECT MAX(`lwtr`.`timestamp`)
                                FROM `lvl_web_tickets_responses` `lwtr`
                                WHERE `lwtr`.`ticket` = `lvl_web_tickets`.`id`
                            )
                            ORDER BY `lwtr`.`timestamp` DESC;
                            ", ['author' => $_SESSION['steamid32']]);
                } else {
                    $tickets = $Db->queryAll("ticket", 0, 0, "SELECT `lvl_web_tickets`.`author`, `lvl_web_tickets`.`id`, `target`, `description`,
                                   `lwtr`.`timestamp`, `lvl_web_tickets`.`closed`
                            FROM `lvl_web_tickets`
                            LEFT JOIN `lvl_web_tickets_responses` `lwtr` ON `lwtr`.`timestamp` = (
                                SELECT MAX(`lwtr`.`timestamp`)
                                FROM `lvl_web_tickets_responses` `lwtr`
                                WHERE `lwtr`.`ticket` = `lvl_web_tickets`.`id`
                            )
                            WHERE `lvl_web_tickets`.`author` = :author
                            ORDER BY `lwtr`.`timestamp` DESC;
                            ", ['author' => $_SESSION['steamid32']]);
                }

                function target($ticket): string
                {
                    return ($_SESSION['user_admin'] == 1 ? '[' . $ticket['author'] . '] - ' : '') . $ticket['target'];
                }

                function cut_long_description($str): string
                {
                    return strlen($str) > 100 ? substr($str, 0, 100) . '...' : $str;
                }

                foreach ($tickets as $ticket) {
                    $status = $ticket['closed'] ? "Closed" : "Open";
                    echo '
                            <tr>
                                <td>' . target($ticket) . '</td>
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
    2021 © <span>Ticket Input Form</span> #0.0.1 by <a href="https://github.com/ReDestroyDeR/">red</a>
</div>