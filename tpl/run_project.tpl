{if $aProject.cmd}
  {* assign "sFrage" "M�chtest du wirklich alle Ist-Zust�de  verwerfen und neu erstellen?" *}
  {assign "sFrage" ""}
  &nbsp;
  <a
    onclick="return confirm('M�chtest du wirklich alle Ist-Zust�de von {$aProject.title} verwerfen und neu erstellen?')"
    href="run_project.php?project={$aProject.title|urlencode}&run=1"
    title="Startet {$aProject.cmd|escape}">Screenshots neu erstellen</a>
{/if}
