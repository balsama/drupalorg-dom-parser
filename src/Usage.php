<?php

namespace Balsama\DrupalOrgProject;

use PHPHtmlParser\Dom;

class Usage {

    /**
     * The contents of the Drupal.org project usage page for the specified
     * project. E.g. https://drupal.org/project/usage/ctools.
     *
     * @var object PHPHtmlParser\Dom
     */
    protected $stats_dom;

    /**
     * Formatted project usage data from the project's usage page.
     *
     * @var array
     */
    protected $project_usage;

    /**
     * Accessor for the project usage table.
     *
     * @return array
     */
    public function getProjectUsage() {
        return $this->project_usage;
    }

    /**
     * Get current the usage for a particular version of Drupal.
     *
     * @param $nth
     *   The major drupal version #. E.g. `8.x`.
     * @return int
     */
    public function getCurrentNthUsage($nth) {
        $all_project_usage = $this->project_usage;
        if (!isset($all_project_usage[0][$nth])) {
            return 0;
        }
        $usage = $all_project_usage[0][$nth];
        if (($usage == 0) && ($all_project_usage[1][$nth] != 0)) {
            // Sometimes the top row is present but without stats. When that happens, use the previous row's data.
            $usage = $all_project_usage[1][$nth];
        }

        return Stats::makeInteger($usage);
    }

    /**
     * Usage constructor.
     *
     * @param string $project_name
     *   The Drupal.org machine name of the project.
     */
    public function __construct($project_name) {
        $this->stats_dom = new Dom();
        $this->stats_dom->loadFromUrl('https://www.drupal.org/project/usage/' . $project_name);
        $this->fetchAllProjectUsage();
    }

    /**
     * Fetched and formats the data from the project's usage table.
     *
     * @return array
     *   Rows of the specified project's usage statistic table.
     */
    protected function fetchAllProjectUsage() {
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

}