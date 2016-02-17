<?php

// hier müssen alle Repos eingetragen werden, die dieses Skript verarbeiten kann:
if ($_REQUEST['repo'] == 'ringdat-online')
  chdir("C:\\xampp\\htdocs\\lvu\\tests\\PhantomJS");

// GIT verbieten Nutzer nach Eingaben zu fragen:
putenv("GIT_ASK_YESNO=false");

// Das folgende geht, weil ein Nutzer �ber den Cred Helper f�r den Nutzer des Webservers eingetragen wurde.
$iErrorLevel = 0;
$sDatei = "Alter_des_Masterbranches.txt";
if(!file_exists($sDatei))
  $iErrorLevel = 2;
else {
  $data = json_decode(file_get_contents('php://input'), true);
  if ($data['ref'] == 'refs/heads/reviewed-code-for-screenshots') {
    file_put_contents($sDatei, strftime('%c'));
    file_put_contents($sDatei, print_r($data, true), FILE_APPEND);
    $iStamp = 0;
    foreach($data['commits'] as $aCommit) {
      $iStamp = max($iStamp, strtotime($aCommit['timestamp']));
    }
    if ($iStamp)
      touch($sDatei, $iStamp);
    `curl.exe -o - "http://localhost/run_project.php?project=RingDat_Online.IBBL&run=1"`;
    #sleep(5);
    #`curl.exe -o - "http://localhost/run_project.php?project=RingDat_Online.InstitutEignungspruefung&run=1"`;
  }
}

if ($iErrorLevel > 0) {
  $sSubj = basename(__FILE__) . ': error';
  $sMsg = "An error occured while running the file " .
    __FILE__ . ":\n\n" .
    $sCmd . "\n\n" . $result . "\n\n" .
    "Repo: $_REQUEST[repo] - " . `echo %cd%`;

  $sHeader = "From: " . basename(__FILE__) . '@quodata.de';
  $aRecip = array(
    'Blaeul@quodata.de',
    'Oertel@quodata.de',
    'Sgorzaly@quodata.de',
    'Pham@quodata.de',
  );
  $sMsg .= "\nWeitere Details gibt es unter https://git04.quodata.de/it/$_REQUEST[repo].\n\n";
  $sMsg .= "Diese E-Mail ging an " . str_replace("@quodata.de", "", join(', ', $aRecip)) . ".\n";
  foreach($aRecip as $sTo) {
    mail($sTo, $sSubj, $sMsg, $sHeader);
  }
}
die($iErrorLevel);