<?php

namespace Balsama\DrupalOrgProject;


use PHPHtmlParser\Dom;

class Releases {
    /**
     * The CSS selector used to identify the releases section on the project page.
     *
     * @var string
     */
    protected $releases_selector = '.view-id-drupalorg_project_downloads .view-content > div.release';

    /**
     * An array of release strings.
     *
     * @var string[]
     */
    protected $releases;

    /**
     * An array of release strings keyed by their stability.
     *
     * @var string[]
     */
    protected $stability_keyed_releases;

    /**
     * Accessor for $stability_keyed_releases.
     *
     * @return string[]
     */
    public function getStabilityKeyedReleases() {
        return $this->stability_keyed_releases;
    }

    /**
     * Releases constructor.
     *
     * @param Dom $project_dom
     */
    public function __construct(Dom $project_dom) {
        /* @var Dom\HtmlNode[] */
        $all_releases_dom = $project_dom->find($this->releases_selector);
        $this->fetchReleases($all_releases_dom);
        $this->setStabilityKeyedReleases();
    }

    /**
     * Gets all releases that are displayed on the main project page. Note that a maintainer might choose to hide
     * certain releases from this list.
     *
     * @param Dom\HtmlNode[] $all_releases_dom
     *   The section of the page identified by the $releases_selector.
     *
     * @return string[]
     *   An array of release strings. E.g.: ['8.x-1.2', '8.x-2.0-alpha3'. '7.x-3.14'].
     */
    protected function fetchReleases($all_releases_dom) {
        $releases = [];
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
        $this->releases = $releases;
    }

    /**
     * Generates an array of Drupal 8 releases keyed by their stability based on the release string. The most stable
     * release will be first in the array followed by less stable releases in the following order:
     *   - full release
     *   - rc
     *   - beta
     *   - alpha
     *   - dev
     *
     * This list only includes Drupal 8 releases.
     */
    protected function setStabilityKeyedReleases() {
        $releases = $this->releases;
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

        $order = ['full release', 'rc', 'beta', 'alpha', 'dev'];
        $ordered_keyed_releases = array_merge(array_flip($order), array_flip($keyed_releases));
        foreach ($ordered_keyed_releases as $stability => $value) {
            if (is_numeric($value)) {
                unset($ordered_keyed_releases[$stability]);
            }
        }

        $this->stability_keyed_releases = array_flip($ordered_keyed_releases);
    }

}