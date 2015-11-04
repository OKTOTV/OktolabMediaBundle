<?php

namespace Oktolab\MediaBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;

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
 * @JMS\ExclusionPolicy("all")
 * @JMS\AccessType("public_method")
 */
class Episode implements EpisodeMergerInterface
{
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
     * @ORM\Column(name="name", type="string", length=255, nullable=true)
     */
    private $name;

    /**
     * @var string
     * @JMS\Expose
     * @JMS\Type("string")
     * @ORM\Column(name="description", type="string", length=500, nullable=true)
     */
    private $description;

    /**
     * @var boolean
     * @JMS\Expose
     * @JMS\Type("boolean")
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
     * @ORM\Column(name="firstran_at", type="datetime", nullable=true)
     */
    private $firstranAt;


    /**
     * @var \DateTime
     * @JMS\Expose
     * @JMS\Type("DateTime")
     * @ORM\Column(name="online_start", type="datetime", nullable=true)
     */
    private $onlineStart;

    /**
     * @var \DateTime
     * @JMS\Expose
     * @JMS\Type("DateTime")
     * @ORM\Column(name="online_end", type="datetime", nullable=true)
     */
    private $onlineEnd;

    /**
     * @var string
     * @JMS\Expose
     * @JMS\Type("string")
     * @ORM\Column(name="uniqID", type="string", length=13)
     */
    private $uniqID;

    /**
    * @JMS\Expose
    * @JMS\ReadOnly
    * @ORM\OneToOne(targetEntity="Bprs\AssetBundle\Entity\AssetInterface")
    * @ORM\JoinColumn(name="video_id", referencedColumnName="id")
    */
    private $video;

    /**
    * @JMS\Expose
    * @JMS\ReadOnly
    * @ORM\OneToOne(targetEntity="Bprs\AssetBundle\Entity\AssetInterface")
    * @ORM\JoinColumn(name="posterframe_id", referencedColumnName="id")
    * @JMS\Type("string")
    */
    private $posterframe;

    public function __construct()
    {
        $this->isActive = true;
        $this->uniqID = uniqid();
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

    public function merge(Episode $episode)
    {
        $this->name = $episode->getName();
        $this->description = $episode->getDescription();
        $this->uniqID = $episode->getUniqID();
        $this->isActive = $episode->getIsActive();
        $this->onlineStart = $episode->getOnlineStart();
        $this->onlineEnd = $episode->getOnlineEnd();
        $this->createdAt = $episode->getCreatedAt();
        $this->firstranAt = $episode->getFirstranAt();
    }
}
