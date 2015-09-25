<?php

namespace Oktolab\MediaBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

interface SeriesMergerInterface
{
    public function merge(Series $series);
}

/**
 * Series
 *
 * @ORM\Table()
 * @ORM\MappedSuperclass()
 * @ORM\HasLifecycleCallbacks()
 */
class Series implements SeriesMergerInterface
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255)
     */
    private $name;

    /**
    * @ORM\Column(name="webtitle", type="string", length=255, unique=true)
    */
    private $webtitle;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="string", length=500)
     */
    private $description;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_active", type="boolean")
     */
    private $isActive;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_at", type="datetime")
     */
    private $createdAt;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated_at", type="datetime")
     */
    private $updatedAt;

    /**
     * @var string
     * @ORM\Column(name="uniqID", type="string", length=13)
     */
    private $uniqID;

    /**
    *
    * @ORM\OneToMany(targetEntity="Oktolab\MediaBundle\Entity\Episode", mappedBy="series")
    */
    private $episodes;

    /**
    * @ORM\OneToOne(targetEntity="Oktolab\MediaBundle\Entity\Asset")
    * @ORM\JoinColumn(name="posterframe_id", referencedColumnName="id")
    */
    private $posterframe;

    public function __construct() {
        $this->uniqID = uniqid();
        $this->isActive = true;
        $this->episodes = new ArrayCollection();
    }

    public function __toString() {
        return $this->name;
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
     * @return Series
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
     * @return Series
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
     * @return Series
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
     *
     * @param \DateTime $createdAt
     * @return Series
     * @ORM\PrePersist
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
     *
     * @param \DateTime $updatedAt
     * @return Series
     * @ORM\PrePersist
     * @ORM\PreUpdate
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
     * Set webtitle
     *
     * @param string $webtitle
     * @return Series
     */
    public function setWebtitle($webtitle)
    {
        $this->webtitle = $webtitle;

        return $this;
    }

    /**
     * Get webtitle
     *
     * @return string
     */
    public function getWebtitle()
    {
        return $this->webtitle;
    }

    /**
     * Add episodes
     *
     * @param \Oktolab\MediaBundle\Entity\Episode $episodes
     * @return Series
     */
    public function addEpisode(\Oktolab\MediaBundle\Entity\Episode $episodes)
    {
        $this->episodes[] = $episodes;
        return $this;
    }

    /**
     * Remove episodes
     *
     * @param \Oktolab\MediaBundle\Entity\Episode $episodes
     */
    public function removeEpisode(\Oktolab\MediaBundle\Entity\Episode $episodes)
    {
        $this->episodes->removeElement($episodes);
    }

    /**
     * Get episodes
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getEpisodes()
    {
        return $this->episodes;
    }

    /**
     * Set posterframe
     *
     * @param \Oktolab\MediaBundle\Entity\Asset $posterframe
     * @return Episode
     */
    public function setPosterframe(\Oktolab\MediaBundle\Entity\Asset $posterframe = null)
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

    public function merge(Series $series)
    {
        $this->name = $series->getName();
        $this->description = $series->getDescription();
        $this->webtitle = $series->webtitle();
        $this->isActive = $series->isActive();
    }
}
