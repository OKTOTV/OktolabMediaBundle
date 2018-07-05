<?php

namespace Oktolab\MediaBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Caption
 *
 * @ORM\Table()
 * @ORM\Entity()
 * @ORM\HasLifecycleCallbacks()
 * @JMS\ExclusionPolicy("all")
 * @JMS\AccessType("public_method")
 */
class Caption {

    const OKTOLAB_CAPTIONKIND_SUB = "subtitles"; // for languages (not used by jw player)
    const OKTOLAB_CAPTIONKIND_CAP = "captions";  // for the deaf or hearing impaired
    const OKTOLAB_CAPTIONKIND_CHAP = "chapters"; // start times of chapters in a clip
    const OKTOLAB_CAPTIONKIND_DESC = "description"; // damn, i forgot and cant find a reference anymore
    const OKTOLAB_CAPTIONKIND_THUMB = "thumbnails"; // reference images in a sprite for the player

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @JMS\Expose
     * @JMS\ReadOnly
     */
    private $id;

    /**
     * @var string
     * @JMS\Expose
     * @JMS\Type("string")
     * @JMS\SerializedName("uniqID")
     * @JMS\Groups({"oktolab"})
     * @ORM\Column(name="uniqID", type="string", length=13)
     */
    private $uniqID;

    /**
     * shown text for capture selection
     *
     * @JMS\Expose
     * @JMS\Type("string")
     * @JMS\Groups({"oktolab"})
     * @Assert\NotBlank()
     * @ORM\Column(type="string", length=20, nullable=true)
     */
    private $label;

    /**
     * the webVTT formatted caption.
     * @JMS\Expose
     * @JMS\Type("string")
     * @JMS\Groups({"search", "oktolab"})
     * @Assert\NotBlank()
     * @ORM\Column(type="text")
     */
    private $content;

    /**
     * the type of the caption. subtitle, captions, chapter or descriptions
     * @Assert\NotBlank()
     * @ORM\Column(type="string", length=20)
     */
    private $kind;

    /**
     * @ORM\ManyToOne(targetEntity="Oktolab\MediaBundle\Entity\EpisodeInterface", inversedBy="captions")
     * @ORM\JoinColumn(name="episode_id", referencedColumnName="id")
     */
    private $episode;

    /**
     * @ORM\Column(name="public", type="boolean", options={"default"=false})
     */
    private $public;

    public function __toString()
    {
        return $this->label;
    }

    public function __construct()
    {
        $this->uniqID = uniqid();
    }

    public function getId()
    {
        return $this->id;
    }

    public function setUniqID($uniqID)
    {
        $this->uniqID = $uniqID;

        return $this;
    }

    public function getUniqID()
    {
        return $this->uniqID;
    }

    public function setLabel($label)
    {
        $this->label = $label;
        return $this;
    }

    public function getLabel()
    {
        return $this->label;
    }

    public function setContent($content)
    {
        $this->content = $content;
    }

    public function getContent()
    {
        return $this->content;
    }

    public function setKind($kind)
    {
        $this->kind = $kind;
    }

    public function getKind()
    {
        return $this->kind;
    }

    public function setEpisode($episode)
    {
        $this->episode = $episode;
        return $this;
    }

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
