<?php

namespace Balsama\DrupalOrgProject;

use PHPHtmlParser\Dom;
use PHPHtmlParser\Exceptions\ChildNotFoundException;

class ProjectInfo {

    /**
     * The css selector used to find the info section in the DOM.
     *
     * @var string
     */
    private $info_selector = '.project-info';

    /**
     * Needle used to determine whether a project info list item is the statistics item.
     *
     * @var string
     */
    private $stats_needle = 'sites report using this';

    /**
     * The list item containing project statistics.
     *
     * @var Dom\HtmlNode
     */
    private $stats;

    /**
     * The reported numbed of project downloads.
     *
     * @var int
     */
    protected $downloads;

    /**
     * The reported number of project installs.
     *
     * @var int
     */
    protected $installs;

    /**
     * Accessor for downloads.
     *
     * @return int
     */
    public function getDownloads() {
        if (!isset($this->downloads)) {
            $this->downloads = Stats::makeInteger(substr($this->stats->find('small')->innerHtml(), 0, -10));
        }
        return $this->downloads;
    }

    /**
     * Accessor for installs.
     *
     * @return int
     */
    public function getInstalls() {
        if (!isset($this->installs)) {
            $this->installs =  Stats::makeInteger($this->stats->find('strong')->innerHtml());
        }
        return $this->installs;
    }

    /**
     * ProjectInfo constructor.
     *
     * @param Dom $project_dom
     *   A D.O project page dom.
     */
    public function __construct(Dom $project_dom) {
        /* @var $project_info \PHPHtmlParser\Dom\Collection */
        $project_info = $project_dom->find($this->info_selector);
        $this->stats = $this->fetchStats($project_info);
    }

    /**
     * Finds and returns the contents of the list item which contains project statistics.
     *
     * @param Dom\Collection $project_info
     *   The project info list item from a D.O project page.
     *
     * @return Dom\HtmlNode
     *   The list item which contains the stats needle. Assumes there is only one.
     *
     * @throws ChildNotFoundException
     */
    protected function fetchStats(Dom\Collection $project_info) {
        /* @var $list_items \PHPHtmlParser\Dom\HtmlNode[] */
        $list_items = $project_info->getChildren();
        foreach ($list_items as $list_item) {
            if (strpos($list_item->innerhtml(), $this->stats_needle)) {
                return $list_item;
            }
        }
        throw new ChildNotFoundException('No statistics items found in project info. Was looking for list item containing `' . $this->stats_needle . '``.');
    }

}