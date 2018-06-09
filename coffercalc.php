<?/*********************************************************************************/
/* Unit Coffer Calculator                                                          */
/* http://foxmwo.com                                                               */
/* Last file update: 04/20/18                                                      */
/*                                                                                 */
/* MIT License                                                                     */
/*                                                                                 */
/* Copyright (c) 2018 Brandon Ballard                                              */
/*                                                                                 */
/* Permission is hereby granted, free of charge, to any person obtaining a copy    */
/* of this software and associated documentation files (the "Software"), to deal   */
/* in the Software without restriction, including without limitation the rights    */
/* to use, copy, modify, merge, publish, distribute, sublicense, and/or sell       */
/* copies of the Software, and to permit persons to whom the Software is           */
/* furnished to do so, subject to the following conditions:                        */
/*                                                                                 */
/* The above copyright notice and this permission notice shall be included in all  */
/* copies or substantial portions of the Software.                                 */
/*                                                                                 */
/* THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR      */
/* IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,        */
/* FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE     */
/* AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER          */
/* LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,   */
/* OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE   */
/* SOFTWARE.                                                                       */
/***********************************************************************************/

$start = microtime(true); //Start page load counter

/************************************************************************/
/* FORM SETTINGS AND URL GENERATION                                     */
/************************************************************************/
$filename = basename($_SERVER['SCRIPT_FILENAME']);
$url_path = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
$url_path = substr($url_path, 0, strpos($url_path, $filename));
$current_member_count = stripJunk($_REQUEST["current_member_count"]);
$goal_member_count = stripJunk($_REQUEST["goal_member_count"]);
$current_coffer_balance = stripJunk($_REQUEST["current_coffer_balance"]);

if ($goal_member_count && $goal_member_count < $current_member_count) {
    $goal_member_count = $current_member_count;
}
if ($current_member_count || $goal_member_count || $current_coffer_balance) {
    $query_arguments = "$url_path$filename?";
    if ($current_member_count) {
        $form_current_member_count = number_format($current_member_count);
        $query_arguments .= "current_member_count=$current_member_count";
    }
    if ($goal_member_count) {
        $form_goal_member_count = number_format($goal_member_count);
        $query_arguments .= "&goal_member_count=$goal_member_count";
    }
    if ($current_coffer_balance) {
        $form_current_coffer_balance = number_format($current_coffer_balance);
        $query_arguments .= "&current_coffer_balance=$current_coffer_balance";
    }
}
/************************************************************************/
/* CONSTANTS AND PRESET VALUES                                          */
/************************************************************************/
define("CALC_INVITES", 1);
define("CALC_BALANCE_LEFT", 2);
$cbills_per_member = 50000; //The member cost goes up 50,000 for each additional member
$mc_per_dollar = 250.1250;
$cbills_per_mc = 1625;
$cbills_per_dollar = 406453.125;
$pgi_sell_penalty = 0.5;
/************************************************************************/
/* FUNCTIONS                                                            */
/************************************************************************/
function stripJunk($number) {
    return preg_replace("/[^0-9\.]/", "", $number);
}

function ordinal($number) { //Returns $number with an ordinal indicator
    $ends = array('th','st','nd','rd','th','th','th','th','th','th');
    if ((($number % 100) >= 11) && (($number%100) <= 13))
        return $number. 'th';
    else
        return $number. $ends[$number % 10];
}

function plural($number) { //Returns "s" if $number is equal to 1
    if ($number == 1) {
        $suffix = "";
    } else {
        $suffix = "s";
    }
    return $suffix;
}

function cBillsToDollarCalc($cbills, $format = false) {
    global $cbills_per_dollar, $pgi_sell_penalty;
    $cbills = stripJunk($cbills);
    if ($cbills > 0) {
        $dollar_equivalent = ($cbills / $cbills_per_dollar) * $pgi_sell_penalty;
    } else {
        $dollar_equivalent = 0;
    }
    if ($format == true) {
        $dollar_equivalent = "$".(money_format('%i', $dollar_equivalent));
    }
    return $dollar_equivalent;
}

function goalCalc($start_members, $end_members, $format = false) { //Calculates and returns the required C-Bills to recruit members between start and end
    global $cbills_per_member;
    $start_members = stripJunk($start_members);
    $end_members = stripJunk($end_members);
    $answer = 0;
    $difference = $end_members - $start_members;
    for ($x = 0; $x < $difference; $x++) {
        $counter = ($start_members + $x) * $cbills_per_member;
        $answer += $counter;
    }
    if ($format == true) {
        $answer = number_format($answer);
    }
    return $answer;
}

function balanceCalc($start_members, $balance, $output = CALC_INVITES, $format = false) { //Calculates and returns either the buyable invites or the balance left over
    global $cbills_per_member;
    $start_members = stripJunk($start_members);
    $balance = stripJunk($balance);
    if ($start_members && $balance) {
        $start_money = $balance;
        $buyable_invites = 0;
        $next_member_cost = $start_members * $cbills_per_member;
        while ($start_money > 0) {
            if ($start_money - $next_member_cost < 0) {
                break;
            }
            $start_members++;
            $prev_member_cost = $next_member_cost;
            $start_money -= $next_member_cost;
            $remaining_balance = $start_money;
            $buyable_invites++;
            $next_member_cost += $cbills_per_member;
        }
    } else {
        $buyable_invites = 0;
        $remaining_balance = $balance;
        if (empty($balance)) {
            $remaining_balance = 0;
        }
    }
    if ($output == CALC_INVITES) {
        $answer = $buyable_invites;
    } else if ($output == CALC_BALANCE_LEFT) {
        $answer = $remaining_balance;
    }
    if ($format == true && $answer >= 0) {
        $answer = number_format($answer);
    }
    return $answer;
}
/************************************************************************/
/* CALCULATIONS                                                         */
/************************************************************************/
if ($current_member_count > 100000 || $goal_member_count > 100000 || $current_coffer_balance > 100000000000) {
    $stopallcalc = true;
} else {
    $goal_cbills_needed = goalCalc($current_member_count, $goal_member_count);
    $goal_cbills_needed_after_spending_coffer = number_format($goal_cbills_needed - $current_coffer_balance);
    $buyable_members = balanceCalc($current_member_count, $current_coffer_balance, CALC_INVITES, true);
    $cbills_left = balanceCalc($current_member_count, $current_coffer_balance, CALC_BALANCE_LEFT, true);
    $next_invite_cost = number_format($current_member_count * $cbills_per_member);
    $goal_next_invite_cost = number_format($goal_member_count * $cbills_per_member);
    if ($current_member_count > 0) {
        $goal_cbills_needed_after_spending_coffer_divided_amongst_members = number_format(($goal_cbills_needed - $current_coffer_balance) / $current_member_count);
    }
    if (($goal_cbills_needed - $current_coffer_balance) < 0) {
        $goal_cbills_needed_after_spending_coffer = 0;
        $goal_cbills_needed_after_spending_coffer_divided_amongst_members = 0;
    }
    $goal_cbills_needed = number_format($goal_cbills_needed);
}
/************************************************************************/
/* PAGE CONTENT                                                         */
/************************************************************************/

?><!DOCTYPE html>
<html>
<head>
    <title>Unit Coffer Calculator</title>
    <style type="text/css">
        .url-copy {
            position:absolute;
            top:0;
            right:0;
            margin: 3px 3px 3px 3px;
        }
    </style>
</head>
<body>
<h3>Unit Coffer Calculator</h3>
<div class="url-copy">
    <button id="copyButton">Copy URL</button> <input id="copyTarget" value="<?=$query_arguments?>" size="25"><br \>
    <div style="text-align: right;"><span id="msg"></span></div>
</div>
<div>
    <form action="<?=$filename?>" method="POST">
        Current Member Count:<br />
        <input type="text" name="current_member_count" value="<?=$form_current_member_count?>">
        <br /><br />
        Goal Member Count:<br />
        <input type="text" name="goal_member_count" value="<?=$form_goal_member_count?>">
        <br /><br />
        Coffer Balance:<br />
        <input type="text" name="current_coffer_balance" value="<?=$form_current_coffer_balance?>">
        <br /><br />
        <input type="submit" value="Calculate">
    </form>
</div>
<br />
<?

if ($stopallcalc == true) {
    echo "<div>Are you trying to kill me? Calculate something realistic.</div>\n";
} else {
    if ($current_member_count) { echo "<div>Next invite cost: $next_invite_cost C-Bills (".(cBillsToDollarCalc($next_invite_cost, true)).")</div>\n"; }
    if ($goal_member_count) { echo "<div>Goal's ".(ordinal($goal_member_count + 1))." member invite cost: $goal_next_invite_cost C-Bills (".(cBillsToDollarCalc($goal_next_invite_cost, true)).")</div>\n"; }
    if ($current_member_count && $current_coffer_balance) { echo "<div>You can afford this many more invites: $buyable_members (With $cbills_left C-Bill".(plural($cbills_left))." left)</div>\n"; }
    if ($current_member_count && $goal_member_count) { echo "<div>Total C-Bills required for goal: $goal_cbills_needed C-Bills (".(cBillsToDollarCalc($goal_cbills_needed, true)).")</div>\n"; }
    if ($current_member_count && $goal_member_count && $current_coffer_balance) { echo "<div>C-Bills you still need: $goal_cbills_needed_after_spending_coffer C-Bills (".(cBillsToDollarCalc($goal_cbills_needed_after_spending_coffer, true)).")</div>\n"; }
    if ($current_member_count && $goal_member_count && $current_coffer_balance) { echo "<div>Each member should donate: $goal_cbills_needed_after_spending_coffer_divided_amongst_members C-Bills (".(cBillsToDollarCalc($goal_cbills_needed_after_spending_coffer_divided_amongst_members, true)).")</div>\n"; }
    echo "<br \>\n";
    if ($current_coffer_balance) { echo "<div>Equivalent coffer balance in dollars: ".(cBillsToDollarCalc($current_coffer_balance, true))."</div>\n"; }
    $end = microtime(true); //Stop page load counter
    $page_load_time = "Page loaded in ".(round($end - $start, 6))." seconds";
}
?>
<div style="position: relative">
    <p style="position: fixed; bottom: 0; width:100%; text-align: center"><?=$page_load_time?></p>
</div>
<script src="urlcopy.js"></script>
</body>
</html>
