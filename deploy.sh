#!/bin/bash
files=`git diff --name-only | grep -E '.php$' `
projects=`ls ../ `
for project in $projects; do
     echo  "Updating $project..."
  for file in $files; do
   echo  "cp ../DEMO/$file ../$project/$file"
  done
     echo  "End of update"
done
