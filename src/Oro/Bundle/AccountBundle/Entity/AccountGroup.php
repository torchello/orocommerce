<?php

namespace Oro\Bundle\AccountBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;

/**
 * @ORM\Entity(repositoryClass="Oro\Bundle\AccountBundle\Entity\Repository\AccountGroupRepository")
 * @ORM\Table(
 *      name="oro_account_group",
 *      indexes={
 *          @ORM\Index(name="oro_account_group_name_idx", columns={"name"})
 *      }
 * )
 * @Config(
 *      routeName="oro_account_group_index",
 *      routeView="oro_account_group_view",
 *      routeUpdate="oro_account_group_update",
 *      defaultValues={
 *          "entity"={
 *              "icon"="icon-group"
 *          },
 *          "form"={
 *              "form_type"="oro_account_group_select",
 *              "grid_name"="account-groups-select-grid",
 *          },
 *          "dataaudit"={
 *              "auditable"=true
 *          }
 *      }
 * )
 */
class AccountGroup
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255)
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $name;

    /**
     * @var Collection|Account[]
     *
     * @ORM\OneToMany(targetEntity="Oro\Bundle\AccountBundle\Entity\Account", mappedBy="group")
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     **/
    protected $accounts;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->accounts = new ArrayCollection();
    }

    /**
     * Get id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return AccountGroup
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
     * Add account
     *
     * @param Account $account
     * @return AccountGroup
     */
    public function addAccount(Account $account)
    {
        if (!$this->accounts->contains($account)) {
            $this->accounts->add($account);
        }

        return $this;
    }

    /**
     * Remove account
     *
     * @param Account $account
     */
    public function removeAccount(Account $account)
    {
        if ($this->accounts->contains($account)) {
            $this->accounts->removeElement($account);
        }
    }

    /**
     * Get accounts
     *
     * @return Collection|Account[]
     */
    public function getAccounts()
    {
        return $this->accounts;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return (string)$this->name;
    }
}
