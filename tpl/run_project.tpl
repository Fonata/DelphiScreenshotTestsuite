{if $aProject.cmd}
  {* assign "sFrage" "Möchtest du wirklich alle Ist-Zustäde  verwerfen und neu erstellen?" *}
  {assign "sFrage" ""}
  &nbsp;
  <a
    onclick="return confirm('Möchtest du wirklich alle Ist-Zustäde von {$aProject.title} verwerfen und neu erstellen?')"
    href="run_project.php?project={$aProject.title|urlencode}&run=1"
    {if $Sprache=='de'}
        title="Startet {$aProject.cmd|escape}">Screenshots neu erstellen</a>
    {else}
        title="Startet {$aProject.cmd|escape}">Create new screenshots</a>
    {/if}
{/if}
