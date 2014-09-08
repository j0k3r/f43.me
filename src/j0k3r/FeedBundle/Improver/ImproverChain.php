<?php

namespace j0k3r\FeedBundle\Improver;

class ImproverChain
{
    private $improvers;

    public function __construct()
    {
        $this->improvers = array();
    }

    public function addImprover(Nothing $improver, $alias)
    {
        $this->improvers[$alias] = $improver;
    }

    /**
     * Get one improver by alias
     *
     * @param string $alias
     *
     * @return bool|object
     */
    public function getImprover($alias)
    {
        if (array_key_exists($alias, $this->improvers)) {
           return $this->improvers[$alias];
        }

        return false;
    }

    /**
     * Loop thru all improver to find one that match
     *
     * @param string $host A host
     *
     * @return bool
     */
    public function match($host)
    {
        foreach ($this->improvers as $alias => $improver) {
            if (true === $improver->match($host)) {
                return $alias;
            }
        }

        return false;
    }
}
