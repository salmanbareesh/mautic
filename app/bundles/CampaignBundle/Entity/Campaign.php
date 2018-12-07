<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CampaignBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping as ORM;
use Mautic\ApiBundle\Serializer\Driver\ApiMetadataDriver;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\CoreBundle\Entity\FormEntity;
use Mautic\FormBundle\Entity\Form;
use Mautic\LeadBundle\Entity\Lead as Contact;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Entity\Tag;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Mapping\ClassMetadata;

/**
 * Class Campaign.
 */
class Campaign extends FormEntity
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $description;

    /**
     * @var null|\DateTime
     */
    private $publishUp;

    /**
     * @var null|\DateTime
     */
    private $publishDown;

    /**
     * @var \Mautic\CategoryBundle\Entity\Category
     **/
    private $category;

    /**
     * @var ArrayCollection
     */
    private $events;

    /**
     * @var ArrayCollection
     */
    private $leads;

    /**
     * @var ArrayCollection
     */
    private $lists;

    /**
     * @var ArrayCollection
     */
    private $forms;

    /**
     * @var ArrayCollection
     */
    private $tags;

    /**
     * @var array
     */
    private $canvasSettings = [];

    /**
     * @var bool
     */
    private $allowRestart = false;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->events = new ArrayCollection();
        $this->leads  = new ArrayCollection();
        $this->lists  = new ArrayCollection();
        $this->forms  = new ArrayCollection();
        $this->tags   = new ArrayCollection();
    }

    public function __clone()
    {
        $this->leads  = new ArrayCollection();
        $this->events = new ArrayCollection();
        $this->lists  = new ArrayCollection();
        $this->forms  = new ArrayCollection();
        $this->tags   = new ArrayCollection();
        $this->id     = null;

        parent::__clone();
    }

    /**
     * @param ORM\ClassMetadata $metadata
     */
    public static function loadMetadata(ORM\ClassMetadata $metadata)
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('campaigns')
            ->setCustomRepositoryClass('Mautic\CampaignBundle\Entity\CampaignRepository');

        $builder->addIdColumns();

        $builder->addPublishDates();

        $builder->addCategory();

        $builder->createOneToMany('events', 'Event')
            ->setIndexBy('id')
            ->setOrderBy(['order' => 'ASC'])
            ->mappedBy('campaign')
            ->cascadeAll()
            ->fetchExtraLazy()
            ->build();

        $builder->createOneToMany('leads', 'Lead')
            ->setIndexBy('id')
            ->mappedBy('campaign')
            ->fetchExtraLazy()
            ->build();

        $builder->createManyToMany('lists', 'Mautic\LeadBundle\Entity\LeadList')
            ->setJoinTable('campaign_leadlist_xref')
            ->setIndexBy('id')
            ->addInverseJoinColumn('leadlist_id', 'id', false, false, 'CASCADE')
            ->addJoinColumn('campaign_id', 'id', true, false, 'CASCADE')
            ->build();

        $builder->createManyToMany('forms', 'Mautic\FormBundle\Entity\Form')
            ->setJoinTable('campaign_form_xref')
            ->setIndexBy('id')
            ->addInverseJoinColumn('form_id', 'id', false, false, 'CASCADE')
            ->addJoinColumn('campaign_id', 'id', true, false, 'CASCADE')
            ->build();

        $builder->createManyToMany('tags', 'Mautic\LeadBundle\Entity\Tag')
            ->setJoinTable('campaign_tags_xref')
            ->addInverseJoinColumn('tag_id', 'id', false)
            ->addJoinColumn('campaign_id', 'id', false, false, 'CASCADE')
            ->setOrderBy(['tag' => 'ASC'])
            ->setIndexBy('tag')
            ->fetchLazy()
            ->cascadeMerge()
            ->cascadePersist()
            ->cascadeDetach()
            ->build();

        $builder->createField('canvasSettings', 'array')
            ->columnName('canvas_settings')
            ->nullable()
            ->build();

        $builder->addNamedField('allowRestart', 'integer', 'allow_restart');
    }

    /**
     * @param ClassMetadata $metadata
     */
    public static function loadValidatorMetadata(ClassMetadata $metadata)
    {
        $metadata->addPropertyConstraint(
            'name',
            new Assert\NotBlank(
                [
                    'message' => 'mautic.core.name.required',
                ]
            )
        );
    }

    /**
     * Prepares the metadata for API usage.
     *
     * @param $metadata
     */
    public static function loadApiMetadata(ApiMetadataDriver $metadata)
    {
        $metadata
            ->setGroupPrefix('campaign')
            ->addListProperties(
                [
                    'id',
                    'name',
                    'category',
                    'description',
                ]
            )
            ->addProperties(
                [
                    'allowRestart',
                    'publishUp',
                    'publishDown',
                    'events',
                    'forms',
                    'tags',
                    'lists', // @deprecated, will be renamed to 'segments' in 3.0.0
                    'canvasSettings',
                ]
            )
            ->setGroupPrefix('campaignBasic')
            ->addListProperties(
                [
                    'id',
                    'name',
                    'description',
                    'allowRestart',
                    'events',
                    'publishUp',
                    'publishDown',
                ]
            )
            ->build();
    }

    /**
     * @return array
     */
    public function convertToArray()
    {
        return get_object_vars($this);
    }

    /**
     * @param string $prop
     * @param mixed  $val
     */
    protected function isChanged($prop, $val)
    {
        $getter  = 'get'.ucfirst($prop);
        $current = $this->$getter();
        if ($prop == 'category') {
            $currentId = ($current) ? $current->getId() : '';
            $newId     = ($val) ? $val->getId() : null;
            if ($currentId != $newId) {
                $this->changes[$prop] = [$currentId, $newId];
            }
        } elseif ('tags' === $prop) {
            if ($val instanceof Tag) {
                $this->changes['tags']['added'][] = $val->getTag();
            } else {
                $this->changes['tags']['removed'][] = $val;
            }
        } else {
            parent::isChanged($prop, $val);
        }
    }

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set description.
     *
     * @param string $description
     *
     * @return Campaign
     */
    public function setDescription($description)
    {
        $this->isChanged('description', $description);
        $this->description = $description;

        return $this;
    }

    /**
     * Get description.
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set name.
     *
     * @param string $name
     *
     * @return Campaign
     */
    public function setName($name)
    {
        $this->isChanged('name', $name);
        $this->name = $name;

        return $this;
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Calls $this->addEvent on every item in the collection.
     *
     * @param array $events
     *
     * @return Campaign
     */
    public function addEvents(array $events)
    {
        foreach ($events as $id => $event) {
            $this->addEvent($id, $event);
        }

        return $this;
    }

    /**
     * Add events.
     *
     * @param                                     $key
     * @param \Mautic\CampaignBundle\Entity\Event $event
     *
     * @return Campaign
     */
    public function addEvent($key, Event $event)
    {
        if ($changes = $event->getChanges()) {
            $this->changes['events']['added'][$key] = [$key, $changes];
        }
        $this->events[$key] = $event;

        return $this;
    }

    /**
     * Remove events.
     *
     * @param \Mautic\CampaignBundle\Entity\Event $event
     */
    public function removeEvent(\Mautic\CampaignBundle\Entity\Event $event)
    {
        $this->changes['events']['removed'][$event->getId()] = $event->getName();

        $this->events->removeElement($event);
    }

    /**
     * Get events.
     *
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function getEvents()
    {
        return $this->events;
    }

    /**
     * @return ArrayCollection
     */
    public function getRootEvents()
    {
        $criteria = Criteria::create()->where(Criteria::expr()->isNull('parent'));
        $events   = $this->getEvents()->matching($criteria);

        // Doctrine loses the indexBy mapping definition when using matching so we have to manually reset them.
        // @see https://github.com/doctrine/doctrine2/issues/4693
        $keyedArrayCollection = new ArrayCollection();
        /** @var Event $event */
        foreach ($events as $event) {
            $keyedArrayCollection->set($event->getId(), $event);
        }

        unset($events);

        return $keyedArrayCollection;
    }

    /**
     * @return ArrayCollection
     */
    public function getInactionBasedEvents()
    {
        $criteria = Criteria::create()->where(Criteria::expr()->eq('decisionPath', Event::PATH_INACTION));
        $events   = $this->getEvents()->matching($criteria);

        // Doctrine loses the indexBy mapping definition when using matching so we have to manually reset them.
        // @see https://github.com/doctrine/doctrine2/issues/4693
        $keyedArrayCollection = new ArrayCollection();
        /** @var Event $event */
        foreach ($events as $event) {
            $keyedArrayCollection->set($event->getId(), $event);
        }

        unset($events);

        return $keyedArrayCollection;
    }

    /**
     * @return ArrayCollection
     */
    public function getEventsByType($type)
    {
        $criteria = Criteria::create()->where(Criteria::expr()->eq('eventType', $type));
        $events   = $this->getEvents()->matching($criteria);

        // Doctrine loses the indexBy mapping definition when using matching so we have to manually reset them.
        // @see https://github.com/doctrine/doctrine2/issues/4693
        $keyedArrayCollection = new ArrayCollection();
        /** @var Event $event */
        foreach ($events as $event) {
            $keyedArrayCollection->set($event->getId(), $event);
        }

        unset($events);

        return $keyedArrayCollection;
    }

    /**
     * Set publishUp.
     *
     * @param \DateTime $publishUp
     *
     * @return Campaign
     */
    public function setPublishUp($publishUp)
    {
        $this->isChanged('publishUp', $publishUp);
        $this->publishUp = $publishUp;

        return $this;
    }

    /**
     * Get publishUp.
     *
     * @return \DateTime
     */
    public function getPublishUp()
    {
        return $this->publishUp;
    }

    /**
     * Set publishDown.
     *
     * @param \DateTime $publishDown
     *
     * @return Campaign
     */
    public function setPublishDown($publishDown)
    {
        $this->isChanged('publishDown', $publishDown);
        $this->publishDown = $publishDown;

        return $this;
    }

    /**
     * Get publishDown.
     *
     * @return \DateTime
     */
    public function getPublishDown()
    {
        return $this->publishDown;
    }

    /**
     * @return mixed
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * @param mixed $category
     */
    public function setCategory($category)
    {
        $this->isChanged('category', $category);
        $this->category = $category;
    }

    /**
     * Add lead.
     *
     * @param      $key
     * @param Lead $lead
     *
     * @return Campaign
     */
    public function addLead($key, Lead $lead)
    {
        $action     = ($this->leads->contains($lead)) ? 'updated' : 'added';
        $leadEntity = $lead->getLead();

        $this->changes['leads'][$action][$leadEntity->getId()] = $leadEntity->getPrimaryIdentifier();
        $this->leads[$key]                                     = $lead;

        return $this;
    }

    /**
     * Remove lead.
     *
     * @param Lead $lead
     */
    public function removeLead(Lead $lead)
    {
        $leadEntity                                              = $lead->getLead();
        $this->changes['leads']['removed'][$leadEntity->getId()] = $leadEntity->getPrimaryIdentifier();
        $this->leads->removeElement($lead);
    }

    /**
     * Get leads.
     *
     * @return Lead[]|\Doctrine\Common\Collections\Collection
     */
    public function getLeads()
    {
        return $this->leads;
    }

    /**
     * @return ArrayCollection
     */
    public function getLists()
    {
        return $this->lists;
    }

    /**
     * Add list.
     *
     * @param LeadList $list
     *
     * @return Campaign
     */
    public function addList(LeadList $list)
    {
        $this->lists[$list->getId()] = $list;

        $this->changes['lists']['added'][$list->getId()] = $list->getName();

        return $this;
    }

    /**
     * Remove list.
     *
     * @param LeadList $list
     */
    public function removeList(LeadList $list)
    {
        $this->changes['lists']['removed'][$list->getId()] = $list->getName();
        $this->lists->removeElement($list);
    }

    /**
     * @return ArrayCollection
     */
    public function getForms()
    {
        return $this->forms;
    }

    /**
     * Add form.
     *
     * @param Form $form
     *
     * @return Campaign
     */
    public function addForm(Form $form)
    {
        $this->forms[] = $form;

        $this->changes['forms']['added'][$form->getId()] = $form->getName();

        return $this;
    }

    /**
     * Remove form.
     *
     * @param Form $form
     */
    public function removeForm(Form $form)
    {
        $this->changes['forms']['removed'][$form->getId()] = $form->getName();
        $this->forms->removeElement($form);
    }

    /**
     * @return mixed
     */
    public function getCanvasSettings()
    {
        return $this->canvasSettings;
    }

    /**
     * @param array $canvasSettings
     */
    public function setCanvasSettings(array $canvasSettings)
    {
        $this->canvasSettings = $canvasSettings;
    }

    /**
     * @return bool
     */
    public function getAllowRestart()
    {
        return $this->allowRestart;
    }

    /**
     * @return bool
     */
    public function allowRestart()
    {
        return $this->getAllowRestart();
    }

    /**
     * @param bool $allowRestart
     *
     * @return Campaign
     */
    public function setAllowRestart($allowRestart)
    {
        $this->isChanged('allowRestart', $allowRestart);

        $this->allowRestart = $allowRestart;

        return $this;
    }

    /**
     * Get contact membership.
     *
     * @param Contact $contact
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getContactMembership(Contact $contact)
    {
        return $this->leads->matching(
            Criteria::create()
                ->where(
                    Criteria::expr()->eq('lead', $contact)
                )
                ->orderBy(['dateAdded' => Criteria::DESC])
        );
    }

    /**
     * Add tag.
     *
     * @param Tag $tag
     *
     * @return $this
     */
    public function addTag(Tag $tag)
    {
        $this->isChanged('tags', $tag);
        $this->tags[$tag->getTag()] = $tag;

        return $this;
    }

    /**
     * Remove tag.
     *
     * @param Tag $tag
     */
    public function removeTag(Tag $tag)
    {
        $this->isChanged('tags', $tag->getTag());
        $this->tags->removeElement($tag);
    }

    /**
     * @return mixed
     */
    public function getTags()
    {
        return $this->tags;
    }

    /**
     * @param $tags
     *
     * @return $this
     */
    public function setTags($tags)
    {
        $this->tags = $tags;

        return $this;
    }
}
