<?php
// / -----------------------------------------------------------------------------------
// / Copyright Information ...
// / HRProprietary Engine, Copyright on 11/13/2026 by Justin Grimes, www.github.com/zelon88
// /
// / License Information ...
// / This project is protected by the GNU GPLv3 Open-Source license.
// / https://www.gnu.org/licenses/gpl-3.0.html
// /
// / File Information ...
// / v4.2.0.
// / The Email Core, one of the Engine's cores.
// / It sends the emails the Authentication Core asks for, through the host's own mail system & nothing else.
// / It never connects to a mail server itself, so it makes no third party connection of its own accord.
// / The host's mail system is whatever PHP's sendmail_path names, & a host without one sends nothing & says so.
// / The text of each email is the Engine's default, replaced by the application's contract & then by the administrator's config.php.
// / The application pins it exactly via $RequiredEmailCoreVersion, & the Engine loads it with the Authentication Core.
// / Error block 43100 through 43119 is reserved for the Email Core, & 43100 is raised here.
// / See Documentation/ABOUT_AUTHENTICATION.txt.
// /
// / <3 Open-Source
// / -----------------------------------------------------------------------------------


// / -----------------------------------------------------------------------------------
// / A component may only be loaded by an application.
// / quickDie is not available here, because a component loaded directly has no core to
// / provide it. This is the one documented exception to quickDie being the only halt.
// / It names the Engine, as every other Engine core does.
if (!isset($CoreLoaded) or $CoreLoaded !== TRUE) die('ERROR!!! Engine-35000, A core component cannot be loaded directly!'.PHP_EOL);
// / -----------------------------------------------------------------------------------


// / -----------------------------------------------------------------------------------
// / The version of this component. Read by the application without executing this file.
$EmailCoreVersion = 'v4.2.0';
// / -----------------------------------------------------------------------------------


// / -----------------------------------------------------------------------------------
// / A function to read what the application & its administrator say about email.
// / Accepts nothing. Returns the settings, keyed by what this core calls them.
// / Each text is the administrator's when config.php sets one, the contract's when it does not, & this core's own otherwise.
// / This core's own text cannot be overwritten by configuration, so an email always has something to say.
// / The sender is the administrator's, & an empty sender leaves the From line to the host's mail system.
function emailSettings() {
  // / Set variables.
  global $EngineEmailConfirmationSubject, $EngineEmailConfirmationBody, $EngineEmailRecoverySubject, $EngineEmailRecoveryBody, $EnableMemoryProtection;
  $EmailSettings = array();
  $defaultConfirmationSubject = '{APPLICATION}: confirm your account';
  $defaultConfirmationBody = "Hello {USERNAME},\n\nYour confirmation code for {APPLICATION} is {CODE}\n\nIt can be used for {MINUTES} minutes.\n";
  $defaultRecoverySubject = '{APPLICATION}: recover your account';
  $defaultRecoveryBody = "Hello {USERNAME},\n\nYour recovery code for {APPLICATION} is {CODE}\n\nIt can be used for {MINUTES} minutes.\n";
  $contractConfirmationSubject = (isset($EngineEmailConfirmationSubject) && is_string($EngineEmailConfirmationSubject) && trim($EngineEmailConfirmationSubject) !== '') ? $EngineEmailConfirmationSubject : $defaultConfirmationSubject;
  $contractConfirmationBody = (isset($EngineEmailConfirmationBody) && is_string($EngineEmailConfirmationBody) && trim($EngineEmailConfirmationBody) !== '') ? $EngineEmailConfirmationBody : $defaultConfirmationBody;
  $contractRecoverySubject = (isset($EngineEmailRecoverySubject) && is_string($EngineEmailRecoverySubject) && trim($EngineEmailRecoverySubject) !== '') ? $EngineEmailRecoverySubject : $defaultRecoverySubject;
  $contractRecoveryBody = (isset($EngineEmailRecoveryBody) && is_string($EngineEmailRecoveryBody) && trim($EngineEmailRecoveryBody) !== '') ? $EngineEmailRecoveryBody : $defaultRecoveryBody;
  $administratorSender = applicationSetting('EmailSender', '');
  $administratorConfirmationSubject = applicationSetting('EmailConfirmationSubject', '');
  $administratorConfirmationBody = applicationSetting('EmailConfirmationBody', '');
  $administratorRecoverySubject = applicationSetting('EmailRecoverySubject', '');
  $administratorRecoveryBody = applicationSetting('EmailRecoveryBody', '');
  $EmailSettings = array(
    'Sender' => is_string($administratorSender) ? trim($administratorSender) : '',
    'confirm' => array(
      'Subject' => (is_string($administratorConfirmationSubject) && trim($administratorConfirmationSubject) !== '') ? $administratorConfirmationSubject : $contractConfirmationSubject,
      'Body' => (is_string($administratorConfirmationBody) && trim($administratorConfirmationBody) !== '') ? $administratorConfirmationBody : $contractConfirmationBody),
    'recover' => array(
      'Subject' => (is_string($administratorRecoverySubject) && trim($administratorRecoverySubject) !== '') ? $administratorRecoverySubject : $contractRecoverySubject,
      'Body' => (is_string($administratorRecoveryBody) && trim($administratorRecoveryBody) !== '') ? $administratorRecoveryBody : $contractRecoveryBody));
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $defaultConfirmationSubject, $defaultConfirmationBody, $defaultRecoverySubject, $defaultRecoveryBody, $contractConfirmationSubject, $contractConfirmationBody, $contractRecoverySubject, $contractRecoveryBody, $administratorSender, $administratorConfirmationSubject, $administratorConfirmationBody, $administratorRecoverySubject, $administratorRecoveryBody);
  return $EmailSettings; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to say whether this host can hand an email to a mail system.
// / Accepts nothing. Returns whether it can & the reason it cannot, in that order.
// / PHP hands every email to the program sendmail_path names, so a host where that program is missing sends nothing.
// / The program is the first word of the setting, because the rest are its arguments.
function emailIsAvailable() {
  // / Set variables.
  global $EnableMemoryProtection;
  $EmailIsAvailable = FALSE;
  $EmailReason = '';
  $sendmailPath = $sendmailProgram = '';
  $sendmailParts = array();
  if (!function_exists('mail')) $EmailReason = 'PHP was built without its mail function.';
  else {
    $sendmailPath = trim((string)ini_get('sendmail_path'));
    $sendmailParts = preg_split('/\s+/', $sendmailPath);
    $sendmailProgram = (is_array($sendmailParts) && isset($sendmailParts[0])) ? (string)$sendmailParts[0] : '';
    if ($sendmailProgram === '') $EmailReason = 'PHP names no mail program in sendmail_path.';
    else if (!is_file($sendmailProgram) or !is_executable($sendmailProgram)) $EmailReason = 'The mail program '.$sendmailProgram.' that PHP names in sendmail_path is not installed. Install a mail transfer agent that provides it.';
    else $EmailIsAvailable = TRUE; }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $sendmailPath, $sendmailProgram, $sendmailParts);
  return array($EmailIsAvailable, $EmailReason); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to say whether a string may be used as an email address.
// / Accepts the address. Returns TRUE when it may.
// / It is checked rather than sanitized, because sanitize() removes the @ every address has, & an address it changed would be someone else's.
// / Only the plain form is accepted, with no display name, no comment, no quotes & nothing that could end a header line.
function emailAddressIsUsable($emailAddress) {
  // / Set variables.
  global $EnableMemoryProtection;
  $AddressIsUsable = FALSE;
  $candidateAddress = is_string($emailAddress) ? $emailAddress : '';
  if (strlen($candidateAddress) >= 6 && strlen($candidateAddress) <= 254 && preg_match('/^[A-Za-z0-9._%+-]{1,64}@[A-Za-z0-9-]+(\.[A-Za-z0-9-]+)*\.[A-Za-z]{2,63}$/', $candidateAddress) === 1 && filter_var($candidateAddress, FILTER_VALIDATE_EMAIL) !== FALSE) $AddressIsUsable = TRUE;
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $candidateAddress, $emailAddress);
  return $AddressIsUsable; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to fill in the names an email's text may carry.
// / Accepts the text & the values, keyed by name without braces. Returns the text with each name replaced.
// / A name the values do not carry is left as it is, so a mistake in the text shows in the email rather than vanishing.
// / A value never carries a line break or a brace, so a value cannot add a line to a header or name another value.
function renderEmailTemplate($templateText, $templateValues) {
  // / Set variables.
  global $EnableMemoryProtection;
  $RenderedText = '';
  $replacements = array();
  $valueName = $valueText = '';
  if (is_array($templateValues)) foreach ($templateValues as $valueName => $valueText) $replacements['{'.strtoupper((string)$valueName).'}'] = str_replace(array("\r", "\n", '{', '}'), '', (string)$valueText);
  $RenderedText = strtr((string)$templateText, $replacements);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $replacements, $valueName, $valueText, $templateText, $templateValues);
  return $RenderedText; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to send one email through the host's mail system.
// / Accepts the recipient, the purpose & the values its text names. Returns whether it was handed over & the reason it was not, in that order.
// / The purpose is confirm or recover, & picks the subject & the text from emailSettings().
// / The email is plain text in UTF-8, & a subject that is not plain ASCII is encoded as MIME requires.
// / A host that cannot send is a warning, because nothing is wrong with HRConvert2 & the administrator chose the host.
// / A mail system that refuses an email it was handed is error 43100, because a person is now waiting for a code that will not come.
// / The address is never written to the log, & the account the values name is.
function sendEngineEmail($recipientAddress, $emailPurpose, $templateValues) {
  // / Set variables.
  global $EnableMemoryProtection;
  $EmailWasSent = FALSE;
  $EmailReason = '';
  $emailSettings = $headerLines = array();
  $emailSubject = $emailBody = $senderAddress = $accountLabel = $hostReason = '';
  $hostCanSend = $mailWasAttempted = FALSE;
  $emailSettings = emailSettings();
  $accountLabel = (is_array($templateValues) && isset($templateValues['USERNAME'])) ? str_replace(array("\r", "\n"), '', (string)$templateValues['USERNAME']) : 'an account';
  list ($hostCanSend, $hostReason) = emailIsAvailable();
  if (!isset($emailSettings[$emailPurpose])) $EmailReason = 'There is no email for '.$emailPurpose.'.';
  else if (!emailAddressIsUsable($recipientAddress)) $EmailReason = 'The address of '.$accountLabel.' cannot be used.';
  else if (!$hostCanSend) $EmailReason = $hostReason;
  else {
    $emailSubject = str_replace(array("\r", "\n"), ' ', renderEmailTemplate($emailSettings[$emailPurpose]['Subject'], $templateValues));
    if (preg_match('/[^\x20-\x7E]/', $emailSubject) === 1) $emailSubject = '=?UTF-8?B?'.base64_encode($emailSubject).'?=';
    $emailBody = str_replace(array("\r\n", "\r"), "\n", renderEmailTemplate($emailSettings[$emailPurpose]['Body'], $templateValues));
    $headerLines = array('MIME-Version: 1.0', 'Content-Type: text/plain; charset=UTF-8', 'Content-Transfer-Encoding: 8bit');
    $senderAddress = (string)$emailSettings['Sender'];
    if ($senderAddress !== '' && emailAddressIsUsable($senderAddress)) $headerLines[] = 'From: '.$senderAddress;
    else if ($senderAddress !== '') warningEntry('The email sender '.$senderAddress.' set by --Email Sender-- cannot be used, so the host\'s mail system names the sender.');
    $mailWasAttempted = TRUE;
    $EmailWasSent = (bool)@mail($recipientAddress, $emailSubject, $emailBody, implode("\r\n", $headerLines));
    if ($EmailWasSent) logEntry('The '.$emailPurpose.' email for '.$accountLabel.' was handed to the host\'s mail system.');
    else {
      $EmailReason = 'The host\'s mail system refused it.';
      errorEntry('The host\'s mail system refused the '.$emailPurpose.' email for '.$accountLabel.'!', 43100, FALSE); } }
  // / An email that was never handed over is a warning, & one the mail system refused was raised above.
  if (!$EmailWasSent && !$mailWasAttempted) warningEntry('The '.$emailPurpose.' email for '.$accountLabel.' was not sent. '.$EmailReason);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $emailSettings, $headerLines, $emailSubject, $emailBody, $senderAddress, $accountLabel, $hostReason, $hostCanSend, $mailWasAttempted, $recipientAddress, $emailPurpose, $templateValues);
  return array($EmailWasSent, $EmailReason); }
// / -----------------------------------------------------------------------------------
?>
