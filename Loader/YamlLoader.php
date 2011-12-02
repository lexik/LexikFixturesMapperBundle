<?php

namespace Lexik\Bundle\FixturesMapperBundle\Loader;

use Symfony\Component\Yaml\Parser;

use Lexik\Bundle\FixturesMapperBundle\Loader\AbstractLoader;

/**
 * YAML loader for fixtures.
 *
 * @author Jeremy Barthe <j.barthe@lexik.fr>
 */
class YamlLoader extends AbstractLoader
{
    /**
     * {@inheritdoc}
     */
    protected function loadData($path, array $options = array())
    {
        $yaml = new Parser();
        return $yaml->parse(file_get_contents($path));
    }
}
