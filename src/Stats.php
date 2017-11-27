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
    protected $project_dom;

    /**
     * Basic info about the project gleaned from the project page's info list.
     *
     * @var ProjectInfo
     */
    protected $project_info;

    /**
     * The machine name of the project provided to the constructor.
     *
     * @var string
     */
    protected $machine_name;

    /**
     * The project releases.
     *
     * @var Releases
     */
    protected $releases;

    /**
     * The project usage.
     *
     * @var Usage
     */
    protected $usage;

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
        return $this->usage->getCurrentNthUsage('8.x');
    }

    /**
     * @return int
     *   Latest D7 project reported installs.
     */
    public function getCurrentD7Usage() {
        return $this->usage->getCurrentNthUsage('7.x');
    }

    /**
     * An array of rows of usage data from the project's usage page.
     *
     * @return array
     */
    public function getAllUsage() {
        return $this->usage->getProjectUsage();
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
     * The machine name of the project. This is also the $project_name value passed into the constructor.
     *
     * @return string
     */
    public function getMachineName() {
        return $this->machine_name;
    }

    /**
     * Stats constructor.
     *
     * @param $project_name
     *   The machine name of a Drupal.org project.
     */
    public function __construct($project_name) {
        $this->machine_name = $project_name;
        $this->project_dom = new Dom;
        $this->project_dom->loadFromUrl('https://www.drupal.org/project/' . $project_name);
        $this->project_info = new ProjectInfo($this->project_dom);
        $this->releases = new Releases($this->project_dom);
        $this->usage = new Usage($project_name);
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
