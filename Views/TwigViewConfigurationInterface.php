<?php namespace Nine\Views;

/**
 * @package Nine
 * @version 0.4.2
 * @author  Greg Truesdell <odd.greg@gmail.com>
 */

interface TwigViewConfigurationInterface extends ViewConfigurationInterface
{
    /**
     * Use '$this->finder()('template_name)' to locate a twig template.
     *
     * @return callable
     */
    public function finder() : callable;

}
