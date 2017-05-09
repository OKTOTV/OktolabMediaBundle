<?php

namespace Oktolab\MediaBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;
use Symfony\Component\Validator\Constraints as Assert;

interface EpisodeMergerInterface
{
    public function merge(Episode $episode);
}


/**
 * Episode
 *
 * @ORM\Table()
 * @ORM\MappedSuperclass()
 * @ORM\HasLifecycleCallbacks()
 * @ORM\EntityListeners({"Oktolab\MediaBundle\Event\EpisodeLifecycleListener"})
 * @JMS\ExclusionPolicy("all")
 * @JMS\AccessType("public_method")
 */
class Episode implements EpisodeMergerInterface
{
    const STATE_READY = 50;                 // Video exists, Media is encoded
    const STATE_FINALIZING_FAILED = 49;     // one or more media does not exist
    const STATE_FINALIZING = 48;            // started job to check this
    const STATE_IN_FINALIZE_QUEUE = 47;     // waiting to be checked
    const STATE_IN_PROGRESS_NO_VIDEO = 41;   // Video does not exist in expected place.
    const STATE_IN_PROGRESS = 40;           // Video exists, Media is encoding
    const STATE_IN_PROGRESS_QUEUE = 30;     // Video exists, Media encoding in queue
    const STATE_IMPORTING = 20;             // Video is importing
    const STATE_IMPORTING_QUEUE = 10;       // Video importing is in queue
    const STATE_NOT_READY = 0;              // No Video. Episode not ready for anything exept metadata

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @JMS\Exclude
     * @JMS\ReadOnly
     */
    private $id;

    /**
     * @var string
     * @JMS\Expose
     * @JMS\Type("string")
     * @JMS\Groups({"search", "oktolab"})
     * @ORM\Column(name="name", type="string", length=255, nullable=true)
     */
    private $name;

    /**
     * @var string
     * @Assert\Length(max = 700, maxMessage = "oktolab_media.max_description_limit")
     * @JMS\Expose
     * @JMS\Type("string")
     * @JMS\Groups({"search", "oktolab"})
     * @ORM\Column(name="description", type="text", length=750, nullable=true)
     */
    private $description;

    /**
     * @var boolean
     * @JMS\Expose
     * @JMS\Type("boolean")
     * @JMS\Groups({"search", "oktolab"})
     * @ORM\Column(name="is_active", type="boolean")
     */
    private $isActive;

    /**
     * @var \DateTime
     * @JMS\Expose
     * @JMS\Type("DateTime")
     * @ORM\Column(name="created_at", type="datetime")
     */
    private $createdAt;

    /**
     * @var \DateTime
     * @JMS\Expose
     * @JMS\Type("DateTime")
     * @ORM\Column(name="updated_at", type="datetime")
     */
    private $updatedAt;

    /**
     * @var \DateTime
     * @JMS\Expose
     * @JMS\Type("DateTime")
     * @JMS\Groups({"search", "oktolab"})
     * @ORM\Column(name="firstran_at", type="datetime", nullable=true)
     */
    private $firstranAt;


    /**
     * @var \DateTime
     * @JMS\Expose
     * @JMS\Type("DateTime")
     * @JMS\Groups({"search"})
     * @ORM\Column(name="online_start", type="datetime", nullable=true)
     */
    private $onlineStart;

    /**
     * @var \DateTime
     * @JMS\Expose
     * @JMS\Type("DateTime")
     * @JMS\Groups({"search"})
     * @ORM\Column(name="online_end", type="datetime", nullable=true)
     */
    private $onlineEnd;

    /**
     * @var string
     * @JMS\Expose
     * @JMS\Type("string")
     * @JMS\SerializedName("uniqID")
     * @JMS\Groups({"search", "oktolab"})
     * @ORM\Column(name="uniqID", type="string", length=13)
     */
    private $uniqID;

    /**
    * @JMS\Expose
    * @JMS\Groups({"oktolab"})
    * @JMS\Type("string")
    * @ORM\OneToOne(targetEntity="Bprs\AssetBundle\Entity\AssetInterface", fetch="EAGER", cascade={"remove", "persist"})
    * @ORM\JoinColumn(name="video_id", referencedColumnName="id")
    */
    private $video;

    /**
    * @JMS\Expose
    * @JMS\Groups({"oktolab", "episode_posterframe"})
    * @ORM\OneToOne(targetEntity="Bprs\AssetBundle\Entity\AssetInterface", fetch="EAGER", cascade={"remove", "persist"})
    * @ORM\JoinColumn(name="posterframe_id", referencedColumnName="id")
    * @JMS\Type("string")
    */
    private $posterframe;

    /**
     * @ORM\Column(name="technical_status", type="integer", nullable=true, options={"default" = 0})
     */
    private $technical_status;

    /**
     * @ORM\ManyToOne(targetEntity="Bprs\AppLinkBundle\Entity\Keychain")
     * @ORM\JoinColumn(name="keychain_id", referencedColumnName="id", nullable=true)
     */
    protected $keychain;

    /**
     * @JMS\Expose
     * @JMS\Groups({"oktolab"})
     * @JMS\Type("ArrayCollection<Oktolab\MediaBundle\Entity\Caption>")
     * @ORM\OneToMany(targetEntity="Oktolab\MediaBundle\Entity\Caption", mappedBy="episode", cascade={"remove"})
     */
    protected $captions;

    /**
     *
     * @ORM\OneToMany(targetEntity="Oktolab\MediaBundle\Entity\Media", mappedBy="episode", cascade={"remove", "persist"})
     */
    protected $media;

    /**
     * the duration of the episode in seconds.milliseconds
     * @ORM\Column(name="duration", type="integer", options={"default" = 0})
     */
    private $duration;

    public function __construct()
    {
        $this->technical_status = $this::STATE_NOT_READY;
        $this->isActive = false;
        $this->uniqID = uniqid();
        $this->createdAt = new \Datetime();
        $this->updatedAt = new \Datetime();
        $this->duration = 0;
    }

    public function __toString()
    {
        return $this->name.'_'.$this->uniqID;
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return Episode
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set description
     *
     * @param string $description
     * @return Episode
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set isActive
     *
     * @param boolean $isActive
     * @return Episode
     */
    public function setIsActive($isActive)
    {
        $this->isActive = $isActive;

        return $this;
    }

    /**
     * Get isActive
     *
     * @return boolean
     */
    public function getIsActive()
    {
        return $this->isActive;
    }

    /**
     * Set createdAt
     * @ORM\PrePersist
     * @param \DateTime $createdAt
     * @return Episode
     */
    public function setCreatedAt()
    {
        $this->createdAt = new \DateTime();
        return $this;
    }

    /**
     * Get createdAt
     *
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Set updatedAt
     * @ORM\PrePersist
     * @ORM\PreUpdate
     *
     * @param \DateTime $updatedAt
     * @return Episode
     */
    public function setUpdatedAt()
    {
        $this->updatedAt = new \DateTime();
        return $this;
    }

    /**
     * Get updatedAt
     *
     * @return \DateTime
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * Set FirstranAt
     *
     * @param \DateTime $onlineStart
     * @return Episode
     */
    public function setFirstranAt($FirstranAt)
    {
        $this->firstranAt = $FirstranAt;

        return $this;
    }

    /**
     * Get FirstranAt
     *
     * @return \DateTime
     */
    public function getFirstranAt()
    {
        return $this->firstranAt;
    }

    /**
     * Set onlineStart
     *
     * @param \DateTime $onlineStart
     * @return Episode
     */
    public function setOnlineStart($onlineStart)
    {
        $this->onlineStart = $onlineStart;

        return $this;
    }

    /**
     * Get onlineStart
     *
     * @return \DateTime
     */
    public function getOnlineStart()
    {
        return $this->onlineStart;
    }

    /**
     * Set onlineEnd
     *
     * @param \DateTime $onlineEnd
     * @return Episode
     */
    public function setOnlineEnd($onlineEnd)
    {
        $this->onlineEnd = $onlineEnd;

        return $this;
    }

    /**
     * Get onlineEnd
     *
     * @return \DateTime
     */
    public function getOnlineEnd()
    {
        return $this->onlineEnd;
    }

    /**
     * Set uniqID
     *
     * @param string $uniqID
     * @return Episode
     */
    public function setUniqID($uniqID)
    {
        $this->uniqID = $uniqID;

        return $this;
    }

    /**
     * Get uniqID
     *
     * @return string
     */
    public function getUniqID()
    {
        return $this->uniqID;
    }

    /**
     * Set video
     *
     * @param \Oktolab\MediaBundle\Entity\Asset $video
     * @return Episode
     */
    public function setVideo($video = null)
    {
        $this->video = $video;

        return $this;
    }

    /**
     * Get video
     *
     * @return \Oktolab\MediaBundle\Entity\Asset
     */
    public function getVideo()
    {
        return $this->video;
    }

    /**
     * Set posterframe
     *
     * @param \Oktolab\MediaBundle\Entity\Asset $posterframe
     * @return Episode
     */
    public function setPosterframe($posterframe = null)
    {
        $this->posterframe = $posterframe;

        return $this;
    }

    /**
     * Get posterframe
     *
     * @return \Oktolab\MediaBundle\Entity\Asset
     */
    public function getPosterframe()
    {
        return $this->posterframe;
    }

    public function getTechnicalStatus()
    {
        return $this->technical_status;
    }

    public function setTechnicalStatus($technical_status)
    {
        $this->technical_status = $technical_status;
        return $this;
    }

    public function getKeychain()
    {
        return $this->keychain;
    }

    public function setKeychain($keychain)
    {
        $this->keychain = $keychain;
        return $this;
    }

    public function getCaptions()
    {
        return $this->captions;
    }

    public function setCaptions($captions)
    {
        $this->captions = $captions;
    }

    public function addCaption($caption)
    {
        $this->captions[] = $caption;
        $caption->setEpisode($this);
        return $this;
    }

    public function removeCaption($caption)
    {
        $this->captions->removeElement($caption);
        $caption->setEpisode(null);
        return $this;
    }

    /**
     * Add media
     *
     * @param \Oktolab\MediaBundle\Entity\Media $media
     * @return Episode
     */
    public function addMedia($media)
    {
        $this->media[] = $media;
        $media->setEpisode($this);
        return $this;
    }

    public function setMedia($media)
    {
        $this->media = $media;
    }

    /**
     * Remove media
     *
     * @param \Oktolab\MediaBundle\Entity\Media $media
     */
    public function removeMedia($media)
    {
        $this->media->removeElement($media);
    }

    /**
     * Get media
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getMedia()
    {
        return $this->media;
    }

    public function merge(Episode $episode)
    {
        $this->name = $episode->getName();
        $this->description = $episode->getDescription();
        $this->uniqID = $episode->getUniqID();
        $this->isActive = $episode->getIsActive();
        $this->onlineStart = $episode->getOnlineStart();
        $this->onlineEnd = $episode->getOnlineEnd();
        $this->firstranAt = $episode->getFirstranAt();
    }

    public function getDuration()
    {
        return $this->duration;
    }

    public function setDuration($duration)
    {
        $this->duration = $duration;
        return $this;
    }
}
