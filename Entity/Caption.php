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

    const OKTOLAB_CAPTIONKIND_SUB = "subtitles";
    const OKTOLAB_CAPTIONKIND_CAP = "captions";
    const OKTOLAB_CAPTIONKIND_CHAP = "chapters";
    const OKTOLAB_CAPTIONKIND_DESC = "description";

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
     * @ORM\Column(type="string", length=20)
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

    public function __toString()
    {
        return $this->label;
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
}
