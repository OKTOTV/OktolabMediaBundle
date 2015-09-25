<?php

namespace Oktolab\MediaBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Bprs\AssetBundle\Entity\Asset as BaseAsset;

/**
 * Asset
 *
 * @ORM\Table()
 * @ORM\Entity
 */
 class Asset extends BaseAsset
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
     * @ORM\Column(name="name", type="string", length=1024)
     */
     private $name;


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
      * @return Asset
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
 }
