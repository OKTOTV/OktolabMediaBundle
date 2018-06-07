# OktolabMediaBundle
In- and exportable media-metainformations with Api. 

Notice: Standalone, the OktolabMediaBundle isn't very useful. It is intended to be overwritten and extended. Check out the OktoMediaBundle (https://github.com/OKTOTV/OktoMediaBundle) for a more finished and ready to use Bundle.
## Installation
```
composer require oktolab/media-bundle
```
Activate Bundle
```php
//AppKernel.php
    public function registerBundles()
    {
        $bundles = array(
          ...
          new Okto\MediaBundle\MediaBundle(),
          ...
        );
    }
```
## Configure Bundle
```yml
# resolve target entities for Media linking

orm:
    resolve_target_entities:
        Oktolab\MediaBundle\Entity\EpisodeInterface: Okto\MediaBundle\Entity\Episode

oktolab_media:
    episode_class: "Your\Name\Space\For\Episode" #link to your Episode
    series_class: "Your\Name\Space\For\Series" # link to your Series
    asset_class: "Your\Name\Space\For\Asset" # see the BprsAssetBundle for more information!
    keep_original: true # if you want to keep the original uploaded video
    resolutions:
        720p: # your resolution
            name: 720p # name that can be displayed in your player/media
            sortNumber: 1 # sorts episode media by number
            video_codec: h264 # the name of the codec you want to use
            video_framerate: 50/1 # frames per second you want to use
            video_width: 1280 # pixel width of video
            video_height: 720 # pixel height of video
            audio_codec: aac # codec you want to use for your audio
            audio_sample_rate: 48000 #sample rate for your audio
            container: mov # container you want to use
            public: true # flag for your media. can be used to show or hide media

# You'll need to configure the bprsAssetBundle!
# Example

oneup_flysystem:
    adapters:
        gallery:
            local:
                directory: %kernel.root_dir%/../web/uploads/posterframes
        video:
            local:
                directory: %kernel.root_dir%/../web/uploads/videos

    filesystems:
        gallery:
            adapter: gallery
            mount: gallery
        video:
            adapter: video
            mount: video

oneup_uploader:
    chunks:
        maxage: 86400
        storage:
            directory: %kernel.cache_dir%/uploader/chunks
    mappings:
        gallery:
            frontend: blueimp
            storage:
                type: flysystem
                filesystem: oneup_flysystem.gallery_filesystem
        video:
            frontend: blueimp
            storage:
                type: flysystem
                filesystem: oneup_flysystem.video_filesystem
    
  bprs_asset:
      class:  "Your\Name\Space\For\Asset"
      filesystem_map: oneup_flysystem.mount_manager
      worker_queue: %worker_queue%
      adapters:
          gallery:
              url:  "http://YourProject/uploads/posterframes"
              path: "/path/to/your/assets/web/uploads/posterframes"
          video:
              url:  "http://YourProject/uploads/videos"
              path: "/path/to/your/assets/web/uploads/videos"
```

## Routing
```yml
oktolab_media:
    resource: .
    type: oktolab_media
```
You can always adapt and overwrite this Bundle AND the routing with Bundle Inheritance and an advanced routing loader
This bundle comes with a predefined jms serializer configuration for series and episodes (and can be directly used with elastica bundle)
