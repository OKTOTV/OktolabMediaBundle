<?php

namespace Oktolab\MediaBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Playlistitem
 *
 * @ORM\Table()
 * @ORM\Entity
 */
class Playlistitem
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
     * @var integer
     *
     * @ORM\Column(name="sortnumber", type="integer")
     */
    private $sortnumber;

    /**
    * @ORM\ManyToOne(targetEntity="Oktolab\MediaBundle\Entity\PlaylistInterface", inversedBy="items")
    * @ORM\JoinColumn(name="playlist_id", referencedColumnName="id")
    */
    private $playlist;

    /**
    * @ORM\ManyToOne(targetEntity="Oktolab\MediaBundle\Entity\EpisodeInterface", inversedBy="playlistitems")
    * @ORM\JoinColumn(name="episode_id", referencedColumnName="id")
    */
    private $episode;

    public function __construct()
    {
        $this->sortnumber = 1;
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
     * Set sortnumber
     *
     * @param integer $sortnumber
     * @return Playlistitem
     */
    public function setSortnumber($sortnumber)
    {
        $this->sortnumber = $sortnumber;

        return $this;
    }

    /**
     * Get sortnumber
     *
     * @return integer
     */
    public function getSortnumber()
    {
        return $this->sortnumber;
    }

    /**
     * Set playlist
     *
     * @param \AppBundle\Entity\Playlist $playlist
     * @return Playlistitem
     */
    public function setPlaylist($playlist = null)
    {
        $this->playlist = $playlist;

        return $this;
    }

    /**
     * Get playlist
     *
     * @return \AppBundle\Entity\Playlist
     */
    public function getPlaylist()
    {
        return $this->playlist;
    }

    /**
     * Set episode
     *
     * @param \MediaBundle\Entity\Episode $episode
     * @return Playlistitem
     */
    public function setEpisode($episode = null)
    {
        $this->episode = $episode;

        return $this;
    }

    /**
     * Get episode
     *
     * @return \MediaBundle\Entity\Episode
     */
    public function getEpisode()
    {
        return $this->episode;
    }
}
