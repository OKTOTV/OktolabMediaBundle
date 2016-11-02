<?php

namespace Oktolab\MediaBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Media
 *
 * @ORM\Table()
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks()
 * @JMS\ExclusionPolicy("all")
 * @JMS\AccessType("public_method")
 */
class Media
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
     * @Assert\NotBlank()
     * @JMS\Expose
     * @JMS\Type("string")
     * @ORM\Column(name="quality", type="string", length=20)
     */
    private $quality;

    /**
     * @Assert\NotBlank()
     * @ORM\Column(name="sortNumber", type="integer")
     */
    private $sortNumber;

    /**
     * @JMS\Expose
     * @JMS\Type("DateTime")
     * @ORM\Column(name="created_at", type="datetime")
     */
    private $createdAt;

    /**
     * @JMS\Expose
     * @JMS\Type("DateTime")
     * @ORM\Column(name="updated_at", type="datetime")
     */
    private $updatedAt;

    /**
    * @JMS\Expose
    * @JMS\ReadOnly
    * @ORM\OneToOne(targetEntity="Bprs\AssetBundle\Entity\AssetInterface", fetch="EAGER", cascade={"remove", "persist"})
    */
    private $asset;

    /**
     * @JMS\Expose
     * @ORM\ManyToOne(targetEntity="Oktolab\MediaBundle\Entity\EpisodeInterface", inversedBy="media")
     * @ORM\JoinColumn(name="episode_id", referencedColumnName="id")
     */
    private $episode;

    /**
     * @ORM\Column(name="public", type="boolean", options={"default"=true})
     */
    private $public;

    public function __construct()
    {

    }

    public function __toString()
    {
        return $this->quality;
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
     * Set quality
     *
     * @param string $quality
     * @return Media
     */
    public function setQuality($quality)
    {
        $this->quality = $quality;

        return $this;
    }

    /**
     * Get quality
     *
     * @return string
     */
    public function getQuality()
    {
        return $this->quality;
    }

    /**
     * Set sortNumber
     *
     * @param integer $sortNumber
     * @return Media
     */
    public function setSortNumber($sortNumber)
    {
        $this->sortNumber = $sortNumber;

        return $this;
    }

    /**
     * Get sortNumber
     *
     * @return integer
     */
    public function getSortNumber()
    {
        return $this->sortNumber;
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
     * Set asset
     *
     * @param \AppBundle\Entity\Asset $asset
     * @return Media
     */
    public function setAsset($asset = null)
    {
        $this->asset = $asset;

        return $this;
    }

    /**
     * Get asset
     *
     * @return \AppBundle\Entity\Asset
     */
    public function getAsset()
    {
        return $this->asset;
    }

    /**
     * Set episode
     *
     * @param \Oktolab\MediaBundle\Entity\EpisodeInterface $episode
     * @return Media
     */
    public function setEpisode($episode = null)
    {
        $this->episode = $episode;

        return $this;
    }

    /**
     * Get episode
     *
     * @return \Oktolab\MediaBundle\Entity\EpisodeInterface
     */
    public function getEpisode()
    {
        return $this->episode;
    }

    /**
     * Set public
     *
     * @param boolean $public
     * @return Media
     */
    public function setPublic($public)
    {
        $this->public = $public;

        return $this;
    }

    /**
     * Get public
     *
     * @return boolean
     */
    public function getPublic()
    {
        return $this->public;
    }
}
