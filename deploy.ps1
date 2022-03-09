$forAll=$args[0]
if($forAll -eq "--all")
{
    $projects =  (Get-ChildItem -Path ../ -exclude *.idea).Name
} else {
    $projects =  (Get-ChildItem -Path ../ -exclude *.idea, BDR).Name
}
$cmdOutput = git diff --name-only # or: $cmdOutput = @(<command>)
$currentLocation = Get-Location;
$date = Get-Date -Format "dd-MM-yyyy"
foreach($project in $projects)
{
    Write-Output ("Update $project...")
    foreach($file in $cmdOutput)
    {
        if(Test-Path -Path "./$file")
            {

                Copy-Item  -Path "./$file" -Destination "../$project/$file" -force
                Write-Output ("Copy $file to ../$project/$file")
            }
    }
    Write-Output ("Push $project updates to server...")
    Set-Location ../$project
    git add .;git commit -m "Update/Deploy - $date";git ftp push;
    Set-Location $currentLocation
    Write-Output ("$project updated")
}
git push