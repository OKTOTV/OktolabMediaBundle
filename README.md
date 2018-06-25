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
    # enitites
    episode_class: "Your\Name\Space\For\Episode" # namespace to your Episode
    media_class: "Oktolab\MediaBundle\Entity\Media" # usually you dont need to change this
    series_class: "Your\Name\Space\For\Series" # namespace to your Series
    asset_class: "Your\Name\Space\For\Asset" # see the BprsAssetBundle for more information!
    # work in progress
    stream_class: "Your\Name\Space\For\Stream # used for livestreams. still work in progress
    
    # cornerbug settings
    origin:
        url: "https://your.websi.te" #https://www.oktolab.at
        position: "top-right" #top-right | top-left | bottm-right | bottom-left
        margin: "8" # pixel margin inset for cornerbug to player
        logo:  # link to the logo (png, jpeg). can even be a svg
        
    # encoding settings
    keep_original: true # if you want to keep the original uploaded video when encoding
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
            
     #work in progress
    streamservers:
        player_url: #the public side stream CDN link
        rtmp_url:  #the private side RTMP Server url. Nginx or Wowza, etc.
        rtmp_control: #the private side RTMP Control module URl. (see Nginx rmtp_module)
         
    player_type: jwplayer (you can implement any other player like for example flowplayer, etc.
    player_url:  the javascript link for your player.
     
    encoding_filesystem: "cache" # the name of your filesystem to use for ffmpeg to write the temporary files to.
    posterframe_filesystem: "posterframe" # the name of the filesystem to use for posterframes
    sprite_fileystem: "posterframe" # the name of the filesystem to use for sprites
    default_filesystem: "video" # fallback filesystem if nothing else is defined
    serializing_schema: # the serializing schema to use for importing external episodes and series!
    worker_queue: oktolab_media # the worker queue for encoding episodes. see BprsCommandLineBundle for more
    sprite_worker_queue: oktolab_media_sprite # the worker queue for sprites
    sprite_height: 180 # defines the resolution for sprite images
    sprite_width: 320 # defines the resolution for sprite images
    sprite_interval: 5 # defines the minimum interval for sprites being made. Will automatically be higher if the video is longer

    api_urls:
        # defines what urls can be offered for API use. default urls are:
        'oktolab_media_api_list_series'
        'oktolab_media_api_show_series'
        'oktolab_media_api_list_episodes'
        'oktolab_media_api_show_episode'
        'oktolab_media_api_show_asset'
        'oktolab_media_api_import_series'
        'oktolab_media_api_import_episode'
        'oktolab_media_embed_episode'
        'oktolab_media_caption_for_episode'
        'oktolab_media_origin_for_episode'
        # these and your urls will be added to the bprs_applinkhelper service as available_apis. See BprsAppLinkBundle for more info


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
Notice: Bundle Inheritance is deprecated starting with Symfony3. You can still overwrite any part of this Bundle (https://symfony.com/doc/3.4/bundles/override.html)
