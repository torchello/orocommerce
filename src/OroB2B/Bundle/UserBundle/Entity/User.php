<?php

namespace OroB2B\Bundle\UserBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="orob2b_user")
 */
class User extends AbstractUser
{
    /**
     * @ORM\ManyToMany(targetEntity="OroB2B\Bundle\UserBundle\Entity\Group")
     * @ORM\JoinTable(name="orob2b_user_group",
     *      joinColumns={@ORM\JoinColumn(name="user_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="group_id", referencedColumnName="id")}
     * )
     */
    protected $groups;
}
