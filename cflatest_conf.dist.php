<?PHP
$projects = array(
    array(
        //Friendly name for your mod, not used internally.
        "name"=>"ModName",
        //The CurseForge URL to your project no trailing slash
        "url"=>"https://minecraft.curseforge.com/projects/ModName",
        //Regex to get the version from the jar on curseforge
        //Make sure that the version is in the first capturegroup
        //This Would match "ModName-MC1.10.2-1.0.1.jar" and extract "1.0.1"
        "regex"=>"ModName-.+-(.+)\.jar",
        //no .json here!
        "cachefile"=>"modname"
    ),
    //Keep adding arrays here for more mods.
);