<?php

namespace Drupal\Composer\Plugin\AutomaticUpdates\Tests;

use Composer\Composer;
use Composer\Config;
use Composer\IO\IOInterface;
use Composer\Package\Package;
use Composer\Plugin\PluginEvents;
use Composer\Plugin\PostFileDownloadEvent;
use Drupal\Composer\Plugin\AutomaticUpdates\Verify;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass Drupal\Composer\Plugin\AutomaticUpdates\Verify
 * @group automatic_updates
 */
class VerifyTest extends TestCase {

  /**
   * @covers ::onPostFileDownload
   */
  public function testOnPostFileDownload() {
    $plugin = new Verify();
    $io = $this->prophesize(IOInterface::class);
    $composer = $this->prophesize(Composer::class);
    $composer->getConfig()->willReturn(new Config());
    $plugin->activate($composer->reveal(), $io->reveal());
    $event = new PostFileDownloadEvent(PluginEvents::POST_FILE_DOWNLOAD, 'drupal.zip', 'checksum', 'https://drupal.org', new Package('drupal', '8.8.3', '8.8.3'));
    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('The downloaded files did not match what was expected.');
    $plugin->onPostFileDownload($event);
  }
}
