<?php

namespace LaLit;

define('XML_CONTENT', 'XmlContent');
define('PHP_CONTENT', 'PhpContent');
define('RESULTS_KEY', 'ResultsKey');
define('ATTRIBUTE_CONTENT', 'AttributeContent');
define('VALUE_CONTENT', 'ValueContent');
define('CDATA_CONTENT', 'CDataContent');
define('ARRAY_TO_XML_ONLY', 'Array2XMLOnly');
define('VALID_TEST_FOR', 'ValidTestFor');
define('ALL_TESTS', 'AllTests');

class MainLaLitTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @param string|string[] $tags
     *
     * @return array
     */
    private function generateTags($tags)
    {
        if (is_array($tags)) {
            $tag = array_shift($tags);
        } else {
            $tag = $tags;
        }

        // Base attribute set.
        $attributeSet = [
            'No attributes' => [
                XML_CONTENT => '',
            ],
            'Empty attribute' => [
                XML_CONTENT => ' attribute1=""',
                PHP_CONTENT => [
                    '@attributes' => [
                        'attribute1' => '',
                    ],
                ],
            ],
            'Encoded attribute' => [
                XML_CONTENT => ' attribute2="&lt;important&gt;"',
                PHP_CONTENT => [
                    '@attributes' => [
                        'attribute2' => '<important>',
                    ],
                ],
            ],
            'Simple attribute' => [
                XML_CONTENT => ' attribute3="1"',
                PHP_CONTENT => [
                    '@attributes' => [
                        'attribute3' => '1',
                    ],
                ],
            ],
            'Quoted and encoded attribute' => [
                XML_CONTENT => ' attribute4="\'&lt;important&gt;\'"',
                PHP_CONTENT => [
                    '@attributes' => [
                        'attribute4' => '\'<important>\'',
                    ],
                ],
            ],
            'Empty quoted attribute' => [
                XML_CONTENT => ' attribute5="\'\'"',
                PHP_CONTENT => [
                    '@attributes' => [
                        'attribute5' => '\'\'',
                    ],
                ],
            ],
            'Null attribute' => [
                XML_CONTENT => ' attribute6=""',
                PHP_CONTENT => [
                    '@attributes' => [
                        'attribute6' => null, // A null in PHP will become an empty value in XML.
                    ],
                ],
                VALID_TEST_FOR => ARRAY_TO_XML_ONLY,
            ],
            'All attributes' => [
                XML_CONTENT => ' attribute1="" attribute2="&lt;important&gt;" attribute3="1" attribute4="\'&lt;important&gt;\'" attribute5="\'\'" attribute6=""',
                PHP_CONTENT => [
                    '@attributes' => [
                        'attribute1' => '',
                        'attribute2' => '<important>',
                        'attribute3' => '1',
                        'attribute4' => '\'<important>\'',
                        'attribute5' => '\'\'',
                        'attribute6' => null, // A null in PHP will become an empty value in XML.
                    ],
                ],
                VALID_TEST_FOR => ARRAY_TO_XML_ONLY,
            ],
            'All attributes without null attribute' => [
                XML_CONTENT => ' attribute1="" attribute2="&lt;important&gt;" attribute3="1" attribute4="\'&lt;important&gt;\'" attribute5="\'\'"',
                PHP_CONTENT => [
                    '@attributes' => [
                        'attribute1' => '',
                        'attribute2' => '<important>',
                        'attribute3' => '1',
                        'attribute4' => '\'<important>\'',
                        'attribute5' => '\'\'',
                    ],
                ],
            ],
        ];

        // Base value set.
        $valueSet = [
            'Null value' => [
                XML_CONTENT => '',
                PHP_CONTENT => [
                    '@value' => null,
                ], // A null in PHP will become an empty value in XML.
                ARRAY_TO_XML_ONLY => true,
            ],
            'Empty value' => [
                XML_CONTENT => '',
                PHP_CONTENT => [
                    '@value' => '',
                ],
            ],
            'Zero' => [
                XML_CONTENT => 0,
                PHP_CONTENT => [
                    '@value' => '0',
                ],
            ],
            'Simple value' => [
                XML_CONTENT => 'normal',
                PHP_CONTENT => [
                    '@value' => 'normal',
                ],
            ],
            'Encoded value' => [
                XML_CONTENT => '&lt;escaped&gt;',
                PHP_CONTENT => [
                    '@value' => '<escaped>',
                ],
            ],
            'Empty CDATA' => [
                XML_CONTENT => '<![CDATA[]]>',
                PHP_CONTENT => [
                    '@cdata' => '',
                ],
            ],
            'CDATA with tagged value' => [
                XML_CONTENT => '<![CDATA[<very_important>]]>',
                PHP_CONTENT => [
                    '@cdata' => '<very_important>',
                ],
            ],
        ];

        // If we have an array of tags, then generate the value for each tag and it to the value set.
        if (is_array($tags) && count($tags) > 0) {
            $valueSet = array_merge($valueSet, $this->generateTags($tags));
        }

        // Build a result set.
        $results = [];

        // Iterate the attribute and value sets.
        foreach ($attributeSet as $attributeType => $attribute) {
            foreach ($valueSet as $valueType => $value) {

                $tagName = is_array($tag) ? $tag[0] : $tag;
                $phpContent = array_merge(
                    array_key_exists(PHP_CONTENT, $attribute) ? $attribute[PHP_CONTENT] : [],
                    array_key_exists(PHP_CONTENT, $value) ? $value[PHP_CONTENT] : []
                ) ?: '';
                if (!array_key_exists('@attributes', $phpContent) && array_key_exists('@value', $phpContent)) {
                    $phpContent = $value[PHP_CONTENT]['@value'];
                }
                $xmlContent = "<{$tagName}{$attribute[XML_CONTENT]}>{$value[XML_CONTENT]}</{$tagName}>";

                // If the tag is not an array, then it is a single tag,
                // build the expected XML and PHP for XML2Array and Array2XML.
                if (!is_array($tag)) {
                    $resultsKey = $attributeType.' - '.$valueType;
                    $results[$resultsKey][XML_CONTENT] = $xmlContent;
                    $results[$resultsKey][PHP_CONTENT][$tagName] = $phpContent;
                } else {
                    $resultsKey = $attributeType.' - '.$valueType.' with '.$tag[1].' nodes';
                    // As the tag is an array, the first element is the tag name, and the second is a count.
                    // Iterate the count, building a collection of nodes.
                    $results[$resultsKey][XML_CONTENT] = '';
                    $results[$resultsKey][PHP_CONTENT][$tag[0]] = [];
                    foreach (range(1, $tag[1]) as $repeat) {
                        $results[$resultsKey][XML_CONTENT] .= $xmlContent;
                        $results[$resultsKey][PHP_CONTENT][$tagName][] = $phpContent;
                    }
                }
                $results[$resultsKey][RESULTS_KEY] = $resultsKey;
                $results[$resultsKey][VALID_TEST_FOR] = $results[$resultsKey][VALID_TEST_FOR] ?? ALL_TESTS;
            }
        }

        return $results;
    }

    public function provideTestData()
    {
        return array_merge(
            $this->generateTags(['root']),
            $this->generateTags(['root', 'node']),
            $this->generateTags(['root', ['node', 2]]),
            $this->generateTags(['root', 'collection', ['node', 2]]),
            $this->generateTags(['root', ['collections', 2], ['node', 2]])
        );
    }

    /**
     * @dataProvider provideTestData
     *
     * @param string $xml
     * @param string $php
     * @param string $structure
     */
    public function testArrayToXML($xml, $php, $structure, $validTestFor)
    {
        $actualResults = preg_replace('`[\\n\\r]++ *`sim', '', Array2XML::createXML('root', $php['root'])->saveXML());
        $expectedResults = '<?xml version="1.0" encoding="utf-8" standalone="no"?>'.$xml;

        $this->assertEquals($expectedResults, $actualResults, $structure);
    }

    /**
     * @dataProvider provideTestData
     *
     * @param string $xml
     * @param string $php
     * @param string $structure
     *
     * @throws \Exception
     */
    public function testXMLToArray($xml, $php, $structure, $validTestFor)
    {
        $xmlDOM = new \DOMDocument(1.0, 'UTF-8');
        $xmlDOM->xmlStandalone = false;
        $xmlDOM->preserveWhiteSpace = false;
        $xmlDOM->loadXML($xml);
        $xmlDOM->formatOutput = true;

        $xmlStringResults = XML2Array::createArray($xml);
        $xmlDOMResults = XML2Array::createArray($xmlDOM);

        $this->assertEquals($php, $xmlStringResults, $structure);
        $this->assertEquals($php, $xmlDOMResults, $structure);
    }
}
