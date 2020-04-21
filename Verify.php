<?php

declare(strict_types=1);

namespace Drupal\Composer\Plugin\AutomaticUpdates;

use Composer\Composer;
use Composer\Downloader\TransportException;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\Factory;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginEvents;
use Composer\Plugin\PluginInterface;
use Composer\Plugin\PostFileDownloadEvent;
use Drupal\Signify\ChecksumList;
use Drupal\Signify\FailedCheckumFilter;
use Drupal\Signify\Verifier;

/**
 * Class Verify
 *
 * @package Drupal\Composer\Plugin\AutomaticUpdates\Verify.
 */
class Verify implements PluginInterface, EventSubscriberInterface
{

  /**
   * @var Composer
   */
    private $composer;

  /**
   * The IO interface.
   *
   * @var IOInterface
   */
    private $inputOutput;

  /**
   * {@inheritdoc}
   */
    public function deactivate(Composer $composer, IOInterface $inputOutput): void
    {
    }

  /**
   * {@inheritdoc}
   */
    public function uninstall(Composer $composer, IOInterface $inputOutput): void
    {
    }

  /**
   * {@inheritdoc}
   */
    public function activate(Composer $composer, IOInterface $inputOutput): void
    {
        $this->composer = $composer;
        $this->inputOutput = $inputOutput;
    }

  /**
   * {@inheritdoc}
   */
    public static function getSubscribedEvents(): array
    {
        return [
        PluginEvents::POST_FILE_DOWNLOAD => 'onPostFileDownload',
        ];
    }

  /**
   * Execute on post file download.
   *
   * @param \Composer\Plugin\PostFileDownloadEvent $event
   *   The post file download event.
   */
    public function onPostFileDownload(PostFileDownloadEvent $event): void
    {
        $downloader = Factory::createHttpDownloader($this->inputOutput, $this->composer->getConfig());
        $key = file_get_contents('artifacts/keys/root.pub');
        $verifier = new Verifier($key);
      // @TODO: hard-code the version and package info until
      // https://github.com/composer/composer/pull/8810 lands.
        $url = sprintf(
            'https://updates.drupal.org/release-hashes/%s/%s/contents-sha256sums-packaged.csig',
            'drupal',
            '8.8.3'
        );
        try {
            $csig = $downloader->get($url)->getBody();
        } catch (TransportException $e) {
            throw new \RuntimeException($e->getMessage(), 0, $e);
        } catch (\Exception $e) {
            throw new \RuntimeException('Could not read ' . $url . "\n\n" . $e->getMessage());
        }
        $files = $verifier->verifyCsigMessage($csig);
        $checksums = new ChecksumList($files, true);
        $failed_checksums = new FailedCheckumFilter($checksums, basename($event->getFileName()));
        if (iterator_count($failed_checksums)) {
            throw new \RuntimeException('The downloaded files did not match what was expected.');
        }
    }
}
