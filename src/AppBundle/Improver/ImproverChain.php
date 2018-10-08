<?php

namespace AppBundle\Improver;

class ImproverChain
{
    private $improvers;

    public function __construct()
    {
        $this->improvers = [];
    }

    /**
     * Add an improver to the chain.
     *
     * @param DefaultImprover $improver
     * @param string          $alias
     */
    public function addImprover(DefaultImprover $improver, $alias)
    {
        $this->improvers[$alias] = $improver;
    }

    /**
     * Loop thru all improver and return one that match.
     *
     * @param string $host A host
     *
     * @return DefaultImprover|false
     */
    public function match($host)
    {
        if (empty($host)) {
            return false;
        }

        foreach ($this->improvers as $alias => $improver) {
            if (true === $improver->match($host)) {
                return $improver;
            }
        }

        return false;
    }
}
