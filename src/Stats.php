<?php

namespace Balsama\DrupalOrgProject;

use PHPHtmlParser\Dom;

class Stats {

    /**
     * The contents of the Drupal.org project page for the specified project.
     * E.g. https://drupal.org/project/ctools.
     *
     * @var object PHPHtmlParser\Dom
     */
    private $project_dom;

    /**
     * The contents of the Drupal.org project usage page for the specified
     * project. E.g. https://drupal.org/project/usage/ctools.
     *
     * @var object PHPHtmlParser\Dom
     */
    private $stats_dom;

    /**
     * Basic info about the project gleaned from the project page's info list.
     *
     * @var ProjectInfo
     */
    private $project_info;

    /**
     * The project releases.
     *
     * @var Releases;
     */
    private $releases;

    /**
     *
     *
     * @var array
     */
    private $project_usage;

    /**
     * Accessor for total project installs.
     *
     * @return int
     */
    public function getTotalInstalls() {
        return $this->project_info->getInstalls();
    }

    /**
     * Accessor for total project downloads.
     *
     * @return int
     */
    public function getTotalDownloads() {
        return $this->project_info->getDownloads();
    }

    /**
     * Accessor for D8 Stability - the highest stability of all D8 releases.
     *
     * @return string
     *   The stability of the D8 release if one exists. Possible values:
     *   - full release
     *   - alpha
     *   - beta
     *   - rc
     *   - dev
     *   - no D8 development
     */
    public function getD8Stability() {
        $stabilities = $this->releases->getStabilityKeyedReleases();
        if (!$stabilities) {
            return 'no D8 development';
        }
        return reset($stabilities);
    }

    /**
     * @return int
     *   Latest D8 project reported installs.
     */
    public function getCurrentD8Usage() {
        return $this->getCurrentNthUsage('8.x');
    }

    /**
     * @return int
     *   Latest D7 project reported installs.
     */
    public function getCurrentD7Usage() {
        return $this->getCurrentNthUsage('7.x');
    }

    /**
     * An array of rows of usage data from the project's usage page.
     *
     * @return array
     */
    public function getAllUsage() {
        return $this->project_usage;
    }

    /**
     * The human-readable name of the project.
     *
     * @return string
     */
    public function getHumanReadableName() {
        $dom = $this->project_dom;
        return $dom->find('#page-subtitle')->innerHtml();
    }

    /**
     * Stats constructor.
     *
     * @param $project_name
     *   The machine name of a Drupal.org project.
     */
    public function __construct($project_name) {
        $this->project_dom = new Dom;
        $this->stats_dom = new Dom;
        $this->project_dom->loadFromUrl('https://www.drupal.org/project/' . $project_name);
        $this->stats_dom->loadFromUrl('https://www.drupal.org/project/usage/' . $project_name);
        $this->project_info = new ProjectInfo($this->project_dom);
        $this->releases = new Releases($this->project_dom);
        $this->fetchAllProjectUsage();
    }

    /**
     * @return array
     *   Rows of the specified project's usage statistic table.
     */
    private function fetchAllProjectUsage() {
        $stats_dom = $this->stats_dom;
        $stat_cols = $stats_dom->find('#project-usage-project-api thead tr', 0);
        $stat = [];

        if (!isset($stat_cols)) {
          return $stat;
        }

        $cols = count($stat_cols);
        $highest_release = substr($stat_cols->find('.project-usage-numbers', ($cols - 4))->innerHtml(), 0, 2);
        $stat_rows = $stats_dom->find('#project-usage-project-api tbody tr');

        foreach ($stat_rows as $stat_row) {
            $stat[] = $this->statRowProcess($stat_row, $highest_release);
        }

        $this->project_usage = $stat;
    }

    /**
     * Helper function to process a single row of the projects statistics table.
     *
     * @param $stat_row
     * @return array
     */
    protected function statRowProcess($stat_row, $highest_release) {
        $count = count($stat_row);
        $stat = [];
        $stat['date'] = $stat_row->firstChild()->innerHtml();
        $stat['total'] = intval(str_replace(',', '', $stat_row->find('.project-usage-numbers', ($count - 3))->innerHtml()));
        $add = ($highest_release - ($count - 4));
        for($count = ($count - 4); $count >= 0; $count--) {
            $major = ($count + $add);
            $stat[$major . '.x'] = intval(str_replace(',', '', $stat_row->find('.project-usage-numbers', $count)->innerHtml()));
        }
        return $stat;
    }

    /**
     * @param $nth
     *   The major drupal version #. E.g. `8.x`.
     * @return int
     */
    protected function getCurrentNthUsage($nth) {
        $all_project_usage = $this->project_usage;
        if (!isset($all_project_usage[0][$nth])) {
            return 0;
        }
        $usage = $all_project_usage[0][$nth];
        if (($usage == 0) && ($all_project_usage[1][$nth] != 0)) {
            // Sometimes the top row is present but without stats. When that happens, use the previous row's data.
            $usage = $all_project_usage[1][$nth];
        }

        return self::makeInteger($usage);
    }

    /**
     * Converts a number string (e.g. "1,234") to an integer ("1234").
     *
     * @param string $number
     *
     * @return int
     */
    public static function makeInteger($number) {
        return intval(str_replace(',', '', $number));
    }

}
