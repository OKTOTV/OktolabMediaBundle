<?php

namespace Oktolab\MediaBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;
use Symfony\Component\Validator\Constraints as Assert;
use Oktolab\MediaBundle\Entity\Episode;

/**
 * Episode
 *
 * @ORM\Table()
 * @ORM\HasLifecycleCallbacks()
 * @ORM\MappedSuperclass()
 * @JMS\ExclusionPolicy("all")
 * @JMS\AccessType("public_method")
 */
class Stream
{
    const STATE_SETUP = 0;          // stream is in preparation
    const STATE_RECEIVING = 5;      // rtmp server requested if stream is allowed to receive
    const STATE_RECORDING = 10;     // one or more media does not exist
    const STATE_STOPPED_RECORDING = 20; // recording is stoped, stream could still go on
    const STATE_ENDED = 30;         // started job to check this

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
     * @Assert\Length(max = 950, maxMessage = "oktolab_media.max_description_limit")
     * @JMS\Expose
     * @JMS\Type("string")
     * @JMS\Groups({"search", "oktolab"})
     * @ORM\Column(name="description", type="text", length=950, nullable=true)
     */
    private $description;

    /**
     * @JMS\Expose
     * @JMS\Type("integer")
     * @JMS\Groups({"oktolab"})
     * @ORM\Column(name="stereomode", type="integer", options={"default"=0})
     */
    private $stereomode;

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
     * @var string
     * @JMS\Expose
     * @JMS\Type("string")
     * @JMS\SerializedName("uniqID")
     * @JMS\Groups({"search", "oktolab"})
     * @ORM\Column(name="uniqID", type="string", length=13)
     */
    private $uniqID;

    /**
     * @ORM\Column(name="technical_status", type="integer", nullable=true, options={"default" = 0})
     */
    private $technical_status;

    /**
     * @ORM\Column(name="rtmp_server", type="string", nullable=true, length=300)
     */
    private $rtmp_server;


    public function __construct()
    {
        $this->technical_status = $this::STATE_SETUP;
        $this->uniqID = uniqid();
        $this->createdAt = new \Datetime();
        $this->updatedAt = new \Datetime();
        $this->stereomode = Episode::STEREOMODE_NONE;
    }

    public function __toString()
    {
        return $this->name.'_'.$this->uniqID;
    }

    public function getRtmpServer()
    {
        return $this->rtmp_server;
    }

    public function setRtmpServer($server_config)
    {
        $this->rtmp_server = $server_config;
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
     * Set uniqID
     *
     * @param string $uniqID
     * @return Stream
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

    public function getTechnicalStatus()
    {
        return $this->technical_status;
    }

    public function setTechnicalStatus($technical_status)
    {
        $this->technical_status = $technical_status;
        return $this;
    }

    public function getStereomode()
    {
        return $this->stereomode;
    }

    public function setStereomode($mode)
    {
        $this->stereomode = $mode;
        return $this;
    }
}
