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
        $project_info = $this->fetchProjectInfo();
        $this->setProjectInfo($project_info);
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
        $project_info = $dom->find('.project-info');
        $maintenance_status = $project_info->firstChild();
        $development_status = $maintenance_status->nextSibling();
        // If the project has automated testing enabled, the Reported Installs
        // stat is bumped down in the list.
        if (substr($development_status->nextSibling()->innerHtml(), 0, strlen('Reported installs: ')) === 'Reported installs: ') {
            $reported_installs = $development_status->nextSibling();
        }
        else {
            $reported_installs = $development_status->nextSibling()->nextSibling();
        }
        $downloads = $reported_installs->nextSibling();
        // If the project has any tags, the Last Modified stat is bumped down
        // too.
        if (substr($downloads->nextSibling()->innerHtml(), 0, strlen('Last modified:')) === 'Last modified:') {
            $last_modified = $downloads->nextSibling();
        }
        else {
            $last_modified = $downloads->nextSibling()->nextSibling();
        }
        $processed_project_info = [
            'maintenance_status' => $maintenance_status->find('a')->innerHtml(),
            'development_status' => $development_status->find('a')->innerHtml(),
            'reported_installs' => intval(str_replace(',', '', $reported_installs->find('strong')->innerHtml())),
            'downloads' => intval(str_replace(',', '', substr($downloads->innerHtml(), 11))),
            'last_modified' => date('d-M-Y', strtotime(substr($last_modified->innerHtml(), 15))),
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
        $stat_rows = $stats_dom->find('#project-usage-project-api tbody tr');
        $stat = [];
        foreach ($stat_rows as $stat_row) {
            $stat[] = $this->statRowProcess($stat_row);
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
    private function statRowProcess($stat_row) {
        $count = count($stat_row);
        $stat = [];
        $stat['date'] = $stat_row->firstChild()->innerHtml();
        $stat['total'] = intval(str_replace(',', '', $stat_row->find('.project-usage-numbers', ($count - 3))->innerHtml()));
        $add = (8 - ($count - 4));
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
     *   List of recommended releases.
     */
    public function getRecommendedReleases() {
        $dom = $this->dom;
        $recommended_releases = [];
        $recommended_releases_dom = $dom->find('.view-id-drupalorg_project_downloads > .view-content table tbody tr');
        foreach ($recommended_releases_dom as $recommended_release) {
            $recommended_releases[] = $recommended_release->find('a')->innerHtml();
        }
        return $recommended_releases;
    }

    /**
     * @return array
     *   List of all releases, including dev releases.
     */
    public function getAllReleases() {
        $dom = $this->dom;
        $releases = [];
        $all_releases_dom = $dom->find('.view-id-drupalorg_project_downloads .view-content table tbody tr');
        foreach ($all_releases_dom as $release) {
            $releases[] = $release->find('a')->innerHtml();
        }
        return $releases;
    }

    /**
     * @return bool|mixed
     *   Whether or not the maintainer has marked a release for D8 as
     *   recommended.
     */
    public function hasRecommendedD8Release() {
        $recommended_releases = $this->getRecommendedReleases();
        foreach ($recommended_releases as $recommended_release) {
            if (substr($recommended_release, 0, 3) === '8.x') {
                return $recommended_release;
            }
        }
        return FALSE;
    }

    /**
     * @return bool|mixed
     *   Whether or not there is a full, tagged release for D8.
     */
    public function hasFullD8Release() {
        $d8Release = $this->hasRecommendedD8Release();
        if (!$d8Release) {
            return FALSE;
        }
        if (preg_match('/^8\.x-[1-9]\.\d*$/', $d8Release)) {
            return $d8Release;
        }
        return FALSE;
    }

    /**
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
        if ($this->hasFullD8Release()) {
            return 'full release';
        }
        $releases = $this->getAllReleases();
        foreach ($releases as $release) {
            if (preg_match('/^8\.x-[1-9]\.\d*-alpha\d*/', $release)) {
                return 'alpha';
            }
            elseif (preg_match('/^8\.x-[1-9]\.\d*-beta\d*/', $release)) {
                return 'beta';
            }
            elseif (preg_match('/^8\.x-[1-9]\.\d*-rc\d*/', $release)) {
                return 'rc';
            }
            elseif (preg_match('/^8\.x-[1-9]\.\d*-dev\d*/', $release)) {
                return 'dev';
            }
        }
        return 'no D8 development';
    }

    public function getHumanReadableName() {
        $dom = $this->dom;
        return $dom->find('#page-subtitle')->innerHtml();
    }

}