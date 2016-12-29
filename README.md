# Drupal.org DOM Parser
Retrieves information about a specified project from Drupal.org.

## Available information
* D8 stability (E.g. 'full release' or 'beta')
* Latest D8 usage statistic
* All usage statistics as an array
* Whether or not there is recommended D8 release
* Whether or not there is a full D8 release
* Whether or not the project is actively maintained
* Human-readable name of the project

## Common usage

````
    $project_name = 'ctools';
    $project = new Balsama\DrupalOrgProject\Stats($project_name);
    
    // Gets the human-readbale name of the project:
    $name = $project->getHumanReadableName();
    
    // Gets the stability of the D8 version. E.g. full release or beta:
    $stability = $project->getD8Stability();
    
    // Gets the most recent reported number of D8 installs:
    $usage = $project->getCurrentD8Usage();
````