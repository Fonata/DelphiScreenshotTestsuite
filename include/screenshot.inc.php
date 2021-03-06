<?php
require_once __DIR__ . '/../include/config.inc.php';

function compareFiles($sFileSoll, $sFileIst, &$retval) {
    $sTime = date('Y-m-d H:i:s', filemtime($sFileIst));
    if (filesize($sFileSoll) != filesize($sFileIst) || file($sFileSoll) != file($sFileIst)) {
        $retval['desc'] = LANG == 'de' ? "Es gibt Unterschiede" : "There are differences";
        $retval['status'] = 0;
        if (file_exists($sFileSoll . "2")) {
            $bIdentical = compareFiles($sFileSoll . "2", $sFileIst, $retval);
            $retval['desc'] .= ' [Alternative]';
            return $bIdentical;
        }
        return false;
    }
    else {
        $retval['desc'] = LANG == 'de' ? "Bilder stimmen &uuml;berein" : "Screenshots are equal";
        $retval['status'] = 1;
        return true;
    }
}

function compareImages($image1, $image2) {
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        $sCompare = '"C:\\Program Files\\ImageMagick-6.8.9-Q16\\compare.exe"';
    } else {
        $sCompare = 'compare';
    }

    $sCmd = "$sCompare -metric RMSE \"$image1\" \"$image2\" NULL:";
    $response = `$sCmd 2>&1`;
    return $response === '0 (0)';
}

function compareAllTestFiles($project) {
    $path = "Bilder/$project";
    $files = glob("$path/*-ist.png");
    foreach ($files as $sFileIst) {
        $sStem = substr($sFileIst, 0, -8);
        $sFileSoll = $sStem . '-soll.png';
        if (file_exists($sFileSoll))
          createDifferenceImage($sFileIst, $sFileSoll, $sStem);
    }
}

function updateAllTestStatus($test, $projekt) {
    // Ist-Zustand als neuen Sollwert f�r alle gleichen Unterschiede abspeichern
    $path = "Bilder/$projekt";
    $sStem = substr($test, 0, -8);
    $differenceFile = "$sStem-difference.png";
    $files = glob("$path/*-difference.png");
    foreach ($files as $sFileDifference) {
        if (compareImages($differenceFile, $sFileDifference)) {
            $sStem = substr($sFileDifference, 0, -15);
            copy("$sStem-ist.png", "$sStem-soll.png");
        }
    }
}

function createDifferenceImage($sFileIst, $sFileSoll, $sStem) {
    global $sCmd;

    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        $sCompare = '"C:\\Program Files\\ImageMagick-6.8.9-Q16\\compare.exe"';
    } else {
        $sCompare = 'compare';
    }

    if (file_exists("$sStem-difference.png")) {
        $iTimeD = filemtime("$sStem-difference.png");
        $iTimeI = filemtime($sFileIst);
        $iTimeS = filemtime($sFileSoll);
        if ($iTimeD > $iTimeI && $iTimeD > $iTimeS)
            return '';

        // Unterschiede sind veraltet
        unlink("$sStem-difference.png");
    }

    $aSizeIst = getimagesize($sFileIst);
    $aSizeSoll = getimagesize($sFileSoll);

    // Diese Bilder werden am Ende der Funktion wieder gelöscht.
    $aTempImages = array();

    if ($aSizeIst[0] !== $aSizeSoll[0] || $aSizeIst[1] !== $aSizeSoll[1]) {
        $aRect = array(
          "width" => min($aSizeIst[0], $aSizeSoll[0]),
          "height" => min($aSizeIst[1], $aSizeSoll[1]),
          "x" => 0,
          "y" => 0,
        );
        $img = imagecrop($imgIst = imagecreatefrompng($sFileIst), $aRect);
        $sFileIst = "$sFileIst.cropped.png";
        $aTempImages[] = $sFileIst;
        imagepng($img, $sFileIst);

        $img = imagecrop($imgSoll = imagecreatefrompng($sFileSoll), $aRect);
        $sFileSoll = "$sFileSoll.cropped.png";
        $aTempImages[] = $sFileSoll;
        imagepng($img, $sFileSoll);

    }

    $sCmd = "$sCompare -compose src \"$sFileIst\" \"$sFileSoll\" \"$sStem-difference.png\"";
    $sOutput = `$sCmd 2>&1`;
    if ($aSizeIst[1] < $aSizeSoll[1]) {
        $aRect['y'] = $aSizeSoll[1] - $aSizeIst[1];
        $img = imagecrop($imgSoll, $aRect); // Unterkanten bündig
        $sFileSoll .= "2.png";
        imagepng($img, $sFileSoll);
        $aTempImages[] = $sFileSoll;

        $sCmd = "$sCompare -compose src \"$sFileIst\" \"$sFileSoll\" \"$sStem-difference2.png\"";
        $sOutput = `$sCmd 2>&1`;
        if (filesize("$sStem-difference2.png") <  filesize("$sStem-difference.png")) {
           copy("$sStem-difference2.png", "$sStem-difference.png");
        }
        $aTempImages[] = "$sStem-difference2.png";
    }
    if ($aSizeIst[1] > $aSizeSoll[1]) {
        $aRect['y'] = $aSizeIst[1] - $aSizeSoll[1];
        $img = imagecrop($imgIst, $aRect); // Unterkanten bündig
        $sFileIst .= "2.png";
        imagepng($img, $sFileIst);
        $aTempImages[] = $sFileIst;

        $sCmd = "$sCompare -compose src \"$sFileIst\" \"$sFileSoll\" \"$sStem-difference2.png\"";
        $sOutput = `$sCmd 2>&1`;
        if (filesize("$sStem-difference2.png") <  filesize("$sStem-difference.png")) {
           copy("$sStem-difference2.png", "$sStem-difference.png");
        }
        $aTempImages[] = "$sStem-difference2.png";
    }
    foreach ($aTempImages as $sTempImage) {
        unlink($sTempImage);
    }
    return $sOutput;
}

function backupScreenshots() {
    $date = date("Y-m-d");
    $project = $_REQUEST['project'];

    $backup = "Bilder/$project/backup_$date";
    if (file_exists($backup))
        // es gibt bereits ein Backup - es wird maximal 1 Backup pro Tag angelegt
        return;

    if (!mkdir($backup, 0777, true))
        die(__FILE__ . ": Error creating backup dir $backup!");

    foreach (glob("Bilder/$project/*-soll.*") as $file)
        copy($file, "$backup/" . basename($file));
}

function handleActions(&$retval) {
    $bCheckedInIndexList = isset($_POST['check']) && in_array(urlencode($retval['name']), $_POST['check']);
    if (isset($_REQUEST['done'])) {
        if ($_REQUEST['done'] == $retval['name'] || $bCheckedInIndexList) {
            backupScreenshots();
            // Taste "A"
            $alt = empty($_REQUEST['alternative']) ? '' : '2';
            copy($retval['fileIst'], $retval['fileSoll'] . $alt);
        }
    }
    if (isset($_REQUEST['doneAll']) && ($_REQUEST['doneAll'] == $retval['name'] || $bCheckedInIndexList)) {
        // Taste "B"
        backupScreenshots();
        set_time_limit(600);
        session_write_close(); // damit andere Skripte des selben Browsers nicht blockiert werden
        compareAllTestFiles($_REQUEST['project']);
        updateAllTestStatus($_REQUEST['doneAll'], $_REQUEST['project']);
    }
    if (isset($_REQUEST['discard']) && ($_REQUEST['discard'] == $retval['name'] || $bCheckedInIndexList)) {
        // Taste "C": In Papierkorb verschieben
        exec('"C:\\Program Files\\AutoHotkey\\AutoHotkey.exe" ..\include\moveFileToRecycleBin.ahk "' . $retval['fileIst'] . '"');

        $retval['desc'] = "Test wurde gelöscht";
        $retval['status'] = 1;
        return $retval;
    }

    if (isset($_REQUEST['soll_no_longer_needed']) && ($_REQUEST['soll_no_longer_needed'] == $retval['name'] || $bCheckedInIndexList)) {
        // Taste "D": In Papierkorb verschieben
        backupScreenshots();
        exec('"C:\\Program Files\\AutoHotkey\\AutoHotkey.exe" ..\include\moveFileToRecycleBin.ahk "' . $retval['fileSoll'] . '"');

        $retval['desc'] = "Solldatei wurde gelöscht";
        $retval['status'] = 1;
        return $retval;
    }
}

function addRtfLink(&$retval) {
    if (in_array($retval['ext'], array('txt', 'rtf', 'html', 'xml', 'js'))) {
        $sContent = file_get_contents($retval['fileIst']);
        if (substr($sContent, 0, 5) == '{\rtf') {
            $retval['sRtfLink'] = "<a href='rtf.php?file=" . urlencode($retval['fileIst']) . "'>in Word öffnen</a>";
        }
    }
}

function getScreenshotStatus($sTestName = 'download-seite') {
    global $iExeTime, $sExePath;

    $sExt = pathinfo($sTestName, PATHINFO_EXTENSION);
    $sStem = substr($sTestName, 0, -5-strlen($sExt));
    if (stristr($sExt, 'bmp')) {
        if (!file_exists("$sStem-ist.$sExt")) {
            header('Location: /details.php?' . substr(http_build_query($_GET), 0, -3) . 'png');
        }
    }

    $sFileIst = "$sStem-ist.$sExt";
    if (strlen($sFileIst) + 25 >= PHP_MAXPATHLEN && !file_exists($sFileIst)) {
        // kann passieren, wenn der Pfad länger als MAX_PATH=255 wird
        $sPathOrig = getcwd();
        chdir(dirname($sStem));
        $sStem = basename($sStem);
    }


    $sFileIst = "$sStem-ist.$sExt";
    $sFileSoll = "$sStem-soll.$sExt";

    if (stristr($sExt, 'bmp') || stristr($sExt, 'pdf')) {
        require_once('../include/convertToPngIfNeeded.inc.php');
        set_time_limit(120);
        $sFileIst = convertToPngIfNeeded("$sStem-ist", $sExt);
        $sFileSoll = convertToPngIfNeeded("$sStem-soll", $sExt);
    }

    $retval = array();
    $retval['iWouldBeStatus'] = '-x';
    $retval['fileIst'] = $sFileIst;
    $retval['fileSoll'] = $sFileSoll;
    $retval['ext'] = strtolower($sExt);
    $retval['name'] = $sTestName;
    $retval['title'] = basename($sTestName);

    addRtfLink($retval);

    if (!handleActions($retval)) {
        if (!file_exists($sFileSoll)) {
            $retval['desc'] = LANG == 'de' ? "Soll-Datei existiert noch nicht" : "Currently no target state file";
            $retval['status'] = 0;
            $retval['sollTime'] = '';
        }
        else {
            $retval['sollTime'] = date(DATE_RSS, filemtime($sFileSoll));
            compareFiles($sFileSoll, $sFileIst, $retval);
        }

        $iIstTime = filemtime($sFileIst);
        $retval['istTime'] = date(DATE_RSS, $iIstTime);

        global $sDoneFile;

        if (file_exists($sDoneFile) && filemtime($sDoneFile) > $iIstTime) {
            // Das Änderungsdatum des DoneFiles ist vom Start des Tests
            $retval['desc'] .= "; Ist-Datei wurde nicht während des letzten Tests angelegt";
            $retval['iWouldBeStatus'] = -1;
            $retval['status'] = 0;
        } else if ($iIstTime < $iExeTime) {
            $retval['desc'] .= "; Ist-Datei kommt nicht von aktueller " . basename($sExePath);
            $retval['iWouldBeStatus'] = $retval['status'];
            $retval['status'] = 0;
        }

    }


    if (!empty($sPathOrig)) {
        chdir($sPathOrig);
    }

    return $retval;
}
