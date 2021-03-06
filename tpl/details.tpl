{* Diese Seite zeigt den Vergleicht von genau ein Pärchen von Soll- und Ist-Zustand *}

{if $Sprache=='de'}
    {$Proj="Projektübersicht"}
    {$IstAcu="Ist"}
    {$SollTar="Soll"}
    {$UntDif="Unterschiede"}
    {$SollD="Soll-Datei existiert noch nicht"}
    {$IstZ="Ist-Zustand als neuen Sollwert abspeichern"}
    {$GleiU="Ist-Zustand als neuen Sollwert für alle gleichen Unterschiede abspeichern"}
    {$ZuVer="Ist-Zustand verwerfen"}
    {$SoVer="Soll-Zustand verwerfen"}
{else}
    {$Proj="Project Overview"}
    {$IstAcu="Actual"}
    {$SollTar="Target"}
    {$UntDif="Differences"}
    {$SollD="Currently no Target state file"}
    {$IstZ="Save actual state as new target state"}
    {$GleiU="Save actual state as new target state for all equal differences"}
    {$ZuVer="Discard actual state"}
    {$SoVer="Discard target state"}
{/if}



{include file="header.tpl" title=Details}

<div id='alt_langs'>
    {* wird in details.php gefüllt *}
    {foreach $aFlags as $sFlagLink}
        {$sFlagLink}
    {/foreach}
</div>

<div id="breadcrumbs">

    <a href='.'>{$Proj}</a> &#187;

    <a href='.?project={$project|urlencode}'>{$project}</a> &#187;
    {$aTest.title}
</div>

{function showDifferences}
    <div style='background:{$color}'><span class='label'>   {$label}:</span>
        {if in_array($aTest.ext, ['bmp', 'png'])}
            <img src="{$file|dirname}/{$file|basename|utf8_decode|rawurlencode|htmlentities}?{$time|urlencode}" title="{$label} {$time}">
        {else}
            {if in_array($aTest.ext, array('txt', 'rtf', 'csv', 'xls', 'html', 'xml', 'js')) }
                {$file = $file|utf8_decode}
                {if file_exists($file)}
                    <div class='iframe_container'>
                        {$aTest.sRtfLink|default}
                        {fetch file=$file assign="content"}
                        <textarea rows=21 cols=75 readonly="readonly">{$content|htmlentities}</textarea>
                    </div>
                    &nbsp; <a href="{$file|dirname}/{$file|basename|utf8_decode|rawurlencode|htmlentities}?{$time|urlencode}" title="{$label} {$time}" target=_blank>Download</a>
                {else}
                    <i>Datei '{$file}' wurde nicht gefunden.</i>
                {/if}
            {else}
                <div class='iframe_container'><iframe src="{$file}?{$sTime|urlencode}" title={$label}></iframe></div>
                {/if}
            {/if}
    </div>
{/function}

<b style='color:red'>{$aTest.title}: {$aTest.desc}</b>
{showDifferences color=red file=$aTest.fileIst|utf8_encode label=$IstAcu time=$aTest.istTime}


<script src="js/jquery-ui-1.10.4.custom.min.js"></script>
<script>
    $(function () {
        $(".iframe_container").resizable({
            helper: "ui-resizable-helper"
        });
    {include file="key_binding.inc.tpl"}
    });
</script>

{if file_exists($aTest.fileSoll)}

    {showDifferences color=green file=$aTest.fileSoll|utf8_encode label={$SollTar} time=$aTest.sollTime}

    {if in_array($aTest.ext, ['bmp', 'png'])}

        <span class='label'>{$UntDif}: </span>

        <div style='position:relative;display:inline-block;background-image:url("{$aTest.fileIst|utf8_encode|escape}?{$sTime|urlencode}")'>
            <img src='compare.php?sTestName={$aTest.name|urlencode}'  style='opacity:0.95' title=Unterschiede >
        </div>
    {/if}
    {if in_array($aTest.ext, array('txt', 'rtf', 'ini', 'lmo', 'csv', 'xls', 'html', 'xml', 'js')) }
        {* von diesen Dateitypen soll ein FineDiff angezeigt werden *}
        <span class='label'>{$UntDif}: </span>

        {php}
        global $aTest;
        include_once('../include/finediff.inc.php');
        $sIst = join('', file($aTest['fileIst']));
        $sSoll = join('', file($aTest['fileSoll']));
        $diff = new FineDiff($sSoll, $sIst);
        echo "<pre>" . $diff->renderDiffToHTML() . "</pre>";
        {/php}
    {/if}
{else}
    {$SollD}
{/if}
<br>
<br>

<form name="submit_comment" id="submit_comment" method="post">
    <div id='comment_textarea'>
        <label for="textarea" style="display: block">Write comment:</label>
        <textarea id="textarea" name="textarea" style="width: 406px; height: 156px; display: block;">{$sComment}</textarea>
        {if !empty($sTime)}
            <p>Comment-time: {$sTime}</p>
        {/if}
        <input type="submit" id="submit_button" name='save_button' value="Submit" />
    </div>
</form>

<!-- NEUE GITLAB-URLS BEI include/smarty.inc.php ANLEGEN!!! -->
{if isset($newGitLabIssueURL)}
<form id="new-issue" data-url="{$newGitLabIssueURL}">
  <fieldset>
    <legend>GitLab</legend>
    <input id="issue-title" placeholder="Titel">
    <input type="submit" value="Issue anlegen">
  </fieldset>
</form>
{/if}

<script>
    $("#new-issue").submit(function(e) {
        e.preventDefault();

        var title = "Screenshot-Test: " + $('#issue-title').val();
        var description = window.location.href;

        window.open($(this).data('url')
          + '?issue[title]=' + encodeURIComponent(title)
          + '&issue[label_ids][]=310' /* Screenshottest (Group) */
          + '&issue[description]=' + encodeURIComponent(description) )
    });
</script>

<div class='buttons' style='z-index:22'>
    <button id="done-button" href="done.php?done={$aTest.name|urlencode}&project={$project|urlencode}">
        A: {$IstZ}
    </button>
    {if $aTest.ext=='png' && file_exists($aTest.fileSoll)}
        <button id="done-button-alternative" title="speichert den Ist-Wert als Sollwert-Alternative" href="done.php?done={$aTest.name|urlencode}&project={$project|urlencode}&alternative=1">
            *
        </button>
        <button id="doneAll-button" href="done.php?doneAll={$aTest.name|urlencode}&project={$project|urlencode}">
            B: {$GleiU}
        </button>
    {/if}
    <button id="discard-button" onclick="if (confirm('M&ouml;chten Sie dieses Testergebnis (Ist-Zustand) wirklich l&ouml;schen?'))
            location.href = 'discard.php?discard={$aTest.name|urlencode}&project={$project|urlencode}';" style='opacity:0.9'>
        C: {$ZuVer}
    </button>
    {if file_exists($aTest.fileSoll)}
        <div id="soll_no_longer_needed-wrap">
            <button id="soll_no_longer_needed-button" onclick="if (confirm('M&ouml;chten Sie den Soll-Zustand wirklich l&ouml;schen? Das macht Sinn, wenn die neuste EXE keine Ist-Zust&auml;nde mit diesem Namen mehr produziert, oder der Sollzustand falsch ist.'))
                    location.href = 'soll_no_longer_needed.php?soll_no_longer_needed={$aTest.name|urlencode}&project={$project|urlencode}';"      >
                D: {$SoVer}
            </button>
        </div>
    {/if}
</div>

{include file="footer.tpl"}
