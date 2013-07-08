<?php

namespace Lexik\Bundle\FixturesMapperBundle\Loader;

use Lexik\Bundle\FixturesMapperBundle\Loader\AbstractLoader;

/**
 * CSV loader for fixtures.
 *
 * @author Jeremy Barthe <j.barthe@lexik.fr>
 */
class CsvLoader extends AbstractLoader
{
    /**
     * {@inheritdoc}
     */
    protected function loadData($path, array $options = array())
    {
        $options = array_merge($this->getDefaultOptions(), $options);
        $values = array();

        if (false !== ($handle = @fopen($path, 'r'))) {
            $row = 0;
            while (false !== ($data = fgetcsv($handle, 0, $options['delimiter'], $options['enclosure']))) {
                $row++;

                // ignored lines
                if (null !== $options['ignored_lines']) {
                    if (is_array($options['ignored_lines'])) {
                        if (in_array($row, $options['ignored_lines'])) {
                            continue;
                        }
                    } elseif ($options['ignored_lines'] == $row) {
                        continue;
                    }
                }

                $values[] = $data;
            }

            fclose($handle);
        } else {
            throw new \RuntimeException('You must provide a valid CSV file.');
        }

        return $values;
    }

    public function getDefaultOptions()
    {
        return array(
            // mixed value: can be an array or integer
            'ignored_lines' => null,
            'delimiter'     => ';',
            'enclosure'     => '"',
        );
    }
}
