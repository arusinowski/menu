<?php
declare(strict_types=1);

/**
 * A KnpMenu seasoned menu plugin for CakePHP.
 *
 * @see https://github.com/icings/menu
 */

namespace Icings\Menu\Test\TestCase\Matcher\Voter;

use Cake\Core\Configure;
use Cake\Routing\Route\DashedRoute;
use Cake\Routing\RouteBuilder;
use Cake\Routing\Router;
use Cake\TestSuite\TestCase;
use Icings\Menu\Matcher\Voter\UrlVoter;
use Icings\Menu\TestSuite\RequestFactoryTrait;
use Knp\Menu\FactoryInterface;
use Knp\Menu\MenuItem;
use PHPUnit\Framework\MockObject\MockObject;

class UrlVoterTest extends TestCase
{
    use RequestFactoryTrait;

    public function setUp(): void
    {
        parent::setUp();

        Router::createRouteBuilder('/')
            ->scope('/', function (RouteBuilder $routes) {
                $routes->setExtensions(['json']);
                $routes->setRouteClass(DashedRoute::class);
                $routes->connect('/{controller}/{action}');
            });
    }

    public function testBaseUrlCompatibility(): void
    {
        Configure::write('App.baseUrl', '/base');

        $request = $this->createRequest('/base/controller/action?action=value');
        $voter = new UrlVoter($request);

        $this->assertEquals('/base/controller/action?action=value', $voter->getUrl());
        $this->assertEquals('/base/controller/action', $voter->getUrlWithoutQuery());
    }

    public function testIgnoreVoter(): void
    {
        $request = $this->createRequest('/controller/action');

        /** @var UrlVoter|MockObject $voter */
        $voter = $this
            ->getMockBuilder(UrlVoter::class)
            ->setConstructorArgs([$request])
            ->setMethods(['config'])
            ->getMock();
        $voter
            ->expects($this->never())
            ->method('config');

        $factory = $this
            ->getMockBuilder(FactoryInterface::class)
            ->getMock();

        /** @var MenuItem|MockObject $item */
        $item = $this
            ->getMockBuilder(MenuItem::class)
            ->setConstructorArgs(['item', $factory])
            ->setMethods(['getExtra'])
            ->getMock();
        $item
            ->expects($this->at(0))
            ->method('getExtra')
            ->with('routes')
            ->willReturn(null);

        $this->assertNull($voter->matchItem($item));
    }

    /**
     * @return array
     */
    public function matchingDataProvider(): array
    {
        return [
            // ---------------------------------------------------------------------------------------------------------
            //region String URLs
            // ---------------------------------------------------------------------------------------------------------

            'Request without query, URL without query, matching path' => [
                '/controller/action',
                '/controller/action',
                [],
                true,
            ],
            'Request without query, URL without query, non-matching path' => [
                '/controller/action',
                '/other/action',
                [],
                false,
            ],
            'Request without query, URL with query, matching path, non-matching query' => [
                '/controller/action',
                '/controller/action?query=value',
                [],
                [
                    ['voterIgnore' => true, 'itemIgnore' => null, 'expected' => true],
                    ['voterIgnore' => true, 'itemIgnore' => true, 'expected' => true],
                    ['voterIgnore' => true, 'itemIgnore' => false, 'expected' => false],
                    ['voterIgnore' => false, 'itemIgnore' => null, 'expected' => false],
                    ['voterIgnore' => false, 'itemIgnore' => false, 'expected' => false],
                    ['voterIgnore' => false, 'itemIgnore' => true, 'expected' => true],
                ],
            ],
            'Request without query, URL with query, non-matching path, non-matching query' => [
                '/controller/action',
                '/other/action?query=value',
                [],
                false,
            ],
            'Request with query, URL without query, matching path, non-matching query' => [
                '/controller/action?query=value',
                '/controller/action',
                [],
                [
                    ['voterIgnore' => true, 'itemIgnore' => null, 'expected' => true],
                    ['voterIgnore' => true, 'itemIgnore' => true, 'expected' => true],
                    ['voterIgnore' => true, 'itemIgnore' => false, 'expected' => false],
                    ['voterIgnore' => false, 'itemIgnore' => null, 'expected' => false],
                    ['voterIgnore' => false, 'itemIgnore' => false, 'expected' => false],
                    ['voterIgnore' => false, 'itemIgnore' => true, 'expected' => true],
                ],
            ],
            'Request with query, URL without query, non-matching path, non-matching query' => [
                '/controller/action?query=value',
                '/other/action',
                [],
                false,
            ],
            'Request with query, URL with query, matching path, matching query' => [
                '/controller/action?query=value',
                '/controller/action?query=value',
                [],
                true,
            ],
            'Request with query, URL with query, non-matching path, matching query' => [
                '/controller/action?query=value',
                '/other/action?query=value',
                [],
                false,
            ],
            'Request with query, URL with query, matching path, non-matching query' => [
                '/controller/action?query=value',
                '/controller/action?other=value',
                [],
                [
                    ['voterIgnore' => true, 'itemIgnore' => null, 'expected' => true],
                    ['voterIgnore' => true, 'itemIgnore' => true, 'expected' => true],
                    ['voterIgnore' => true, 'itemIgnore' => false, 'expected' => false],
                    ['voterIgnore' => false, 'itemIgnore' => null, 'expected' => false],
                    ['voterIgnore' => false, 'itemIgnore' => false, 'expected' => false],
                    ['voterIgnore' => false, 'itemIgnore' => true, 'expected' => true],
                ],
            ],
            'Request with query, URL with query, non-matching path, non-matching query' => [
                '/controller/action?query=value',
                '/other/action?other=value',
                [],
                false,
            ],

            //endregion

            // ---------------------------------------------------------------------------------------------------------
            //region Array URLs
            // ---------------------------------------------------------------------------------------------------------

            'Request without query, URL array, matching path' => [
                '/controller/action',
                '/other/action',
                [['controller' => 'Controller', 'action' => 'action']],
                true,
            ],
            'Request without query, URL array, non-matching path' => [
                '/controller/action',
                '/other/action',
                [['controller' => 'Other', 'action' => 'action']],
                false,
            ],
            'Request without query, URL array, matching path, non-matching query' => [
                '/controller/action',
                '/other/action',
                [['controller' => 'Controller', 'action' => 'action', '?' => ['query' => 'value']]],
                [
                    ['voterIgnore' => true, 'itemIgnore' => null, 'expected' => true],
                    ['voterIgnore' => true, 'itemIgnore' => true, 'expected' => true],
                    ['voterIgnore' => true, 'itemIgnore' => false, 'expected' => false],
                    ['voterIgnore' => false, 'itemIgnore' => null, 'expected' => false],
                    ['voterIgnore' => false, 'itemIgnore' => false, 'expected' => false],
                    ['voterIgnore' => false, 'itemIgnore' => true, 'expected' => true],
                ],
            ],
            'Request without query, URL array with query, non-matching path, non-matching query' => [
                '/controller/action',
                '/other/action',
                [['controller' => 'Other', 'action' => 'action', '?' => ['query' => 'value']]],
                false,
            ],
            'Request with query, URL array without query, matching path, non-matching query' => [
                '/controller/action?query=value',
                '/other/action',
                [['controller' => 'Controller', 'action' => 'action']],
                [
                    ['voterIgnore' => true, 'itemIgnore' => null, 'expected' => true],
                    ['voterIgnore' => true, 'itemIgnore' => true, 'expected' => true],
                    ['voterIgnore' => true, 'itemIgnore' => false, 'expected' => false],
                    ['voterIgnore' => false, 'itemIgnore' => null, 'expected' => false],
                    ['voterIgnore' => false, 'itemIgnore' => false, 'expected' => false],
                    ['voterIgnore' => false, 'itemIgnore' => true, 'expected' => true],
                ],
            ],
            'Request with query, URL array without query, non-matching path, non-matching query' => [
                '/controller/action?query=value',
                '/other/action',
                [['controller' => 'Other', 'action' => 'action']],
                false,
            ],
            'Request with query, URL array with query, matching path, matching query' => [
                '/controller/action?query=value',
                '/other/action',
                [['controller' => 'Controller', 'action' => 'action', '?' => ['query' => 'value']]],
                true,
            ],
            'Request with query, URL array with query, non-matching path, matching query' => [
                '/controller/action?query=value',
                '/other/action',
                [['controller' => 'Other', 'action' => 'action', '?' => ['query' => 'value']]],
                false,
            ],
            'Request with query, URL array with query, matching path, non-matching query' => [
                '/controller/action?query=value',
                '/other/action',
                [['controller' => 'Controller', 'action' => 'action', '?' => ['other' => 'value']]],
                [
                    ['voterIgnore' => true, 'itemIgnore' => null, 'expected' => true],
                    ['voterIgnore' => true, 'itemIgnore' => true, 'expected' => true],
                    ['voterIgnore' => true, 'itemIgnore' => false, 'expected' => false],
                    ['voterIgnore' => false, 'itemIgnore' => null, 'expected' => false],
                    ['voterIgnore' => false, 'itemIgnore' => false, 'expected' => false],
                    ['voterIgnore' => false, 'itemIgnore' => true, 'expected' => true],
                ],
            ],
            'Request with query, URL array with query, non-matching path, non-matching query' => [
                '/controller/action?query=value',
                '/other/action',
                [['controller' => 'Other', 'action' => 'action', '?' => ['other' => 'value']]],
                false,
            ],

            //endregion
        ];
    }

    /**
     * @return array
     */
    public function expandedMatchingDataProvider(): array
    {
        $sets = $this->matchingDataProvider();
        $expanded = [];
        foreach ($sets as $name => $set) {
            if (is_bool($set[3])) {
                $set[3] = [
                    ['voterIgnore' => true, 'itemIgnore' => null, 'expected' => $set[3]],
                    ['voterIgnore' => true, 'itemIgnore' => true, 'expected' => $set[3]],
                    ['voterIgnore' => true, 'itemIgnore' => false, 'expected' => $set[3]],
                    ['voterIgnore' => false, 'itemIgnore' => null, 'expected' => $set[3]],
                    ['voterIgnore' => false, 'itemIgnore' => false, 'expected' => $set[3]],
                    ['voterIgnore' => false, 'itemIgnore' => true, 'expected' => $set[3]],
                ];
            }

            foreach ($set[3] as $index => $config) {
                $expanded[$name . ' (#' . $index . ')'] = [
                    $set[0],
                    $set[1],
                    $set[2],
                    $config['voterIgnore'],
                    $config['itemIgnore'],
                    $config['expected'],
                ];
            }
        }

        return $expanded;
    }

    /**
     * @dataProvider expandedMatchingDataProvider
     * @param string $requestUri The request URI to match against.
     * @param string $uri The menu items main URI.
     * @param array[] $routes An array of URL arrays.
     * @param bool $voterIgnore
     * @param bool $itemIgnore
     * @param bool $expected The expected assertion result.
     */
    public function testMatching($requestUri, $uri, $routes, $voterIgnore, $itemIgnore, $expected): void
    {
        $request = $this->createRequest($requestUri);

        $voter = new UrlVoter($request, [
            'ignoreQueryString' => $voterIgnore,
        ]);

        $factory = $this
            ->getMockBuilder(FactoryInterface::class)
            ->getMock();

        /** @var MenuItem|MockObject $item */
        $item = $this
            ->getMockBuilder(MenuItem::class)
            ->setConstructorArgs(['item', $factory])
            ->setMethods(['getExtra'])
            ->getMock();

        if (empty($routes)) {
            $routes = [$uri];
        }
        $item
            ->expects($this->at(0))
            ->method('getExtra')
            ->with('routes')
            ->willReturn($routes);

        $item
            ->expects($this->at(1))
            ->method('getExtra')
            ->with('ignoreQueryString')
            ->willReturn($itemIgnore);

        $this->assertEquals($expected, $voter->matchItem($item));
    }
}
