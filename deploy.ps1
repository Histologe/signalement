$forAll=$args[0]
if($forAll -eq "--all")
{
    $projects =  (Get-ChildItem -Path ../ -exclude *.idea,DEMO, DEMO_TURBO).Name
}
elseif($forAll -eq "--bdr")
{
   $projects =  @('BDR')
} else {
    $projects =  (Get-ChildItem -Path ../ -exclude *.idea,DEMO, BDR, DEMO_TURBO,AM,CRZ).Name
}
$cmdOutput = git diff --name-only
$currentLocation = Get-Location;
$date = Get-Date -Format "dd-MM-yyyy"
foreach($project in $projects)
{
    Write-Output ("Update $project...")
    <# foreach($file in $cmdOutput)
    {
        if(Test-Path -Path "./$file")
            {
                Copy-Item  -Path "./$file" -Destination "../$project/$file" -force
                Write-Output ("Copy $file to ../$project/$file")
            } else {
                Remove-Item -Path "../$project/$file" -force
            }
    } #>
    Write-Output ("Push $project updates to server...")
    Set-Location ../$project
    git add .;git commit -m "Update/Deploy - $date";git ftp push;
    Set-Location $currentLocation
    Write-Output ("$project updated")
}
if($forAll -eq "--all")
{
    git ftp push
}
# cd ../AM && sudo php bin/console cache:clear;
# cd ../AHP && sudo php bin/console cache:clear;
# cd ../BDR && sudo php bin/console cache:clear;
# cd ../DEMO && sudo php bin/console cache:clear;
# cd ../HG && sudo php bin/console cache:clear;
# cd ../MEL && sudo php bin/console cache:clear;
# cd ../PAU && sudo php bin/console cache:clear;
# cd ../S\&L && sudo php bin/console cache:clear;
# cd ../VDG && sudo php bin/console cache:clear;