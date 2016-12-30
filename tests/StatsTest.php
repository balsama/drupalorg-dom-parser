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

        // Project with four columns; 5.x, 6.x, 7.x, & 8.x.
        $project_name = 'pathauto';
        $project = new Stats($project_name);

        $usage = $project->getCurrentD8Usage();
        $this->assertInternalType('int', $usage);
        $this->assertTrue($usage > 24000);
        $this->assertTrue($usage < 40000);
    }
 }