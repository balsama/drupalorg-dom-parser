<?php
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
include __DIR__.'/../vendor/autoload.php';

echo '<pre>';

$project_name = 'facets';
$project = new Balsama\DrupalOrgProject\Stats($project_name);

$name = $project->getHumanReadableName();

if ($project->hasRecommendedD8Release()) {
    $recommended =  $name . ' has a recommended D8 release.';
}
else {
    $recommended = $name . ' does NOT have a recommended D8 release.';
}
print '<p>' . $recommended . '</p>';

if ($project->hasFullD8Release()) {
    $full = $name . ' has a full D8 release.';
}
else {
    $full = $name . ' does NOT have a full d8 release.';
}
print '<p>' . $full . '</p>';


print '<p>' . $name . 'is marked as ' . $project->getD8Stability() . '</p>';

print '<p>' . number_format($project->getCurrentD8Usage()) . ' Drupal 8 sites report using ' . $name . '</p>';

$maintenance = ($project->isActivelyMaintained()) ? '':'not ';

print '<p>' . $name . ' is ' . $maintenance . 'actively maintained.';

echo '</pre>';