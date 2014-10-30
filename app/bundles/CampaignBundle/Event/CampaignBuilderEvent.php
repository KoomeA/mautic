<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CampaignBundle\Event;

use Symfony\Component\Process\Exception\InvalidArgumentException;
use Symfony\Component\EventDispatcher\Event;

/**
 * Class CampaignBuilderEvent
 *
 * @package Mautic\CampaignBundle\Event
 */
class CampaignBuilderEvent extends Event
{
    private $leadActions   = array();
    private $systemActions = array();
    private $outcomes      = array();
    private $translator;

    public function __construct ($translator)
    {
        $this->translator = $translator;
    }

    /**
     * Add an lead action to the list of available .
     *
     * @param string $key     - a unique identifier; it is recommended that it be namespaced i.e. lead.mytrigger
     * @param array  $action - can contain the following keys:
     *                        'label'       => (required) what to display in the list
     *                        'description' => (optional) short description of event
     *                        'formType'    => (optional) name of the form type SERVICE for the action
     *                        'callback'    => (optional) callback function that will be passed when the event is triggered
     *                            The callback function should return a bool to determine if the trigger's actions
     *                            should be executed.  For example, only trigger actions for specific entities.
     *                            it can can receive the following arguments by name (via ReflectionMethod::invokeArgs())
     *                              mixed $passthrough Whatever the bundle passes when triggering the event
     *                              Mautic\CoreBundle\Factory\MauticFactory $factory
     *                              Mautic\LeadBundle\Entity\Lead $lead
     *                              array $event
     */
    public function addLeadAction ($key, array $action)
    {
        if (array_key_exists($key, $this->leadActions)) {
            throw new InvalidArgumentException("The key, '$key' is already used by another lead action. Please use a different key.");
        }

        //check for required keys and that given functions are callable
        $this->verifyComponent(
            array('label'),
            array('callback'),
            $action
        );

        $action['label']       = $this->translator->trans($action['label']);
        $action['description'] = (isset($action['description'])) ? $this->translator->trans($action['description']) : '';

        $this->leadActions[$key] = $action;
    }

    /**
     * Get lead actions
     *
     * @return array
     */
    public function getLeadActions ()
    {
        static $sorted = false;

        if (empty($sorted)) {
            uasort($this->leadActions, function ($a, $b) {
                return strnatcasecmp(
                    $a['label'], $b['label']);
            });
            $sorted = true;
        }

        return $this->leadActions;
    }


    /**
     * Add an system action to the list of available .
     *
     * @param string $key     - a unique identifier; it is recommended that it be namespaced i.e. lead.mytrigger
     * @param array  $trigger - can contain the following keys:
     *                        'label'       => (required) what to display in the list
     *                        'description' => (optional) short description of event
     *                        'formType'    => (optional) name of the form type SERVICE for the action
     *                        'callback'    => (optional) callback function that will be passed when the event is triggered
     *                            The callback function should return a bool to determine if the trigger's actions
     *                            should be executed.  For example, only trigger actions for specific entities.
     *                            it can can receive the following arguments by name (via ReflectionMethod::invokeArgs())
     *                              mixed $passthrough Whatever the bundle passes when triggering the event
     *                              Mautic\CoreBundle\Factory\MauticFactory $factory
     *                              Mautic\SystemBundle\Entity\System $lead
     *                              array $event
     */
    public function addSystemAction ($key, array $action)
    {
        if (array_key_exists($key, $this->leadActions)) {
            throw new InvalidArgumentException("The key, '$key' is already used by another system action. Please use a different key.");
        }

        //check for required keys and that given functions are callable
        $this->verifyComponent(
            array('label'),
            array('callback'),
            $action
        );

        $action['label']       = $this->translator->trans($action['label']);
        $action['description'] = (isset($action['description'])) ? $this->translator->trans($action['description']) : '';

        $this->systemActions[$key] = $action;
    }

    /**
     * Get lead actions
     *
     * @return array
     */
    public function getSystemActions ()
    {
        static $sorted = false;

        if (empty($sorted)) {
            uasort($this->systemActions, function ($a, $b) {
                return strnatcasecmp(
                    $a['label'], $b['label']);
            });
            $sorted = true;
        }

        return $this->systemActions;
    }

    /**
     * Add an outcome to the list of available .
     *
     * @param string $key    - a unique identifier; it is recommended that it be namespaced i.e. lead.action
     * @param array  $action - can contain the following keys:
     *                       'label'       => (required) what to display in the list
     *                       'description' => (optional) short description of event
     *                       'formType'    => (optional) name of the form type SERVICE for the action
     *                       'callback'    => (required) callback function that will be passed when the action is triggered
     *                            The callback function can receive the following arguments by name (via ReflectionMethod::invokeArgs())
     *                              Mautic\CoreBundle\Factory\MauticFactory $factory
     *                              Mautic\LeadBundle\Entity\Lead $lead
     *                              array $event
     */
    public function addOutcome ($key, array $outcome)
    {
        if (array_key_exists($key, $this->outcomes)) {
            throw new InvalidArgumentException("The key, '$key' is already used by another outcome. Please use a different key.");
        }

        //check for required keys and that given functions are callable
        $this->verifyComponent(
            array('label', 'callback'),
            array('callback'),
            $outcome
        );

        //translate the group

        $outcome['label']       = $this->translator->trans($outcome['label']);
        $outcome['description'] = (isset($outcome['description'])) ? $this->translator->trans($outcome['description']) : '';

        $this->outcomes[$key] = $outcome;
    }

    /**
     * Get outcomes
     *
     * @return array
     */
    public function getOutcomes ()
    {
        static $sorted = false;

        if (empty($sorted)) {
            uasort($this->outcomes, function ($a, $b) {
                return strnatcasecmp(
                    $a['label'], $b['label']);
            });
            $sorted = true;
        }

        return $this->outcomes;
    }

    /**
     * @param array $component
     */
    private function verifyComponent (array $keys, array $methods, array $component)
    {
        foreach ($keys as $k) {
            if (!array_key_exists($k, $component)) {
                throw new InvalidArgumentException("The key, '$k' is missing.");
            }
        }

        foreach ($methods as $m) {
            if (isset($component[$m]) && !is_callable($component[$m], true)) {
                throw new InvalidArgumentException($component[$m] . ' is not callable.  Please ensure that it exists and that it is a fully qualified namespace.');
            }
        }
    }
}