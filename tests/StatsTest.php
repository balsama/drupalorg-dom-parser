<?php

use Balsama\DrupalOrgProject\Stats;
use PHPHtmlParser\Dom;

class StatsTest extends PHPUnit_Framework_TestCase {

    /**
     * The dom and stats_dom variables are set and that they are
     * PHPHtmlParser\Dom objects.
     */
    public function testDomObjects() {
        $project_name = 'ctools';
        $project = new Stats($project_name);

        $dom = $project->getDom();
        $this->assertInstanceOf('PHPHtmlParser\Dom', $dom);

        $stats_dom = $project->getStatsDom();
        $this->assertInstanceOf('PHPHtmlParser\Dom', $stats_dom);
    }

    /**
     * Usage statistics are retrieved and within the expected range.
     */
    public function testUsageStatistics() {
        // Project with two columns; 7.x & 8.x.
        $project_name = 'metatag';
        $project = new Stats($project_name);

        $usage = $project->getCurrentD8Usage();
        $this->assertInternalType('int', $usage);
        $this->assertTrue($usage > 13000);
        $this->assertTrue($usage < 20000);

        $d7usage = $project->getCurrentD7Usage();
        $this->assertInternalType('int', $d7usage);
        $this->assertTrue($d7usage > 290000);
        $this->assertTrue($d7usage < 340000);

        // Project with four columns; 5.x, 6.x, 7.x, & 8.x.
        $project_name = 'pathauto';
        $project = new Stats($project_name);

        $usage = $project->getCurrentD8Usage();
        $this->assertInternalType('int', $usage);
        $this->assertTrue($usage > 24000);
        $this->assertTrue($usage < 40000);

        $d7usage = $project->getCurrentD7Usage();
        $this->assertInternalType('int', $d7usage);
        $this->assertTrue($d7usage > 610000);
        $this->assertTrue($d7usage < 670000);
    }

    /**
     * Projects with no nth release don't return usage statistics for that
     * release.
     */
    public function testNoReleaseStatistics() {
        // Facet API was renamed facets, so it should never have a D8 release.
        $project_name = 'facetapi';
        $project = new Stats($project_name);

        $d8_usage = $project->getCurrentD8Usage();
        $this->assertFalse(boolval($d8_usage));
    }
 }