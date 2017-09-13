<?php

namespace Oktolab\MediaBundle\Model;

use Bprs\CommandLineBundle\Model\BprsContainerAwareJob;
use Oktolab\MediaBundle\Entity\Caption;

/**
 * generates the final sprite and a webvtt caption to display in a player
 */
class GenerateThumbnailSpriteJob extends BprsContainerAwareJob {

    private $asset_helper;
    private $sprite_height;
    private $sprite_width;
    private $sprite_interval;
    private $logbook;

    public function getName()
    {
        return 'Sprite Generator';
    }

    public function perform()
    {
        $this->asset_helper = $this->getContainer()->get('bprs.asset_helper');
        $this->sprite_height = $this->getContainer()->getParameter('oktolab_media.sprite_height');
        $this->sprite_width = $this->getContainer()->getParameter('oktolab_media.sprite_width');
        $this->sprite_interval = $this->getContainer()->getParameter('oktolab_media.sprite_interval');
        $this->logbook = $this->getContainer()->get('bprs_logbook');
        $episode = $this->getContainer()->get('oktolab_media')->getEpisode($this->args['uniqID']);

        if ($episode->getVideo()) {
            $cache_asset = $this->createNewCacheAssetForSprite($episode);
            $this->calculateInterval($episode);
            $this->logbook->info('oktolab_media.episode_start_gen_spritethumbs', [], $this->args['uniqID']);
            $this->generateThumbs($episode, $cache_asset);
            $this->logbook->info('oktolab_media.episode_end_gen_spritethumbs', [], $this->args['uniqID']);

            $this->logbook->info('oktolab_media.episode_start_gen_sprite', [], $this->args['uniqID']);
            $this->stitchThumbs($episode, $cache_asset);
            $this->logbook->info('oktolab_media.episode_end_gen_sprite', [], $this->args['uniqID']);
        } else {
            $this->logbook->info('oktolab_media.episode_sprite_no_vid', [], $this->args['uniqID']);
        }
    }

    // calculates the interval time for the length of an episode and considers jpeg dimension limit of 65500 px.
    private function calculateInterval($episode)
    {
        $max_image = floor(65500/$this->sprite_height);
        $calculated_interval = ceil($episode->getDuration()/$max_image);
        if ($calculated_interval > $this->sprite_interval) {
            $this->sprite_interval = $calculated_interval;
        }
    }

    // takes episode and creates thumbnails in given width and height
    private function generateThumbs($episode, $cache_asset)
    {
        $uri = $this->asset_helper->getAbsoluteUrl($episode->getVideo());
        $path = $this->asset_helper->getPath($cache_asset, true);
        $cmd = sprintf(
            "ffmpeg -i %s -s %sx%s -vf fps=1/%s %s 2>&1",
            $uri,
            $this->sprite_width,
            $this->sprite_height,
            $this->sprite_interval,
            $path
        );
        // open (ffmpeg) process, read stdout to get duration if unknown to this point.
        $fp = popen($cmd, "r");
        while(!feof($fp)) {
            // read outputstream of ffmpeg
            $chunk = fread($fp, 1024);

            // try to get the duration information at the beginning of the ffmpeg output.
            if (!$episode->getDuration()) {
                preg_match("/Duration: (.*?), start:/", $chunk, $matches);
                if (array_key_exists(1, $matches)) {
                    list($hours,$minutes,$seconds) = explode(":",$matches[1]);
                    // calculate the duration in seconds. Used to calculate overall progress in percent.
                    $episode->setDuration((($hours * 3600) + ($minutes * 60) + $seconds));
                }
            }
            // flush the content to the browser
            flush();
        }
        fclose($fp);
    }

    // create sprite image from thumbs, write it to the sprite adapter, remove old thumbs from cache
    // info: we need to break the line every 100 images to not reach jpeg limitation of 65500px per dimension.
    // at a resolution of 360*180 every 10 seconds, we get about 50 hours of maximal video length.
    // at a res of 1280*720
    private function stitchThumbs($episode, $cache_asset)
    {
        $path = $this->asset_helper->getPath($cache_asset, true);
        // calculate total number of thumbs based on length and timeinterval.
        $numberOfSprites = ceil($episode->getDuration()/$this->sprite_interval);
        //one long image, with every image below it.
        $spriteImage = imagecreatetruecolor($this->sprite_width, $numberOfSprites*$this->sprite_height);

        // create a long image containing all thumbnails on top of each other
        for ($number = 1; $number <= $numberOfSprites; $number++) {
            $currentSprite = sprintf($path, $number);
            $image = \imagecreatefromjpeg($currentSprite);
            imagecopy(
                $spriteImage, // destination image
                $image,       // image to copy onto destination image
                0,            // start point x on destination
                $this->sprite_height*($number-1),   // start point y on destination
                0,            // start point x on image
                0,            // start point y on image
                $this->sprite_width, // width of image to copy onto destination
                $this->sprite_height // height of image to copy onto destinaion
            );
        }

        // get final image content
        ob_start();
            header('Content-Type: image/jpeg');
            imagejpeg($spriteImage);
            $sprite = ob_get_contents();
        ob_end_clean();

        $filesystem = $this->asset_helper->getFilesystem($cache_asset->getAdapter());

        // delete thumbnails
        for ($number = 0; $number <= $numberOfSprites; $number++) {
            if ($filesystem->has(sprintf($cache_asset->getFilekey(), $number+1))) {
                $filesystem->delete(sprintf($cache_asset->getFilekey(), $number+1));
            }
        }

        // finalize asset, write to adapter, link to episode
        $cache_asset->setFilekey(uniqID());
        $filesystem->write($cache_asset, $sprite);
        $this->getContainer()->get('oktolab_media_helper')->deleteEpisodeSprite($episode);
        $episode->setSprite($cache_asset);
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $em->persist($episode);
        $em->flush();

        // move sprite to correct destination
        $this->getContainer()->get('bprs.asset_job')->addMoveAssetJob(
            $cache_asset,
            $this->getContainer()->getParameter('oktolab_media.sprite_filesystem'),
            $this->getContainer()->getParameter('oktolab_media.sprite_worker_queue')
        );
    }

    private function createNewCacheAssetForSprite($episode)
    {
        $asset = $this->getContainer()->get('bprs.asset')->createAsset();
        $asset->setFilekey($episode->getUniqID()."_spritethumb_%04d.jpg");

        $asset->setAdapter(
            $this->getContainer()->getParameter(
                'oktolab_media.encoding_filesystem'
            )
        );
        $asset->setName($episode->getUniqID().'_sprite');
        $asset->setMimetype('image/jpeg');

        return $asset;
    }
}
?>
