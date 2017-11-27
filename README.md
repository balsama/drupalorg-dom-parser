[![Build Status](https://travis-ci.org/balsama/drupalorg-dom-parser.svg?branch=1.0.x)](https://travis-ci.org/balsama/drupalorg-dom-parser)

# Drupal.org DOM Parser
Retrieves information about a specified project from Drupal.org.

## Usage

```
$project_name = 'ctools';
$project_stats = new Balsama\DrupalOrgProject\Stats($project_name);
```

## Available information

### General (Taken from the "Project information" section of the project page)
* Total downloads `$project->getTotalDownloads`
* Total installs `$project->getTotalInstalls`

### Releases (Deduced from the "Downloads" section of the project page)
* Drupal 8 Stability `$project_stats->getD8Stability`

### Usage (Taken from the Project Usage table on the "project/usage" page)
* Current Drupal 8 usage `$project_stats->getCurrentD8Usage`
* Current Drupal 7 usage `$project_stats->getCurrentD7Usage`
* All usage data `$project_stats->getAllUsage`

### Other
* Human-readable name `$project_stats->getHumanReadableName`
* Machine name `$project_stats->getMachineName`

## Other classes
If you're only interested in getting the info from the usage table, you can use the `Usage` class instead which only
fetches data from the /project/usage/{project_name} page. This is somewhat less likely to break as D.O seems to change
the markup of that page less frequently.

## Why?
At the time this was created, there wasn't any public facing D.O API. Even now, there is some information that's either
not available or is cumbersome to get via the API.