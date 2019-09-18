<? View::show_header('Login'); ?>
    <span id="no-cookies" class="hidden warning">You appear to have cookies disabled.<br /><br /></span>
    <noscript><span class="warning"><?=SITE_NAME?> requires JavaScript to function properly. Please enable JavaScript in your browser.</span><br /><br /></noscript>
    <p style='max-width: 600px; font-family: "Helvetica Neue",Helvetica,Arial,sans-serif;'>
        Welcome Home!
        <br /><br />
        If you had an account on Apollo at the time of the backup (June 2017),<br />please use the Recovery page to restore your account.
        <br /><br />
        If you are unsure if you are in the backup or not, use the Recovery page.
        <br /><br />
        If you had an account on Apollo but you signed up after the backup date, you can use either the Referral page (if you are on PTP, BTN, MTV or EMP) or the Recovery page.
        <br /><br />
        If you did not have an account on Apollo but you would like to join Orpheus, and you are on PTP, BTN, MTV or EMP, feel free to use the Referral page and join!
        <br /><br />
        See you on the other side.
    </p>
    <br />
<?
if (strtotime($BannedUntil) < time()) {
?>
    <form class="auth_form" name="login" id="loginform" method="post" action="login.php">
<?

    if (!empty($BannedUntil) && $BannedUntil != '0000-00-00 00:00:00') {
        $DB->query("
            UPDATE login_attempts
            SET BannedUntil = '0000-00-00 00:00:00', Attempts = '0'
            WHERE ID = '".db_string($AttemptID)."'");
        $Attempts = 0;
    }
    if (isset($Err)) {
?>
    <span class="warning"><?=$Err?><br /><br /></span>
<?    } ?>
<?    if ($Attempts > 0) { ?>
    You have <span class="info"><?=(6 - $Attempts)?></span> attempts remaining.<br /><br />
    <strong>WARNING:</strong> You will be banned for 6 hours after your login attempts run out!<br /><br />
<?    } ?>
<?    if (isset($_GET['invalid2fa'])) { ?>
    <span class="warning">You have entered an invalid two-factor authentication key. Please login again.</span>
<?    } ?>
    <table class="layout">
        <tr>
            <td>Username&nbsp;</td>
            <td colspan="2">
                <input type="text" name="username" id="username" class="inputtext" required="required" maxlength="20" pattern="[A-Za-z0-9_?\.]{1,20}" autofocus="autofocus" placeholder="Username" />
            </td>
        </tr>
        <tr>
            <td>Password&nbsp;</td>
            <td colspan="2">
                <input type="password" name="password" id="password" class="inputtext" required="required" pattern=".{6,}" placeholder="Password" />
            </td>
        </tr>
        <tr>
            <td></td>
            <td>
                <input type="checkbox" id="keeplogged" name="keeplogged" value="1"<?=(isset($_REQUEST['keeplogged']) && $_REQUEST['keeplogged']) ? ' checked="checked"' : ''?> />
                <label for="keeplogged">Remember me</label>
            </td>
            <td><input type="submit" name="login" value="Log in" class="submit" /></td>
        </tr>
    </table>
    </form>
<?
} else {
?>
    <span class="warning">You are banned from logging in for another <?=time_diff($BannedUntil)?>.</span>
<?
}

if ($Attempts > 0) {
?>
    <br /><br />
    Lost your password? <a href="login.php?act=recover" class="tooltip" title="Recover your password">Recover it here!</a>
<?
}
?>
<script type="text/javascript">
cookie.set('cookie_test', 1, 1);
if (cookie.get('cookie_test') != null) {
    cookie.del('cookie_test');
} else {
    $('#no-cookies').gshow();
}
window.onload = function() {document.getElementById("username").focus();};
</script>
<? View::show_footer();
