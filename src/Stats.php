<?php

namespace Balsama\DrupalOrgProject;

use PHPHtmlParser\Dom;

class Stats {

    /**
     * @var object PHPHtmlParser\Dom
     *   The contents of the Drupal.org project page for the specified project.
     *   E.g. https://drupal.org/project/ctools.
     */
    private $dom;

    /**
     * @var object PHPHtmlParser\Dom
     *   The contents of the Drupal.org project usage page for the specified
     *   project. E.g. https://drupal.org/project/usage/ctools.
     */
    private $stats_dom;

    /**
     * @var array
     *   Rows of the specified project's usage statistic table.
     */
    private $all_project_usage;

    /**
     * @var array
     *   Information from the Project Information section of the project page.
     */
    private $project_info;

    /**
     * @var string
     * The machine name of the project.
     */
    private $machine_name;

    /**
     * All releases found on D.O
     *
     * @var string[]
     */
    protected $allReleases;

    /**
     * @param $project_name
     *   The machine name of a Drupal.org project.
     * @return \PHPHtmlParser\Dom
     */
    private function fetchDom($project_name) {
        $dom = new Dom;
        $this->dom = $dom->loadFromUrl('https://www.drupal.org/project/' . $project_name);
        return $this->dom;
    }

    /**
     * @param Dom $dom
     */
    private function setDom(Dom $dom) {
        $this->dom = $dom;
    }

    /**
     * Access method for main project page dom.
     *
     * @return object PHPHtmlParser\Dom
     */
    public function getDom() {
        return $this->dom;
    }

    /**
     * @param Dom $project_name
     *   The machine name of a Drupal.org project.
     * @return \PHPHtmlParser\Dom
     */
    private function fetchStatsDom($project_name) {
        $stats_dom = new Dom;
        $this->stats_dom = $stats_dom->loadFromUrl('https://www.drupal.org/project/usage/' . $project_name);
        return $this->stats_dom;
    }

    /**
     * @param Dom $stats_dom
     */
    private function setStatsDom(Dom $stats_dom) {
        $this->stats_dom = $stats_dom;
    }

    /**
     * Access method for project statistics page dom.
     *
     * @return object PHPHtmlParser\Dom
     */
    public function getStatsDom() {
        return $this->stats_dom;
    }

    /**
     * Stats constructor.
     * @param $project_name
     *   The machine name of a Drupal.org project.
     */
    public function __construct($project_name) {
        $dom = $this->fetchDom($project_name);
        $this->setDom($dom);
        $stats_dom = $this->fetchStatsDom($project_name);
        $this->setStatsDom($stats_dom);
        $all_project_usage = $this->fetchAllProjectUsage();
        $this->setAllProjectUsage($all_project_usage);
        if (($project_name != 'drupal') && ($project_name != 'imce_wysiwyg')) {
            // Main drupal project has a different project page than other
            // projects so we can't parse it the same way. imce_wysiwyg also
            // seems to be be different somehow. I'm not troubleshooting what
            // exactly is different because I'd rather just make
            // Stats::getProjectInfo more scalable.
            $project_info = $this->fetchProjectInfo();
            $this->setProjectInfo($project_info);
        }
        $this->machine_name = $project_name;
        $this->allReleases = $this->getAllReleases();
    }

    /**
     * Information about the specified project.
     *
     * @return array
     *   Contents of the Project Information section of the specified project's
     *   project page. Keys:
     *   - maintenance_status (string)
     *   - developkent_status (string)
     *   - reported_installs (int)
     *   - downloads (int)
     *   - last_modified (date)
     */
    private function fetchProjectInfo() {
        // @TODO: make this a little smarter so that it returns all of the
        // values from the Project Information section rather than hard-coding
        // which ones we want.
        $dom = $this->dom;

        /* @var $project_info \PHPHtmlParser\Dom\HtmlNode */
        $project_info = $dom->find('.project-info');

        /* @var $list_items \PHPHtmlParser\Dom\HtmlNode[] */
        $list_items = $project_info->getChildren();
        foreach ($list_items as $list_item) {
          if (strpos($list_item->innerhtml(), 'sites report using this')) {
            // This is the stats item.
            $stats = $list_item;
          }
        }
        if (!isset($stats)) {
          return;
        }

        $processed_project_info = [
            'reported_installs' => intval(str_replace(',', '', $stats->find('strong')->innerHtml())),
            'downloads' => intval(str_replace(',', '', substr($stats->find('small')->innerHtml(), 0, -10))),
        ];

        return $processed_project_info;
    }

    /**
     * @param $project_info array
     */
    private function setProjectInfo($project_info) {
        $this->project_info = $project_info;
    }

    /**
     * Access method for project info.
     *
     * @return array
     */
    public function getProjectInfo() {
        return $this->project_info;
    }

    /**
     * @return array
     *   Rows of the specified project's usage statistic table.
     */
    private function fetchAllProjectUsage() {
        $stats_dom = $this->stats_dom;
        $stat_cols = $stats_dom->find('#project-usage-project-api thead tr', 0);
        if (!isset($stat_cols)) {
          return ['No stats'];
        }
        $cols = count($stat_cols);
        $highest_release = substr($stat_cols->find('.project-usage-numbers', ($cols - 4))->innerHtml(), 0, 2);
        $stat_rows = $stats_dom->find('#project-usage-project-api tbody tr');
        $stat = [];
        foreach ($stat_rows as $stat_row) {
            $stat[] = $this->statRowProcess($stat_row, $highest_release);
        }
        return $stat;
    }

    /**
     * Access method for project usage.
     *
     * @return array
     */
    public function getAllProjectUsage() {
        return $this->all_project_usage;
    }

    /**
     * @param $all_project_usage array
     *   Result of $this->getAllProjectInfo.
     */
    private function setAllProjectUsage(array $all_project_usage) {
        $this->all_project_usage = $all_project_usage;
    }

    /**
     * Helper function to process a single row of the projects statistics table.
     *
     * @param $stat_row
     * @return array
     */
    private function statRowProcess($stat_row, $highest_release) {
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
     * @param $nth
     *   The major drupal version #. E.g. `8.x`.
     * @return int
     */
    private function getCurrentNthUsage($nth) {
        $all_project_usage = $this->all_project_usage;
        if (!isset($all_project_usage[0][$nth])) {
            return 0;
        }
        return intval(str_replace(',', '', $all_project_usage[0][$nth]));
    }

    /**
     * @return bool|mixed
     *   Whether or not the maintainers report that the project is actively
     *   maintained.
     */
    public function isActivelyMaintained() {
        $maintenance_status = $this->project_info['maintenance_status'];
        if ($maintenance_status === 'Actively maintained') {
            return $maintenance_status;
        }
        return FALSE;
    }

    /**
     * @return array
     *   List of all releases, including dev releases.
     */
    public function getAllReleases() {
        $dom = $this->dom;
        $releases = [];
        $all_releases_dom = $dom->find('.view-id-drupalorg_project_downloads .view-content > div.release');
        /* @var $release \PHPHtmlParser\Dom\HtmlNode */
        foreach ($all_releases_dom as $release) {
            if (!empty(trim($release->innerHtml()))) {
                if (strpos($release->innerHtml(), 'Development version') !== false) {
                    $releases[] = $release->find('.release-info > p > a')->innerHtml();
                }
                if ($release->find('span > strong.field-content a')->count()) {
                    $releases[] = $release->find('span > strong.field-content a')->innerHtml();
                }
            }
        }
        return $releases;
    }

    /**
     * Gets the stability of D8 releases.
     *
     * @return array
     *   An array keyed by the project's D8 release values with the stability as the value.
     */
    public function getKeyedStabilities() {
        $releases = $this->allReleases;
        $keyed_releases = [];
        foreach ($releases as $release) {
            $stability = null;
            if (preg_match('/^8\.x-[1-9]\.\d*$/', $release)) {
                $stability = 'full release';
            }
            elseif (preg_match('/^8\.x-[1-9]\.\d*-rc\d*/', $release)) {
                $stability = 'rc';
            }
            elseif (preg_match('/^8\.x-[1-9]\.\d*-beta\d*/', $release)) {
                $stability = 'beta';
            }
            elseif (preg_match('/^8\.x-[1-9]\.\d*-alpha\d*/', $release)) {
                $stability = 'alpha';
            }
            elseif (preg_match('/^8\.x-[1-9]\.x-dev/', $release)) {
                $stability = 'dev';
            }
            if ($stability) {
                $keyed_releases[$release] = $stability;
            }
        }

        $order = ['full_release', 'rc', 'beta', 'alpha', 'dev'];
        $ordered_keyed_releases = array_merge(array_flip($order), array_flip($keyed_releases));
        foreach ($ordered_keyed_releases as $stability => $value) {
            if (is_numeric($value)) {
                unset($ordered_keyed_releases[$stability]);
            }
        }

        return $ordered_keyed_releases;
    }

    /**
     * The highest stability of any D8 release.
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
        $stabilities = $this->getKeyedStabilities();
        if (!$stabilities) {
            return 'no D8 development';
        }
        return $stabilities[0];
    }

    public function getHumanReadableName() {
        $dom = $this->dom;
        return $dom->find('#page-subtitle')->innerHtml();
    }

    public function getMachineName() {
        return $this->machine_name;
    }

}
