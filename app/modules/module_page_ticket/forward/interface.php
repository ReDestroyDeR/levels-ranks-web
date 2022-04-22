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

    $ticket = $Db->query("ticket", 0, 0,
        "SELECT `target`, `description`, `proofs`, `lvl_web_tickets`.`author`, `lvl_web_tickets`.`timestamp`,
                    `lwtr`.`author` as `admin`, `lwtr`.`response`, `lwtr`.`timestamp` as `response_timestamp`
            FROM `lvl_web_tickets`
            LEFT JOIN `lvl_web_tickets_responses` `lwtr` on `lvl_web_tickets`.`id` = `lwtr`.`ticket`
            WHERE `lvl_web_tickets`.`id` = :id
            LIMIT 1;
            ", ['id' => $ticket_id]);

    if ($ticket == -1 || $ticket['target'] == null)
        return http_send_status(404);

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
                    <div class="text-left">
                        <h3>Author: ' . $ticket['author'] . '</h3>
                        <h4>Target: ' . $ticket['target'] . '</h4>
                        <h4>Proofs: ' . $ticket['proofs'] . '</h4>
                        <p> ' . $ticket['description'] . '
                        <span> - ' . date('Y-m-d H:i:s', $ticket['timestamp']) .'</span>
                        </p>
                    </div>';
    if ($ticket['response_timestamp'] != null) {
        echo '      <div class="text-right">
                        <h3>Admin: ' . $ticket['admin'] . '</h3>
                        <p> ' . $ticket['response'] . '
                        <span> - ' . date('Y-m-d H:i:s', $ticket['response_timestamp']) . '</span>
                        </p>
                    </div>
                    <br><br>
                    <h4 class="text-center">Still have any questions? Contact us in <a href="https://discord.gg/bRNJmegBpW">Discord</a></h4>';
    }
    else  {
        echo '      <div class="text-center">
                        <span>Waiting for response</span>
                    </div>';
        if ($_SESSION['user_admin'] == 1) {
            echo '  <div class="text-center">
                        <form class="input-form" method="post" role="form">
                            <input type="hidden" name="ticket_id" value="' . $ticket_id . '">
                            <div>
                                <input type="text" class="input_text" name="response" placeholder="Write response here"/>
                            </div>
                            <div style="align-items: center; display: flex; flex-direction: column">
                                <button type="submit" class="button col-2">Submit</button>
                            </div>
                        </form>
                    </div>';
        }
    }

    echo '
                </div>
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
            <h3><?php echo $Translate->get_translate_phrase('_TicketInput') ?></h3>
            <div class="card-block">Here you can file a ticket to an admin</div>
            <div class="align-center" style="margin-top: 2rem">
                <form class="input-form" method="post" role="form">
                    <div style="padding-bottom: 1rem">
                        <span>Fill the form below to submit a ticket: </span>
                        <label for="target"></label><input id="target" name="target" type="text" placeholder="Reference user (Nickname)">
                    </div>
                    <div style="padding-bottom: 1rem">
                        <label for="description"></label><input id="description" name="description" type="text" placeholder="Explain what happened (Minimum 10 characters)">
                    </div>
                    <div style="padding-bottom: 1rem">
                        <label for="proofs"></label><input id="proofs"  name="proofs" type="url" placeholder="Provide proofs (URL)">
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
                    <td>Assigned admin</td>
                    <td>Last activity</td>
                </tr>
                </thead>
                <tbody>
                <?php
                $Auth->check_session_admin();
                $tickets = [];

                if ($_SESSION['user_admin'] == 1) {
                    $tickets = $Db->queryAll("ticket", 0, 0,
                        "SELECT `lvl_web_tickets`.`author`, `lvl_web_tickets`.`id`, `target`, `description`,
                                    `lwtr`.`author` as `admin`, `lvl_web_tickets`.`timestamp` 
                            FROM `lvl_web_tickets`
                            LEFT JOIN `lvl_web_tickets_responses` `lwtr` on `lvl_web_tickets`.`id` = `lwtr`.`ticket`
                            ORDER BY `lvl_web_tickets`.`timestamp` DESC
                            ", ['author' => $_SESSION['steamid32']]);
                } else {
                    $tickets = $Db->queryAll("ticket", 0, 0,
                        "SELECT `lvl_web_tickets`.`id`, `target`, `description`, `lwtr`.`author`, `lvl_web_tickets`.`timestamp` 
                            FROM `lvl_web_tickets`
                            LEFT JOIN `lvl_web_tickets_responses` `lwtr` on `lvl_web_tickets`.`id` = `lwtr`.`ticket`
                            WHERE `lvl_web_tickets`.`author` = :author
                            ORDER BY `lvl_web_tickets`.`timestamp` DESC
                            ", ['author' => $_SESSION['steamid32']]);
                }

                function target($ticket) : string {
                    return  ($_SESSION['user_admin'] == 1 ? '[' . $ticket['author'] . '] - ' : '') . $ticket['target'];
                }

                function cut_long_description($str) : string {
                    return strlen($str) > 100
                        ? substr($str, 0, 100) . '...'
                        : $str;
                }

                foreach ($tickets as $ticket) {
                    $admin = $ticket['admin'] == null ? "Not assigned" : $ticket['admin'];
                    echo '
                            <tr>
                                <td>' . target($ticket) .'</td>
                                <td>
                                    <a href="/ticket?id= ' . $ticket["id"] . '">
                                    ' . cut_long_description($ticket['description']) . '
                                    </a>
                                </td>
                                <td>' . $admin .'</td>
                                <td>' . date("Y-m-d H:i:s", $ticket['timestamp']) .'</td>
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